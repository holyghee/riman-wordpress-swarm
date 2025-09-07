<?php
/**
 * Import correctly named images from image-server instead of random Midjourney images
 */

// Load WordPress
require_once '/var/www/html/wp-config.php';
require_once '/var/www/html/wp-load.php';

echo "=== Importing Correctly Named Images from Image Server ===\n\n";

// Define the correct image mappings using the perfectly named files from image-server
$correct_mappings = [
    'schadstoffe-asbest' => '/Users/holgerbrandt/dev/claude-code/projects/riman-content/image-server/asbestsanierung-schutzausruestung-fachpersonal.jpg',
    'altlasten-bodensanierung' => '/Users/holgerbrandt/dev/claude-code/projects/riman-content/image-server/altlastensanierung-grundwasser-bodenschutz.jpg',
    'beratung-baumediation' => '/Users/holgerbrandt/dev/claude-code/projects/riman-content/image-server/baumediation-konfliktloesung-projektmanagement.jpg',
    'rueckbau-recycling' => '/Users/holgerbrandt/dev/claude-code/projects/riman-content/image-server/nachhaltiger-rueckbau-baustelle-recycling.jpg',
    'schadstoffe' => '/Users/holgerbrandt/dev/claude-code/projects/riman-content/image-server/schadstoffsanierung-industrieanlage-riman-gmbh.jpg',
    'rueckbau' => '/Users/holgerbrandt/dev/claude-code/projects/riman-content/image-server/systematischer-gebaeuderueckbau-kreislaufwirtschaft.jpg'
];

$upload_dir = wp_upload_dir();
$successful_imports = 0;

foreach ($correct_mappings as $category_slug => $image_path) {
    echo "🔍 Processing: $category_slug\n";
    
    if (!file_exists($image_path)) {
        echo "   ❌ Image file not found: $image_path\n\n";
        continue;
    }
    
    // Get category
    $category = get_term_by('slug', $category_slug, 'category');
    if (!$category) {
        echo "   ❌ Category not found: $category_slug\n\n";
        continue;
    }
    
    // Generate WordPress filename
    $original_filename = basename($image_path);
    $file_extension = pathinfo($original_filename, PATHINFO_EXTENSION);
    $clean_name = pathinfo($original_filename, PATHINFO_FILENAME);
    $wp_filename = $clean_name . '_correct.' . $file_extension;
    $target_path = $upload_dir['path'] . '/' . $wp_filename;
    $target_url = $upload_dir['url'] . '/' . $wp_filename;
    
    // Copy file to WordPress uploads
    if (!copy($image_path, $target_path)) {
        echo "   ❌ Failed to copy image\n\n";
        continue;
    }
    
    echo "   ✅ Copied to WordPress uploads\n";
    
    // Create WordPress attachment
    $attachment_data = [
        'guid' => $target_url,
        'post_mime_type' => 'image/' . $file_extension,
        'post_title' => ucwords(str_replace('-', ' ', $clean_name)),
        'post_content' => 'Correctly assigned image for ' . $category->name,
        'post_status' => 'inherit'
    ];
    
    $attachment_id = wp_insert_attachment($attachment_data, $target_path);
    
    if (is_wp_error($attachment_id)) {
        echo "   ❌ Failed to create attachment\n\n";
        continue;
    }
    
    // Generate metadata
    require_once(ABSPATH . 'wp-admin/includes/image.php');
    $attach_metadata = wp_generate_attachment_metadata($attachment_id, $target_path);
    wp_update_attachment_metadata($attachment_id, $attach_metadata);
    
    // Mark as correct image
    update_post_meta($attachment_id, '_correct_category_image', true);
    update_post_meta($attachment_id, '_assigned_category', $category_slug);
    
    echo "   ✅ Created WordPress attachment (ID: $attachment_id)\n";
    echo "   📄 Title: " . $attachment_data['post_title'] . "\n";
    
    // Assign to category
    update_term_meta($category->term_id, 'thumbnail_id', $attachment_id);
    echo "   🖼️  Assigned as category thumbnail\n";
    
    // Find and assign to linked pages
    $linked_pages = get_posts([
        'post_type' => 'page',
        'meta_query' => [
            [
                'key' => '_linked_category',
                'value' => $category->term_id,
                'compare' => '='
            ]
        ]
    ]);
    
    foreach ($linked_pages as $page) {
        set_post_thumbnail($page->ID, $attachment_id);
        echo "   📄 Assigned to page: {$page->post_title}\n";
    }
    
    $successful_imports++;
    echo "   ✅ SUCCESS!\n\n";
}

// Clear cache
if (function_exists('wp_cache_flush')) {
    wp_cache_flush();
    echo "🗑️  Cache cleared\n";
}

echo "\n=== SUMMARY ===\n";
echo "✅ Successfully imported and assigned: $successful_imports images\n";
echo "📊 Total mappings processed: " . count($correct_mappings) . "\n";
echo "\n🎯 Correct image assignment complete!\n";
echo "🔗 Test your site: http://127.0.0.1:8801/\n";