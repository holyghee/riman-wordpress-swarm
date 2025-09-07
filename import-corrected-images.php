<?php
/**
 * Import corrected Discord timeline images to WordPress with proper category mapping
 * Based on content analysis, not sequential order
 */

// Load WordPress
require_once '/var/www/html/wp-config.php';
require_once '/var/www/html/wp-load.php';

// Load the correct mapping
$mapping_file = '/var/www/html/discord_wordpress_mapping.json';
$image_dir = '/tmp/downloaded_images/';

if (!file_exists($mapping_file)) {
    die("Mapping file not found: $mapping_file\n");
}

$mapping_data = json_decode(file_get_contents($mapping_file), true);
if (!$mapping_data) {
    die("Failed to parse mapping file\n");
}

echo "=== Importing Corrected Images to WordPress ===\n";
echo "Total mappings: " . count($mapping_data['mappings']) . "\n\n";

$imported_count = 0;
$skipped_count = 0;

foreach ($mapping_data['mappings'] as $mapping) {
    $image_id = $mapping['image_id'];
    $main_category = $mapping['wordpress_mapping']['main_category'];
    $subcategory = $mapping['wordpress_mapping']['subcategory'];
    $confidence = $mapping['wordpress_mapping']['confidence'];
    
    // Find the matching image file
    $image_files = glob($image_dir . "*{$image_id}*.png");
    if (empty($image_files)) {
        echo "⚠️  Image file not found for ID: $image_id\n";
        $skipped_count++;
        continue;
    }
    
    $image_file = $image_files[0];
    $filename = basename($image_file);
    
    // Check if already imported
    $existing = get_posts([
        'post_type' => 'attachment',
        'meta_query' => [
            [
                'key' => '_midjourney_image_id',
                'value' => $image_id,
                'compare' => '='
            ]
        ]
    ]);
    
    if (!empty($existing)) {
        echo "⏭️  Already imported: $filename (ID: $image_id)\n";
        continue;
    }
    
    // Copy file to WordPress uploads
    $upload_dir = wp_upload_dir();
    $target_filename = $main_category . '_' . $subcategory . '_' . $image_id . '.png';
    $target_path = $upload_dir['path'] . '/' . $target_filename;
    $target_url = $upload_dir['url'] . '/' . $target_filename;
    
    if (!copy($image_file, $target_path)) {
        echo "❌ Failed to copy: $filename\n";
        $skipped_count++;
        continue;
    }
    
    // Create attachment
    $attachment = [
        'guid' => $target_url,
        'post_mime_type' => 'image/png',
        'post_title' => ucwords(str_replace(['_', '-'], ' ', $main_category . ' ' . $subcategory)),
        'post_content' => '',
        'post_status' => 'inherit'
    ];
    
    $attachment_id = wp_insert_attachment($attachment, $target_path);
    
    if (is_wp_error($attachment_id)) {
        echo "❌ Failed to create attachment: $filename\n";
        $skipped_count++;
        continue;
    }
    
    // Generate metadata
    require_once(ABSPATH . 'wp-admin/includes/image.php');
    $attachment_data = wp_generate_attachment_metadata($attachment_id, $target_path);
    wp_update_attachment_metadata($attachment_id, $attachment_data);
    
    // Add custom meta
    update_post_meta($attachment_id, '_midjourney_image_id', $image_id);
    update_post_meta($attachment_id, '_midjourney_prompt', $mapping['prompt']);
    update_post_meta($attachment_id, '_wordpress_main_category', $main_category);
    update_post_meta($attachment_id, '_wordpress_subcategory', $subcategory);
    update_post_meta($attachment_id, '_mapping_confidence', $confidence);
    
    // Try to find WordPress category and set as featured image
    $category_slug = $subcategory ?: $main_category;
    $category = get_term_by('slug', $category_slug, 'category');
    
    if ($category) {
        // Set as category featured image
        update_term_meta($category->term_id, 'thumbnail_id', $attachment_id);
        
        // Also try to find linked page via Category Page Content Connector plugin
        $pages = get_posts([
            'post_type' => 'page',
            'meta_query' => [
                [
                    'key' => '_linked_category',
                    'value' => $category->term_id,
                    'compare' => '='
                ]
            ]
        ]);
        
        foreach ($pages as $page) {
            set_post_thumbnail($page->ID, $attachment_id);
            echo "📄 Set as featured image for page: {$page->post_title}\n";
        }
        
        echo "✅ Imported: $target_filename → Category: {$category->name} (ID: $attachment_id, Confidence: $confidence)\n";
    } else {
        echo "⚠️  Category not found for slug: $category_slug (Image imported as ID: $attachment_id)\n";
    }
    
    $imported_count++;
}

echo "\n=== Import Complete ===\n";
echo "✅ Imported: $imported_count images\n";
echo "⏭️  Skipped: $skipped_count images\n";
echo "🎯 All images now correctly mapped based on content analysis!\n";