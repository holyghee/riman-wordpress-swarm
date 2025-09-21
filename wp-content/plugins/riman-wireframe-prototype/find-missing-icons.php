<?php
/**
 * Find All RIMAN Pages without Icons
 * Identifies pages that are missing hero icons
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

echo "<h1>🔍 Icon-Analyse</h1>";
echo "<p>Analysiere alle RIMAN-Seiten auf fehlende Icons...</p>";

// Alle RIMAN Posts holen
$posts = get_posts([
    'post_type' => 'riman_seiten',
    'posts_per_page' => -1,
    'post_status' => 'publish'
]);

$missing_icons = [];
$has_icons = [];

foreach ($posts as $post) {
    $current_icon = get_post_meta($post->ID, '_riman_hero_icon', true);
    $current_label = get_post_meta($post->ID, '_riman_hero_area_label', true);

    if (!$current_icon) {
        $missing_icons[] = [
            'id' => $post->ID,
            'title' => $post->post_title,
            'slug' => $post->post_name,
            'label' => $current_label,
            'url' => get_permalink($post->ID)
        ];
    } else {
        $has_icons[] = [
            'id' => $post->ID,
            'title' => $post->post_title,
            'icon' => $current_icon,
            'label' => $current_label
        ];
    }
}

echo "<div style='margin: 20px 0; padding: 15px; background: #ffebee; border: 1px solid #f44336; border-radius: 8px;'>";
echo "<h3>🚨 Seiten ohne Icons (" . count($missing_icons) . ")</h3>";
if (!empty($missing_icons)) {
    echo "<ul>";
    foreach ($missing_icons as $page) {
        echo "<li><strong>Post {$page['id']}:</strong> {$page['title']} (Label: {$page['label']}) <br>";
        echo "<small>URL: <a href='{$page['url']}' target='_blank'>{$page['url']}</a></small></li>";
    }
    echo "</ul>";
} else {
    echo "<p>✅ Alle Seiten haben Icons!</p>";
}
echo "</div>";

echo "<div style='margin: 20px 0; padding: 15px; background: #e8f5e9; border: 1px solid #4caf50; border-radius: 8px;'>";
echo "<h3>✅ Seiten mit Icons (" . count($has_icons) . ")</h3>";
if (count($has_icons) <= 10) {
    echo "<ul>";
    foreach ($has_icons as $page) {
        echo "<li><i class='{$page['icon']}'></i> <strong>{$page['label']}</strong> - {$page['title']}</li>";
    }
    echo "</ul>";
} else {
    echo "<p>Zeige nur erste 10:</p>";
    echo "<ul>";
    for ($i = 0; $i < 10 && $i < count($has_icons); $i++) {
        $page = $has_icons[$i];
        echo "<li><i class='{$page['icon']}'></i> <strong>{$page['label']}</strong> - {$page['title']}</li>";
    }
    echo "</ul>";
    echo "<p>... und " . (count($has_icons) - 10) . " weitere.</p>";
}
echo "</div>";

// Zusammenfassung
echo "<div style='margin: 20px 0; padding: 15px; background: #f0f8ff; border: 1px solid #2196f3; border-radius: 8px;'>";
echo "<h3>📊 Icon-Zusammenfassung</h3>";
echo "<p><strong>Gesamt:</strong> " . count($posts) . " Seiten</p>";
echo "<p><strong>Mit Icons:</strong> " . count($has_icons) . "</p>";
echo "<p><strong>Ohne Icons:</strong> " . count($missing_icons) . "</p>";
echo "<p><strong>Icon-Abdeckung:</strong> " . round((count($has_icons) / count($posts)) * 100, 1) . "%</p>";
echo "</div>";
?>