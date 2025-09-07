<?php
/**
 * Assign semantically correct images based on swarm-config.yaml mappings
 * Uses the professionally matched images from the image-server
 */

// Load WordPress
require_once '/var/www/html/wp-config.php';
require_once '/var/www/html/wp-load.php';
require_once ABSPATH . 'wp-admin/includes/image.php';

echo "=== Assigning Semantically Correct Images from swarm-config.yaml ===\n\n";

// Define semantic mappings from swarm-config.yaml
$semantic_mappings = [
    // Main categories with correct image assignments
    'rueckbau' => 'nachhaltiger-rueckbau-baustelle-recycling.jpg',
    'altlasten' => 'altlastensanierung-grundwasser-bodenschutz.jpg', 
    'schadstoffe' => 'schadstoffsanierung-industrieanlage-riman-gmbh.jpg',
    'sicherheit' => 'sicherheitsvorbereitung-schutzausruestung-schritt-3.jpg',
    'beratung' => 'baumediation-konfliktloesung-projektmanagement.jpg',
    
    // Rückbau subcategories
    'rueckbau-planung' => 'rueckbau-planung-professional.png',
    'rueckbau-ausschreibung' => 'systematischer-gebaeuderueckbau-kreislaufwirtschaft.jpg',
    'rueckbau-durchfuehrung' => 'sanierung-durchfuehrung-fachgerecht-schritt-4.jpg',
    'rueckbau-entsorgung' => 'materialverarbeitung-entsorgung-vorschriften-schritt-6.jpg',
    'rueckbau-recycling' => 'nachhaltiger-rueckbau-baustelle-recycling.jpg',
    
    // Altlasten subcategories
    'altlasten-erkundung' => 'standortbewertung-umweltanalyse-schritt-1.jpg',
    'altlasten-sanierungsplanung' => 'projektplanung-bim-visualisierung-schritt-2.jpg',
    'altlasten-bodensanierung' => 'sanierung-durchfuehrung-fachgerecht-schritt-4.jpg',
    'altlasten-grundwassersanierung' => 'altlastensanierung-grundwasser-bodenschutz.jpg',
    'altlasten-monitoring' => 'qualitaetskontrolle-abnahme-pruefung-schritt-7.jpg',
    
    // Schadstoffe subcategories
    'schadstoffe-asbest' => 'schadstoffsanierung-industrieanlage-riman-gmbh.jpg',
    'schadstoffe-kmf' => 'schadstoff-analytik-professional.png',
    'schadstoffe-pak' => 'materialverarbeitung-entsorgung-vorschriften-schritt-6.jpg',
    'schadstoffe-pcb' => 'schadstoffsanierung-industrieanlage-riman-gmbh.jpg',
    'schadstoffe-schwermetalle' => 'materialverarbeitung-entsorgung-vorschriften-schritt-6.jpg',
    
    // Sicherheit subcategories
    'sicherheit-sigeko-planung' => 'sicherheitsvorbereitung-schutzausruestung-schritt-3.jpg',
    'sicherheit-sigeko-ausfuehrung' => 'sicherheit-koordination-professional.png',
    'sicherheit-arbeitsschutz' => 'sicherheitsvorbereitung-schutzausruestung-schritt-3.jpg',
    'sicherheit-gefaehrdungsbeurteilung' => 'sicherheit-koordination-professional.png',
    'sicherheit-notfallmanagement' => 'sicherheit-koordination-professional.png',
    
    // Beratung subcategories
    'beratung-baumediation' => 'baumediation-konfliktloesung-projektmanagement.jpg',
    'beratung-projektberatung' => 'projektplanung-bim-visualisierung-schritt-2.jpg',
    'beratung-gutachten' => 'qualitaetskontrolle-abnahme-pruefung-schritt-7.jpg',
    'beratung-schulungen' => 'sicherheit-koordination-professional.png',
    'beratung-compliance' => 'zertifizierung-dokumentation-abschluss-schritt-8.jpg'
];

// Clean attachment metadata first
echo "1. Cleaning up old attachments...\n";
$attachments = get_posts([
    'post_type' => 'attachment',
    'numberposts' => -1,
    'post_status' => 'any'
]);

foreach($attachments as $attachment) {
    wp_delete_attachment($attachment->ID, true);
}
echo "   ✅ Removed " . count($attachments) . " old attachments\n\n";

// Process each semantic mapping
$upload_dir = wp_upload_dir();
$successful = 0;
$failed = 0;

echo "2. Processing semantic image mappings...\n\n";

foreach ($semantic_mappings as $category_slug => $image_filename) {
    echo "📸 Processing: $category_slug → $image_filename\n";
    
    // Build source path (from image-server)
    $source_paths = [
        '/var/www/html/wp-content/uploads/image-server/' . $image_filename,
        '/var/www/html/wp-content/uploads/correct-images/' . $image_filename,
        '/tmp/correct-images/' . $image_filename,
        '/var/www/html/assets/images/' . $image_filename
    ];
    
    $source_path = null;
    foreach ($source_paths as $path) {
        if (file_exists($path)) {
            $source_path = $path;
            break;
        }
    }
    
    if (!$source_path) {
        echo "   ❌ Image file not found in any location\n\n";
        $failed++;
        continue;
    }
    
    // Get category
    $category = get_term_by('slug', $category_slug, 'category');
    if (!$category) {
        echo "   ❌ Category not found: $category_slug\n\n";
        $failed++;
        continue;
    }
    
    // Use original filename - no renaming!
    $file_info = pathinfo($image_filename);
    $wp_filename = $image_filename; // Keep original name
    $target_path = $upload_dir['path'] . '/' . $wp_filename;
    $target_url = $upload_dir['url'] . '/' . $wp_filename;
    
    // Only copy if not already exists
    if (!file_exists($target_path)) {
        if (!copy($source_path, $target_path)) {
            echo "   ❌ Failed to copy image\n\n";
            $failed++;
            continue;
        }
    }
    
    // Create attachment
    $attachment_data = [
        'guid' => $target_url,
        'post_mime_type' => mime_content_type($target_path),
        'post_title' => ucwords(str_replace('-', ' ', $category_slug)),
        'post_content' => 'Semantically correct image for ' . $category->name,
        'post_status' => 'inherit'
    ];
    
    $attachment_id = wp_insert_attachment($attachment_data, $target_path);
    
    if (is_wp_error($attachment_id)) {
        echo "   ❌ Failed to create attachment\n\n";
        $failed++;
        continue;
    }
    
    // Generate metadata
    $attach_metadata = wp_generate_attachment_metadata($attachment_id, $target_path);
    wp_update_attachment_metadata($attachment_id, $attach_metadata);
    
    // Mark as semantic image
    update_post_meta($attachment_id, '_semantic_image', true);
    update_post_meta($attachment_id, '_source_config', 'swarm-config.yaml');
    
    // Assign to category using robust method
    $thumbnail_set = update_term_meta($category->term_id, 'thumbnail_id', $attachment_id);
    
    // Verify it was set
    $verify = get_term_meta($category->term_id, 'thumbnail_id', true);
    if ($verify != $attachment_id) {
        update_term_meta($category->term_id, '_thumbnail_id', $attachment_id);
    }
    
    echo "   ✅ Category thumbnail assigned (ID: $attachment_id)\n";
    
    // Find and update linked pages
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
        // Use robust featured image assignment
        $success = set_post_thumbnail($page->ID, $attachment_id);
        
        // Fallback if set_post_thumbnail fails
        if (!$success) {
            update_post_meta($page->ID, '_thumbnail_id', $attachment_id);
        }
        
        // Verify
        $verify_page = get_post_thumbnail_id($page->ID);
        if ($verify_page != $attachment_id) {
            update_post_meta($page->ID, '_thumbnail_id', $attachment_id);
        }
        
        echo "   ✅ Featured image set for page: {$page->post_title}\n";
    }
    
    $successful++;
    echo "\n";
}

// Clear cache
if (function_exists('wp_cache_flush')) {
    wp_cache_flush();
}

echo "=== SUMMARY ===\n";
echo "✅ Successfully assigned: $successful semantic images\n";
echo "❌ Failed: $failed\n";
echo "📊 Total mappings: " . count($semantic_mappings) . "\n\n";

// Verify results
echo "=== VERIFICATION ===\n";
$categories = get_terms(['taxonomy' => 'category', 'hide_empty' => false]);
$with_images = 0;
$without_images = 0;

foreach ($categories as $cat) {
    if ($cat->slug === 'uncategorized') continue;
    $thumb = get_term_meta($cat->term_id, 'thumbnail_id', true);
    if ($thumb) {
        $with_images++;
    } else {
        $without_images++;
        echo "⚠️  Missing: {$cat->name} ({$cat->slug})\n";
    }
}

echo "\n📸 Categories with images: $with_images\n";
echo "❌ Categories without images: $without_images\n";
echo "\n🎯 Semantic image assignment complete!\n";
echo "🔗 Test at: http://127.0.0.1:8801/\n";