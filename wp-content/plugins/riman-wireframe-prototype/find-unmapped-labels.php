<?php
/**
 * Find All RIMAN Pages with Non-Standard Labels
 * Identifies pages that need label updates to powerful one-word labels
 */

// WordPress Environment laden
if (!defined('ABSPATH')) {
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

// Standard kraftvolle Labels
$powerful_labels = [
    'FACHGERECHT', 'NACHHALTIG', 'KOMPETENT', 'VERTRAUENSVOLL', 'PROFESSIONELL',
    'ZUVERLÄSSIG', 'SICHER', 'PRÄZISE', 'SYSTEMATISCH', 'METHODISCH',
    'WISSENSCHAFTLICH', 'GRÜNDLICH', 'ERFAHREN', 'BERATEND', 'STRATEGISCH',
    'EFFIZIENT', 'ANALYTISCH', 'KONSTRUKTIV', 'LÖSUNGSORIENTIERT', 'STRUKTURIERT',
    'VERSTÄNDNISVOLL', 'EINFÜHLSAM', 'AUSGLEICHEND'
];

echo "<h1>🔍 Label-Analyse</h1>";
echo "<p>Analysiere alle RIMAN-Seiten auf nicht-kraftvolle Labels...</p>";

// Alle RIMAN Posts holen
$posts = get_posts([
    'post_type' => 'riman_seiten',
    'posts_per_page' => -1,
    'post_status' => 'publish'
]);

$need_update = [];
$already_powerful = [];
$no_label = [];

foreach ($posts as $post) {
    $current_label = get_post_meta($post->ID, '_riman_hero_area_label', true);

    if (!$current_label) {
        $no_label[] = [
            'id' => $post->ID,
            'title' => $post->post_title,
            'slug' => $post->post_name
        ];
    } elseif (!in_array($current_label, $powerful_labels)) {
        $need_update[] = [
            'id' => $post->ID,
            'title' => $post->post_title,
            'slug' => $post->post_name,
            'current_label' => $current_label
        ];
    } else {
        $already_powerful[] = [
            'id' => $post->ID,
            'title' => $post->post_title,
            'current_label' => $current_label
        ];
    }
}

echo "<div style='margin: 20px 0; padding: 15px; background: #ffebee; border: 1px solid #f44336; border-radius: 8px;'>";
echo "<h3>🚨 Seiten mit nicht-kraftvollen Labels (" . count($need_update) . ")</h3>";
if (!empty($need_update)) {
    echo "<ul>";
    foreach ($need_update as $page) {
        echo "<li><strong>Post {$page['id']}:</strong> {$page['title']} (Slug: {$page['slug']}) - Label: '<strong>{$page['current_label']}</strong>'</li>";
    }
    echo "</ul>";
} else {
    echo "<p>✅ Alle Seiten haben bereits kraftvolle Labels!</p>";
}
echo "</div>";

echo "<div style='margin: 20px 0; padding: 15px; background: #fff3e0; border: 1px solid #ff9800; border-radius: 8px;'>";
echo "<h3>⚠️ Seiten ohne Labels (" . count($no_label) . ")</h3>";
if (!empty($no_label)) {
    echo "<ul>";
    foreach ($no_label as $page) {
        echo "<li><strong>Post {$page['id']}:</strong> {$page['title']} (Slug: {$page['slug']})</li>";
    }
    echo "</ul>";
} else {
    echo "<p>✅ Alle Seiten haben Labels!</p>";
}
echo "</div>";

echo "<div style='margin: 20px 0; padding: 15px; background: #e8f5e9; border: 1px solid #4caf50; border-radius: 8px;'>";
echo "<h3>✅ Seiten mit kraftvollen Labels (" . count($already_powerful) . ")</h3>";
echo "<p>Diese Seiten sind bereits korrekt:</p>";
echo "<ul>";
foreach ($already_powerful as $page) {
    echo "<li><strong>{$page['current_label']}</strong> - {$page['title']}</li>";
}
echo "</ul>";
echo "</div>";

// Zusammenfassung
echo "<div style='margin: 20px 0; padding: 15px; background: #f0f8ff; border: 1px solid #2196f3; border-radius: 8px;'>";
echo "<h3>📊 Zusammenfassung</h3>";
echo "<p><strong>Gesamt:</strong> " . count($posts) . " Seiten</p>";
echo "<p><strong>Kraftvolle Labels:</strong> " . count($already_powerful) . "</p>";
echo "<p><strong>Benötigen Update:</strong> " . count($need_update) . "</p>";
echo "<p><strong>Ohne Label:</strong> " . count($no_label) . "</p>";
echo "</div>";
?>