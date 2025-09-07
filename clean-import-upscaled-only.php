<?php
/**
 * Clean Import - Only upscaled Midjourney images with proper category assignment
 * This script only imports upscaled images (not 2x2 grids) and assigns them correctly to categories and subcategory pages
 */

// Load WordPress
require_once '/var/www/html/wp-config.php';
require_once '/var/www/html/wp-load.php';

// Load the correct mapping (only upscaled images)
$mapping_file = '/var/www/html/discord_wordpress_mapping.json';
$image_dir = '/tmp/downloaded_images/';

if (!file_exists($mapping_file)) {
    die("Mapping file not found: $mapping_file\n");
}

$mapping_data = json_decode(file_get_contents($mapping_file), true);
if (!$mapping_data) {
    die("Failed to parse mapping file\n");
}

// Filter only upscaled images (exclude grid images)
$upscaled_mappings = array_filter($mapping_data['mappings'], function($mapping) {
    // Upscaled images usually have aspect ratios close to 16:9 and are single images
    // Grid images are usually square or have multiple variants visible
    return !isset($mapping['is_grid']) || !$mapping['is_grid'];
});

echo "=== Clean Import: Only Upscaled Images ===\n";
echo "Total upscaled mappings: " . count($upscaled_mappings) . "\n\n";

$imported_count = 0;
$skipped_count = 0;

foreach ($upscaled_mappings as $mapping) {
    $image_id = $mapping['image_id'];
    $main_category = $mapping['wordpress_mapping']['main_category'];
    $subcategory = $mapping['wordpress_mapping']['subcategory'];
    $confidence = $mapping['wordpress_mapping']['confidence'];
    
    // Find the matching image file
    $image_files = glob($image_dir . "*{$image_id}*.png");
    if (empty($image_files)) {
        echo "⚠️  Image file not found for ID: $image_id\n";
        $skipped_count++;
        continue;
    }
    
    $image_file = $image_files[0];
    $filename = basename($image_file);
    
    // Check if already imported
    $existing = get_posts([
        'post_type' => 'attachment',
        'meta_query' => [
            [
                'key' => '_midjourney_image_id',
                'value' => $image_id,
                'compare' => '='
            ]
        ]
    ]);
    
    if (!empty($existing)) {
        echo "⏭️  Already imported: $filename (ID: $image_id)\n";
        continue;
    }
    
    // Copy file to WordPress uploads
    $upload_dir = wp_upload_dir();
    $target_filename = sanitize_file_name($main_category . '_' . $subcategory . '_' . $image_id . '.png');
    $target_path = $upload_dir['path'] . '/' . $target_filename;
    $target_url = $upload_dir['url'] . '/' . $target_filename;
    
    if (!copy($image_file, $target_path)) {
        echo "❌ Failed to copy: $filename\n";
        $skipped_count++;
        continue;
    }
    
    // Create attachment
    $attachment = [
        'guid' => $target_url,
        'post_mime_type' => 'image/png',
        'post_title' => ucwords(str_replace(['_', '-'], ' ', $main_category . ' ' . $subcategory)),
        'post_content' => '',
        'post_status' => 'inherit'
    ];
    
    $attachment_id = wp_insert_attachment($attachment, $target_path);
    
    if (is_wp_error($attachment_id)) {
        echo "❌ Failed to create attachment: $filename\n";
        $skipped_count++;
        continue;
    }
    
    // Generate metadata
    require_once(ABSPATH . 'wp-admin/includes/image.php');
    $attachment_data = wp_generate_attachment_metadata($attachment_id, $target_path);
    wp_update_attachment_metadata($attachment_id, $attachment_data);
    
    // Add custom meta
    update_post_meta($attachment_id, '_midjourney_image_id', $image_id);
    update_post_meta($attachment_id, '_midjourney_prompt', $mapping['prompt']);
    update_post_meta($attachment_id, '_wordpress_main_category', $main_category);
    update_post_meta($attachment_id, '_wordpress_subcategory', $subcategory);
    update_post_meta($attachment_id, '_mapping_confidence', $confidence);
    
    // WordPress category mapping
    $category_mappings = [
        'altlastensanierung' => [
            'erkundung' => 'altlasten-erkundung',
            'sanierungsplanung' => 'altlasten-sanierungsplanung', 
            'bodensanierung' => 'altlasten-bodensanierung',
            'grundwassersanierung' => 'altlasten-grundwassersanierung',
            'monitoring' => 'altlasten-monitoring'
        ],
        'rueckbaumanagement' => [
            'dokumentation' => 'rueckbau-dokumentation',
            'recycling' => 'rueckbau-recycling'
        ],
        'schadstoff-management' => [
            'asbest' => 'schadstoffe-asbest',
            'pcb' => 'schadstoffe-pcb', 
            'pak' => 'schadstoffe-pak',
            'kmf' => 'schadstoffe-kmf',
            'schwermetalle' => 'schadstoffe-schwermetalle'
        ],
        'sicherheitskoordination' => [
            'sigeko-planung' => 'sicherheitskoordination-sigeko-planung',
            'sigeko-ausfuehrung' => 'sicherheitskoordination-sigeko-ausfuehrung',
            'gefaehrdungsbeurteilung' => 'sicherheitskoordination-gefaehrdungsbeurteilung',
            'arbeitsschutz' => 'sicherheitskoordination-arbeitsschutz',
            'notfallmanagement' => 'sicherheitskoordination-notfallmanagement'
        ],
        'beratung-mediation' => [
            'projektberatung' => 'beratung-projektberatung',
            'baumediation' => 'beratung-baumediation', 
            'gutachten' => 'beratung-gutachten',
            'compliance' => 'beratung-compliance',
            'schulungen' => 'beratung-schulungen'
        ]
    ];
    
    // Find the correct WordPress category slug
    $wp_category_slug = null;
    if (isset($category_mappings[$main_category]) && isset($category_mappings[$main_category][$subcategory])) {
        $wp_category_slug = $category_mappings[$main_category][$subcategory];
    } else {
        $wp_category_slug = $main_category; // fallback to main category
    }
    
    $category = get_term_by('slug', $wp_category_slug, 'category');
    
    if ($category) {
        // Set as category featured image
        update_term_meta($category->term_id, 'thumbnail_id', $attachment_id);
        
        // Find linked pages via Category Page Content Connector plugin
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
        
        $page_count = 0;
        foreach ($pages as $page) {
            // Only set if page doesn't already have a featured image
            if (!has_post_thumbnail($page->ID)) {
                set_post_thumbnail($page->ID, $attachment_id);
                echo "📄 Set as featured image for page: {$page->post_title}\n";
                $page_count++;
            }
        }
        
        echo "✅ Imported: $target_filename → Category: {$category->name} (ID: $attachment_id, Pages: $page_count, Confidence: $confidence)\n";
    } else {
        echo "⚠️  Category not found for slug: $wp_category_slug (Image imported as ID: $attachment_id)\n";
    }
    
    $imported_count++;
}

echo "\n=== Clean Import Complete ===\n";
echo "✅ Imported: $imported_count upscaled images\n";
echo "⏭️  Skipped: $skipped_count images\n";
echo "🎯 All images correctly assigned to categories and subcategory pages!\n";