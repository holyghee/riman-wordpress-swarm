<?php
/**
 * FINALER Import - Nutzt NUR die vorhandenen Mappings aus dem JSON
 * Ignoriert die überlangen page_mappings Slugs
 */

// WordPress laden
require_once('/var/www/html/wp-load.php');
require_once(ABSPATH . 'wp-admin/includes/image.php');
require_once(ABSPATH . 'wp-admin/includes/file.php');
require_once(ABSPATH . 'wp-admin/includes/media.php');

echo "=== Import mit Enhanced Mappings JSON ===\n\n";

// Lade JSON
$json = json_decode(file_get_contents('/var/www/html/wordpress-enhanced-image-mappings.json'), true);
$image_dir = '/tmp/categorized-images/';

// Helper: Import Bild
function import_image($image_file, $title) {
    global $image_dir;
    
    $path = $image_dir . $image_file;
    if (!file_exists($path)) return false;
    
    $temp = tempnam(sys_get_temp_dir(), 'wp_');
    copy($path, $temp);
    
    $file = [
        'name' => sanitize_title($title) . '.png',
        'tmp_name' => $temp,
        'size' => filesize($temp),
        'type' => 'image/png'
    ];
    
    $id = media_handle_sideload($file, 0);
    @unlink($temp);
    
    return is_wp_error($id) ? false : $id;
}

// 1. HAUPTKATEGORIEN aus main_categories
echo "SCHRITT 1: Hauptkategorien\n";
foreach ($json['main_categories'] as $slug => $image) {
    if (empty($slug) || empty($image)) continue;
    
    // WordPress nutzt diese Slugs für Hauptkategorien:
    $wp_slugs = [
        'rueckbau' => 'rueckbau',
        'altlasten' => 'altlasten', 
        'schadstoffe' => 'schadstoffe',
        'sicherheit' => 'sicherheitskoordination',
        'beratung' => 'beratung'
    ];
    
    $wp_slug = isset($wp_slugs[$slug]) ? $wp_slugs[$slug] : $slug;
    $category = get_term_by('slug', $wp_slug, 'category');
    
    if (!$category) {
        echo "⚠️  Kategorie nicht gefunden: $wp_slug\n";
        continue;
    }
    
    $attachment_id = import_image($image, $category->name);
    if ($attachment_id) {
        // Setze für Kategorie
        update_term_meta($category->term_id, '_thumbnail_id', $attachment_id);
        
        // Finde verknüpfte Seite
        $pages = get_posts([
            'post_type' => 'page',
            'meta_key' => '_linked_category_id',
            'meta_value' => $category->term_id,
            'numberposts' => 1
        ]);
        
        if (!empty($pages)) {
            set_post_thumbnail($pages[0]->ID, $attachment_id);
            echo "✅ {$category->name}: Bild gesetzt\n";
        }
    }
}

// 2. UNTERKATEGORIEN aus subcategories
echo "\nSCHRITT 2: Unterkategorien\n";
foreach ($json['subcategories'] as $parent => $subs) {
    if (!is_array($subs)) continue;
    
    foreach ($subs as $sub_slug => $image) {
        if (empty($sub_slug) || empty($image) || strpos($sub_slug, '.md') !== false) continue;
        
        // Versuche verschiedene Slug-Kombinationen
        $possible_slugs = [
            $parent . '-' . $sub_slug,
            'altlasten-' . $sub_slug,
            'rueckbau-' . $sub_slug,
            'schadstoffe-' . $sub_slug,
            'sicherheitskoordination-' . $sub_slug,
            'beratung-' . $sub_slug
        ];
        
        $category = null;
        foreach ($possible_slugs as $try) {
            $category = get_term_by('slug', $try, 'category');
            if ($category) break;
        }
        
        if (!$category) continue;
        
        $attachment_id = import_image($image, $category->name);
        if ($attachment_id) {
            update_term_meta($category->term_id, '_thumbnail_id', $attachment_id);
            
            // Verknüpfte Seite
            $pages = get_posts([
                'post_type' => 'page',
                'meta_key' => '_linked_category_id',
                'meta_value' => $category->term_id,
                'numberposts' => 1
            ]);
            
            if (!empty($pages)) {
                set_post_thumbnail($pages[0]->ID, $attachment_id);
                echo "✅ {$category->name}: Bild gesetzt\n";
            }
        }
    }
}

// 3. SPEZIELLE SEITEN (nur die wichtigsten aus page_mappings)
echo "\nSCHRITT 3: Spezielle Seiten\n";
$special = [
    'home' => 'startseite',
    'kontakt' => 'kontakt',
    'impressum' => 'impressum',
    'ueber-uns' => 'ueber-uns'
];

foreach ($special as $json_slug => $wp_slug) {
    if (!isset($json['page_mappings'][$json_slug])) continue;
    
    $image = $json['page_mappings'][$json_slug];
    if (empty($image)) continue;
    
    $pages = get_posts([
        'post_type' => 'page',
        'name' => $wp_slug,
        'numberposts' => 1
    ]);
    
    if (empty($pages)) continue;
    $page = $pages[0];
    
    if (!get_post_thumbnail_id($page->ID)) {
        $attachment_id = import_image($image, $page->post_title);
        if ($attachment_id) {
            set_post_thumbnail($page->ID, $attachment_id);
            echo "✅ {$page->post_title}: Bild gesetzt\n";
        }
    }
}

// Statistik
echo "\n=== FINALE STATISTIK ===\n";
$pages = get_posts(['post_type' => 'page', 'numberposts' => -1]);
$with = 0;
$without = 0;

foreach ($pages as $page) {
    if (get_post_thumbnail_id($page->ID)) {
        $with++;
    } else {
        $without++;
        echo "  ❌ Ohne Bild: {$page->post_title}\n";
    }
}

echo "\n📊 Seiten mit Bild: $with\n";
echo "📊 Seiten ohne Bild: $without\n";
echo "\n✅ Import abgeschlossen!\n";
