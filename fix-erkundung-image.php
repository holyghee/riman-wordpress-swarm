<?php
/**
 * Fix the Altlasten-Erkundung image assignment 
 */

// Load WordPress
require_once '/var/www/html/wp-config.php';
require_once '/var/www/html/wp-load.php';

echo "=== Fixing Altlasten-Erkundung Image ===\n";

// Find the page
$page = get_page_by_path('altlasten/altlasten-erkundung');
if (!$page) {
    $page = get_page_by_path('altlasten-erkundung');
}

if (!$page) {
    die("❌ Page not found!\n");
}

echo "📄 Page: {$page->post_title} (ID: {$page->ID})\n";

// Find the newest/best Altlasten-Erkundung image
$upscaled_images = get_posts([
    'post_type' => 'attachment',
    'posts_per_page' => -1,
    'meta_key' => '_midjourney_upscaled_image',
    'meta_value' => true,
    'orderby' => 'ID',
    'order' => 'DESC'
]);

$best_image = null;
echo "🔍 Looking for best Altlasten-Erkundung image...\n";

foreach ($upscaled_images as $image) {
    if (stripos($image->post_title, 'erkundung') !== false || 
        stripos($image->post_title, 'contaminated') !== false ||
        stripos($image->post_title, 'investigation') !== false) {
        
        // Check if this image is actually appropriate
        $image_url = wp_get_attachment_url($image->ID);
        echo "   Found: {$image->post_title} (ID: {$image->ID}) - $image_url\n";
        
        if (!$best_image) {
            $best_image = $image;
        }
    }
}

if (!$best_image) {
    die("❌ No suitable image found!\n");
}

echo "✅ Selected image: {$best_image->post_title} (ID: {$best_image->ID})\n";

// Set as featured image for the page
set_post_thumbnail($page->ID, $best_image->ID);

// Also update the category
$category = get_term_by('slug', 'altlasten-erkundung', 'category');
if ($category) {
    update_term_meta($category->term_id, 'thumbnail_id', $best_image->ID);
    echo "✅ Updated category thumbnail as well\n";
}

echo "🎯 Fixed! Please check: " . get_permalink($page->ID) . "\n";
echo "🖼️  New image URL: " . wp_get_attachment_url($best_image->ID) . "\n";

// Clear any caching
if (function_exists('wp_cache_flush')) {
    wp_cache_flush();
    echo "🗑️  Cache cleared\n";
}