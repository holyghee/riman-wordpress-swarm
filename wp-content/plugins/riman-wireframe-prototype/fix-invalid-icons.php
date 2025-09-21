<?php
/**
 * Fix Invalid Font Awesome Icons
 * Replace non-existent icons with valid ones
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

// Korrekte Font Awesome 6 Icons
$icon_fixes = [
    'fas fa-shield-check' => 'fas fa-shield-alt',    // shield-check gibt es nicht
    'fas fa-cogs' => 'fas fa-gear',                  // cogs ist veraltet
    'fas fa-tachometer-alt' => 'fas fa-gauge',       // tachometer-alt ist veraltet
    'fas fa-microscope' => 'fas fa-flask',           // falls microscope nicht existiert
    'fas fa-hard-hat' => 'fas fa-helmet-safety'     // hard-hat könnte veraltet sein
];

echo "<h1>🔧 Icon-Reparatur</h1>";
echo "<p>Überprüfe und repariere ungültige Font Awesome Icons...</p>";

// Alle RIMAN Posts holen
$posts = get_posts([
    'post_type' => 'riman_seiten',
    'posts_per_page' => -1,
    'post_status' => 'publish'
]);

$fixed_count = 0;
$invalid_icons = [];

foreach ($posts as $post) {
    $current_icon = get_post_meta($post->ID, '_riman_hero_icon', true);

    if ($current_icon && isset($icon_fixes[$current_icon])) {
        $new_icon = $icon_fixes[$current_icon];

        // Update Icon
        update_post_meta($post->ID, '_riman_hero_icon', $new_icon);

        echo "<div style='margin: 5px 0; color: green;'>";
        echo "✅ Post {$post->ID}: <strong>'{$current_icon}'</strong> → <strong>'{$new_icon}'</strong>";
        echo "<br><small>{$post->post_title}</small>";
        echo "</div>";

        $fixed_count++;
    } elseif ($current_icon) {
        // Sammle alle aktuellen Icons für Analyse
        if (!in_array($current_icon, $invalid_icons)) {
            $invalid_icons[] = $current_icon;
        }
    }
}

echo "<div style='margin: 20px 0; padding: 15px; background: #e8f5e9; border: 1px solid #4caf50; border-radius: 8px;'>";
echo "<h3>🎉 Reparatur abgeschlossen!</h3>";
echo "<p><strong>$fixed_count Icons</strong> wurden repariert.</p>";
echo "</div>";

// Zeige alle verwendeten Icons
echo "<div style='margin: 20px 0; padding: 15px; background: #f0f8ff; border: 1px solid #2196f3; border-radius: 8px;'>";
echo "<h3>📋 Alle verwendeten Icons:</h3>";
echo "<div style='display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 10px;'>";

$unique_icons = [];
foreach ($posts as $post) {
    $icon = get_post_meta($post->ID, '_riman_hero_icon', true);
    if ($icon && !isset($unique_icons[$icon])) {
        $unique_icons[$icon] = 0;
    }
    if ($icon) {
        $unique_icons[$icon]++;
    }
}

foreach ($unique_icons as $icon => $count) {
    echo "<div style='padding: 8px; background: white; border-radius: 4px; border: 1px solid #ddd;'>";
    echo "<i class='$icon' style='margin-right: 8px;'></i>";
    echo "<code>$icon</code> ($count×)";
    echo "</div>";
}
echo "</div>";
echo "</div>";

// Empfohlene Icons für Labels ohne gültige Icons
echo "<div style='margin: 20px 0; padding: 15px; background: #fff3e0; border: 1px solid #ff9800; border-radius: 8px;'>";
echo "<h3>💡 Empfohlene Standard-Icons:</h3>";
$recommended_icons = [
    'FACHGERECHT' => 'fas fa-award',
    'NACHHALTIG' => 'fas fa-leaf',
    'KOMPETENT' => 'fas fa-star',
    'VERTRAUENSVOLL' => 'fas fa-handshake',
    'PROFESSIONELL' => 'fas fa-briefcase',
    'ZUVERLÄSSIG' => 'fas fa-shield-alt',
    'SICHER' => 'fas fa-shield-alt',
    'PRÄZISE' => 'fas fa-crosshairs',
    'SYSTEMATISCH' => 'fas fa-list-ol',
    'METHODISCH' => 'fas fa-project-diagram',
    'WISSENSCHAFTLICH' => 'fas fa-flask',
    'GRÜNDLICH' => 'fas fa-search-plus',
    'ERFAHREN' => 'fas fa-user-graduate',
    'BERATEND' => 'fas fa-comments',
    'STRATEGISCH' => 'fas fa-chess',
    'EFFIZIENT' => 'fas fa-gauge',
    'ANALYTISCH' => 'fas fa-chart-line',
    'KONSTRUKTIV' => 'fas fa-puzzle-piece',
    'LÖSUNGSORIENTIERT' => 'fas fa-lightbulb',
    'STRUKTURIERT' => 'fas fa-sitemap',
    'VERSTÄNDNISVOLL' => 'fas fa-heart',
    'EINFÜHLSAM' => 'fas fa-hands-helping',
    'AUSGLEICHEND' => 'fas fa-balance-scale'
];

echo "<div style='display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 8px;'>";
foreach ($recommended_icons as $label => $icon) {
    echo "<div style='padding: 6px; background: white; border-radius: 4px; border: 1px solid #ddd;'>";
    echo "<i class='$icon' style='margin-right: 8px;'></i>";
    echo "<strong>$label</strong> → <code>$icon</code>";
    echo "</div>";
}
echo "</div>";
echo "</div>";
?>