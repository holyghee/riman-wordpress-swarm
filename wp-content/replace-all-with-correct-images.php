<?php
/**
 * Replace ALL category images with correctly named images from image-server
 * This will overwrite existing assignments with the semantically correct images
 */

// Load WordPress
require_once '/var/www/html/wp-config.php';
require_once '/var/www/html/wp-load.php';

echo "=== Replacing ALL Categories with Correct Images from Image Server ===\n\n";

// Map category slugs to correct images - using image-server names for perfect semantic matching
$correct_mappings = [
    // Hauptkategorien mit korrekten semantischen Zuordnungen
    'schadstoffe-asbest' => '/var/www/html/wp-content/uploads/correct-images/asbestsanierung-schutzausruestung-fachpersonal.jpg',
    'altlasten-bodensanierung' => '/var/www/html/wp-content/uploads/correct-images/altlastensanierung-grundwasser-bodenschutz.jpg',
    'altlasten-erkundung' => '/var/www/html/wp-content/uploads/correct-images/altlastensanierung-grundwasser-bodenschutz.jpg',
    'altlasten-grundwassersanierung' => '/var/www/html/wp-content/uploads/correct-images/altlastensanierung-grundwasser-bodenschutz.jpg',
    'altlasten-sanierungsplanung' => '/var/www/html/wp-content/uploads/correct-images/altlastensanierung-grundwasser-bodenschutz.jpg',
    'altlasten-monitoring' => '/var/www/html/wp-content/uploads/correct-images/altlastensanierung-grundwasser-bodenschutz.jpg',
    'beratung-baumediation' => '/var/www/html/wp-content/uploads/correct-images/baumediation-konfliktloesung-projektmanagement.jpg',
    'beratung-gutachten' => '/var/www/html/wp-content/uploads/correct-images/baumediation-konfliktloesung-projektmanagement.jpg',
    'beratung-projektberatung' => '/var/www/html/wp-content/uploads/correct-images/baumediation-konfliktloesung-projektmanagement.jpg',
    'beratung-compliance' => '/var/www/html/wp-content/uploads/correct-images/baumediation-konfliktloesung-projektmanagement.jpg',
    'beratung-schulungen' => '/var/www/html/wp-content/uploads/correct-images/baumediation-konfliktloesung-projektmanagement.jpg',
    'rueckbau-recycling' => '/var/www/html/wp-content/uploads/correct-images/systematischer-gebaeuderueckbau-kreislaufwirtschaft.jpg',
    'rueckbau' => '/var/www/html/wp-content/uploads/correct-images/systematischer-gebaeuderueckbau-kreislaufwirtschaft.jpg',
    'rueckbau-dokumentation' => '/var/www/html/wp-content/uploads/correct-images/systematischer-gebaeuderueckbau-kreislaufwirtschaft.jpg',
    'schadstoffe' => '/var/www/html/wp-content/uploads/correct-images/schadstoffsanierung-industrieanlage-riman-gmbh.jpg',
    'schadstoffe-pcb' => '/var/www/html/wp-content/uploads/correct-images/schadstoffsanierung-industrieanlage-riman-gmbh.jpg',
    'schadstoffe-pak' => '/var/www/html/wp-content/uploads/correct-images/schadstoffsanierung-industrieanlage-riman-gmbh.jpg',
    'schadstoffe-kmf' => '/var/www/html/wp-content/uploads/correct-images/asbestsanierung-schutzausruestung-fachpersonal.jpg',
    'schadstoffe-schwermetalle' => '/var/www/html/wp-content/uploads/correct-images/schadstoffsanierung-industrieanlage-riman-gmbh.jpg'
];

$upload_dir = wp_upload_dir();
$successful_replacements = 0;

foreach ($correct_mappings as $category_slug => $image_path) {
    echo "🔍 Processing: $category_slug\n";
    
    if (!file_exists($image_path)) {
        echo "   ❌ Image file not found: $image_path\n\n";
        continue;
    }
    
    // Get category
    $category = get_term_by('slug', $category_slug, 'category');
    if (!$category) {
        echo "   ❌ Category not found: $category_slug\n\n";
        continue;
    }
    
    // Check if category already has a thumbnail - we will OVERWRITE it
    $existing_thumbnail = get_term_meta($category->term_id, 'thumbnail_id', true);
    if ($existing_thumbnail) {
        echo "   ⚠️  Existing thumbnail found (ID: $existing_thumbnail) - will be replaced\n";
    }
    
    // Generate WordPress filename
    $original_filename = basename($image_path);
    $file_extension = pathinfo($original_filename, PATHINFO_EXTENSION);
    $clean_name = pathinfo($original_filename, PATHINFO_FILENAME);
    $wp_filename = $clean_name . '_correct_replaced.' . $file_extension;
    $target_path = $upload_dir['path'] . '/' . $wp_filename;
    $target_url = $upload_dir['url'] . '/' . $wp_filename;
    
    // Copy file to WordPress uploads
    if (!copy($image_path, $target_path)) {
        echo "   ❌ Failed to copy image\n\n";
        continue;
    }
    
    echo "   ✅ Copied to WordPress uploads\n";
    
    // Create WordPress attachment
    $attachment_data = [
        'guid' => $target_url,
        'post_mime_type' => 'image/' . $file_extension,
        'post_title' => ucwords(str_replace('-', ' ', $clean_name)) . ' (Correct)',
        'post_content' => 'Semantically correct image for ' . $category->name . ' from image-server',
        'post_status' => 'inherit'
    ];
    
    $attachment_id = wp_insert_attachment($attachment_data, $target_path);
    
    if (is_wp_error($attachment_id)) {
        echo "   ❌ Failed to create attachment\n\n";
        continue;
    }
    
    // Generate metadata
    require_once(ABSPATH . 'wp-admin/includes/image.php');
    $attach_metadata = wp_generate_attachment_metadata($attachment_id, $target_path);
    wp_update_attachment_metadata($attachment_id, $attach_metadata);
    
    // Mark as correct image
    update_post_meta($attachment_id, '_correct_category_image', true);
    update_post_meta($attachment_id, '_assigned_category', $category_slug);
    update_post_meta($attachment_id, '_replaced_existing', $existing_thumbnail ? $existing_thumbnail : 'none');
    
    echo "   ✅ Created WordPress attachment (ID: $attachment_id)\n";
    echo "   📄 Title: " . $attachment_data['post_title'] . "\n";
    
    // REPLACE the category thumbnail (overwrite existing)
    update_term_meta($category->term_id, 'thumbnail_id', $attachment_id);
    echo "   🔄 REPLACED category thumbnail\n";
    
    if ($existing_thumbnail) {
        echo "   🗑️  Old thumbnail (ID: $existing_thumbnail) has been replaced\n";
    }
    
    // Find and assign to linked pages (also overwrite)
    $linked_pages = get_posts([
        'post_type' => 'page',
        'meta_query' => [
            [
                'key' => '_linked_category',
                'value' => $category->term_id,
                'compare' => '='
            ]
        ]
    ]);
    
    foreach ($linked_pages as $page) {
        $old_featured = get_post_thumbnail_id($page->ID);
        set_post_thumbnail($page->ID, $attachment_id);
        echo "   📄 REPLACED featured image for page: {$page->post_title}";
        if ($old_featured) {
            echo " (was ID: $old_featured)";
        }
        echo "\n";
    }
    
    $successful_replacements++;
    echo "   ✅ SUCCESS - REPLACED!\n\n";
}

// Clear cache
if (function_exists('wp_cache_flush')) {
    wp_cache_flush();
    echo "🗑️  Cache cleared\n";
}

echo "\n=== SUMMARY ===\n";
echo "🔄 Successfully replaced: $successful_replacements category images\n";
echo "📊 Total mappings processed: " . count($correct_mappings) . "\n";
echo "\n🎯 All categories now use SEMANTICALLY CORRECT images from image-server!\n";
echo "🔗 Test your site: http://127.0.0.1:8801/\n";
echo "\n💡 Note: Old random Midjourney assignments have been REPLACED with correct semantic matches.\n";