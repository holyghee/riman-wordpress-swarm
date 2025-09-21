<?php
/**
 * Debug Script: Zeige alle existierenden RIMAN Posts mit ihren Slugs
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

// Nur für Administratoren
if (!current_user_can('manage_options')) {
    wp_die('Nur Administratoren dürfen dieses Script ausführen.');
}

?>
<!DOCTYPE html>
<html>
<head>
    <title>RIMAN Posts Debug</title>
    <style>
        body { font-family: -apple-system, BlinkMacSystemFont, sans-serif; margin: 20px; }
        .container { max-width: 1200px; margin: 0 auto; }
        .post-item { border: 1px solid #ddd; padding: 15px; margin: 10px 0; border-radius: 4px; background: #f9f9f9; }
        .hauptseite { border-color: #2196f3; background: #e3f2fd; }
        .unterseite { border-color: #4caf50; background: #e8f5e9; }
        .detailseite { border-color: #ff9800; background: #fff3e0; }
        .info { border-color: #9c27b0; background: #f3e5f5; }
        .meta { font-size: 12px; color: #666; margin-top: 10px; }
        .hero-meta { background: #fff; padding: 10px; border-radius: 4px; margin-top: 10px; }
    </style>
</head>
<body>
    <div class="container">
        <h1>🔍 RIMAN Posts Debug</h1>

        <?php
        // Alle RIMAN Posts holen
        $posts = get_posts([
            'post_type' => 'riman_seiten',
            'posts_per_page' => -1,
            'post_status' => 'publish',
            'orderby' => 'menu_order',
            'order' => 'ASC'
        ]);

        echo "<h2>Gefundene RIMAN Posts: " . count($posts) . "</h2>";

        // Nach Seitentyp gruppieren
        $by_type = [];
        foreach ($posts as $post) {
            $terms = get_the_terms($post->ID, 'seitentyp');
            $type = 'unbekannt';
            if ($terms && !is_wp_error($terms)) {
                $type = $terms[0]->slug;
            }

            if (!isset($by_type[$type])) {
                $by_type[$type] = [];
            }
            $by_type[$type][] = $post;
        }

        // Anzeigen nach Typ
        $type_names = [
            'hauptseite' => 'Hauptseiten',
            'unterseite' => 'Unterseiten',
            'detailseite' => 'Detailseiten',
            'info' => 'Info-Seiten'
        ];

        foreach ($type_names as $type_slug => $type_name) {
            if (isset($by_type[$type_slug])) {
                echo "<h3>$type_name (" . count($by_type[$type_slug]) . ")</h3>";

                foreach ($by_type[$type_slug] as $post) {
                    // Hero-Metadaten prüfen
                    $hero_title = get_post_meta($post->ID, '_riman_hero_title', true);
                    $hero_subtitle = get_post_meta($post->ID, '_riman_hero_subtitle', true);
                    $hero_area_label = get_post_meta($post->ID, '_riman_hero_area_label', true);
                    $hero_icon = get_post_meta($post->ID, '_riman_hero_icon', true);

                    $has_hero = !empty($hero_title) || !empty($hero_subtitle) || !empty($hero_area_label) || !empty($hero_icon);

                    echo '<div class="post-item ' . $type_slug . '">';
                    echo '<strong>' . esc_html($post->post_title) . '</strong>';
                    echo '<div class="meta">';
                    echo 'ID: ' . $post->ID . ' | ';
                    echo 'Slug: <code>' . esc_html($post->post_name) . '</code> | ';
                    echo 'Parent: ' . ($post->post_parent ? $post->post_parent : 'None') . ' | ';
                    echo 'Menu Order: ' . $post->menu_order;
                    echo '</div>';

                    if ($has_hero) {
                        echo '<div class="hero-meta">';
                        echo '<strong>✅ Hero-Metadaten vorhanden:</strong><br>';
                        if ($hero_title) echo 'Titel: ' . esc_html($hero_title) . '<br>';
                        if ($hero_subtitle) echo 'Untertitel: ' . esc_html($hero_subtitle) . '<br>';
                        if ($hero_area_label) echo 'Bereichslabel: ' . esc_html($hero_area_label) . '<br>';
                        if ($hero_icon) echo 'Icon: ' . esc_html($hero_icon) . '<br>';
                        echo '</div>';
                    } else {
                        echo '<div style="color: #f44336; margin-top: 10px;">❌ Keine Hero-Metadaten</div>';
                    }

                    echo '</div>';
                }
            }
        }

        // Unbekannte Typen
        if (isset($by_type['unbekannt'])) {
            echo "<h3>Unbekannte Typen (" . count($by_type['unbekannt']) . ")</h3>";
            foreach ($by_type['unbekannt'] as $post) {
                echo '<div class="post-item">';
                echo '<strong>' . esc_html($post->post_title) . '</strong>';
                echo '<div class="meta">ID: ' . $post->ID . ' | Slug: <code>' . esc_html($post->post_name) . '</code></div>';
                echo '</div>';
            }
        }
        ?>

        <h2>📋 Slug-Mapping für JSON</h2>
        <div style="background: #f5f5f5; padding: 15px; border-radius: 4px; font-family: monospace; font-size: 12px;">
            <?php
            // Erstelle Mapping für JSON
            foreach ($posts as $post) {
                $terms = get_the_terms($post->ID, 'seitentyp');
                $type = 'unbekannt';
                if ($terms && !is_wp_error($terms)) {
                    $type = $terms[0]->slug;
                }

                echo '"' . esc_html($post->post_name) . '" => "' . esc_html($post->post_title) . '" (' . $type . ')<br>';
            }
            ?>
        </div>
    </div>
</body>
</html>