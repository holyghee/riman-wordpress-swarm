<?php
/**
 * Intelligent WordPress Image Assignment Solution
 * 
 * This script intelligently matches upscaled images with German titles to WordPress categories
 * using multiple matching strategies including semantic similarity, translation matching,
 * and partial string matching. It then assigns these images to both categories and their
 * linked pages via the Category Page Content Connector plugin.
 */

// WordPress laden
if (file_exists(__DIR__ . '/wp-load.php')) {
    require_once(__DIR__ . '/wp-load.php');
} else {
    require_once('/var/www/html/wp-load.php');
}

echo "=== INTELLIGENT IMAGE ASSIGNMENT SOLUTION ===\n\n";

class IntelligentImageMatcher {
    
    private $germanToEnglishTranslations = [
        'altlasten' => 'contaminated sites',
        'erkundung' => 'exploration',
        'investigation' => 'investigation',
        'bodensanierung' => 'soil remediation',
        'grundwassersanierung' => 'groundwater remediation',
        'monitoring' => 'monitoring',
        'überwachung' => 'monitoring',
        'sanierungsplanung' => 'remediation planning',
        'planung' => 'planning',
        'asbestsanierung' => 'asbestos removal',
        'asbest' => 'asbestos',
        'kmf' => 'mineral fiber',
        'kmf-sanierung' => 'mineral fiber remediation',
        'pak' => 'pah',
        'pak-sanierung' => 'pah remediation',
        'pcb' => 'pcb',
        'pcb-sanierung' => 'pcb remediation',
        'schwermetalle' => 'heavy metals',
        'schadstoffe' => 'pollutants',
        'rückbau' => 'deconstruction',
        'dokumentation' => 'documentation',
        'recycling' => 'recycling',
        'entsorgung' => 'disposal',
        'ausschreibung' => 'tendering',
        'durchführung' => 'execution',
        'sicherheitskoordination' => 'safety coordination',
        'sigeko' => 'safety coordination',
        'arbeitsschutz' => 'workplace safety',
        'gefährdungsbeurteilung' => 'risk assessment',
        'notfallmanagement' => 'emergency management',
        'beratung' => 'consulting',
        'projektberatung' => 'project consulting',
        'baumediation' => 'construction mediation',
        'gutachten' => 'expert assessment',
        'compliance' => 'compliance',
        'schulungen' => 'training'
    ];
    
    private $semanticKeywords = [
        'altlasten-erkundung' => ['contaminated', 'site', 'investigation', 'exploration', 'assessment', 'analysis'],
        'altlasten-bodensanierung' => ['soil', 'remediation', 'ground', 'earth', 'treatment', 'cleanup'],
        'altlasten-grundwassersanierung' => ['groundwater', 'water', 'treatment', 'filtration', 'remediation'],
        'altlasten-monitoring' => ['monitoring', 'surveillance', 'observation', 'data', 'analysis'],
        'altlasten-sanierungsplanung' => ['planning', 'engineering', 'design', 'strategy', 'remediation'],
        'schadstoffe-asbest' => ['asbestos', 'removal', 'hazardous', 'material', 'protective', 'safety'],
        'schadstoffe-kmf' => ['mineral', 'fiber', 'insulation', 'removal', 'protective'],
        'schadstoffe-pak' => ['pah', 'polycyclic', 'aromatic', 'hydrocarbons', 'tar', 'industrial'],
        'schadstoffe-pcb' => ['pcb', 'electrical', 'equipment', 'decontamination', 'polychlorinated'],
        'schadstoffe-schwermetalle' => ['heavy', 'metal', 'laboratory', 'testing', 'analysis'],
        'rueckbau-dokumentation' => ['documentation', 'digital', 'archiving', 'project', 'compliance'],
        'rueckbau-recycling' => ['recycling', 'material', 'facility', 'sustainable', 'resource'],
        'rueckbau-entsorgung' => ['disposal', 'waste', 'management', 'processing'],
        'rueckbau-ausschreibung' => ['tendering', 'procurement', 'bidding', 'contract'],
        'rueckbau-durchfuehrung' => ['execution', 'construction', 'implementation', 'work'],
        'sicherheitskoordination-arbeitsschutz' => ['workplace', 'safety', 'protection', 'equipment', 'standards'],
        'sicherheitskoordination-sigeko-planung' => ['safety', 'planning', 'coordination', 'blueprints', 'risk'],
        'sicherheitskoordination-sigeko-ausfuehrung' => ['safety', 'coordination', 'construction', 'inspection', 'execution'],
        'sicherheitskoordination-gefaehrdungsbeurteilung' => ['risk', 'assessment', 'hazard', 'evaluation', 'analysis'],
        'sicherheitskoordination-notfallmanagement' => ['emergency', 'response', 'crisis', 'management', 'incident'],
        'beratung-projektberatung' => ['consulting', 'project', 'advisory', 'expertise', 'business'],
        'beratung-baumediation' => ['mediation', 'conflict', 'resolution', 'dispute', 'collaboration'],
        'beratung-gutachten' => ['expert', 'assessment', 'technical', 'evaluation', 'witness'],
        'beratung-compliance' => ['compliance', 'regulatory', 'certification', 'standards', 'audit'],
        'beratung-schulungen' => ['training', 'education', 'workshop', 'classroom', 'learning']
    ];

    /**
     * Get all upscaled images from WordPress
     */
    public function getUpscaledImages() {
        $images = get_posts([
            'post_type' => 'attachment',
            'post_mime_type' => 'image',
            'posts_per_page' => -1,
            'meta_query' => [
                [
                    'key' => '_wp_attachment_metadata',
                    'compare' => 'EXISTS'
                ]
            ]
        ]);
        
        $upscaled_images = [];
        foreach ($images as $image) {
            if (stripos($image->post_title, 'upscaled') !== false) {
                $upscaled_images[] = $image;
            }
        }
        
        return $upscaled_images;
    }

    /**
     * Get all categories
     */
    public function getCategories() {
        return get_terms([
            'taxonomy' => 'category',
            'hide_empty' => false
        ]);
    }

    /**
     * Extract German keywords from image title
     */
    private function extractGermanKeywords($title) {
        // Remove "Upscaled" and common words
        $title = str_ireplace(['upscaled', '(upscaled)', 'upscaled)', '(', ')'], '', $title);
        $title = trim($title);
        
        // Split into words and clean
        $words = preg_split('/[\s\-_]+/', strtolower($title));
        $keywords = array_filter($words, function($word) {
            return strlen($word) > 2 && !in_array($word, ['der', 'die', 'das', 'und', 'oder', 'für', 'von', 'mit']);
        });
        
        return $keywords;
    }

    /**
     * Calculate similarity score between image and category
     */
    public function calculateSimilarity($image, $category) {
        $score = 0;
        $reasons = [];
        
        $image_title = strtolower($image->post_title);
        $category_slug = $category->slug;
        $category_name = strtolower($category->name);
        
        // 1. Direct German word matching (highest priority)
        $german_keywords = $this->extractGermanKeywords($image->post_title);
        foreach ($german_keywords as $keyword) {
            if (strpos($category_slug, $keyword) !== false || strpos($category_name, $keyword) !== false) {
                $score += 100;
                $reasons[] = "Direct German match: '$keyword'";
            }
        }
        
        // 2. Translation matching
        foreach ($this->germanToEnglishTranslations as $german => $english) {
            if (strpos($image_title, $german) !== false) {
                if (strpos($category_slug, str_replace(' ', '-', $german)) !== false ||
                    strpos($category_slug, str_replace(' ', '', $german)) !== false ||
                    strpos($category_name, $german) !== false) {
                    $score += 80;
                    $reasons[] = "Translation match: '$german' -> '$english'";
                }
            }
        }
        
        // 3. Semantic keyword matching
        if (isset($this->semanticKeywords[$category_slug])) {
            $semantic_keywords = $this->semanticKeywords[$category_slug];
            foreach ($semantic_keywords as $keyword) {
                if (strpos($image_title, strtolower($keyword)) !== false) {
                    $score += 60;
                    $reasons[] = "Semantic match: '$keyword'";
                }
            }
        }
        
        // 4. Partial string matching
        $category_parts = explode('-', $category_slug);
        foreach ($category_parts as $part) {
            if (strlen($part) > 3) {
                foreach ($german_keywords as $keyword) {
                    $similarity = 0;
                    similar_text($part, $keyword, $similarity);
                    if ($similarity > 70) {
                        $score += 40;
                        $reasons[] = "Partial match: '$part' ~= '$keyword' ($similarity% similar)";
                    }
                }
            }
        }
        
        // 5. Category hierarchy matching (parent-child relationships)
        $category_hierarchy_terms = explode('-', $category_slug);
        if (count($category_hierarchy_terms) >= 2) {
            $main_category = $category_hierarchy_terms[0];
            $sub_category = $category_hierarchy_terms[1];
            
            foreach ($german_keywords as $keyword) {
                if (strpos($keyword, $main_category) !== false || strpos($keyword, $sub_category) !== false) {
                    $score += 30;
                    $reasons[] = "Hierarchy match: '$keyword' matches category structure";
                }
            }
        }
        
        return [
            'score' => $score,
            'reasons' => $reasons
        ];
    }

    /**
     * Find best matching image for a category
     */
    public function findBestMatch($category, $images) {
        $best_match = null;
        $best_score = 0;
        $best_reasons = [];
        
        foreach ($images as $image) {
            $match_result = $this->calculateSimilarity($image, $category);
            
            if ($match_result['score'] > $best_score) {
                $best_score = $match_result['score'];
                $best_match = $image;
                $best_reasons = $match_result['reasons'];
            }
        }
        
        return [
            'image' => $best_match,
            'score' => $best_score,
            'reasons' => $best_reasons
        ];
    }

    /**
     * Assign image to category
     */
    public function assignImageToCategory($category_id, $image_id) {
        update_term_meta($category_id, '_thumbnail_id', $image_id);
    }

    /**
     * Assign image to linked page via Category Page Content Connector
     */
    public function assignImageToLinkedPage($category_id, $image_id) {
        // Check for _linked_category meta (Category Page Content Connector)
        $linked_page_id = get_term_meta($category_id, '_linked_page_id', true);
        
        if ($linked_page_id) {
            set_post_thumbnail($linked_page_id, $image_id);
            return $linked_page_id;
        }
        
        return false;
    }

    /**
     * Main execution function
     */
    public function execute() {
        echo "Fetching upscaled images...\n";
        $images = $this->getUpscaledImages();
        echo "Found " . count($images) . " upscaled images\n\n";
        
        echo "Fetching categories...\n";
        $categories = $this->getCategories();
        echo "Found " . count($categories) . " categories\n\n";
        
        $assignments = [];
        $total_assigned = 0;
        $total_linked_pages = 0;
        
        echo "=== INTELLIGENT MATCHING PROCESS ===\n\n";
        
        foreach ($categories as $category) {
            echo "Processing category: {$category->name} (slug: {$category->slug})\n";
            
            $match_result = $this->findBestMatch($category, $images);
            
            if ($match_result['image'] && $match_result['score'] > 25) { // Minimum threshold
                $image = $match_result['image'];
                
                echo "  ✅ MATCH FOUND: {$image->post_title} (Score: {$match_result['score']})\n";
                foreach ($match_result['reasons'] as $reason) {
                    echo "    - $reason\n";
                }
                
                // Assign to category
                $this->assignImageToCategory($category->term_id, $image->ID);
                $total_assigned++;
                
                // Assign to linked page if exists
                $linked_page_id = $this->assignImageToLinkedPage($category->term_id, $image->ID);
                if ($linked_page_id) {
                    $page = get_post($linked_page_id);
                    echo "  ✅ Also assigned to linked page: {$page->post_title}\n";
                    $total_linked_pages++;
                }
                
                $assignments[] = [
                    'category' => $category,
                    'image' => $image,
                    'score' => $match_result['score'],
                    'reasons' => $match_result['reasons'],
                    'linked_page_id' => $linked_page_id
                ];
                
                echo "\n";
            } else {
                echo "  ⚠️  No suitable match found (best score: {$match_result['score']})\n";
                if ($match_result['image']) {
                    echo "    Best candidate was: {$match_result['image']->post_title}\n";
                }
                echo "\n";
            }
        }
        
        echo "=== ASSIGNMENT SUMMARY ===\n";
        echo "Categories with assigned images: $total_assigned\n";
        echo "Linked pages with assigned images: $total_linked_pages\n\n";
        
        echo "=== DETAILED ASSIGNMENTS ===\n";
        foreach ($assignments as $assignment) {
            echo "Category: {$assignment['category']->name}\n";
            echo "Image: {$assignment['image']->post_title}\n";
            echo "Score: {$assignment['score']}\n";
            if ($assignment['linked_page_id']) {
                $page = get_post($assignment['linked_page_id']);
                echo "Linked Page: {$page->post_title}\n";
            }
            echo "Reasons:\n";
            foreach ($assignment['reasons'] as $reason) {
                echo "  - $reason\n";
            }
            echo "\n";
        }
        
        // Final verification
        echo "=== VERIFICATION ===\n";
        $this->verifyAssignments();
        
        return $assignments;
    }

    /**
     * Verify all assignments
     */
    private function verifyAssignments() {
        $categories = $this->getCategories();
        
        $categories_with_images = 0;
        $pages_with_images = 0;
        
        foreach ($categories as $category) {
            $category_image_id = get_term_meta($category->term_id, '_thumbnail_id', true);
            
            if ($category_image_id) {
                $categories_with_images++;
                $image = get_post($category_image_id);
                echo "✅ {$category->name} -> {$image->post_title}\n";
                
                // Check linked page
                $linked_page_id = get_term_meta($category->term_id, '_linked_page_id', true);
                if ($linked_page_id) {
                    $page_image_id = get_post_thumbnail_id($linked_page_id);
                    if ($page_image_id == $category_image_id) {
                        $page = get_post($linked_page_id);
                        echo "  ✅ Linked page '{$page->post_title}' has same image\n";
                        $pages_with_images++;
                    }
                }
            } else {
                echo "❌ {$category->name} -> No image assigned\n";
            }
        }
        
        echo "\nFinal count:\n";
        echo "Categories with images: $categories_with_images\n";
        echo "Linked pages with images: $pages_with_images\n";
    }
}

// Execute the intelligent image assignment
$matcher = new IntelligentImageMatcher();
$assignments = $matcher->execute();

echo "\n=== SCRIPT COMPLETED ===\n";
echo "Intelligent image assignment process finished successfully!\n";
echo "Total assignments made: " . count($assignments) . "\n";