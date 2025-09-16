<?php
/**
 * Plugin manuell aktivieren
 * Diese Datei über Browser aufrufen um Plugin zu aktivieren
 */

// WordPress laden
require_once '../../../wp-config.php';

echo "<h1>RIMAN Plugin Aktivierung</h1>";

$plugin_file = 'riman-wireframe-prototype/riman-wireframe-prototype.php';

// 1. Prüfen ob Plugin existiert
$plugin_path = WP_PLUGIN_DIR . '/' . $plugin_file;
echo "<h2>Plugin-Datei</h2>";
echo "Pfad: " . $plugin_path . "<br>";
echo "Existiert: " . (file_exists($plugin_path) ? "✅ JA" : "❌ NEIN") . "<br>";

if (!file_exists($plugin_path)) {
    echo "<strong style='color: red;'>Plugin-Datei nicht gefunden!</strong><br>";
    exit;
}

// 2. Plugin-Daten laden
if (!function_exists('get_plugin_data')) {
    require_once ABSPATH . 'wp-admin/includes/plugin.php';
}

$plugin_data = get_plugin_data($plugin_path);
echo "<h2>Plugin-Daten</h2>";
echo "Name: " . $plugin_data['Name'] . "<br>";
echo "Version: " . $plugin_data['Version'] . "<br>";
echo "Beschreibung: " . $plugin_data['Description'] . "<br>";

// 3. Aktuelle Plugin-Liste
$active_plugins = get_option('active_plugins', array());
echo "<h2>Plugin-Status</h2>";
echo "Ist aktiviert: " . (in_array($plugin_file, $active_plugins) ? "✅ JA" : "❌ NEIN") . "<br>";

// 4. Plugin aktivieren
if (!in_array($plugin_file, $active_plugins)) {
    echo "<h2>Plugin aktivieren</h2>";
    
    // Plugin zur aktiven Liste hinzufügen
    $active_plugins[] = $plugin_file;
    update_option('active_plugins', $active_plugins);
    
    echo "✅ Plugin wurde zur aktiven Liste hinzugefügt<br>";
    
    // Plugin-Aktivierungs-Hook manuell ausführen
    if (file_exists($plugin_path)) {
        include_once $plugin_path;
        
        // Prüfen ob Aktivierungs-Hook existiert
        if (class_exists('RIMAN_Wireframe_Prototype')) {
            echo "✅ Plugin-Klasse gefunden<br>";
            
            // Plugin-Instanz erstellen und aktivieren
            $plugin_instance = RIMAN_Wireframe_Prototype::get_instance();
            if (method_exists($plugin_instance, 'activate')) {
                $plugin_instance->activate();
                echo "✅ Aktivierungs-Hook ausgeführt<br>";
            }
        } else {
            echo "❌ Plugin-Klasse nicht gefunden<br>";
        }
    }
    
    echo "<br><strong style='color: green;'>Plugin sollte jetzt aktiviert sein!</strong><br>";
    echo "<a href='/wp-admin/plugins.php' target='_blank'>Plugin-Seite öffnen</a><br>";
    echo "<a href='/wp-admin/' target='_blank'>WordPress Admin öffnen</a><br>";
    
} else {
    echo "<strong>Plugin ist bereits aktiviert</strong><br>";
    echo "<a href='/wp-admin/' target='_blank'>WordPress Admin öffnen</a><br>";
}

// 5. Debug-Infos
echo "<h2>Debug-Informationen</h2>";
echo "WordPress Version: " . get_bloginfo('version') . "<br>";
echo "PHP Version: " . PHP_VERSION . "<br>";
echo "Plugin-Verzeichnis: " . WP_PLUGIN_DIR . "<br>";

// 6. Alle Plugins auflisten
echo "<h2>Alle verfügbaren Plugins</h2>";
$all_plugins = get_plugins();
$found = false;
foreach ($all_plugins as $plugin_path => $plugin_info) {
    if (strpos($plugin_path, 'riman-wireframe') !== false) {
        echo "✅ " . $plugin_info['Name'] . " (" . $plugin_path . ")<br>";
        $found = true;
    }
}

if (!$found) {
    echo "❌ RIMAN Plugin nicht in der WordPress Plugin-Liste gefunden<br>";
    echo "<strong>Mögliche Lösungen:</strong><br>";
    echo "1. WordPress Cache leeren<br>";
    echo "2. Plugin-Ordner umbenennen und zurück<br>";
    echo "3. WordPress neu starten<br>";
}

echo "<br><em>Diese Datei nach dem Test löschen!</em>";
?>