<?php
/**
 * RIMAN WordPress Content-Image-SEO Import Script
 * 
 * Imports 121 content mappings with semantic image assignment and SEO optimization
 * Based on content-image-seo-mapping-enhanced.json
 * 
 * Requirements: WordPress 6.7+, PHP 8.0+
 * Environment: Docker Container (localhost:8801)
 */

// Load WordPress
require_once '/var/www/html/wp-config.php';
require_once '/var/www/html/wp-load.php';
require_once ABSPATH . 'wp-admin/includes/image.php';
require_once ABSPATH . 'wp-admin/includes/file.php';
require_once ABSPATH . 'wp-admin/includes/media.php';

class RIMANContentImporter {
    
    private $mapping_file = '/tmp/content-image-seo-mapping-enhanced.json';
    private $content_base = '/tmp/content';
    private $image_base = '/tmp/organized-images';
    private $organized_mappings = [];
    private $batch_size = 15;
    private $min_confidence = 0.4;
    private $log = [];
    private $stats = [
        'processed' => 0,
        'successful' => 0,
        'failed' => 0,
        'skipped' => 0,
        'images_imported' => 0,
        'categories_created' => 0
    ];
    
    public function __construct() {
        $this->log("=== RIMAN WordPress Import Script Started ===");
        $this->log("Mapping file: {$this->mapping_file}");
        $this->log("Content base: {$this->content_base}");
        $this->log("Image base: {$this->image_base}");
        $this->log("Minimum confidence: {$this->min_confidence}");
        
        // Load organized image mappings
        $this->loadOrganizedMappings();
    }
    
    public function run($batch_start = 0) {
        try {
            // Load mappings
            $mappings = $this->loadMappings();
            if (!$mappings) {
                throw new Exception("Failed to load mappings");
            }
            
            $total_mappings = count($mappings['mappings']);
            $this->log("Total mappings: {$total_mappings}");
            $this->log("Processing batch starting at: {$batch_start}");
            
            // Process batch
            $batch = array_slice($mappings['mappings'], $batch_start, $this->batch_size);
            $this->log("Batch size: " . count($batch));
            
            foreach ($batch as $index => $mapping) {
                $current_index = $batch_start + $index;
                $this->log("\n--- Processing [{$current_index}] {$mapping['content_title']} ---");
                
                $this->stats['processed']++;
                
                try {
                    // Skip low confidence mappings
                    if ($mapping['confidence_score'] < $this->min_confidence) {
                        $this->log("⚠️  Skipping: confidence {$mapping['confidence_score']} < {$this->min_confidence}");
                        $this->stats['skipped']++;
                        continue;
                    }
                    
                    // Process content
                    $result = $this->processMapping($mapping);
                    
                    if ($result['success']) {
                        $this->log("✅ Success: Post ID {$result['post_id']}");
                        $this->stats['successful']++;
                        
                        if ($result['image_imported']) {
                            $this->stats['images_imported']++;
                        }
                        
                        if ($result['category_created']) {
                            $this->stats['categories_created']++;
                        }
                    } else {
                        $this->log("❌ Failed: {$result['error']}");
                        $this->stats['failed']++;
                    }
                    
                } catch (Exception $e) {
                    $this->log("💥 Exception: " . $e->getMessage());
                    $this->stats['failed']++;
                }
            }
            
            // Progress tracking
            $this->logStats($batch_start, $total_mappings);
            
            // Check if more batches needed
            $next_batch = $batch_start + $this->batch_size;
            if ($next_batch < $total_mappings) {
                $this->log("\n🔄 Next batch command:");
                $this->log("php wordpress-import-script.php batch={$next_batch}");
            } else {
                $this->log("\n🎉 Import complete! All mappings processed.");
            }
            
        } catch (Exception $e) {
            $this->log("💥 Critical error: " . $e->getMessage());
            throw $e;
        }
    }
    
    private function loadMappings() {
        if (!file_exists($this->mapping_file)) {
            $this->log("❌ Mapping file not found: {$this->mapping_file}");
            return false;
        }
        
        $json = file_get_contents($this->mapping_file);
        $data = json_decode($json, true);
        
        if (!$data || !isset($data['mappings'])) {
            $this->log("❌ Invalid mapping file format");
            return false;
        }
        
        $this->log("✅ Loaded {$data['statistics']['content_analysis']['successfully_mapped']} mappings");
        return $data;
    }
    
    private function loadOrganizedMappings() {
        $wp_mappings_file = '/tmp/wordpress-unique-image-mappings.json';
        
        if (!file_exists($wp_mappings_file)) {
            $this->log("⚠️  WordPress mappings not found: {$wp_mappings_file}");
            return;
        }
        
        $json = file_get_contents($wp_mappings_file);
        $data = json_decode($json, true);
        
        if (!$data) {
            $this->log("⚠️  Invalid WordPress mappings format");
            return;
        }
        
        // Build reverse lookup from WordPress mappings
        $this->organized_mappings = [];
        
        // Add main categories
        foreach ($data['main_categories'] as $category => $filename) {
            $this->organized_mappings[$category] = $filename;
        }
        
        // Add subcategories
        foreach ($data['subcategories'] as $category => $subcats) {
            foreach ($subcats as $subcat => $filename) {
                $this->organized_mappings[$subcat] = $filename;
                $this->organized_mappings[$category . '-' . $subcat] = $filename;
            }
        }
        
        $this->log("✅ Loaded " . count($this->organized_mappings) . " organized image mappings");
    }
    
    private function findOrganizedImageName($image_data) {
        // Try to match by theme/description keywords
        $theme = strtolower($image_data['theme']);
        $description = strtolower($image_data['description']);
        
        // Look for direct category matches
        foreach ($this->organized_mappings as $key => $filename) {
            if (stripos($theme, $key) !== false || stripos($description, $key) !== false) {
                return $filename;
            }
        }
        
        // Try keyword matching for common themes
        $keywords = [
            'asbest' => 'asbestsanierung-schutzausruestung-fachpersonal.jpg',
            'grundwasser' => 'altlastensanierung-grundwasser-bodenschutz.jpg', 
            'recycling' => 'nachhaltiger-rueckbau-baustelle-recycling.jpg',
            'planung' => 'projektplanung-bim-visualisierung-schritt-2.jpg',
            'monitoring' => 'luftqualitaet-monitoring-echtzeitdaten-schritt-5.jpg',
            'mediation' => 'baumediation-konfliktloesung-projektmanagement.jpg',
            'sicherheit' => 'sicherheitsvorbereitung-schutzausruestung-schritt-3.jpg'
        ];
        
        foreach ($keywords as $keyword => $filename) {
            if (stripos($theme, $keyword) !== false || stripos($description, $keyword) !== false) {
                return $filename;
            }
        }
        
        return null;
    }
    
    private function processMapping($mapping) {
        $result = [
            'success' => false,
            'post_id' => null,
            'error' => null,
            'image_imported' => false,
            'category_created' => false
        ];
        
        try {
            // 1. Load and parse content file
            $content_path = $this->content_base . '/' . $mapping['content_file'];
            
            if (!file_exists($content_path)) {
                throw new Exception("Content file not found: {$content_path}");
            }
            
            $content = file_get_contents($content_path);
            $parsed = $this->parseMarkdown($content);
            
            // 2. Create/get category
            $category_id = $this->ensureCategory($mapping['content_category']);
            if ($category_id && !get_category($category_id)) {
                $result['category_created'] = true;
            }
            
            // 3. Import image to media library
            $attachment_id = $this->importImage($mapping['matched_image']);
            if ($attachment_id) {
                $result['image_imported'] = true;
            }
            
            // 4. Check for existing page first
            $slug = $mapping['seo_optimization']['url_slug'];
            $existing = get_page_by_path($slug, OBJECT, 'page');
            
            if (!$existing) {
                // Also check by title
                $existing_by_title = get_page_by_title($mapping['content_title'], OBJECT, 'page');
                if ($existing_by_title) {
                    $existing = $existing_by_title;
                }
            }
            
            $post_data = [
                'post_title' => $mapping['content_title'],
                'post_content' => $parsed['content'],
                'post_excerpt' => $mapping['seo_optimization']['meta_description'],
                'post_status' => 'publish',
                'post_type' => 'page',
                'post_name' => $slug
            ];
            
            if ($existing) {
                // Update existing page
                $post_data['ID'] = $existing->ID;
                $post_id = wp_update_post($post_data);
                $this->log("📝 Updated existing page: {$existing->ID}");
            } else {
                // Create new page
                $post_id = wp_insert_post($post_data);
                $this->log("✨ Created new page: {$post_id}");
            }
            
            if (is_wp_error($post_id)) {
                throw new Exception("WordPress error: " . $post_id->get_error_message());
            }
            
            $result['post_id'] = $post_id;
            
            // 5. Set featured image with verification
            if ($attachment_id) {
                $success = set_post_thumbnail($post_id, $attachment_id);
                
                // Fallback if set_post_thumbnail fails
                if (!$success) {
                    update_post_meta($post_id, '_thumbnail_id', $attachment_id);
                }
                
                // Verify it was set correctly
                $verify = get_post_thumbnail_id($post_id);
                if ($verify != $attachment_id) {
                    // Try again with direct meta update
                    update_post_meta($post_id, '_thumbnail_id', $attachment_id);
                    $this->log("🖼️  Featured image set via meta: {$attachment_id}");
                } else {
                    $this->log("🖼️  Featured image verified: {$attachment_id}");
                }
            }
            
            // 6. Add SEO meta data
            $this->setSEOMeta($post_id, $mapping['seo_optimization']);
            
            // 7. Add custom meta
            update_post_meta($post_id, '_riman_confidence_score', $mapping['confidence_score']);
            update_post_meta($post_id, '_riman_agent_id', $mapping['matched_image']['agent_id']);
            update_post_meta($post_id, '_riman_import_date', current_time('mysql'));
            update_post_meta($post_id, '_riman_keywords', json_encode($mapping['content_keywords']));
            
            $result['success'] = true;
            
        } catch (Exception $e) {
            $result['error'] = $e->getMessage();
        }
        
        return $result;
    }
    
    private function parseMarkdown($content) {
        // Basic markdown parsing
        $lines = explode("\n", $content);
        $parsed_content = '';
        $frontmatter = false;
        
        foreach ($lines as $line) {
            if (trim($line) === '---' && !$frontmatter) {
                $frontmatter = true;
                continue;
            }
            
            if (trim($line) === '---' && $frontmatter) {
                $frontmatter = false;
                continue;
            }
            
            if ($frontmatter) {
                continue; // Skip frontmatter
            }
            
            // Convert markdown to basic HTML
            $line = preg_replace('/^# (.+)/', '<h1>$1</h1>', $line);
            $line = preg_replace('/^## (.+)/', '<h2>$1</h2>', $line);
            $line = preg_replace('/^### (.+)/', '<h3>$1</h3>', $line);
            $line = preg_replace('/\*\*(.+?)\*\*/', '<strong>$1</strong>', $line);
            $line = preg_replace('/\*(.+?)\*/', '<em>$1</em>', $line);
            
            if (trim($line)) {
                $parsed_content .= "<p>{$line}</p>\n";
            }
        }
        
        return ['content' => $parsed_content];
    }
    
    private function ensureCategory($category_name) {
        // First check if category exists
        $category = get_term_by('slug', $category_name, 'category');
        
        if (!$category) {
            // Also check by name
            $display_name = ucfirst(str_replace('-', ' ', $category_name));
            $category = get_term_by('name', $display_name, 'category');
        }
        
        if (!$category) {
            // Create category only if it doesn't exist
            $result = wp_insert_category([
                'cat_name' => ucfirst(str_replace('-', ' ', $category_name)),
                'category_nicename' => $category_name,
                'category_description' => "RIMAN {$category_name} services"
            ]);
            
            if (is_wp_error($result)) {
                $this->log("⚠️  Category creation failed: " . $result->get_error_message());
                return null;
            }
            
            $this->log("📁 Created new category: {$category_name} (ID: {$result})");
            return $result;
        }
        
        $this->log("📁 Using existing category: {$category_name} (ID: {$category->term_id})");
        return $category->term_id;
    }
    
    private function importImage($image_data) {
        // Try to find organized filename first
        $organized_filename = $this->findOrganizedImageName($image_data);
        
        if ($organized_filename) {
            $image_filename = $organized_filename;
            $source_path = $this->image_base . '/' . $image_filename;
            $this->log("📋 Using organized image: {$image_filename}");
        } else {
            // Fallback to original filename
            $image_filename = basename($image_data['image_path']);
            $source_path = $this->image_base . '/' . $image_filename;
            $this->log("⚠️  Using original image: {$image_filename}");
        }
        
        if (!file_exists($source_path)) {
            $this->log("⚠️  Image not found: {$source_path}");
            return null;
        }
        
        // Check if already imported by agent_id or filename
        $existing = get_posts([
            'post_type' => 'attachment',
            'meta_query' => [[
                'key' => '_riman_agent_id',
                'value' => $image_data['agent_id'],
                'compare' => '='
            ]],
            'posts_per_page' => 1
        ]);
        
        if (!empty($existing)) {
            $this->log("🔄 Image already imported by agent_id: {$existing[0]->ID}");
            return $existing[0]->ID;
        }
        
        // Also check by filename
        $wp_filename = 'riman-' . $image_data['agent_id'] . '-' . $image_filename;
        $existing_by_name = get_posts([
            'post_type' => 'attachment',
            'name' => sanitize_title($wp_filename),
            'posts_per_page' => 1
        ]);
        
        if (!empty($existing_by_name)) {
            $this->log("🔄 Image already imported by filename: {$existing_by_name[0]->ID}");
            // Add the agent_id meta for future lookups
            update_post_meta($existing_by_name[0]->ID, '_riman_agent_id', $image_data['agent_id']);
            return $existing_by_name[0]->ID;
        }
        
        // Import to media library
        $upload_dir = wp_upload_dir();
        $wp_filename = 'riman-' . $image_data['agent_id'] . '-' . $image_filename;
        $target_path = $upload_dir['path'] . '/' . $wp_filename;
        
        if (!copy($source_path, $target_path)) {
            $this->log("❌ Failed to copy image: {$source_path}");
            return null;
        }
        
        // Create attachment
        $attachment_data = [
            'guid' => $upload_dir['url'] . '/' . $wp_filename,
            'post_mime_type' => mime_content_type($target_path),
            'post_title' => $image_data['theme'],
            'post_content' => $image_data['description'],
            'post_status' => 'inherit',
            'post_excerpt' => "Agent {$image_data['agent_id']} - {$image_data['quadrant']}"
        ];
        
        $attachment_id = wp_insert_attachment($attachment_data, $target_path);
        
        if (is_wp_error($attachment_id)) {
            $this->log("❌ Attachment creation failed: " . $attachment_id->get_error_message());
            return null;
        }
        
        // Generate metadata
        $attach_metadata = wp_generate_attachment_metadata($attachment_id, $target_path);
        wp_update_attachment_metadata($attachment_id, $attach_metadata);
        
        // Add custom meta
        update_post_meta($attachment_id, '_riman_agent_id', $image_data['agent_id']);
        update_post_meta($attachment_id, '_riman_quadrant', $image_data['quadrant']);
        update_post_meta($attachment_id, '_riman_theme', $image_data['theme']);
        
        $this->log("📷 Image imported: {$wp_filename} (ID: {$attachment_id})");
        return $attachment_id;
    }
    
    private function setSEOMeta($post_id, $seo_data) {
        // Yoast SEO meta
        update_post_meta($post_id, '_yoast_wpseo_title', $seo_data['meta_title']);
        update_post_meta($post_id, '_yoast_wpseo_metadesc', $seo_data['meta_description']);
        update_post_meta($post_id, '_yoast_wpseo_focuskw', implode(', ', $seo_data['focus_keywords']));
        
        // RankMath SEO meta (fallback)
        update_post_meta($post_id, 'rank_math_title', $seo_data['meta_title']);
        update_post_meta($post_id, 'rank_math_description', $seo_data['meta_description']);
        update_post_meta($post_id, 'rank_math_focus_keyword', $seo_data['focus_keywords'][0] ?? '');
        
        $this->log("🎯 SEO meta added");
    }
    
    private function logStats($batch_start, $total) {
        $processed_total = $batch_start + $this->stats['processed'];
        $progress = round(($processed_total / $total) * 100, 1);
        
        $this->log("\n=== BATCH STATISTICS ===");
        $this->log("Progress: {$processed_total}/{$total} ({$progress}%)");
        $this->log("Processed: {$this->stats['processed']}");
        $this->log("Successful: {$this->stats['successful']}");
        $this->log("Failed: {$this->stats['failed']}");
        $this->log("Skipped (low confidence): {$this->stats['skipped']}");
        $this->log("Images imported: {$this->stats['images_imported']}");
        $this->log("Categories created: {$this->stats['categories_created']}");
    }
    
    private function log($message) {
        $timestamp = date('Y-m-d H:i:s');
        $line = "[{$timestamp}] {$message}\n";
        echo $line;
        $this->log[] = $line;
        
        // Also log to file
        file_put_contents('/var/www/html/riman-import.log', $line, FILE_APPEND);
    }
    
    public function rollback($batch_start = 0, $batch_size = null) {
        $batch_size = $batch_size ?: $this->batch_size;
        
        $this->log("=== ROLLBACK MODE ===");
        $this->log("Removing posts imported in batch {$batch_start}-" . ($batch_start + $batch_size));
        
        // Find posts by import meta
        $posts = get_posts([
            'post_type' => ['post', 'page'],
            'numberposts' => $batch_size,
            'offset' => $batch_start,
            'meta_query' => [[
                'key' => '_riman_import_date',
                'compare' => 'EXISTS'
            ]]
        ]);
        
        foreach ($posts as $post) {
            // Get featured image
            $thumbnail_id = get_post_thumbnail_id($post->ID);
            
            // Delete post
            wp_delete_post($post->ID, true);
            
            // Delete image if exists
            if ($thumbnail_id) {
                wp_delete_attachment($thumbnail_id, true);
            }
            
            $this->log("🗑️  Deleted post: {$post->post_title} (ID: {$post->ID})");
        }
        
        $this->log("Rollback complete. Deleted " . count($posts) . " posts.");
    }
}

// CLI execution
if (php_sapi_name() === 'cli') {
    $importer = new RIMANContentImporter();
    
    // Parse arguments
    $batch_start = 0;
    $rollback = false;
    
    foreach ($argv as $arg) {
        if (strpos($arg, 'batch=') === 0) {
            $batch_start = intval(substr($arg, 6));
        }
        if ($arg === 'rollback') {
            $rollback = true;
        }
    }
    
    try {
        if ($rollback) {
            $importer->rollback($batch_start);
        } else {
            $importer->run($batch_start);
        }
    } catch (Exception $e) {
        echo "Fatal error: " . $e->getMessage() . "\n";
        exit(1);
    }
    
} else {
    // Web execution
    echo "<h1>RIMAN WordPress Import Script</h1>";
    echo "<p>This script must be run via CLI for security.</p>";
    echo "<pre>Usage: php wordpress-import-script.php batch=0</pre>";
}