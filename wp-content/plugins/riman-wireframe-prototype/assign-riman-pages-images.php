<?php
/**
 * Assign featured images to riman_seiten from LLM mapping
 * Uses wordpress-enhanced-image-mappings-seo.llm.json → pages section
 */

// Try to find WordPress installation
// For local development servers, use relative path detection
if (defined('ABSPATH')) {
    // Already in WordPress context
    $wp_config_found = ABSPATH . 'wp-config.php';
} else {
    $wp_config_candidates = [
        dirname(__DIR__, 3) . '/wp-config.php',
        '/Applications/MAMP/htdocs/riman/wp-config.php',
        '/Applications/MAMP/htdocs/wp-config.php',
        '/opt/homebrew/var/www/wp-config.php',
        '/usr/local/var/www/wp-config.php',
        '/var/www/html/wp-config.php',
        dirname(__DIR__, 4) . '/wp-config.php',
        '/Users/' . get_current_user() . '/Sites/wp-config.php',
        // Auto-detect from current script location
        dirname(dirname(dirname(dirname(__FILE__)))) . '/wp-config.php',
    ];
}

if (!defined('ABSPATH')) {
    $wp_config_found = null;
    foreach ($wp_config_candidates as $candidate) {
        if (file_exists($candidate)) {
            $wp_config_found = $candidate;
            break;
        }
    }
}

if (!defined('ABSPATH')) {
    if (!$wp_config_found) {
        echo "❌ WordPress installation not found. Tried:\n";
        foreach ($wp_config_candidates as $c) {
            echo " - $c\n";
        }
        echo "\nThis script needs to run in a WordPress environment.\n";
        echo "Try accessing it via browser: http://127.0.0.1:8801/wp-content/plugins/riman-wireframe-prototype/assign-riman-pages-images.php?dry=1\n";
        exit(1);
    }
    require_once $wp_config_found;
}
require_once ABSPATH . 'wp-load.php';
require_once ABSPATH . 'wp-admin/includes/image.php';
require_once ABSPATH . 'wp-admin/includes/file.php';
require_once ABSPATH . 'wp-admin/includes/media.php';

// Output as plain text when called via HTTP
if (php_sapi_name() !== 'cli') {
    header('Content-Type: text/plain; charset=utf-8');
    if (!is_user_logged_in() || !current_user_can('manage_options')) {
        wp_die('Nur Administratoren dürfen dieses Script ausführen.');
    }
}

echo "=== Assign images to riman_seiten from LLM mapping ===\n\n";

// Flags: overwrite featured images if already present
$OVERWRITE = false; $DRY=false; $LIMIT=0; $FAST=false;
if (PHP_SAPI === 'cli' && !empty($_SERVER['argv'])) {
    foreach ($_SERVER['argv'] as $a){
        if ($a==='--overwrite') $OVERWRITE=true;
        if ($a==='--dry-run') $DRY=true;
        if ($a==='--fast') $FAST=true;
        if (strpos($a,'--limit=')===0) $LIMIT = max(0,intval(substr($a,8)));
    }
} else {
    $OVERWRITE = !empty($_GET['overwrite']);
    $DRY = !empty($_GET['dry']);
    $FAST = !empty($_GET['fast']);
    $LIMIT = isset($_GET['limit']) ? max(0, intval($_GET['limit'])) : 0;
}

// Optional: schneller Import ohne zusätzliche Zwischengrößen
if ($FAST){
    add_filter('intermediate_image_sizes_advanced', function($sizes){ return []; });
}

@set_time_limit(0);
@ini_set('memory_limit','512M');

// Locate mapping file with multiple fallbacks or explicit ?file= param
$json_param = isset($_GET['file']) ? trim($_GET['file']) : '';
if ($json_param) {
    $json_file = $json_param;
    if ($json_file[0] !== '/' && !preg_match('~^[A-Za-z]:\\\\~', $json_file)) {
        $json_file = ABSPATH . ltrim($json_file,'/');
    }
} else {
    $candidates = [
        ABSPATH . 'wp-content/uploads/wordpress-enhanced-image-mappings-seo-final.llm.json',
        ABSPATH . 'wordpress-enhanced-image-mappings-seo-final.llm.json',
        ABSPATH . 'wp-content/wordpress-enhanced-image-mappings-seo-final.llm.json',
        ABSPATH . 'wp-content/uploads/wordpress-enhanced-image-mappings-seo.llm.json',
        ABSPATH . 'wordpress-enhanced-image-mappings-seo.llm.json',
        ABSPATH . 'wp-content/wordpress-enhanced-image-mappings-seo.llm.json',
        __DIR__ . '/wordpress-enhanced-image-mappings-seo.llm.json',
    ];
    $json_file = '';
    foreach ($candidates as $c) { if (file_exists($c)) { $json_file = $c; break; } }
}
if (!$json_file || !file_exists($json_file)) {
    echo "❌ Mapping file not found. Tried:\n";
    echo " - ".ABSPATH."wordpress-enhanced-image-mappings-seo.llm.json\n";
    echo " - ".ABSPATH."wp-content/wordpress-enhanced-image-mappings-seo.llm.json\n";
    echo " - ".ABSPATH."wp-content/uploads/wordpress-enhanced-image-mappings-seo.llm.json\n";
    echo " - ".__DIR__."/wordpress-enhanced-image-mappings-seo.llm.json\n\n";
    echo "Tipp: Lege die Datei neben dieses Script oder übergib ?file=wp-content/uploads/wordpress-enhanced-image-mappings-seo.llm.json\n";
    exit(1);
}

$map = json_decode(file_get_contents($json_file), true);
// Support both 'pages' and 'site_pages' structure
$pages_data = null;
if (!empty($map['site_pages'])) {
    $pages_data = $map['site_pages'];
} elseif (!empty($map['pages'])) {
    $pages_data = $map['pages'];
}
if (!is_array($map) || empty($pages_data)) {
    die("❌ No pages or site_pages mapping in LLM file.\n");
}

$base = rtrim($map['image_base_path'] ?? '', '/');

$ok = 0; $skipped = 0; $fail = 0;
foreach ($pages_data as $path_key => $data) {
    if (empty($data['image'])) { $skipped++; continue; }
    $image_file = $data['image'];
    $bn = basename($image_file);
    $src_variants = [
        $base . '/' . $bn,
        ABSPATH . 'images/' . $bn,
        ABSPATH . 'images/*' . $bn,
        ABSPATH . 'wp-content/uploads/correct-images/' . $bn,
        ABSPATH . 'wp-content/uploads/image-server/' . $bn,
        ABSPATH . 'assets/images/' . $bn,
    ];
    $src = '';
    foreach ($src_variants as $p) {
        if (strpos($p, '*') !== false) {
            $hits = glob($p);
            if ($hits && file_exists($hits[0])) { $src = $hits[0]; break; }
        } elseif (file_exists($p)) { $src = $p; break; }
    }
    if (!$src) { echo "⚠️  Missing file for {$path_key}: {$bn}\n"; $fail++; continue; }

    // Find post by MD site path meta
    $posts = get_posts([
        'post_type' => 'riman_seiten',
        'numberposts' => 1,
        'post_status' => ['publish','draft','pending'],
        'meta_key' => '_riman_mdsite_path',
        'meta_value' => $path_key,
        'fields' => 'ids'
    ]);
    if (!$posts) {
        echo "ℹ️  Post not found for path: {$path_key}\n";
        $skipped++;
        continue;
    }
    $pid = (int)$posts[0];
    if (has_post_thumbnail($pid) && !$OVERWRITE) { $skipped++; if($LIMIT && ($ok+$skipped+$fail)>=$LIMIT) break; continue; }

    if ($DRY){ echo "🔎 Would set image for #{$pid} ← {$bn} ({$path_key})\n"; $ok++; if($LIMIT && ($ok+$skipped+$fail)>=$LIMIT) break; continue; }

    // Sideload and set featured image
    $tmp = wp_tempnam(basename($src));
    if (!$tmp || !copy($src, $tmp)) { echo "❌ Temp copy failed for {$bn}\n"; $fail++; continue; }
    $file = [ 'name' => basename($src), 'tmp_name' => $tmp ];
    $handled = wp_handle_sideload($file, [ 'test_form' => false ]);
    if (!empty($handled['error'])) { echo "❌ Sideload failed for {$bn}: {$handled['error']}\n"; $fail++; continue; }
    $attachment = [
        'post_mime_type' => $handled['type'],
        'post_title' => !empty($data['title']) ? $data['title'] : sanitize_file_name(pathinfo($handled['file'], PATHINFO_FILENAME)),
        'post_content' => $data['description'] ?? '',
        'post_excerpt' => $data['caption'] ?? '', // WordPress uses post_excerpt for image captions
        'post_status' => 'inherit'
    ];
    $att_id = wp_insert_attachment($attachment, $handled['file'], $pid);
    if (is_wp_error($att_id) || !$att_id) { echo "❌ Attachment insert failed for {$bn}\n"; $fail++; continue; }
    $meta = wp_generate_attachment_metadata($att_id, $handled['file']);
    wp_update_attachment_metadata($att_id, $meta);

    // Set all image metadata
    if (!empty($data['alt'])) update_post_meta($att_id, '_wp_attachment_image_alt', $data['alt']);
    if (!empty($data['caption'])) update_post_meta($att_id, '_wp_attachment_caption', $data['caption']);
    if (!empty($data['title'])) update_post_meta($att_id, '_wp_attachment_title', $data['title']);
    set_post_thumbnail($pid, $att_id);
    echo "✅ #{$pid} ← {$bn} ({$path_key})\n";
    $ok++;
    if ($LIMIT && ($ok+$skipped+$fail) >= $LIMIT) break;
}

echo "\nSummary: set {$ok}, skipped {$skipped}, failed {$fail}\n";
echo "Done.\n";
