<?php
/**
 * Clean WordPress - Remove all Midjourney images and reset featured images
 */

// Load WordPress
require_once '/var/www/html/wp-config.php';
require_once '/var/www/html/wp-load.php';

echo "=== Cleaning WordPress from Midjourney Images ===\n";

// Find all Midjourney attachments (by meta key)
$midjourney_attachments = get_posts([
    'post_type' => 'attachment',
    'posts_per_page' => -1,
    'meta_key' => '_midjourney_image_id'
]);

echo "Found " . count($midjourney_attachments) . " Midjourney images to remove\n";

$deleted_count = 0;
foreach ($midjourney_attachments as $attachment) {
    // Remove featured image assignments from categories
    $categories = get_categories();
    foreach ($categories as $category) {
        $current_thumbnail = get_term_meta($category->term_id, 'thumbnail_id', true);
        if ($current_thumbnail == $attachment->ID) {
            delete_term_meta($category->term_id, 'thumbnail_id');
            echo "Removed featured image from category: {$category->name}\n";
        }
    }
    
    // Remove featured image assignments from pages 
    $pages_with_thumbnail = get_posts([
        'post_type' => 'page',
        'meta_key' => '_thumbnail_id',
        'meta_value' => $attachment->ID,
        'posts_per_page' => -1
    ]);
    
    foreach ($pages_with_thumbnail as $page) {
        delete_post_thumbnail($page->ID);
        echo "Removed featured image from page: {$page->post_title}\n";
    }
    
    // Delete the attachment and its files
    wp_delete_attachment($attachment->ID, true);
    $deleted_count++;
    echo "Deleted attachment: {$attachment->post_title} (ID: {$attachment->ID})\n";
}

echo "\n=== Cleanup Complete ===\n";
echo "✅ Deleted: $deleted_count Midjourney images\n";
echo "✅ Reset all featured image assignments\n";
echo "🎯 WordPress is now clean and ready for fresh import!\n";