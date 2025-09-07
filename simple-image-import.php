<?php
/**
 * SUPER SIMPLE Image Import - Use Wildcards, No Mappings!
 */

require_once('/var/www/html/wp-load.php');

echo "=== SUPER SIMPLE Image Import ===\n\n";

// Alle Bilder finden
$image_dir = '/tmp/images/';
$images = glob($image_dir . '*.{jpg,jpeg,png,gif,webp}', GLOB_BRACE);

echo "Gefundene Bilder: " . count($images) . "\n\n";

// Alle Seiten laden
$pages = get_posts(array(
    'post_type' => 'page',
    'numberposts' => -1,
    'post_status' => 'any'
));

echo "Gefundene Seiten: " . count($pages) . "\n\n";

$assigned = 0;

foreach ($pages as $page) {
    // Skip wenn bereits Featured Image vorhanden
    if (get_post_thumbnail_id($page->ID)) {
        echo "⏭️  {$page->post_name} hat bereits Bild\n";
        continue;
    }
    
    // Finde passendes Bild mit Wildcards
    $matching_image = null;
    $slug = $page->post_name;
    
    foreach ($images as $image_path) {
        $filename = basename($image_path);
        
        // Wildcard Matching - irgendein Keyword aus dem Slug
        $slug_parts = explode('-', $slug);
        foreach ($slug_parts as $part) {
            if (strlen($part) > 3 && stripos($filename, $part) !== false) {
                $matching_image = $image_path;
                echo "✅ Match für $slug: $filename (Keyword: $part)\n";
                break 2;
            }
        }
    }
    
    // Fallback: Nimm einfach irgendein Bild
    if (!$matching_image && !empty($images)) {
        $matching_image = $images[array_rand($images)];
        echo "🎲 Random für $slug: " . basename($matching_image) . "\n";
    }
    
    if ($matching_image) {
        // Import
        $upload = wp_upload_bits(basename($matching_image), null, file_get_contents($matching_image));
        
        if (!$upload['error']) {
            $attachment_data = array(
                'guid' => $upload['url'],
                'post_mime_type' => mime_content_type($matching_image),
                'post_title' => pathinfo($matching_image, PATHINFO_FILENAME),
                'post_content' => '',
                'post_status' => 'inherit'
            );
            
            $attachment_id = wp_insert_attachment($attachment_data, $upload['file'], $page->ID);
            
            if (!is_wp_error($attachment_id)) {
                require_once(ABSPATH . 'wp-admin/includes/image.php');
                $attach_metadata = wp_generate_attachment_metadata($attachment_id, $upload['file']);
                wp_update_attachment_metadata($attachment_id, $attach_metadata);
                
                set_post_thumbnail($page->ID, $attachment_id);
                $assigned++;
            }
        }
    }
}

echo "\n=== Ergebnis ===\n";
echo "✅ Bilder zugewiesen: $assigned\n";

// Check final status
$with = 0;
$without = 0;
foreach ($pages as $page) {
    if (get_post_thumbnail_id($page->ID)) {
        $with++;
    } else {
        $without++;
    }
}

echo "📊 Seiten mit Bildern: $with\n";
echo "📊 Seiten ohne Bilder: $without\n";