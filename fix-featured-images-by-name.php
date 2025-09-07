<?php
/**
 * Fix featured images by matching page names to category names
 * Since pages have the same names as categories, we can match them directly
 */

// Load WordPress
require_once '/var/www/html/wp-config.php';
require_once '/var/www/html/wp-load.php';

echo "=== Fixing Featured Images by Name Matching ===\n\n";

// Get all pages
$pages = get_posts([
    'post_type' => 'page',
    'numberposts' => -1,
    'post_status' => 'publish'
]);

$fixed = 0;
$already_has_image = 0;
$no_matching_category = 0;

foreach ($pages as $page) {
    // Skip system pages
    if (in_array($page->post_name, ['impressum', 'kontakt', 'ueber-uns', 'startseite'])) {
        echo "⏭️  Skipping system page: {$page->post_title}\n";
        continue;
    }
    
    // Check if page already has featured image
    $current_featured = get_post_thumbnail_id($page->ID);
    if ($current_featured) {
        echo "✅ Page '{$page->post_title}' already has featured image\n";
        $already_has_image++;
        continue;
    }
    
    echo "📄 Processing page: {$page->post_title} (slug: {$page->post_name})\n";
    
    // Try to find matching category by slug
    $category = get_term_by('slug', $page->post_name, 'category');
    
    if (!$category) {
        // Try with variations (remove -sanierung, -management suffixes)
        $variations = [
            $page->post_name,
            str_replace('-sanierung', '', $page->post_name),
            str_replace('-management', '', $page->post_name),
            str_replace('-koordination', '', $page->post_name)
        ];
        
        foreach ($variations as $variant) {
            $category = get_term_by('slug', $variant, 'category');
            if ($category) break;
        }
    }
    
    if (!$category) {
        echo "   ❌ No matching category found\n";
        $no_matching_category++;
        continue;
    }
    
    // Get category thumbnail
    $category_thumbnail = get_term_meta($category->term_id, 'thumbnail_id', true);
    
    if (!$category_thumbnail) {
        echo "   ❌ Category '{$category->name}' has no thumbnail\n";
        continue;
    }
    
    // Der korrekte Weg Featured Images zu setzen:
    // 1. Hauptmethode - WordPress Standard
    $result = set_post_thumbnail($page->ID, $category_thumbnail);
    
    // 2. Fallback wenn set_post_thumbnail fehlschlägt
    if (!$result) {
        update_post_meta($page->ID, '_thumbnail_id', $category_thumbnail);
    }
    
    // 3. WICHTIG: Immer verifizieren!
    $verify = get_post_thumbnail_id($page->ID);
    if ($verify != $category_thumbnail) {
        // Nochmal mit direktem Meta-Update versuchen
        update_post_meta($page->ID, '_thumbnail_id', $category_thumbnail);
        
        // Final verification
        $verify = get_post_thumbnail_id($page->ID);
        if ($verify == $category_thumbnail) {
            // Also link the page to the category for future reference
            update_post_meta($page->ID, '_linked_category', $category->term_id);
            echo "   ✅ FIXED (meta update)! Set featured image from category '{$category->name}' (ID: $category_thumbnail)\n";
            $fixed++;
        } else {
            echo "   ❌ FAILED! Could not set featured image for page '{$page->post_title}'\n";
        }
    } else {
        // Also link the page to the category for future reference
        update_post_meta($page->ID, '_linked_category', $category->term_id);
        echo "   ✅ FIXED! Set featured image from category '{$category->name}' (ID: $category_thumbnail)\n";
        $fixed++;
    }
}

// Clear cache
if (function_exists('wp_cache_flush')) {
    wp_cache_flush();
}

echo "\n=== SUMMARY ===\n";
echo "✅ Fixed: $fixed pages\n";
echo "✅ Already had images: $already_has_image pages\n";
echo "❌ No matching category: $no_matching_category pages\n\n";

// Final verification
echo "=== FINAL VERIFICATION ===\n";
$all_pages = get_posts([
    'post_type' => 'page',
    'numberposts' => -1
]);

$with_featured = 0;
$without_featured = 0;
$missing_pages = [];

foreach ($all_pages as $page) {
    $thumb = get_post_thumbnail_id($page->ID);
    if ($thumb) {
        $with_featured++;
    } else {
        $without_featured++;
        $missing_pages[] = $page->post_title;
    }
}

echo "\n📸 Pages with featured images: $with_featured\n";
echo "❌ Pages without featured images: $without_featured\n";

if (!empty($missing_pages)) {
    echo "\nPages still missing featured images:\n";
    foreach ($missing_pages as $title) {
        echo "   - $title\n";
    }
}

echo "\n🎯 Featured image fix complete!\n";
echo "🔗 Check your pages at: http://127.0.0.1:8801/wp-admin/edit.php?post_type=page\n";