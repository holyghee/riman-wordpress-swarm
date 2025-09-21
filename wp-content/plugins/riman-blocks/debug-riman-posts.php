<?php
// Debug RIMAN Seiten
require_once(dirname(__FILE__) . '/../../../wp-load.php');

echo "=== RIMAN Seiten Debug ===\n\n";

// Get all RIMAN Seiten
$posts = get_posts([
    'post_type' => 'riman_seiten',
    'posts_per_page' => -1,
    'post_status' => 'any',
    'orderby' => 'menu_order title',
    'order' => 'ASC'
]);

echo "Total RIMAN Seiten found: " . count($posts) . "\n\n";

// Group by seitentyp
$by_type = [];
foreach($posts as $post) {
    $seitentyp = get_post_meta($post->ID, 'seitentyp', true);
    if (empty($seitentyp)) $seitentyp = 'NONE';

    if (!isset($by_type[$seitentyp])) {
        $by_type[$seitentyp] = [];
    }
    $by_type[$seitentyp][] = $post;
}

// Show counts by type
echo "Posts by Seitentyp:\n";
foreach($by_type as $type => $posts) {
    echo "- $type: " . count($posts) . " posts\n";
}

echo "\n\n=== Top-Level RIMAN Seiten (Parent = 0) ===\n";
$top_level = get_posts([
    'post_type' => 'riman_seiten',
    'posts_per_page' => -1,
    'post_status' => 'publish',
    'post_parent' => 0,
    'orderby' => 'menu_order title',
    'order' => 'ASC'
]);

foreach($top_level as $post) {
    $seitentyp = get_post_meta($post->ID, 'seitentyp', true);
    $hero = get_post_meta($post->ID, 'hero_metadata', true);
    $has_image = false;
    if (is_array($hero) && !empty($hero['image_url'])) {
        $has_image = true;
    } elseif (has_post_thumbnail($post->ID)) {
        $has_image = true;
    }

    echo sprintf(
        "- [%s] %s (ID: %d, Seitentyp: %s, Image: %s)\n",
        $post->post_status,
        $post->post_title,
        $post->ID,
        $seitentyp ?: 'none',
        $has_image ? 'YES' : 'NO'
    );
}

echo "\n=== Hauptseite Type ===\n";
$hauptseiten = get_posts([
    'post_type' => 'riman_seiten',
    'posts_per_page' => -1,
    'post_status' => 'publish',
    'meta_key' => 'seitentyp',
    'meta_value' => 'Hauptseite'
]);

foreach($hauptseiten as $post) {
    echo sprintf(
        "- %s (ID: %d, Parent: %d)\n",
        $post->post_title,
        $post->ID,
        $post->post_parent
    );
}