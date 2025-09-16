<?php
/**
 * Reset Sample Content for RIMAN Wireframe Prototype (local dev helper)
 * Usage: open this file in the browser while running the local site, e.g.
 *   http://127.0.0.1:8801/wp-content/plugins/riman-wireframe-prototype/reset-sample-content.php
 */

// Bootstrap WordPress
require_once '../../../wp-config.php';

header('Content-Type: text/html; charset=utf-8');
echo "<h1>RIMAN Wireframe Prototype – Reset Sample Content</h1>";

// Basic capability check (only run for logged-in admins)
if (!is_user_logged_in() || !current_user_can('manage_options')) {
    echo "<p style='color:red'>❌ Zugriff verweigert. Bitte als Admin einloggen.</p>";
    exit;
}

// Ensure plugin classes are available
if (!class_exists('RIMAN_Wireframe_Sample_Content')) {
    // Try to load plugin main file
    $plugin_main = ABSPATH . 'wp-content/plugins/riman-wireframe-prototype/riman-wireframe-prototype.php';
    if (file_exists($plugin_main)) {
        include_once $plugin_main;
    }
}

if (!class_exists('RIMAN_Wireframe_Sample_Content')) {
    echo "<p style='color:red'>❌ Klasse RIMAN_Wireframe_Sample_Content nicht gefunden.</p>";
    exit;
}

// Run reset
$sample = new RIMAN_Wireframe_Sample_Content();
$deleted = $sample->delete_sample_content();
echo "<p>🗑️ Gelöschte Sample-Posts: <strong>" . intval($deleted) . "</strong></p>";

$created = $sample->create_sample_content();
echo $created
    ? "<p>✅ Neuer Sample-Content wurde erstellt.</p>"
    : "<p>ℹ️ Sample-Content existiert bereits (nichts erstellt).</p>";

// Show small stats
$stats = $sample->get_sample_content_stats();
echo "<h2>Aktuelle Sample-Content Statistiken</h2>";
echo "<ul>";
echo "<li>Gesamt: " . intval($stats['total'] ?? 0) . "</li>";
echo "<li>Hauptseiten: " . intval($stats['hauptseiten'] ?? 0) . "</li>";
echo "<li>Unterseiten: " . intval($stats['unterseiten'] ?? 0) . "</li>";
echo "<li>Detailseiten: " . intval($stats['detailseiten'] ?? 0) . "</li>";
echo "</ul>";

echo "<p><a href='" . admin_url('edit.php?post_type=riman_seiten') . "'>Zu riman_seiten</a></p>";

?>

