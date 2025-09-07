<?php
/**
 * Import real content from Markdown files
 * Reads MD files from riman-content repository and creates proper WordPress posts/pages
 */

// Load WordPress
require_once '/var/www/html/wp-config.php';
require_once '/var/www/html/wp-load.php';

echo "=== Importing Real Content from Markdown Files ===\n\n";

// Content directory path
$content_base = '/tmp/riman-content/';

// First, copy content files to container if not already there
if (!file_exists($content_base)) {
    echo "❌ Content directory not found. Please copy content files to container first.\n";
    echo "   Run: docker cp /path/to/riman-content riman-wordpress-swarm-wordpress-1:/tmp/\n";
    exit(1);
}

/**
 * Parse markdown file to extract metadata and content
 */
function parse_markdown_file($filepath) {
    $content = file_get_contents($filepath);
    if (!$content) return false;
    
    // Check for frontmatter
    if (strpos($content, '---') === 0) {
        $parts = explode('---', $content, 3);
        if (count($parts) >= 3) {
            $frontmatter = trim($parts[1]);
            $body = trim($parts[2]);
            
            // Parse YAML frontmatter
            $metadata = [];
            foreach (explode("\n", $frontmatter) as $line) {
                if (strpos($line, ':') !== false) {
                    list($key, $value) = explode(':', $line, 2);
                    $metadata[trim($key)] = trim($value, " \t\n\r\0\x0B\"'");
                }
            }
            
            return [
                'metadata' => $metadata,
                'content' => $body
            ];
        }
    }
    
    // No frontmatter, return content as-is
    return [
        'metadata' => [],
        'content' => $content
    ];
}

/**
 * Convert Markdown to HTML (basic conversion)
 */
function markdown_to_html($markdown) {
    // Basic markdown conversion
    $html = $markdown;
    
    // Headers
    $html = preg_replace('/^### (.+)$/m', '<h3>$1</h3>', $html);
    $html = preg_replace('/^## (.+)$/m', '<h2>$1</h2>', $html);
    $html = preg_replace('/^# (.+)$/m', '<h1>$1</h1>', $html);
    
    // Bold and italic
    $html = preg_replace('/\*\*(.+?)\*\*/', '<strong>$1</strong>', $html);
    $html = preg_replace('/\*(.+?)\*/', '<em>$1</em>', $html);
    
    // Lists
    $html = preg_replace('/^- (.+)$/m', '<li>$1</li>', $html);
    $html = preg_replace('/(<li>.*<\/li>)/s', '<ul>$1</ul>', $html);
    
    // Paragraphs
    $html = preg_replace('/\n\n/', '</p><p>', $html);
    $html = '<p>' . $html . '</p>';
    
    // Clean up
    $html = preg_replace('/<p><\/p>/', '', $html);
    $html = preg_replace('/<p>(<h[1-6]>)/', '$1', $html);
    $html = preg_replace('/(<\/h[1-6]>)<\/p>/', '$1', $html);
    
    return $html;
}

/**
 * Import content from a specific directory
 */
function import_content_directory($dir_path, $post_type = 'page', $parent_id = 0) {
    $imported = 0;
    $failed = 0;
    
    if (!is_dir($dir_path)) {
        echo "   ❌ Directory not found: $dir_path\n";
        return [$imported, $failed];
    }
    
    $files = glob($dir_path . '/*.md');
    
    foreach ($files as $file) {
        $filename = basename($file, '.md');
        echo "   📄 Processing: $filename.md\n";
        
        $parsed = parse_markdown_file($file);
        if (!$parsed) {
            echo "      ❌ Failed to parse file\n";
            $failed++;
            continue;
        }
        
        // Prepare post data
        $post_data = [
            'post_type' => $post_type,
            'post_status' => 'publish',
            'post_parent' => $parent_id,
            'post_content' => markdown_to_html($parsed['content'])
        ];
        
        // Set title
        if (isset($parsed['metadata']['title'])) {
            $post_data['post_title'] = $parsed['metadata']['title'];
        } else {
            // Generate title from filename
            $post_data['post_title'] = ucwords(str_replace(['-', '_'], ' ', $filename));
        }
        
        // Set slug
        if (isset($parsed['metadata']['slug'])) {
            $post_data['post_name'] = $parsed['metadata']['slug'];
        } else {
            $post_data['post_name'] = $filename;
        }
        
        // Check if page/post already exists
        $existing = get_page_by_path($post_data['post_name'], OBJECT, $post_type);
        
        if ($existing) {
            // Update existing
            $post_data['ID'] = $existing->ID;
            $post_id = wp_update_post($post_data);
            echo "      ✅ Updated existing $post_type (ID: $post_id)\n";
        } else {
            // Create new
            $post_id = wp_insert_post($post_data);
            if ($post_id && !is_wp_error($post_id)) {
                echo "      ✅ Created new $post_type (ID: $post_id)\n";
            } else {
                echo "      ❌ Failed to create $post_type\n";
                $failed++;
                continue;
            }
        }
        
        // Add metadata
        if (isset($parsed['metadata']['category'])) {
            $category = get_term_by('slug', $parsed['metadata']['category'], 'category');
            if ($category) {
                wp_set_post_categories($post_id, [$category->term_id]);
                update_post_meta($post_id, '_linked_category', $category->term_id);
                echo "      📁 Linked to category: {$category->name}\n";
            }
        }
        
        // Set as draft if marked as placeholder
        if (isset($parsed['metadata']['status']) && $parsed['metadata']['status'] === 'draft') {
            wp_update_post(['ID' => $post_id, 'post_status' => 'draft']);
            echo "      📝 Set as draft (placeholder content)\n";
        }
        
        $imported++;
    }
    
    // Process subdirectories
    $subdirs = glob($dir_path . '/*', GLOB_ONLYDIR);
    foreach ($subdirs as $subdir) {
        $dirname = basename($subdir);
        echo "\n   📁 Processing subdirectory: $dirname\n";
        
        // Create parent page for subdirectory if needed
        $parent_slug = basename($dir_path) . '-' . $dirname;
        $parent_page = get_page_by_path($parent_slug, OBJECT, 'page');
        $sub_parent_id = $parent_page ? $parent_page->ID : $parent_id;
        
        list($sub_imported, $sub_failed) = import_content_directory($subdir, $post_type, $sub_parent_id);
        $imported += $sub_imported;
        $failed += $sub_failed;
    }
    
    return [$imported, $failed];
}

// Main import process
$total_imported = 0;
$total_failed = 0;

// Import services
echo "1. Importing Services Content...\n";
$services_dir = $content_base . 'riman-website-rebuild/content/services';
if (is_dir($services_dir)) {
    list($imported, $failed) = import_content_directory($services_dir, 'page');
    $total_imported += $imported;
    $total_failed += $failed;
    echo "   ✅ Imported: $imported | ❌ Failed: $failed\n\n";
} else {
    echo "   ❌ Services directory not found\n\n";
}

// Import pages
echo "2. Importing General Pages...\n";
$pages_dir = $content_base . 'riman-website-rebuild/content/pages';
if (is_dir($pages_dir)) {
    list($imported, $failed) = import_content_directory($pages_dir, 'page');
    $total_imported += $imported;
    $total_failed += $failed;
    echo "   ✅ Imported: $imported | ❌ Failed: $failed\n\n";
} else {
    echo "   ❌ Pages directory not found\n\n";
}

// Import company pages
echo "3. Importing Company Pages...\n";
$company_dir = $content_base . 'riman-website-rebuild/content/company';
if (is_dir($company_dir)) {
    list($imported, $failed) = import_content_directory($company_dir, 'page');
    $total_imported += $imported;
    $total_failed += $failed;
    echo "   ✅ Imported: $imported | ❌ Failed: $failed\n\n";
} else {
    echo "   ❌ Company directory not found\n\n";
}

// Clear cache
if (function_exists('wp_cache_flush')) {
    wp_cache_flush();
}

echo "=== IMPORT COMPLETE ===\n";
echo "✅ Total imported: $total_imported\n";
echo "❌ Total failed: $total_failed\n\n";

// List all pages to verify
echo "=== VERIFICATION ===\n";
$all_pages = get_posts([
    'post_type' => 'page',
    'numberposts' => -1,
    'post_status' => ['publish', 'draft']
]);

$published = 0;
$drafts = 0;

foreach ($all_pages as $page) {
    if ($page->post_status === 'publish') {
        $published++;
    } else {
        $drafts++;
    }
}

echo "📄 Published pages: $published\n";
echo "📝 Draft pages (placeholders): $drafts\n";
echo "\n🎯 Real content import complete!\n";
echo "🔗 Check your site at: http://127.0.0.1:8801/\n";