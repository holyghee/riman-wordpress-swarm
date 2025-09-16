<?php
/**
 * Debug-Datei für RIMAN Wireframe Prototype
 * Diese Datei temporär über Browser aufrufen um Plugin-Status zu prüfen
 */

// WordPress laden
require_once '../../../wp-config.php';

echo "<h1>RIMAN Wireframe Prototype - Debug</h1>";

// 1. Plugin-Status prüfen
echo "<h2>1. Plugin-Status</h2>";
$active_plugins = get_option('active_plugins', array());
$plugin_file = 'riman-wireframe-prototype/riman-wireframe-prototype.php';
$is_active = in_array($plugin_file, $active_plugins);
echo "Plugin aktiv: " . ($is_active ? "✅ JA" : "❌ NEIN") . "<br>";

if (!$is_active) {
    echo "<strong style='color: red;'>Das Plugin ist nicht aktiviert!</strong><br>";
    echo "Bitte in WordPress Admin unter 'Plugins' aktivieren.<br><br>";
}

// 2. Plugin-Datei prüfen
echo "<h2>2. Plugin-Dateien</h2>";
$plugin_main_file = ABSPATH . 'wp-content/plugins/' . $plugin_file;
echo "Hauptdatei existiert: " . (file_exists($plugin_main_file) ? "✅ JA" : "❌ NEIN") . "<br>";
echo "Pfad: " . $plugin_main_file . "<br>";

// 3. Post Type prüfen
echo "<h2>3. Custom Post Type</h2>";
$post_types = get_post_types(array(), 'names');
$has_riman_seiten = in_array('riman_seiten', $post_types);
echo "Post Type 'riman_seiten' registriert: " . ($has_riman_seiten ? "✅ JA" : "❌ NEIN") . "<br>";

if ($has_riman_seiten) {
    $post_type_obj = get_post_type_object('riman_seiten');
    echo "Menü Position: " . ($post_type_obj->menu_position ?? 'nicht gesetzt') . "<br>";
    echo "Menü Icon: " . ($post_type_obj->menu_icon ?? 'nicht gesetzt') . "<br>";
    echo "Show in Menu: " . ($post_type_obj->show_in_menu ? "✅ JA" : "❌ NEIN") . "<br>";
}

// 4. Taxonomy prüfen
echo "<h2>4. Custom Taxonomy</h2>";
$taxonomies = get_taxonomies(array(), 'names');
$has_seitentyp = in_array('seitentyp', $taxonomies);
echo "Taxonomy 'seitentyp' registriert: " . ($has_seitentyp ? "✅ JA" : "❌ NEIN") . "<br>";

if ($has_seitentyp) {
    $terms = get_terms(array(
        'taxonomy' => 'seitentyp',
        'hide_empty' => false
    ));
    echo "Anzahl Terms: " . count($terms) . "<br>";
    if (!empty($terms)) {
        foreach ($terms as $term) {
            echo "- " . $term->name . " (Slug: " . $term->slug . ")<br>";
        }
    }
}

// 5. Plugin-Konstanten prüfen
echo "<h2>5. Plugin-Konstanten</h2>";
echo "RIMAN_WIREFRAME_VERSION: " . (defined('RIMAN_WIREFRAME_VERSION') ? RIMAN_WIREFRAME_VERSION : "❌ Nicht definiert") . "<br>";
echo "RIMAN_WIREFRAME_PLUGIN_DIR: " . (defined('RIMAN_WIREFRAME_PLUGIN_DIR') ? "✅ Definiert" : "❌ Nicht definiert") . "<br>";
echo "RIMAN_WIREFRAME_PLUGIN_URL: " . (defined('RIMAN_WIREFRAME_PLUGIN_URL') ? "✅ Definiert" : "❌ Nicht definiert") . "<br>";

// 6. Sample Content prüfen
echo "<h2>6. Sample Content</h2>";
$sample_posts = get_posts(array(
    'post_type' => 'riman_seiten',
    'meta_key' => '_riman_sample_content',
    'meta_value' => '1',
    'numberposts' => -1,
    'post_status' => 'any'
));
echo "Sample Posts gefunden: " . count($sample_posts) . "<br>";

// 7. Plugin-Aktivierung prüfen
echo "<h2>7. Plugin-Aktivierung</h2>";
$activated = get_option('riman_wireframe_activated', false);
echo "Aktivierungs-Flag gesetzt: " . ($activated ? "✅ JA (" . date('Y-m-d H:i:s', $activated) . ")" : "❌ NEIN") . "<br>";

// 8. Klassen-Existenz prüfen
echo "<h2>8. Plugin-Klassen</h2>";
$classes = array(
    'RIMAN_Wireframe_Prototype',
    'RIMAN_Wireframe_Post_Types',
    'RIMAN_Wireframe_Taxonomies',
    'RIMAN_Wireframe_Meta_Boxes',
    'RIMAN_Wireframe_Admin_Pages',
    'RIMAN_Wireframe_Sample_Content'
);

foreach ($classes as $class) {
    echo $class . ": " . (class_exists($class) ? "✅ Existiert" : "❌ Nicht gefunden") . "<br>";
}

// 9. WordPress Hooks prüfen
echo "<h2>9. WordPress Hooks</h2>";
global $wp_filter;
$init_hooks = $wp_filter['init'] ?? null;
if ($init_hooks) {
    $has_plugin_hooks = false;
    foreach ($init_hooks->callbacks as $priority => $callbacks) {
        foreach ($callbacks as $callback) {
            if (is_array($callback['function']) && is_object($callback['function'][0])) {
                $class_name = get_class($callback['function'][0]);
                if (strpos($class_name, 'RIMAN_Wireframe') !== false) {
                    echo "Hook gefunden: " . $class_name . "->" . $callback['function'][1] . " (Priorität: $priority)<br>";
                    $has_plugin_hooks = true;
                }
            }
        }
    }
    if (!$has_plugin_hooks) {
        echo "❌ Keine RIMAN Plugin Hooks auf 'init' gefunden<br>";
    }
}

// 10. Empfohlene Aktionen
echo "<h2>10. Empfohlene Aktionen</h2>";

if (!$is_active) {
    echo "🔧 Plugin in WordPress Admin aktivieren<br>";
}

if (!$has_riman_seiten) {
    echo "🔧 Plugin deaktivieren und erneut aktivieren<br>";
}

if (!$activated) {
    echo "🔧 Plugin-Aktivierungs-Hook wurde nicht ausgeführt<br>";
}

echo "<br><em>Debug-Datei nach dem Test löschen!</em>";
?>