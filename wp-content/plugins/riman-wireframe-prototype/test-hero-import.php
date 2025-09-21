<?php
// Test script to verify hero metadata import
if (!defined('ABSPATH')) {
    // Load WordPress environment
    $wp_load_path = dirname(__FILE__);
    while ($wp_load_path && !file_exists($wp_load_path . '/wp-load.php')) {
        $parent = dirname($wp_load_path);
        if ($parent === $wp_load_path) break;
        $wp_load_path = $parent;
    }

    if (file_exists($wp_load_path . '/wp-load.php')) {
        require_once($wp_load_path . '/wp-load.php');
    } else {
        die("WordPress not found!");
    }
}

error_reporting(E_ALL);
ini_set('display_errors', 1);

// Include import functions
require_once(dirname(__FILE__) . '/run-md-import.php');

echo "<h1>Hero Metadata Import Test</h1>\n";

// Load hero data
$hero_data = riman_load_hero_metadata();
echo "<h2>Available Hero Data Keys:</h2>\n";
echo "<pre>" . print_r(array_keys($hero_data), true) . "</pre>\n";

// Check specific page
$target_key = "01_Sicherheits-Koordination_und_Mediation/index.md";
if (isset($hero_data[$target_key])) {
    echo "<h2>Hero Data for $target_key:</h2>\n";
    echo "<pre>" . print_r($hero_data[$target_key]['hero'], true) . "</pre>\n";

    // Find the corresponding WordPress post
    $post = get_page_by_path('sicherheits-koordination_und_mediation', OBJECT, 'riman_seiten');
    if ($post) {
        echo "<h2>WordPress Post Found: ID={$post->ID}, Title='{$post->post_title}'</h2>\n";

        // Check current meta values
        echo "<h3>Current Meta Values:</h3>\n";
        $meta_keys = ['_riman_hero_title', '_riman_hero_subtitle', '_riman_hero_area_label', '_riman_hero_icon'];
        foreach ($meta_keys as $key) {
            $value = get_post_meta($post->ID, $key, true);
            echo "$key: " . ($value ? "'$value'" : "NOT SET") . "<br>\n";
        }

        // Try to set hero metadata
        echo "<h3>Setting Hero Metadata...</h3>\n";
        riman_set_hero_metadata($post->ID, $target_key);

        // Check meta values after setting
        echo "<h3>Meta Values After Setting:</h3>\n";
        foreach ($meta_keys as $key) {
            $value = get_post_meta($post->ID, $key, true);
            echo "$key: " . ($value ? "'$value'" : "NOT SET") . "<br>\n";
        }
    } else {
        echo "<p>WordPress post not found for slug 'sicherheits-koordination_und_mediation'</p>\n";

        // Let's check what posts do exist
        echo "<h3>Available riman_seiten posts:</h3>\n";
        $posts = get_posts(array(
            'post_type' => 'riman_seiten',
            'posts_per_page' => -1,
            'post_status' => 'publish'
        ));
        foreach ($posts as $p) {
            echo "ID: {$p->ID}, Slug: '{$p->post_name}', Title: '{$p->post_title}'<br>\n";
        }
    }
} else {
    echo "<p>Hero data not found for key: $target_key</p>\n";

    // Show available keys that match the pattern
    echo "<h3>Keys containing 'Sicherheits':</h3>\n";
    foreach (array_keys($hero_data) as $key) {
        if (stripos($key, 'sicherheits') !== false) {
            echo "$key<br>\n";
        }
    }
}
?>