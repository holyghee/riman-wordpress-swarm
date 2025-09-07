<?php
/**
 * Import korrekt kategorisierte Midjourney Bilder nach WordPress
 * Verwendet die bereits organisierten und benannten Bilder
 */

// WordPress laden
if (file_exists(__DIR__ . '/wp-load.php')) {
    require_once(__DIR__ . '/wp-load.php');
} else {
    require_once('/var/www/html/wp-load.php');
}

// WordPress Media Funktionen laden
require_once(ABSPATH . 'wp-admin/includes/image.php');
require_once(ABSPATH . 'wp-admin/includes/file.php');
require_once(ABSPATH . 'wp-admin/includes/media.php');

echo "=== Import der kategorisierten Midjourney Bilder ===\n\n";

// Pfad zu den kategorisierten Bildern
$local_categorized_dir = '/Users/holgerbrandt/dev/claude-code/projects/riman-wordpress-swarm/categorized-images/';
$container_categorized_dir = '/tmp/categorized-images/';

// Prüfe welcher Pfad existiert oder kopiere lokal ins Container
if (file_exists($container_categorized_dir)) {
    $image_dir = $container_categorized_dir;
} elseif (file_exists($local_categorized_dir)) {
    // Wenn lokal, kopiere ins Container tmp Verzeichnis
    exec("docker cp $local_categorized_dir riman-wordpress-swarm-wordpress-1:/tmp/");
    $image_dir = $container_categorized_dir;
} else {
    echo "❌ Fehler: Kategorisierte Bilder nicht gefunden!\n";
    exit(1);
}

// Hole alle Kategorien
$categories = get_terms(array(
    'taxonomy' => 'category',
    'hide_empty' => false
));

$imported_count = 0;
$updated_count = 0;
$error_count = 0;

foreach ($categories as $category) {
    if ($category->slug === 'uncategorized') continue;
    
    // Finde passendes Bild für diese Kategorie
    $pattern = $image_dir . $category->slug . '_*.png';
    $matching_files = glob($pattern);
    
    if (empty($matching_files)) {
        echo "⚠️  Kein Bild gefunden für: {$category->name} (Slug: {$category->slug})\n";
        continue;
    }
    
    $image_path = $matching_files[0]; // Nimm das erste Match
    
    // Prüfe ob Kategorie schon ein Bild hat
    $existing_thumbnail = get_term_meta($category->term_id, '_thumbnail_id', true);
    
    if (!empty($existing_thumbnail)) {
        // Lösche das alte Bild
        wp_delete_attachment($existing_thumbnail, true);
        echo "🔄 Ersetze altes Bild für {$category->name}\n";
        $updated_count++;
    } else {
        $imported_count++;
    }
    
    // Kopiere Datei in temporäres Verzeichnis für WordPress Upload
    $temp_file = tempnam(sys_get_temp_dir(), 'riman_');
    copy($image_path, $temp_file);
    
    // Erstelle File Array für WordPress
    $file_array = array(
        'name' => 'riman-' . basename($image_path),
        'tmp_name' => $temp_file,
        'size' => filesize($temp_file),
        'type' => 'image/png'
    );
    
    // Handle upload using WordPress functions
    $attachment_id = media_handle_sideload($file_array, 0);
    
    // Lösche temporäre Datei
    @unlink($temp_file);
    
    if (is_wp_error($attachment_id)) {
        echo "❌ Fehler beim Import für {$category->name}: " . $attachment_id->get_error_message() . "\n";
        $error_count++;
        continue;
    }
    
    // Setze Metadaten für das Bild
    $attachment_data = array(
        'ID' => $attachment_id,
        'post_title' => $category->name,
        'post_excerpt' => 'Featured image für ' . $category->name,
        'post_content' => 'Automatisch generiertes Bild für die Kategorie ' . $category->name
    );
    wp_update_post($attachment_data);
    
    // Weise das Bild der Kategorie zu
    update_term_meta($category->term_id, '_thumbnail_id', $attachment_id);
    echo "✅ Bild importiert: {$category->name} ← " . basename($image_path) . "\n";
    
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
        echo "   └─ Bild auch der Seite '{$page->post_title}' zugewiesen\n";
    }
}

echo "\n=== Import abgeschlossen ===\n";
echo "✅ Neu importiert: $imported_count Bilder\n";
echo "🔄 Aktualisiert: $updated_count Bilder\n";
if ($error_count > 0) {
    echo "❌ Fehler: $error_count\n";
}

// Zeige finale Statistik
$categories_with_images = 0;
$categories_without_images = 0;

foreach ($categories as $category) {
    if ($category->slug === 'uncategorized') continue;
    $thumbnail_id = get_term_meta($category->term_id, '_thumbnail_id', true);
    if (!empty($thumbnail_id)) {
        $categories_with_images++;
    } else {
        $categories_without_images++;
    }
}

echo "\n=== Finale Statistik ===\n";
echo "Kategorien mit Bildern: $categories_with_images\n";
echo "Kategorien ohne Bilder: $categories_without_images\n";
echo "Gesamt: " . ($categories_with_images + $categories_without_images) . "\n";

// Liste Kategorien ohne Bilder auf
if ($categories_without_images > 0) {
    echo "\nKategorien ohne Bilder:\n";
    foreach ($categories as $category) {
        if ($category->slug === 'uncategorized') continue;
        $thumbnail_id = get_term_meta($category->term_id, '_thumbnail_id', true);
        if (empty($thumbnail_id)) {
            echo "  - {$category->name} ({$category->slug})\n";
        }
    }
}