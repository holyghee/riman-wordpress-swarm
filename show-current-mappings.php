<?php
/**
 * Show Current Image-to-Category Mappings
 * Creates a detailed overview of what images are assigned to which categories and pages
 */

// Load WordPress
require_once '/var/www/html/wp-config.php';
require_once '/var/www/html/wp-load.php';

echo "=== Current Image-to-Category Mappings ===\n\n";

// Get all categories with thumbnails
$categories = get_categories(['hide_empty' => false]);
$categories_with_images = [];
$categories_without_images = [];

foreach ($categories as $category) {
    $thumbnail_id = get_term_meta($category->term_id, 'thumbnail_id', true);
    
    if ($thumbnail_id) {
        $image = get_post($thumbnail_id);
        $image_url = wp_get_attachment_url($thumbnail_id);
        $image_meta = wp_get_attachment_metadata($thumbnail_id);
        $dimensions = isset($image_meta['width']) ? $image_meta['width'] . 'x' . $image_meta['height'] : 'unknown';
        
        // Check for linked pages
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
        
        $page_info = [];
        foreach ($pages as $page) {
            $page_thumbnail = get_post_thumbnail_id($page->ID);
            $page_info[] = [
                'title' => $page->post_title,
                'slug' => $page->post_name,
                'has_image' => $page_thumbnail ? 'YES' : 'NO',
                'image_matches' => ($page_thumbnail == $thumbnail_id) ? 'MATCH' : 'DIFFERENT'
            ];
        }
        
        $categories_with_images[] = [
            'category' => $category,
            'image' => $image,
            'image_url' => $image_url,
            'dimensions' => $dimensions,
            'pages' => $page_info
        ];
    } else {
        // Find pages without images too
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
        
        if (!empty($pages)) {
            $categories_without_images[] = [
                'category' => $category,
                'pages' => $pages
            ];
        }
    }
}

echo "=== CATEGORIES WITH IMAGES (" . count($categories_with_images) . ") ===\n";
foreach ($categories_with_images as $item) {
    echo "\n📁 Category: {$item['category']->name} (slug: {$item['category']->slug})\n";
    echo "   🖼️  Image: {$item['image']->post_title} (ID: {$item['image']->ID})\n";
    echo "   📏 Dimensions: {$item['dimensions']}\n";
    echo "   🔗 URL: {$item['image_url']}\n";
    
    if (!empty($item['pages'])) {
        echo "   📄 Linked Pages:\n";
        foreach ($item['pages'] as $page) {
            echo "      • {$page['title']} (/{$page['slug']}) - Image: {$page['has_image']} ({$page['image_matches']})\n";
        }
    } else {
        echo "   📄 No linked pages found\n";
    }
}

echo "\n\n=== CATEGORIES WITHOUT IMAGES (" . count($categories_without_images) . ") ===\n";
foreach ($categories_without_images as $item) {
    echo "\n❌ Category: {$item['category']->name} (slug: {$item['category']->slug})\n";
    if (!empty($item['pages'])) {
        echo "   📄 Linked Pages (also without images):\n";
        foreach ($item['pages'] as $page) {
            echo "      • {$page->post_title} (/{$page->post_name})\n";
        }
    }
}

// Check for images that might be orphaned
echo "\n\n=== UPSCALED IMAGES NOT USED AS CATEGORY THUMBNAILS ===\n";
$upscaled_images = get_posts([
    'post_type' => 'attachment',
    'posts_per_page' => -1,
    'meta_key' => '_midjourney_upscaled_image',
    'meta_value' => true
]);

$used_as_thumbnails = [];
foreach ($categories_with_images as $item) {
    $used_as_thumbnails[] = $item['image']->ID;
}

$unused_images = [];
foreach ($upscaled_images as $image) {
    if (!in_array($image->ID, $used_as_thumbnails)) {
        $unused_images[] = $image;
    }
}

if (!empty($unused_images)) {
    foreach ($unused_images as $image) {
        echo "🗑️  Unused: {$image->post_title} (ID: {$image->ID})\n";
    }
} else {
    echo "✅ All upscaled images are being used as category thumbnails\n";
}

echo "\n=== SUMMARY ===\n";
echo "✅ Categories with images: " . count($categories_with_images) . "\n";
echo "❌ Categories without images: " . count($categories_without_images) . "\n";
echo "🗑️  Unused upscaled images: " . count($unused_images) . "\n";
echo "📊 Total upscaled images: " . count($upscaled_images) . "\n";
