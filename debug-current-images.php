<?php
/**
 * Debug current images - Check what images are currently in WordPress and what's assigned
 */

// Load WordPress
require_once '/var/www/html/wp-config.php';
require_once '/var/www/html/wp-load.php';

echo "=== Debug Current Images in WordPress ===\n";

// Check altlasten-erkundung page specifically
$page = get_page_by_path('altlasten-erkundung');
if ($page) {
    echo "Page: Altlasten-Erkundung (ID: {$page->ID})\n";
    $thumbnail_id = get_post_thumbnail_id($page->ID);
    if ($thumbnail_id) {
        $attachment = get_post($thumbnail_id);
        $attachment_url = wp_get_attachment_url($thumbnail_id);
        echo "  Featured Image ID: $thumbnail_id\n";
        echo "  Filename: {$attachment->post_title}\n";
        echo "  URL: $attachment_url\n";
        
        // Get image dimensions to identify if it's a grid
        $meta = wp_get_attachment_metadata($thumbnail_id);
        if ($meta) {
            $width = $meta['width'] ?? 'unknown';
            $height = $meta['height'] ?? 'unknown';
            $ratio = $width && $height ? round($width/$height, 2) : 'unknown';
            echo "  Dimensions: {$width}x{$height} (ratio: $ratio)\n";
            if ($ratio >= 0.9 && $ratio <= 1.1) {
                echo "  ⚠️  WARNING: Square ratio detected - likely a 2x2 grid!\n";
            }
        }
    } else {
        echo "  No featured image assigned\n";
    }
} else {
    echo "Page 'altlasten-erkundung' not found\n";
}

echo "\n=== All Current Attachments ===\n";
$attachments = get_posts([
    'post_type' => 'attachment',
    'posts_per_page' => -1,
    'post_status' => 'inherit'
]);

echo "Total attachments: " . count($attachments) . "\n\n";

$grid_images = 0;
$upscaled_images = 0;

foreach ($attachments as $attachment) {
    if (strpos($attachment->post_mime_type, 'image') !== false) {
        $meta = wp_get_attachment_metadata($attachment->ID);
        $width = $meta['width'] ?? 0;
        $height = $meta['height'] ?? 0;
        $ratio = $width && $height ? round($width/$height, 2) : 0;
        
        $is_midjourney = get_post_meta($attachment->ID, '_midjourney_image_id', true);
        
        if ($is_midjourney) {
            echo "ID: {$attachment->ID} | {$attachment->post_title}\n";
            echo "  Size: {$width}x{$height} (ratio: $ratio)\n";
            echo "  URL: " . wp_get_attachment_url($attachment->ID) . "\n";
            
            if ($ratio >= 0.9 && $ratio <= 1.1) {
                echo "  🔲 GRID IMAGE (square ratio)\n";
                $grid_images++;
            } elseif ($ratio >= 1.7 && $ratio <= 1.9) {
                echo "  📐 UPSCALED IMAGE (16:9 ratio)\n";
                $upscaled_images++;
            } else {
                echo "  ❓ UNKNOWN RATIO\n";
            }
            echo "\n";
        }
    }
}

echo "=== Summary ===\n";
echo "Grid images (2x2): $grid_images\n";
echo "Upscaled images: $upscaled_images\n";
echo "🎯 Need to clean grid images and reassign upscaled ones!\n";