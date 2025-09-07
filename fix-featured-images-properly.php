<?php
/**
 * Fix Featured Images für RIMAN Seiten
 * Dieses Script diagnostiziert und behebt das Featured Image Problem
 */

// WordPress laden
if (file_exists(__DIR__ . '/wp-load.php')) {
    require_once(__DIR__ . '/wp-load.php');
} else {
    require_once('/var/www/html/wp-load.php');
}

// WordPress Media Funktionen laden - WICHTIG!
require_once(ABSPATH . 'wp-admin/includes/image.php');
require_once(ABSPATH . 'wp-admin/includes/file.php');
require_once(ABSPATH . 'wp-admin/includes/media.php');
require_once(ABSPATH . 'wp-admin/includes/post.php');

echo "=== Featured Images Diagnose und Fix ===\n\n";

// Schritt 1: Prüfe alle Kategorien und deren Bilder
echo "SCHRITT 1: Prüfe Kategorien und ihre Bilder...\n";
echo str_repeat("-", 60) . "\n";

$categories = get_terms(array(
    'taxonomy' => 'category',
    'hide_empty' => false
));

$category_images = [];
$missing_images = [];

foreach ($categories as $category) {
    if ($category->slug === 'uncategorized') continue;
    
    $thumbnail_id = get_term_meta($category->term_id, '_thumbnail_id', true);
    
    if ($thumbnail_id) {
        // Prüfe ob Attachment existiert
        $attachment = get_post($thumbnail_id);
        if ($attachment && $attachment->post_type === 'attachment') {
            $category_images[$category->term_id] = $thumbnail_id;
            echo "✅ {$category->name} (ID: {$category->term_id}): Bild gefunden (Attachment ID: $thumbnail_id)\n";
        } else {
            echo "❌ {$category->name}: Bild ID $thumbnail_id existiert nicht mehr!\n";
            $missing_images[] = $category->name;
        }
    } else {
        echo "⚠️  {$category->name}: Kein Bild zugewiesen\n";
        $missing_images[] = $category->name;
    }
}

// Schritt 2: Prüfe alle Seiten und deren Featured Images
echo "\n\nSCHRITT 2: Prüfe Seiten und ihre Featured Images...\n";
echo str_repeat("-", 60) . "\n";

$pages = get_posts(array(
    'post_type' => 'page',
    'post_status' => 'publish',
    'numberposts' => -1
));

$pages_without_images = [];
$pages_with_images = [];

foreach ($pages as $page) {
    // Prüfe verknüpfte Kategorie
    $linked_category_id = get_post_meta($page->ID, '_linked_category_id', true);
    $current_thumbnail = get_post_thumbnail_id($page->ID);
    
    if ($current_thumbnail) {
        // Verifiziere dass das Bild existiert
        $attachment = get_post($current_thumbnail);
        if ($attachment && $attachment->post_type === 'attachment') {
            $pages_with_images[] = $page->post_title;
            echo "✅ {$page->post_title}: Hat Featured Image (ID: $current_thumbnail)\n";
        } else {
            echo "❌ {$page->post_title}: Featured Image ID $current_thumbnail existiert nicht!\n";
            $pages_without_images[] = ['page' => $page, 'category_id' => $linked_category_id];
            // Lösche die ungültige Referenz
            delete_post_meta($page->ID, '_thumbnail_id');
        }
    } else {
        echo "⚠️  {$page->post_title}: Kein Featured Image";
        if ($linked_category_id) {
            echo " (Verknüpft mit Kategorie ID: $linked_category_id)";
        }
        echo "\n";
        $pages_without_images[] = ['page' => $page, 'category_id' => $linked_category_id];
    }
}

// Schritt 3: Behebe fehlende Featured Images
echo "\n\nSCHRITT 3: Behebe fehlende Featured Images...\n";
echo str_repeat("-", 60) . "\n";

if (count($pages_without_images) > 0) {
    echo "Versuche Featured Images für " . count($pages_without_images) . " Seiten zu setzen...\n\n";
    
    $fixed = 0;
    $failed = 0;
    
    foreach ($pages_without_images as $page_data) {
        $page = $page_data['page'];
        $category_id = $page_data['category_id'];
        
        if ($category_id && isset($category_images[$category_id])) {
            $attachment_id = $category_images[$category_id];
            
            // Versuche Featured Image zu setzen
            echo "Setze Bild für '{$page->post_title}'... ";
            
            // Methode 1: Standard WordPress Funktion
            $result = set_post_thumbnail($page->ID, $attachment_id);
            
            if ($result) {
                // Verifiziere dass es gesetzt wurde
                $verify = get_post_thumbnail_id($page->ID);
                if ($verify == $attachment_id) {
                    echo "✅ Erfolgreich!\n";
                    $fixed++;
                } else {
                    // Methode 2: Direkte Meta-Update als Fallback
                    update_post_meta($page->ID, '_thumbnail_id', $attachment_id);
                    $verify = get_post_thumbnail_id($page->ID);
                    if ($verify == $attachment_id) {
                        echo "✅ Erfolgreich (via Meta)!\n";
                        $fixed++;
                    } else {
                        echo "❌ Fehlgeschlagen\n";
                        $failed++;
                    }
                }
            } else {
                // Methode 2: Direkte Meta-Update als Fallback
                update_post_meta($page->ID, '_thumbnail_id', $attachment_id);
                $verify = get_post_thumbnail_id($page->ID);
                if ($verify == $attachment_id) {
                    echo "✅ Erfolgreich (via Meta)!\n";
                    $fixed++;
                } else {
                    echo "❌ Fehlgeschlagen\n";
                    $failed++;
                }
            }
        } else {
            echo "⚠️  Keine verknüpfte Kategorie mit Bild für '{$page->post_title}'\n";
        }
    }
    
    echo "\n";
    echo "✅ Behoben: $fixed Seiten\n";
    if ($failed > 0) {
        echo "❌ Fehlgeschlagen: $failed Seiten\n";
    }
} else {
    echo "✅ Alle Seiten haben bereits Featured Images!\n";
}

// Schritt 4: Kategorien ohne Bilder – Mapping-only, kein Fallback
if (count($missing_images) > 0) {
    echo "\n\nSCHRITT 4: Kategorien ohne Bilder (Mapping-only) ...\n";
    echo str_repeat("-", 60) . "\n";
    
    // Mapping laden
    $mapping_path = '/tmp/wordpress-enhanced-image-mappings-seo.json';
    if (!file_exists($mapping_path)) {
        echo "❌ Kein Mapping im Container gefunden: $mapping_path\n";
        echo "Bitte Mapping verbessern und Setup erneut ausführen.\n";
    } else {
        $mapping = json_decode(file_get_contents($mapping_path), true);
        $errors = 0;
        foreach ($categories as $category) {
            if ($category->slug === 'uncategorized') continue;
            $thumb = get_term_meta($category->term_id, '_thumbnail_id', true);
            if ($thumb && get_post($thumb)) continue; // bereits gesetzt

            $map_key = $category->slug === 'sicherheitskoordination' ? 'sicherheit' : $category->slug;
            $image_rel = isset($mapping['main_categories'][$map_key]['image']) ? basename($mapping['main_categories'][$map_key]['image']) : '';
            $image_abs = $image_rel ? '/tmp/images/' . $image_rel : '';

            if (!$image_rel || !file_exists($image_abs)) {
                echo "❌ FEHLT: Kategorie '{$category->name}' hat kein gültiges Bild im Mapping (Schlüssel: $map_key)\n";
                $errors++;
                continue;
            }

            echo "Importiere Mapping-Bild für {$category->name}... ";
            $temp_file = tempnam(sys_get_temp_dir(), 'riman_');
            copy($image_abs, $temp_file);
            $file_array = array(
                'name' => 'riman-' . $category->slug . '-' . basename($image_abs),
                'tmp_name' => $temp_file,
                'size' => filesize($temp_file),
                'type' => 'image/png'
            );
            $attachment_id = media_handle_sideload($file_array, 0);
            @unlink($temp_file);
            if (!is_wp_error($attachment_id)) {
                update_term_meta($category->term_id, '_thumbnail_id', $attachment_id);
                echo "✅ Erfolgreich!\n";
            } else {
                echo "❌ Fehler: " . $attachment_id->get_error_message() . "\n";
                $errors++;
            }
        }
        if ($errors > 0) {
            echo "\n❌ Es fehlen Kategorie-Bilder im Mapping. Bitte Mapping verbessern und Script erneut ausführen.\n";
        }
    }
}

// Schritt 5: Finale Statistik
echo "\n\n=== FINALE STATISTIK ===\n";
echo str_repeat("-", 60) . "\n";

$total_pages = count($pages);
$pages_with_featured = 0;
$pages_without_featured = 0;

foreach ($pages as $page) {
    $thumbnail_id = get_post_thumbnail_id($page->ID);
    if ($thumbnail_id && get_post($thumbnail_id)) {
        $pages_with_featured++;
    } else {
        $pages_without_featured++;
    }
}

echo "Seiten gesamt: $total_pages\n";
echo "✅ Mit Featured Image: $pages_with_featured\n";
echo "❌ Ohne Featured Image: $pages_without_featured\n";

if ($pages_without_featured > 0) {
    echo "\nSeiten ohne Featured Image:\n";
    foreach ($pages as $page) {
        $thumbnail_id = get_post_thumbnail_id($page->ID);
        if (!$thumbnail_id || !get_post($thumbnail_id)) {
            echo "  - {$page->post_title}\n";
        }
    }
}

echo "\n✅ Script abgeschlossen!\n";
