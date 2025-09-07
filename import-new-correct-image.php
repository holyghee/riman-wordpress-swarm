<?php
/**
 * Import the new correct Site Investigation image and assign it
 */

// Load WordPress
require_once '/var/www/html/wp-config.php';
require_once '/var/www/html/wp-load.php';

echo "=== Importing New Correct Site Investigation Image ===\n";

// Source file from Midjourney generation
$source_file = '/Users/holgerbrandt/dev/claude-code/tools/midjourney-mcp-server/midjourney-images/midjourney_upscaled_1756980979254.png';

if (!file_exists($source_file)) {
    die("❌ Source file not found: $source_file\n");
}

echo "✅ Found new image file\n";

// Copy file to WordPress uploads
$upload_dir = wp_upload_dir();
$target_filename = 'altlasten-erkundung_correct_' . time() . '.png';
$target_path = $upload_dir['path'] . '/' . $target_filename;
$target_url = $upload_dir['url'] . '/' . $target_filename;

if (!copy($source_file, $target_path)) {
    die("❌ Failed to copy image file\n");
}

echo "✅ Copied image to WordPress uploads\n";

// Create attachment
$attachment = [
    'guid' => $target_url,
    'post_mime_type' => 'image/png',
    'post_title' => 'Professional Site Investigation (Correct)',
    'post_content' => 'Professional contaminated site investigation and exploration. Soil sampling and analysis equipment in action.',
    'post_status' => 'inherit'
];

$attachment_id = wp_insert_attachment($attachment, $target_path);

if (is_wp_error($attachment_id)) {
    die("❌ Failed to create attachment\n");
}

// Generate metadata
require_once(ABSPATH . 'wp-admin/includes/image.php');
$attachment_data = wp_generate_attachment_metadata($attachment_id, $target_path);
wp_update_attachment_metadata($attachment_id, $attachment_data);

// Add custom meta
update_post_meta($attachment_id, '_midjourney_upscaled_image', true);
update_post_meta($attachment_id, '_midjourney_correct_image', true);
update_post_meta($attachment_id, '_midjourney_timestamp', time());

echo "✅ Created WordPress attachment (ID: $attachment_id)\n";

// Find the Altlasten-Erkundung page and category
$page = get_page_by_path('altlasten/altlasten-erkundung');
$category = get_term_by('slug', 'altlasten-erkundung', 'category');

if ($page && $category) {
    // Set as page featured image
    set_post_thumbnail($page->ID, $attachment_id);
    echo "✅ Set as featured image for page: {$page->post_title}\n";
    
    // Set as category thumbnail
    update_term_meta($category->term_id, 'thumbnail_id', $attachment_id);
    echo "✅ Set as category thumbnail: {$category->name}\n";
    
    echo "🎯 New correct image assigned!\n";
    echo "🔗 Page URL: " . get_permalink($page->ID) . "\n";
    echo "🖼️  Image URL: $target_url\n";
} else {
    echo "⚠️  Page or category not found\n";
    if (!$page) echo "   - Page not found\n";
    if (!$category) echo "   - Category not found\n";
}

// Clear cache
if (function_exists('wp_cache_flush')) {
    wp_cache_flush();
    echo "🗑️  Cache cleared\n";
}