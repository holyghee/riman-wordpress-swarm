<?php
/**
 * Konvertiert enhanced mappings in das alte WordPress Setup Format
 */

// Lade Enhanced Mappings
$enhanced_file = 'content-image-seo-mapping-enhanced.json';
if (!file_exists($enhanced_file)) {
    die("Enhanced mapping file not found: $enhanced_file\n");
}

$enhanced_data = json_decode(file_get_contents($enhanced_file), true);
if (!$enhanced_data || !isset($enhanced_data['mappings'])) {
    die("Invalid enhanced mapping file format\n");
}

// Konvertiere ins alte Format
$wordpress_mappings = [
    "description" => "WordPress Image Mappings aus Enhanced Content-Image-SEO Zuordnungen",
    "image_base_path" => "/Users/holgerbrandt/dev/claude-code/projects/riman-wordpress-swarm/images",
    
    "main_categories" => [],
    "subcategories" => [
        "rueckbau" => [],
        "altlasten" => [],
        "schadstoffe" => [],
        "sicherheit" => [],
        "beratung" => []
    ],
    "page_mappings" => []
];

$category_main_images = [
    'rueckbau' => '',
    'altlasten' => '',
    'schadstoffe' => '',
    'sicherheit' => '',
    'beratung' => ''
];

echo "Converting " . count($enhanced_data['mappings']) . " enhanced mappings...\n";

foreach ($enhanced_data['mappings'] as $mapping) {
    $category = $mapping['content_category'];
    $image_filename = basename($mapping['matched_image']['image_path']);
    
    // Extract subcategory from content_file path
    $path_parts = explode('/', $mapping['content_file']);
    $subcategory = '';
    
    if (count($path_parts) >= 3) {
        // Format: services/category/subcategory/main.md
        $subcategory = $path_parts[2];
    } else {
        // Format: services/category/main.md - use category as subcategory
        $subcategory = $category;
    }
    
    // Set main category image (first one found)
    if (empty($category_main_images[$category])) {
        $category_main_images[$category] = $image_filename;
    }
    
    // Add to subcategories
    if (!isset($wordpress_mappings['subcategories'][$category][$subcategory])) {
        $wordpress_mappings['subcategories'][$category][$subcategory] = $image_filename;
    }
    
    // Add to page mappings for individual pages
    $page_slug = $mapping['seo_optimization']['url_slug'];
    $wordpress_mappings['page_mappings'][$page_slug] = $image_filename;
    
    echo "✅ $category/$subcategory -> $image_filename\n";
}

// Set main category images
$wordpress_mappings['main_categories'] = $category_main_images;

// Save converted mappings
$output_file = 'wordpress-enhanced-image-mappings.json';
file_put_contents($output_file, json_encode($wordpress_mappings, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));

echo "\n🎉 Conversion completed!\n";
echo "📊 Statistics:\n";
echo "   Main categories: " . count($wordpress_mappings['main_categories']) . "\n";
echo "   Total page mappings: " . count($wordpress_mappings['page_mappings']) . "\n";
echo "   Output file: $output_file\n";

foreach ($wordpress_mappings['subcategories'] as $cat => $subcats) {
    echo "   $cat subcategories: " . count($subcats) . "\n";
}

echo "\nNow you can run: ./setup-riman-with-images-fixed.sh\n";
?>