<?php
/**
 * Debug Category Images - Check what's actually assigned
 */

// Load WordPress
require_once '/var/www/html/wp-config.php';
require_once '/var/www/html/wp-load.php';

echo "=== DEBUG: Category Image Assignments ===\n\n";

// Get all categories
$categories = get_terms([
    'taxonomy' => 'category',
    'hide_empty' => false,
    'orderby' => 'name',
    'order' => 'ASC'
]);

echo "Found " . count($categories) . " categories\n\n";

foreach ($categories as $category) {
    if ($category->slug === 'uncategorized') continue;
    
    echo "📁 Category: {$category->name}\n";
    echo "   Slug: {$category->slug}\n";
    echo "   ID: {$category->term_id}\n";
    
    // Check both possible meta keys
    $thumbnail_id = get_term_meta($category->term_id, 'thumbnail_id', true);
    $thumbnail_id_alt = get_term_meta($category->term_id, '_thumbnail_id', true);
    
    echo "   thumbnail_id: " . ($thumbnail_id ?: "NONE") . "\n";
    echo "   _thumbnail_id: " . ($thumbnail_id_alt ?: "NONE") . "\n";
    
    // If we have an ID, check if the attachment exists
    $attachment_id = $thumbnail_id ?: $thumbnail_id_alt;
    if ($attachment_id) {
        $attachment = get_post($attachment_id);
        if ($attachment) {
            echo "   ✅ Attachment exists: " . $attachment->post_title . "\n";
            echo "   URL: " . wp_get_attachment_url($attachment_id) . "\n";
        } else {
            echo "   ❌ Attachment ID {$attachment_id} does NOT exist!\n";
        }
    } else {
        echo "   ⚠️  NO IMAGE ASSIGNED\n";
    }
    
    // Check linked pages
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
        $page_thumb = get_post_thumbnail_id($page->ID);
        echo "   📄 Linked page: {$page->post_title} (Featured Image ID: " . ($page_thumb ?: "NONE") . ")\n";
    }
    
    echo "\n";
}

// Show all attachments
echo "=== ALL ATTACHMENTS ===\n";
$attachments = get_posts([
    'post_type' => 'attachment',
    'numberposts' => -1,
    'post_status' => 'any'
]);

echo "Total attachments: " . count($attachments) . "\n\n";

foreach ($attachments as $att) {
    echo "ID: {$att->ID} | Title: {$att->post_title}\n";
    echo "   URL: " . wp_get_attachment_url($att->ID) . "\n";
    
    // Check if it's assigned to any category
    $assigned_to = [];
    foreach ($categories as $cat) {
        $thumb = get_term_meta($cat->term_id, 'thumbnail_id', true);
        $thumb_alt = get_term_meta($cat->term_id, '_thumbnail_id', true);
        if ($thumb == $att->ID || $thumb_alt == $att->ID) {
            $assigned_to[] = $cat->name;
        }
    }
    if ($assigned_to) {
        echo "   📎 Assigned to: " . implode(', ', $assigned_to) . "\n";
    }
    echo "\n";
}

// Check for the specific problematic category
echo "=== SPECIFIC CHECK: altlasten ===\n";
$altlasten = get_term_by('slug', 'altlasten', 'category');
if ($altlasten) {
    echo "Category exists with ID: {$altlasten->term_id}\n";
    
    // Get all meta for this category
    $all_meta = get_term_meta($altlasten->term_id);
    echo "ALL META DATA:\n";
    print_r($all_meta);
    
    // Try to find the image
    $image_file = 'altlastensanierung-grundwasser-bodenschutz.jpg';
    echo "\nSearching for image file: $image_file\n";
    
    $found = false;
    foreach ($attachments as $att) {
        if (strpos($att->guid, $image_file) !== false || 
            strpos(wp_get_attachment_url($att->ID), $image_file) !== false) {
            echo "✅ Found attachment ID {$att->ID} with this filename\n";
            echo "   URL: " . wp_get_attachment_url($att->ID) . "\n";
            $found = true;
        }
    }
    if (!$found) {
        echo "❌ No attachment found with filename: $image_file\n";
    }
}