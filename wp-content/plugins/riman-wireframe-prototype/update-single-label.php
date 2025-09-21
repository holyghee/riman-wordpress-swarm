<?php
/**
 * Update Single Page Label - Direct WordPress Meta Update
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

// Parameter
$target_slug = 'gefahrstoffmanagement_wo';
$new_label = $_GET['label'] ?? 'WISSENSCHAFTLICH';
$new_icon = $_GET['icon'] ?? 'fas fa-flask';

echo "<h1>🎯 Single Label Update</h1>";
echo "<p>Target: <strong>$target_slug</strong></p>";
echo "<p>New Label: <strong>$new_label</strong></p>";
echo "<p>New Icon: <strong>$new_icon</strong></p>";

// Post finden
$post = get_page_by_path($target_slug, OBJECT, 'riman_seiten');

if (!$post) {
    // Alternative Suche über Slug-Query
    $posts = get_posts([
        'post_type' => 'riman_seiten',
        'name' => $target_slug,
        'posts_per_page' => 1,
        'post_status' => 'publish'
    ]);

    if (!empty($posts)) {
        $post = $posts[0];
    }
}

if ($post) {
    echo "<div style='margin: 20px 0; padding: 15px; background: #f0f8ff; border: 1px solid #cde; border-radius: 8px;'>";
    echo "<h3>✅ Post gefunden!</h3>";
    echo "<p><strong>ID:</strong> {$post->ID}</p>";
    echo "<p><strong>Titel:</strong> {$post->post_title}</p>";
    echo "<p><strong>Slug:</strong> {$post->post_name}</p>";

    // Aktuelle Meta-Daten anzeigen
    $current_label = get_post_meta($post->ID, '_riman_hero_area_label', true);
    $current_icon = get_post_meta($post->ID, '_riman_hero_icon', true);

    echo "<p><strong>Aktuelles Label:</strong> '$current_label'</p>";
    echo "<p><strong>Aktuelles Icon:</strong> '$current_icon'</p>";
    echo "</div>";

    if (isset($_GET['execute']) && $_GET['execute'] === 'true') {
        // Update ausführen
        $updated_label = update_post_meta($post->ID, '_riman_hero_area_label', $new_label);
        $updated_icon = update_post_meta($post->ID, '_riman_hero_icon', $new_icon);

        echo "<div style='margin: 20px 0; padding: 15px; background: #e8f5e9; border: 1px solid #4caf50; border-radius: 8px;'>";
        echo "<h3>🎉 Update ausgeführt!</h3>";
        echo "<p>Label Update: " . ($updated_label ? 'Erfolgreich' : 'Bereits aktuell') . "</p>";
        echo "<p>Icon Update: " . ($updated_icon ? 'Erfolgreich' : 'Bereits aktuell') . "</p>";
        echo "</div>";

        // Neue Werte verifizieren
        $new_current_label = get_post_meta($post->ID, '_riman_hero_area_label', true);
        $new_current_icon = get_post_meta($post->ID, '_riman_hero_icon', true);

        echo "<div style='margin: 20px 0; padding: 15px; background: #fff3e0; border: 1px solid #ff9800; border-radius: 8px;'>";
        echo "<h3>🔍 Verifikation</h3>";
        echo "<p><strong>Neues Label:</strong> '$new_current_label'</p>";
        echo "<p><strong>Neues Icon:</strong> '$new_current_icon'</p>";
        echo "<p><a href='{$post->post_name}/' target='_blank'>🔗 Seite öffnen</a></p>";
        echo "</div>";

    } else {
        echo "<div style='margin: 20px 0; padding: 15px; background: #fff3e0; border: 1px solid #ff9800; border-radius: 8px;'>";
        echo "<h3>⚠️ Vorschau-Modus</h3>";
        echo "<p>Update wird ausgeführt: <strong>'$current_label'</strong> → <strong>'$new_label'</strong></p>";
        echo "<p><a href='?label=$new_label&icon=$new_icon&execute=true' class='button'>✅ Update ausführen</a></p>";
        echo "</div>";
    }

} else {
    echo "<div style='margin: 20px 0; padding: 15px; background: #ffebee; border: 1px solid #f44336; border-radius: 8px;'>";
    echo "<h3>❌ Post nicht gefunden!</h3>";
    echo "<p>Slug '$target_slug' wurde nicht gefunden.</p>";
    echo "</div>";

    // Debug: Alle Posts mit ähnlichem Slug anzeigen
    $similar_posts = get_posts([
        'post_type' => 'riman_seiten',
        's' => 'gefahrstoff',
        'posts_per_page' => 10,
        'post_status' => 'publish'
    ]);

    if (!empty($similar_posts)) {
        echo "<h3>🔍 Ähnliche Posts:</h3>";
        echo "<ul>";
        foreach ($similar_posts as $similar) {
            echo "<li><strong>{$similar->post_title}</strong> (Slug: {$similar->post_name}, ID: {$similar->ID})</li>";
        }
        echo "</ul>";
    }
}

echo "<style>
.button {
    background: #1976d2;
    color: white;
    padding: 10px 20px;
    text-decoration: none;
    border-radius: 4px;
    display: inline-block;
    margin: 10px 0;
}
.button:hover { background: #1565c0; color: white; }
</style>";
?>