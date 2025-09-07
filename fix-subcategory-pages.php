<?php
/**
 * Fix subcategory pages - Assign featured images to pages linked via Category Page Content Connector
 */

// Load WordPress
require_once '/var/www/html/wp-config.php';
require_once '/var/www/html/wp-load.php';

echo "=== Fixing Subcategory Pages Featured Images ===\n";

// Get all categories with thumbnail_id
$categories = get_categories(['hide_empty' => false]);
$fixed_pages = 0;

foreach ($categories as $category) {
    $thumbnail_id = get_term_meta($category->term_id, 'thumbnail_id', true);
    
    if ($thumbnail_id) {
        echo "Category: {$category->name} (ID: {$category->term_id}) has thumbnail: $thumbnail_id\n";
        
        // Try different meta keys that the Category Page Content Connector might use
        $possible_meta_keys = [
            '_linked_category',
            'category_id', 
            'connected_category',
            'category_page_id',
            'page_category_id'
        ];
        
        $pages_found = false;
        
        foreach ($possible_meta_keys as $meta_key) {
            $pages = get_posts([
                'post_type' => 'page',
                'posts_per_page' => -1,
                'meta_query' => [
                    [
                        'key' => $meta_key,
                        'value' => $category->term_id,
                        'compare' => '='
                    ]
                ]
            ]);
            
            if (!empty($pages)) {
                $pages_found = true;
                echo "  Found " . count($pages) . " pages with meta_key: $meta_key\n";
                
                foreach ($pages as $page) {
                    set_post_thumbnail($page->ID, $thumbnail_id);
                    echo "  📄 Set featured image for page: {$page->post_title} (ID: {$page->ID})\n";
                    $fixed_pages++;
                }
                break; // Found the right meta key, no need to try others
            }
        }
        
        if (!$pages_found) {
            // Try to find pages by category slug in URL/permalink
            $category_slug = $category->slug;
            $parent_pages = get_posts([
                'post_type' => 'page',
                'name' => str_replace('-', '', $category_slug), // Try without dashes
                'posts_per_page' => 1
            ]);
            
            if (empty($parent_pages)) {
                // Try with exact slug
                $parent_pages = get_posts([
                    'post_type' => 'page', 
                    'name' => $category_slug,
                    'posts_per_page' => 1
                ]);
            }
            
            if (!empty($parent_pages)) {
                $page = $parent_pages[0];
                set_post_thumbnail($page->ID, $thumbnail_id);
                echo "  📄 Found by slug - Set featured image for page: {$page->post_title} (ID: {$page->ID})\n";
                $fixed_pages++;
                $pages_found = true;
            }
        }
        
        if (!$pages_found) {
            echo "  ⚠️  No linked pages found for category: {$category->name}\n";
        }
    }
}

echo "\n=== Fix Complete ===\n";
echo "✅ Fixed featured images for $fixed_pages pages\n";
echo "🎯 All subcategory pages should now have correct featured images!\n";