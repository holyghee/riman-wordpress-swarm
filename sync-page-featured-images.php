<?php
/**
 * Sync featured images from categories to their linked pages
 * This ensures all pages have the same featured image as their category thumbnail
 */

// Load WordPress
require_once '/var/www/html/wp-config.php';
require_once '/var/www/html/wp-load.php';

echo "=== Syncing Featured Images from Categories to Pages ===\n\n";

// Get all categories
$categories = get_terms([
    'taxonomy' => 'category',
    'hide_empty' => false
]);

$synced = 0;
$failed = 0;
$already_correct = 0;

foreach ($categories as $category) {
    if ($category->slug === 'uncategorized') continue;
    
    // Get category thumbnail
    $category_thumbnail = get_term_meta($category->term_id, 'thumbnail_id', true);
    
    if (!$category_thumbnail) {
        echo "❌ Category {$category->name} has no thumbnail\n";
        continue;
    }
    
    // Verify the attachment exists
    $attachment = get_post($category_thumbnail);
    if (!$attachment) {
        echo "❌ Category {$category->name} has invalid thumbnail ID: {$category_thumbnail}\n";
        continue;
    }
    
    echo "📁 Category: {$category->name} (Thumbnail ID: {$category_thumbnail})\n";
    
    // Find all pages linked to this category
    $pages = get_posts([
        'post_type' => 'page',
        'numberposts' => -1,
        'meta_query' => [
            [
                'key' => '_linked_category',
                'value' => $category->term_id,
                'compare' => '='
            ]
        ]
    ]);
    
    if (empty($pages)) {
        echo "   ⚠️  No pages linked to this category\n\n";
        continue;
    }
    
    foreach ($pages as $page) {
        $current_thumbnail = get_post_thumbnail_id($page->ID);
        
        if ($current_thumbnail == $category_thumbnail) {
            echo "   ✅ Page '{$page->post_title}' already has correct featured image\n";
            $already_correct++;
        } else {
            // WICHTIG: Der korrekte Weg Featured Images zu setzen
            // 1. Hauptmethode - WordPress Standard
            $result = set_post_thumbnail($page->ID, $category_thumbnail);
            
            // 2. Fallback wenn set_post_thumbnail fehlschlägt
            if (!$result) {
                update_post_meta($page->ID, '_thumbnail_id', $category_thumbnail);
                $result = true; // Assume success after direct meta update
            }
            
            // 3. WICHTIG: Immer verifizieren!
            $verify = get_post_thumbnail_id($page->ID);
            if ($verify != $category_thumbnail) {
                // Nochmal mit direktem Meta-Update versuchen
                update_post_meta($page->ID, '_thumbnail_id', $category_thumbnail);
                
                // Final verification
                $verify = get_post_thumbnail_id($page->ID);
                if ($verify == $category_thumbnail) {
                    echo "   ✅ SYNCED (meta update) featured image for page: {$page->post_title}\n";
                    $synced++;
                } else {
                    echo "   ❌ Failed to sync featured image for page: {$page->post_title} (tried all methods)\n";
                    $failed++;
                }
            } else {
                echo "   ✅ SYNCED featured image for page: {$page->post_title}\n";
                $synced++;
            }
        }
    }
    
    echo "\n";
}

// Clear cache
if (function_exists('wp_cache_flush')) {
    wp_cache_flush();
}

echo "=== SUMMARY ===\n";
echo "✅ Synced: $synced pages\n";
echo "✅ Already correct: $already_correct pages\n";
echo "❌ Failed: $failed pages\n\n";

// Final verification - check all pages
echo "=== FINAL VERIFICATION ===\n";
$all_pages = get_posts([
    'post_type' => 'page',
    'numberposts' => -1
]);

$with_featured = 0;
$without_featured = 0;

foreach ($all_pages as $page) {
    $thumb = get_post_thumbnail_id($page->ID);
    if ($thumb) {
        $with_featured++;
    } else {
        $without_featured++;
        echo "⚠️  No featured image: {$page->post_title}\n";
    }
}

echo "\n📸 Pages with featured images: $with_featured\n";
echo "❌ Pages without featured images: $without_featured\n";
echo "\n🎯 Featured image sync complete!\n";
echo "🔗 Check your pages at: http://127.0.0.1:8801/wp-admin/edit.php?post_type=page\n";