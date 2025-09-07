<?php
/**
 * Debug specific page - Check what image is actually assigned
 */

// Load WordPress
require_once '/var/www/html/wp-config.php';
require_once '/var/www/html/wp-load.php';

echo "=== Debug: Altlasten-Erkundung Page ===\n";

// Find the page
$page = get_page_by_path('altlasten/altlasten-erkundung');
if (!$page) {
    // Try alternative paths
    $page = get_page_by_path('altlasten-erkundung');
}

if (!$page) {
    echo "❌ Page not found!\n";
    exit;
}

echo "📄 Page found: {$page->post_title} (ID: {$page->ID})\n";
echo "🔗 URL: " . get_permalink($page->ID) . "\n";

// Check featured image
$featured_image_id = get_post_thumbnail_id($page->ID);
if ($featured_image_id) {
    $image = get_post($featured_image_id);
    $image_url = wp_get_attachment_url($featured_image_id);
    $image_meta = wp_get_attachment_metadata($featured_image_id);
    
    echo "🖼️  Current Featured Image: {$image->post_title} (ID: $featured_image_id)\n";
    echo "🔗 Image URL: $image_url\n";
    echo "📏 Dimensions: " . ($image_meta['width'] ?? 'unknown') . "x" . ($image_meta['height'] ?? 'unknown') . "\n";
    
    // Check if this is a Midjourney image
    $is_midjourney = get_post_meta($featured_image_id, '_midjourney_upscaled_image', true);
    $midjourney_id = get_post_meta($featured_image_id, '_midjourney_image_id', true);
    
    if ($is_midjourney) {
        echo "✅ This IS a Midjourney upscaled image\n";
        if ($midjourney_id) {
            echo "🆔 Midjourney ID: $midjourney_id\n";
        }
    } else {
        echo "❌ This is NOT a Midjourney upscaled image!\n";
    }
    
    // Check all meta data
    $all_meta = get_post_meta($featured_image_id);
    echo "\n📊 All image meta data:\n";
    foreach ($all_meta as $key => $value) {
        if (is_array($value) && count($value) == 1) {
            $value = $value[0];
        }
        if (is_string($value) && strlen($value) < 200) {
            echo "   $key: $value\n";
        }
    }
} else {
    echo "❌ No featured image assigned to this page!\n";
}

// Check category connection
$category_id = get_post_meta($page->ID, '_linked_category', true);
if ($category_id) {
    $category = get_term($category_id);
    echo "\n📁 Linked to category: {$category->name} (ID: $category_id)\n";
    
    $category_thumbnail = get_term_meta($category_id, 'thumbnail_id', true);
    if ($category_thumbnail) {
        echo "🖼️  Category has thumbnail: ID $category_thumbnail\n";
        
        if ($category_thumbnail != $featured_image_id) {
            echo "⚠️  MISMATCH: Page image ($featured_image_id) != Category image ($category_thumbnail)\n";
            
            // Show category image details
            $cat_image = get_post($category_thumbnail);
            echo "📋 Category image: {$cat_image->post_title}\n";
            echo "🔗 Category image URL: " . wp_get_attachment_url($category_thumbnail) . "\n";
        } else {
            echo "✅ Page and category images match\n";
        }
    }
}

echo "\n=== Checking for correct Altlasten-Erkundung images ===\n";
$upscaled_images = get_posts([
    'post_type' => 'attachment',
    'posts_per_page' => -1,
    'meta_key' => '_midjourney_upscaled_image',
    'meta_value' => true
]);

foreach ($upscaled_images as $image) {
    if (stripos($image->post_title, 'erkundung') !== false || 
        stripos($image->post_title, 'contaminated') !== false ||
        stripos($image->post_title, 'investigation') !== false) {
        echo "✅ Found correct image: {$image->post_title} (ID: {$image->ID})\n";
        echo "🔗 URL: " . wp_get_attachment_url($image->ID) . "\n";
    }
}