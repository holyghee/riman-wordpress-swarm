<?php
/**
 * Update All RIMAN Pages with Powerful One-Word Labels
 * Executes bulk update of hero metadata with new powerful labels
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

// Neue Label-Mappings
$label_mappings = [
    // Altlasten & Gefahrstoffe
    'Altlasten-Management' => 'FACHGERECHT',
    'Altlasten-Sanierung' => 'FACHGERECHT',
    'Altlasten-Beratung' => 'ERFAHREN',
    'Altlasten-Erkundung' => 'GRÜNDLICH',
    'Gefahrstoff-Management' => 'FACHGERECHT',
    'Gefahrstoff-Erkennung' => 'GRÜNDLICH',
    'Gefahrstoff-Analyse' => 'WISSENSCHAFTLICH',

    // Nachhaltigkeit & Umwelt
    'Entsorgung' => 'NACHHALTIG',
    'Entsorgungsberatung' => 'BERATEND',
    'Entsorgungsplanung' => 'STRATEGISCH',
    'Entsorgungsleitung' => 'EFFIZIENT',
    'Entsorgungsanalyse' => 'GRÜNDLICH',
    'Schadstoff-Bewertung' => 'ANALYTISCH',
    'Schadstoff-Analyse' => 'WISSENSCHAFTLICH',
    'Altlasten-Planung' => 'METHODISCH',
    'Bau-Sicherheit' => 'KOMPETENT',

    // Mediation Labels
    'Interne Mediation' => 'EFFIZIENT',
    'Paar-Mediation' => 'EINFÜHLSAM',
    'Nachbarschafts-Mediation' => 'KONSTRUKTIV',
    'Freundschafts-Mediation' => 'VERTRAUENSVOLL',
    'Planungsmediation' => 'STRATEGISCH',
    'Genehmigungs-Mediation' => 'LÖSUNGSORIENTIERT',
    'Ausführungsmediation' => 'ZUVERLÄSSIG',

    // Koordination Labels
    'Gefahrstoff-Koordination' => 'FACHGERECHT',
    'BauStVO-Koordination' => 'KOMPETENT',
    'Asbest-Koordination' => 'SICHER',

    // Kompetenz & Management
    'Bau-Management' => 'KOMPETENT',
    'Sicherheits-Koordination' => 'KOMPETENT',
    'Abbruch-Projekte' => 'SICHER',
    'Baustellen-Leitung' => 'ZUVERLÄSSIG',
    'Bau-Koordination' => 'ZUVERLÄSSIG',

    // Mediation & Vertrauen
    'Mediation & Sicherheit' => 'VERTRAUENSVOLL',
    'Private Mediation' => 'VERTRAUENSVOLL',
    'Familien-Mediation' => 'VERTRAUENSVOLL',
    'Gemeinde-Mediation' => 'KONSTRUKTIV',
    'Unternehmens-Mediation' => 'PROFESSIONELL',
    'Betriebsinterne Mediation' => 'EFFIZIENT',
    'B2B-Mediation' => 'PROFESSIONELL',
    'Bau-Mediation' => 'LÖSUNGSORIENTIERT',
    'Generationen-Mediation' => 'VERSTÄNDNISVOLL',
    'Beziehungs-Mediation' => 'EINFÜHLSAM',
    'Finanz-Mediation' => 'AUSGLEICHEND',

    // Planung & Struktur
    'Abbruch-Planung' => 'STRUKTURIERT',
    'Projekt-Planung' => 'SYSTEMATISCH',
    'Sanierungsplanung' => 'METHODISCH',
    'Sanierungsleitung' => 'ZUVERLÄSSIG',
    'Statik-Analyse' => 'PRÄZISE'
];

// Icon-Mappings
$icon_mappings = [
    'FACHGERECHT' => 'fas fa-award',
    'NACHHALTIG' => 'fas fa-leaf',
    'KOMPETENT' => 'fas fa-star',
    'VERTRAUENSVOLL' => 'fas fa-handshake',
    'PROFESSIONELL' => 'fas fa-briefcase',
    'ZUVERLÄSSIG' => 'fas fa-shield-alt',
    'SICHER' => 'fas fa-shield-check',
    'PRÄZISE' => 'fas fa-crosshairs',
    'SYSTEMATISCH' => 'fas fa-list-ol',
    'METHODISCH' => 'fas fa-project-diagram',
    'WISSENSCHAFTLICH' => 'fas fa-flask',
    'GRÜNDLICH' => 'fas fa-search-plus',
    'ERFAHREN' => 'fas fa-user-graduate',
    'BERATEND' => 'fas fa-comments',
    'STRATEGISCH' => 'fas fa-chess',
    'EFFIZIENT' => 'fas fa-tachometer-alt',
    'ANALYTISCH' => 'fas fa-chart-line',
    'KONSTRUKTIV' => 'fas fa-puzzle-piece',
    'LÖSUNGSORIENTIERT' => 'fas fa-lightbulb',
    'STRUKTURIERT' => 'fas fa-sitemap',
    'VERSTÄNDNISVOLL' => 'fas fa-heart',
    'EINFÜHLSAM' => 'fas fa-hands-helping',
    'AUSGLEICHEND' => 'fas fa-balance-scale'
];

echo "<h1>🎯 Powerful Labels Update</h1>";
echo "<p>Aktualisiere alle RIMAN-Seiten mit kraftvollen Ein-Wort-Labels...</p>";

// Alle RIMAN Posts holen
$posts = get_posts([
    'post_type' => 'riman_seiten',
    'posts_per_page' => -1,
    'post_status' => 'publish'
]);

$updated_count = 0;
$total_count = count($posts);

echo "<div style='margin: 20px 0; padding: 15px; background: #f0f8ff; border: 1px solid #cde; border-radius: 8px;'>";
echo "<h3>📊 Update-Status</h3>";
echo "<p><strong>Gefundene Posts:</strong> $total_count</p>";

foreach ($posts as $post) {
    $current_label = get_post_meta($post->ID, '_riman_hero_area_label', true);

    if ($current_label && isset($label_mappings[$current_label])) {
        $new_label = $label_mappings[$current_label];
        $new_icon = $icon_mappings[$new_label] ?? 'fas fa-star';

        // Update Meta-Felder
        update_post_meta($post->ID, '_riman_hero_area_label', $new_label);
        update_post_meta($post->ID, '_riman_hero_icon', $new_icon);

        echo "<div style='margin: 5px 0; color: green;'>";
        echo "✅ Post {$post->ID}: <strong>'{$current_label}'</strong> → <strong>'{$new_label}'</strong> ({$new_icon})";
        echo "</div>";

        $updated_count++;
    } else {
        echo "<div style='margin: 5px 0; color: #666;'>";
        echo "⏭️ Post {$post->ID}: Kein Mapping für '{$current_label}'";
        echo "</div>";
    }
}

echo "</div>";

echo "<div style='margin: 20px 0; padding: 15px; background: #e8f5e9; border: 1px solid #4caf50; border-radius: 8px;'>";
echo "<h3>🎉 Update abgeschlossen!</h3>";
echo "<p><strong>$updated_count von $total_count</strong> Posts wurden mit kraftvollen Labels aktualisiert.</p>";
echo "<p>Alle Bereichslabel sind jetzt Ein-Wort-Labels mit passenden Icons!</p>";
echo "</div>";

echo "<div style='margin: 20px 0; padding: 15px; background: #fff3e0; border: 1px solid #ff9800; border-radius: 8px;'>";
echo "<h3>📋 Verwendete kraftvolle Labels:</h3>";
echo "<ul>";
foreach (array_unique(array_values($label_mappings)) as $label) {
    $icon = $icon_mappings[$label] ?? 'fas fa-star';
    echo "<li><i class='$icon'></i> <strong>$label</strong></li>";
}
echo "</ul>";
echo "</div>";
?>