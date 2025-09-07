<?php
/**
 * Importiere die neuesten Midjourney Bilder für fehlende Kategorien
 */

// WordPress laden
if (file_exists(__DIR__ . '/wp-load.php')) {
    require_once(__DIR__ . '/wp-load.php');
} else {
    require_once('/var/www/html/wp-load.php');
}

echo "=== Import der neuesten Midjourney Bilder ===\n\n";

// Hole alle Kategorien ohne Bilder
$categories = get_terms(array(
    'taxonomy' => 'category',
    'hide_empty' => false
));

// Sammle Kategorien ohne Bilder
$categories_without_images = [];
foreach ($categories as $category) {
    if ($category->slug === 'uncategorized') continue;
    
    $thumbnail_id = get_term_meta($category->term_id, '_thumbnail_id', true);
    if (empty($thumbnail_id)) {
        $categories_without_images[] = $category;
    }
}

echo "Gefundene Kategorien ohne Bilder: " . count($categories_without_images) . "\n\n";

// Hole die neuesten Bilder
$image_dir = '/tmp/midjourney-images/';
$images = glob($image_dir . '*upscaled*.png');

// Sortiere nach Änderungszeit (neueste zuerst)
usort($images, function($a, $b) {
    return filemtime($b) - filemtime($a);
});

// Nimm nur die neuesten 30 Bilder
$latest_images = array_slice($images, 0, 30);

echo "Gefundene Bilder: " . count($latest_images) . "\n\n";

// Weise Bilder zu
$imported = 0;
foreach ($categories_without_images as $index => $category) {
    if ($index >= count($latest_images)) {
        echo "⚠️  Keine weiteren Bilder verfügbar für: {$category->name}\n";
        break;
    }
    
    $image_path = $latest_images[$index];
    
    // Importiere das Bild
    $file_array = array(
        'name' => 'riman-' . $category->slug . '-' . basename($image_path),
        'tmp_name' => $image_path
    );
    
    // Handle upload using WordPress functions
    require_once(ABSPATH . 'wp-admin/includes/image.php');
    require_once(ABSPATH . 'wp-admin/includes/file.php');
    require_once(ABSPATH . 'wp-admin/includes/media.php');
    
    $attachment_id = media_handle_sideload($file_array, 0);
    
    if (is_wp_error($attachment_id)) {
        echo "❌ Fehler beim Import für {$category->name}: " . $attachment_id->get_error_message() . "\n";
        continue;
    }
    
    // Weise das Bild der Kategorie zu
    update_term_meta($category->term_id, '_thumbnail_id', $attachment_id);
    echo "✅ Bild importiert für {$category->name} (ID: $attachment_id)\n";
    $imported++;
    
    // Finde verknüpfte Seite und weise ihr auch das Bild zu
    $pages = get_posts(array(
        'post_type' => 'page',
        'meta_key' => '_category_id',
        'meta_value' => $category->term_id,
        'numberposts' => 1
    ));
    
    if (!empty($pages)) {
        $page = $pages[0];
        set_post_thumbnail($page->ID, $attachment_id);
        echo "   └─ Auch der Seite '{$page->post_title}' zugewiesen\n";
    }
}

echo "\n=== Import abgeschlossen ===\n";
echo "✅ Importiert: $imported Bilder\n";

// Zeige finale Statistik
$total_with_images = 0;
$total_without_images = 0;

foreach ($categories as $category) {
    if ($category->slug === 'uncategorized') continue;
    $thumbnail_id = get_term_meta($category->term_id, '_thumbnail_id', true);
    if (!empty($thumbnail_id)) {
        $total_with_images++;
    } else {
        $total_without_images++;
    }
}

echo "\n=== Finale Statistik ===\n";
echo "Kategorien mit Bildern: $total_with_images\n";
echo "Kategorien ohne Bilder: $total_without_images\n";
echo "Gesamt: " . ($total_with_images + $total_without_images) . "\n";