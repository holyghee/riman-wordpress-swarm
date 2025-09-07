<?php
/**
 * Fix ALL image assignments using proper content-based matching
 * This solves the core problem: images were assigned randomly instead of by content
 */

// Load WordPress
require_once '/var/www/html/wp-config.php';
require_once '/var/www/html/wp-load.php';

echo "=== Fixing ALL Image Assignments with Content-Based Matching ===\n\n";

// Define the correct category -> prompt mapping
$category_to_prompt = [
    'sicherheitskoordination-notfallmanagement' => 'Emergency response and crisis management',
    'sicherheitskoordination-arbeitsschutz' => 'Workplace safety and occupational health',
    'altlasten-erkundung' => 'contaminated site investigation',
    'altlasten-planung' => 'remediation planning and engineering',
    'altlasten-bodensanierung' => 'soil remediation',
    'altlasten-grundwassersanierung' => 'Groundwater remediation treatment',
    'altlasten-langzeitmonitoring' => 'Environmental monitoring station',
    'altlasten-dokumentation' => 'project documentation and digital archiving',
    'altlasten-verwertung' => 'Construction material recycling',
    'schadstoffe-asbest' => 'asbestos removal and remediation',
    'schadstoffe-pcb' => 'PCB contamination remediation',
    'schadstoffe-pak' => 'PAH polycyclic aromatic hydrocarbons',
    'schadstoffe-kuenstliche-mineralfasern' => 'Mineral fiber insulation removal',
    'schadstoffe-schwermetalle' => 'Heavy metal contamination analysis',
    'sicherheitskoordination-planung' => 'Construction safety planning office',
    'sicherheitskoordination-ausfuehrung' => 'Safety coordination during construction execution',
    'sicherheitskoordination-risikobewertung' => 'Risk assessment and hazard evaluation',
    'beratung-projektberatung' => 'construction project consulting office',
    'beratung-mediation' => 'Construction mediation and conflict resolution',
    'beratung-sachverstaendige' => 'expert assessment and technical evaluation',
    'beratung-compliance' => 'Regulatory compliance and certification',
    'beratung-schulungen' => 'safety and environmental training'
];

// Get all upscaled images from WordPress
$all_upscaled = get_posts([
    'post_type' => 'attachment',
    'posts_per_page' => -1,
    'meta_key' => '_midjourney_upscaled_image',
    'meta_value' => true
]);

echo "Found " . count($all_upscaled) . " upscaled images in WordPress\n\n";

// Create content-based mappings
$successful_assignments = 0;
$skipped_assignments = 0;

foreach ($category_to_prompt as $category_slug => $expected_prompt) {
    echo "🔍 Processing category: $category_slug\n";
    echo "   Looking for images matching: '$expected_prompt'\n";
    
    // Find the best matching image
    $best_match = null;
    $best_score = 0;
    
    foreach ($all_upscaled as $image) {
        $title = strtolower($image->post_title);
        $content = strtolower($image->post_content);
        $search_text = $title . ' ' . $content;
        
        // Calculate match score based on keywords
        $score = 0;
        $keywords = explode(' ', strtolower($expected_prompt));
        
        foreach ($keywords as $keyword) {
            if (strlen($keyword) > 3) { // Only meaningful keywords
                if (stripos($search_text, $keyword) !== false) {
                    $score++;
                }
            }
        }
        
        if ($score > $best_score) {
            $best_score = $score;
            $best_match = $image;
        }
    }
    
    if ($best_match && $best_score > 0) {
        echo "   ✅ Found match: {$best_match->post_title} (score: $best_score)\n";
        
        // Get the category
        $category = get_term_by('slug', $category_slug, 'category');
        if ($category) {
            // Set as category thumbnail
            update_term_meta($category->term_id, 'thumbnail_id', $best_match->ID);
            
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
                set_post_thumbnail($page->ID, $best_match->ID);
                echo "      📄 Updated page: {$page->post_title}\n";
            }
            
            $successful_assignments++;
        } else {
            echo "   ❌ Category not found: $category_slug\n";
            $skipped_assignments++;
        }
    } else {
        echo "   ❌ No matching image found for: $expected_prompt\n";
        $skipped_assignments++;
    }
    
    echo "\n";
}

// Clear cache
if (function_exists('wp_cache_flush')) {
    wp_cache_flush();
    echo "🗑️  Cache cleared\n";
}

echo "\n=== SUMMARY ===\n";
echo "✅ Successful assignments: $successful_assignments\n";
echo "❌ Skipped assignments: $skipped_assignments\n";
echo "📊 Total categories processed: " . count($category_to_prompt) . "\n";
echo "\n🎯 Content-based image assignment complete!\n";
echo "🔗 Check your pages now: http://127.0.0.1:8801/altlasten/altlasten-erkundung/\n";