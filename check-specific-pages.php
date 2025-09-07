<?php
/**
 * Check what images are actually assigned to specific pages
 */

// Load WordPress
require_once '/var/www/html/wp-config.php';
require_once '/var/www/html/wp-load.php';

echo "=== Checking Specific Page Image Assignments ===\n\n";

$test_pages = [
    'altlasten/altlasten-erkundung' => 'Should show: Site Investigation',
    'altlasten/altlasten-bodensanierung' => 'Should show: Soil Remediation', 
    'schadstoffe/schadstoffe-asbest' => 'Should show: Asbestos Removal',
    'sicherheitskoordination/sicherheitskoordination-arbeitsschutz' => 'Should show: Workplace Safety',
    'beratung/beratung-compliance' => 'Should show: Compliance Documentation'
];

foreach ($test_pages as $page_path => $expected) {
    echo "🔍 Testing: $page_path\n";
    echo "   Expected: $expected\n";
    
    $page = get_page_by_path($page_path);
    if (!$page) {
        echo "   ❌ Page not found!\n\n";
        continue;
    }
    
    $featured_image_id = get_post_thumbnail_id($page->ID);
    if (!$featured_image_id) {
        echo "   ❌ No featured image assigned!\n\n";
        continue;
    }
    
    $image = get_post($featured_image_id);
    $image_url = wp_get_attachment_url($featured_image_id);
    echo "   🖼️  Actual: {$image->post_title}\n";
    echo "   🔗 URL: $image_url\n";
    
    // Check if it's a Midjourney upscaled image
    $is_upscaled = get_post_meta($featured_image_id, '_midjourney_upscaled_image', true);
    echo "   📊 Upscaled: " . ($is_upscaled ? 'YES' : 'NO') . "\n";
    
    // Try to analyze if the image matches the expected content
    $title_lower = strtolower($image->post_title);
    $matches = false;
    
    if (stripos($page_path, 'erkundung') !== false) {
        $matches = (stripos($title_lower, 'erkundung') !== false || 
                   stripos($title_lower, 'investigation') !== false ||
                   stripos($title_lower, 'contaminated') !== false);
    } elseif (stripos($page_path, 'bodensanierung') !== false) {
        $matches = (stripos($title_lower, 'bodensanierung') !== false || 
                   stripos($title_lower, 'soil') !== false ||
                   stripos($title_lower, 'remediation') !== false);
    } elseif (stripos($page_path, 'asbest') !== false) {
        $matches = (stripos($title_lower, 'asbest') !== false || 
                   stripos($title_lower, 'asbestos') !== false);
    } elseif (stripos($page_path, 'arbeitsschutz') !== false) {
        $matches = (stripos($title_lower, 'arbeitsschutz') !== false || 
                   stripos($title_lower, 'workplace') !== false ||
                   stripos($title_lower, 'safety') !== false);
    } elseif (stripos($page_path, 'compliance') !== false) {
        $matches = (stripos($title_lower, 'compliance') !== false || 
                   stripos($title_lower, 'regulatory') !== false);
    }
    
    echo "   " . ($matches ? "✅ MATCHES" : "❌ WRONG IMAGE") . "\n\n";
}

echo "\n=== Available Upscaled Images by Category ===\n";

// Show what upscaled images we have for each category
$categories_to_check = [
    'erkundung' => ['investigation', 'contaminated', 'erkundung'],
    'bodensanierung' => ['soil', 'remediation', 'bodensanierung'],  
    'asbest' => ['asbestos', 'asbest'],
    'arbeitsschutz' => ['workplace', 'safety', 'arbeitsschutz'],
    'compliance' => ['compliance', 'regulatory']
];

$all_upscaled = get_posts([
    'post_type' => 'attachment',
    'posts_per_page' => -1,
    'meta_key' => '_midjourney_upscaled_image',
    'meta_value' => true
]);

foreach ($categories_to_check as $category => $keywords) {
    echo "\n📁 Available for $category:\n";
    $found_count = 0;
    
    foreach ($all_upscaled as $image) {
        $title_lower = strtolower($image->post_title);
        $match = false;
        
        foreach ($keywords as $keyword) {
            if (stripos($title_lower, $keyword) !== false) {
                $match = true;
                break;
            }
        }
        
        if ($match) {
            echo "   ✅ {$image->post_title} (ID: {$image->ID})\n";
            $found_count++;
        }
    }
    
    if ($found_count === 0) {
        echo "   ❌ No matching images found!\n";
    }
}