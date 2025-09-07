<?php
/**
 * KORREKTER Import Enhanced Images mit funktionierendem Slug-Mapping
 * Behebt das Problem der falschen Slug-Zuordnung
 */

// WordPress laden
if (file_exists(__DIR__ . '/wp-load.php')) {
    require_once(__DIR__ . '/wp-load.php');
} else {
    require_once('/var/www/html/wp-load.php');
}

// WICHTIG: WordPress Media Functions laden
require_once(ABSPATH . 'wp-admin/includes/image.php');
require_once(ABSPATH . 'wp-admin/includes/file.php');
require_once(ABSPATH . 'wp-admin/includes/media.php');

echo "=== KORREKTER Import Enhanced Images mit Slug-Mapping ===\n\n";

// Lade Enhanced Mappings JSON
$json_file = '/var/www/html/wordpress-enhanced-image-mappings.json';
if (!file_exists($json_file)) {
    die("❌ Enhanced Mappings JSON nicht gefunden!\n");
}

$mappings = json_decode(file_get_contents($json_file), true);
if (!$mappings) {
    die("❌ Fehler beim Lesen der Enhanced Mappings!\n");
}

// WICHTIG: Erstelle Slug-Mapping Tabelle
// Diese Tabelle mappt lange Enhanced-Slugs auf kurze WordPress-Slugs
$slug_mapping = [
    // Hauptkategorien
    'altlastensanierung' => 'altlasten',
    'rueckbaumanagement' => 'rueckbau', 
    'schadstoff-management' => 'schadstoffe',
    'sicherheitskoordination' => 'sicherheitskoordination',
    'beratung-mediation' => 'beratung',
    
    // Unterkategorien - Altlastensanierung
    'altlasten-erkundung' => 'altlasten-erkundung',
    'sanierungsplanung' => 'altlasten-sanierungsplanung',
    'bodensanierung' => 'altlasten-bodensanierung',
    'grundwassersanierung' => 'altlasten-grundwassersanierung',
    'altlasten-monitoring' => 'altlasten-monitoring',
    
    // Unterkategorien - Rückbau
    'rueckbauplanung' => 'rueckbau-planung',
    'ausschreibung' => 'rueckbau-ausschreibung',
    'durchfuehrung' => 'rueckbau-durchfuehrung',
    'entsorgung' => 'rueckbau-entsorgung',
    'recycling' => 'rueckbau-recycling',
    'dokumentation' => 'rueckbau-dokumentation',
    
    // Unterkategorien - Schadstoffe
    'asbest' => 'schadstoffe-asbest',
    'pcb' => 'schadstoffe-pcb',
    'pak' => 'schadstoffe-pak',
    'kmf' => 'schadstoffe-kmf',
    'schwermetalle' => 'schadstoffe-schwermetalle',
    
    // Unterkategorien - Sicherheit
    'sigeko-planung' => 'sicherheitskoordination-sigeko-planung',
    'sigeko-ausfuehrung' => 'sicherheitskoordination-sigeko-ausfuehrung',
    'gefaehrdungsbeurteilung' => 'sicherheitskoordination-gefaehrdungsbeurteilung',
    'arbeitsschutz' => 'sicherheitskoordination-arbeitsschutz',
    'notfallmanagement' => 'sicherheitskoordination-notfallmanagement',
    
    // Unterkategorien - Beratung
    'projektberatung' => 'beratung-projektberatung',
    'baumediation' => 'beratung-baumediation',
    'gutachten' => 'beratung-gutachten',
    'compliance' => 'beratung-compliance',
    'schulungen' => 'beratung-schulungen'
];

// Schritt 1: Importiere Hauptkategorie-Bilder
echo "SCHRITT 1: Importiere Bilder für Hauptkategorien...\n";
echo str_repeat("-", 60) . "\n";

foreach ($mappings['main_categories'] as $json_slug => $image_file) {
    if (empty($json_slug) || empty($image_file)) continue;
    
    // Finde den richtigen WordPress Slug
    $wp_slug = isset($slug_mapping[$json_slug]) ? $slug_mapping[$json_slug] : $json_slug;
    
    // Finde die Kategorie
    $category = get_term_by('slug', $wp_slug, 'category');
    if (!$category) {
        echo "⚠️  Kategorie nicht gefunden: $wp_slug (von $json_slug)\n";
        continue;
    }
    
    // Prüfe ob Bild existiert
    $image_path = '/tmp/categorized-images/' . $image_file;
    if (!file_exists($image_path)) {
        echo "⚠️  Bild nicht gefunden: $image_file\n";
        continue;
    }
    
    // Importiere das Bild
    $temp_file = tempnam(sys_get_temp_dir(), 'riman_');
    copy($image_path, $temp_file);
    
    $file_array = array(
        'name' => $wp_slug . '_' . basename($image_file),
        'tmp_name' => $temp_file,
        'size' => filesize($temp_file),
        'type' => 'image/png'
    );
    
    $attachment_id = media_handle_sideload($file_array, 0);
    @unlink($temp_file);
    
    if (is_wp_error($attachment_id)) {
        echo "❌ Fehler beim Import für {$category->name}: " . $attachment_id->get_error_message() . "\n";
        continue;
    }
    
    // Setze Attachment Metadaten
    wp_update_post(array(
        'ID' => $attachment_id,
        'post_title' => $category->name . ' Featured Image',
        'post_excerpt' => 'Featured Image für ' . $category->name,
        'post_content' => 'Automatisch importiertes Bild für ' . $category->name
    ));
    
    // Weise der Kategorie zu
    update_term_meta($category->term_id, '_thumbnail_id', $attachment_id);
    
    // WICHTIG: Finde die verknüpfte Seite und setze dort auch das Featured Image
    $linked_page_id = get_term_meta($category->term_id, '_linked_page_id', true);
    if (!$linked_page_id) {
        // Alternative: Suche nach Seite mit gleichem Titel
        $pages = get_posts(array(
            'post_type' => 'page',
            'name' => $wp_slug,
            'numberposts' => 1
        ));
        if (!empty($pages)) {
            $linked_page_id = $pages[0]->ID;
        }
    }
    
    if ($linked_page_id) {
        // Setze Featured Image für die Seite
        $result = set_post_thumbnail($linked_page_id, $attachment_id);
        if (!$result) {
            // Fallback: Direkte Meta-Update
            update_post_meta($linked_page_id, '_thumbnail_id', $attachment_id);
        }
        echo "✅ {$category->name}: Bild importiert und Seite zugewiesen (ID: $attachment_id)\n";
    } else {
        echo "✅ {$category->name}: Bild importiert (ID: $attachment_id)\n";
    }
}

// Schritt 2: Importiere Unterkategorie-Bilder
echo "\nSCHRITT 2: Importiere Bilder für Unterkategorien...\n";
echo str_repeat("-", 60) . "\n";

foreach ($mappings['subcategories'] as $parent_slug => $subcats) {
    if (!is_array($subcats)) continue;
    
    foreach ($subcats as $subcat_slug => $image_file) {
        if (empty($subcat_slug) || empty($image_file)) continue;
        
        // Erstelle kombinierten Slug
        $wp_parent_slug = isset($slug_mapping[$parent_slug]) ? $slug_mapping[$parent_slug] : $parent_slug;
        $full_slug = $wp_parent_slug . '-' . $subcat_slug;
        
        // Versuche verschiedene Slug-Varianten
        $possible_slugs = [
            $full_slug,
            $subcat_slug,
            isset($slug_mapping[$subcat_slug]) ? $slug_mapping[$subcat_slug] : null
        ];
        
        $category = null;
        foreach ($possible_slugs as $try_slug) {
            if (!$try_slug) continue;
            $category = get_term_by('slug', $try_slug, 'category');
            if ($category) break;
        }
        
        if (!$category) {
            echo "⚠️  Unterkategorie nicht gefunden: $subcat_slug\n";
            continue;
        }
        
        // Prüfe ob Bild existiert
        $image_path = '/tmp/categorized-images/' . $image_file;
        if (!file_exists($image_path)) {
            echo "⚠️  Bild nicht gefunden: $image_file\n";
            continue;
        }
        
        // Importiere das Bild
        $temp_file = tempnam(sys_get_temp_dir(), 'riman_');
        copy($image_path, $temp_file);
        
        $file_array = array(
            'name' => $category->slug . '_' . basename($image_file),
            'tmp_name' => $temp_file,
            'size' => filesize($temp_file),
            'type' => 'image/png'
        );
        
        $attachment_id = media_handle_sideload($file_array, 0);
        @unlink($temp_file);
        
        if (is_wp_error($attachment_id)) {
            echo "❌ Fehler beim Import für {$category->name}: " . $attachment_id->get_error_message() . "\n";
            continue;
        }
        
        // Setze Attachment Metadaten
        wp_update_post(array(
            'ID' => $attachment_id,
            'post_title' => $category->name . ' Featured Image',
            'post_excerpt' => 'Featured Image für ' . $category->name,
            'post_content' => 'Automatisch importiertes Bild für ' . $category->name
        ));
        
        // Weise der Kategorie zu
        update_term_meta($category->term_id, '_thumbnail_id', $attachment_id);
        
        // Finde und aktualisiere verknüpfte Seite
        $linked_page_id = get_term_meta($category->term_id, '_linked_page_id', true);
        if (!$linked_page_id) {
            // Suche nach Seite mit passendem Titel
            $pages = get_posts(array(
                'post_type' => 'page',
                'title' => $category->name,
                'numberposts' => 1
            ));
            if (!empty($pages)) {
                $linked_page_id = $pages[0]->ID;
            }
        }
        
        if ($linked_page_id) {
            // Setze Featured Image
            $result = set_post_thumbnail($linked_page_id, $attachment_id);
            if (!$result) {
                update_post_meta($linked_page_id, '_thumbnail_id', $attachment_id);
            }
            echo "✅ {$category->name}: Bild importiert und Seite zugewiesen\n";
        } else {
            echo "✅ {$category->name}: Bild importiert\n";
        }
    }
}

// Schritt 3: Importiere spezielle Seiten-Bilder aus page_mappings
echo "\nSCHRITT 3: Importiere spezielle Seiten-Bilder...\n";
echo str_repeat("-", 60) . "\n";

$special_pages = ['home' => 'startseite', 'kontakt' => 'kontakt', 'impressum' => 'impressum', 'ueber-uns' => 'ueber-uns'];

foreach ($special_pages as $json_slug => $wp_slug) {
    if (!isset($mappings['page_mappings'][$json_slug])) continue;
    
    $image_file = $mappings['page_mappings'][$json_slug];
    if (empty($image_file)) continue;
    
    // Finde die Seite
    $pages = get_posts(array(
        'post_type' => 'page',
        'name' => $wp_slug,
        'numberposts' => 1
    ));
    
    if (empty($pages)) {
        echo "⚠️  Seite nicht gefunden: $wp_slug\n";
        continue;
    }
    
    $page = $pages[0];
    
    // Prüfe ob Seite bereits Featured Image hat
    if (get_post_thumbnail_id($page->ID)) {
        echo "ℹ️  {$page->post_title} hat bereits ein Featured Image\n";
        continue;
    }
    
    // Prüfe ob Bild existiert
    $image_path = '/tmp/categorized-images/' . $image_file;
    if (!file_exists($image_path)) {
        echo "⚠️  Bild nicht gefunden: $image_file\n";
        continue;
    }
    
    // Importiere das Bild
    $temp_file = tempnam(sys_get_temp_dir(), 'riman_');
    copy($image_path, $temp_file);
    
    $file_array = array(
        'name' => $wp_slug . '_' . basename($image_file),
        'tmp_name' => $temp_file,
        'size' => filesize($temp_file),
        'type' => 'image/png'
    );
    
    $attachment_id = media_handle_sideload($file_array, 0);
    @unlink($temp_file);
    
    if (is_wp_error($attachment_id)) {
        echo "❌ Fehler beim Import für {$page->post_title}: " . $attachment_id->get_error_message() . "\n";
        continue;
    }
    
    // Setze Featured Image
    $result = set_post_thumbnail($page->ID, $attachment_id);
    if (!$result) {
        update_post_meta($page->ID, '_thumbnail_id', $attachment_id);
    }
    
    echo "✅ {$page->post_title}: Featured Image gesetzt\n";
}

// Finale Statistik
echo "\n=== FINALE STATISTIK ===\n";
echo str_repeat("-", 60) . "\n";

$pages = get_posts(array('post_type' => 'page', 'numberposts' => -1));
$with_featured = 0;
$without_featured = 0;

foreach ($pages as $page) {
    if (get_post_thumbnail_id($page->ID)) {
        $with_featured++;
    } else {
        $without_featured++;
    }
}

echo "📊 Seiten gesamt: " . count($pages) . "\n";
echo "✅ Mit Featured Image: $with_featured\n";
echo "❌ Ohne Featured Image: $without_featured\n";

if ($without_featured > 0) {
    echo "\nSeiten ohne Featured Image:\n";
    foreach ($pages as $page) {
        if (!get_post_thumbnail_id($page->ID)) {
            echo "  - {$page->post_title}\n";
        }
    }
}

echo "\n✅ Import abgeschlossen!\n";
