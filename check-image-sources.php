<?php
/**
 * Check which images are actually being used as featured images
 */

// Load WordPress
require_once '/var/www/html/wp-config.php';
require_once '/var/www/html/wp-load.php';

echo "=== Checking Featured Image Sources ===\n\n";

// Get pages with featured images
$pages = get_posts([
    'post_type' => 'page',
    'numberposts' => -1,
    'meta_key' => '_thumbnail_id',
    'meta_compare' => 'EXISTS'
]);

$swarm_config_images = [
    'nachhaltiger-rueckbau-baustelle-recycling.jpg',
    'altlastensanierung-grundwasser-bodenschutz.jpg',
    'schadstoffsanierung-industrieanlage-riman-gmbh.jpg',
    'sicherheitsvorbereitung-schutzausruestung-schritt-3.jpg',
    'baumediation-konfliktloesung-projektmanagement.jpg'
];

$from_swarm_config = 0;
$from_other = 0;
$image_details = [];

foreach ($pages as $page) {
    $thumbnail_id = get_post_thumbnail_id($page->ID);
    if (!$thumbnail_id) continue;
    
    $attachment = get_post($thumbnail_id);
    if (!$attachment) continue;
    
    // Get the filename
    $filename = basename($attachment->guid);
    
    // Check if this is from swarm-config
    $is_swarm_config = false;
    foreach ($swarm_config_images as $config_image) {
        if (strpos($filename, $config_image) !== false) {
            $is_swarm_config = true;
            $from_swarm_config++;
            break;
        }
    }
    
    if (!$is_swarm_config) {
        $from_other++;
    }
    
    $image_details[] = [
        'page' => $page->post_title,
        'slug' => $page->post_name,
        'image' => $filename,
        'source' => $is_swarm_config ? 'swarm-config' : 'other'
    ];
}

// Show results
echo "📊 Summary:\n";
echo "   From swarm-config.yaml: $from_swarm_config\n";
echo "   From other sources: $from_other\n\n";

echo "📸 Detailed Image Assignments:\n";
echo str_repeat("-", 80) . "\n";

foreach ($image_details as $detail) {
    $icon = $detail['source'] === 'swarm-config' ? '✅' : '❌';
    echo "$icon {$detail['page']} ({$detail['slug']})\n";
    echo "   Image: {$detail['image']}\n";
    echo "   Source: {$detail['source']}\n\n";
}

// Check what the semantic mappings should be
echo "=== Expected from swarm-config.yaml ===\n";
$expected_mappings = [
    'rueckbau' => 'nachhaltiger-rueckbau-baustelle-recycling.jpg',
    'altlasten' => 'altlastensanierung-grundwasser-bodenschutz.jpg',
    'schadstoffe' => 'schadstoffsanierung-industrieanlage-riman-gmbh.jpg',
    'sicherheitskoordination' => 'sicherheitsvorbereitung-schutzausruestung-schritt-3.jpg',
    'beratung' => 'baumediation-konfliktloesung-projektmanagement.jpg'
];

foreach ($expected_mappings as $slug => $image) {
    $page = get_page_by_path($slug);
    if ($page) {
        $current_thumb = get_post_thumbnail_id($page->ID);
        if ($current_thumb) {
            $current_attachment = get_post($current_thumb);
            $current_file = basename($current_attachment->guid);
            if (strpos($current_file, $image) !== false) {
                echo "✅ $slug → $image (CORRECT)\n";
            } else {
                echo "❌ $slug → Expected: $image, Got: $current_file\n";
            }
        } else {
            echo "❌ $slug → No featured image\n";
        }
    } else {
        echo "⚠️  Page '$slug' not found\n";
    }
}