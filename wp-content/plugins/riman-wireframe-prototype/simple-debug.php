<?php
// Einfacher Debug-Check ohne WordPress zu laden
echo "<h1>RIMAN Plugin Debug - Basis Check</h1>";

// 1. Dateien prüfen
echo "<h2>Plugin Dateien</h2>";
$files = array(
    'riman-wireframe-prototype.php',
    'includes/class-post-types.php',
    'includes/class-taxonomies.php',
    'includes/class-meta-boxes.php',
    'includes/class-admin-pages.php',
    'includes/class-sample-content.php'
);

foreach ($files as $file) {
    $exists = file_exists(__DIR__ . '/' . $file);
    echo $file . ": " . ($exists ? "✅ Existiert" : "❌ Fehlt") . "<br>";
    
    if ($exists) {
        // PHP Syntax prüfen
        $output = array();
        $return_var = 0;
        exec("php -l " . escapeshellarg(__DIR__ . '/' . $file) . " 2>&1", $output, $return_var);
        if ($return_var === 0) {
            echo "   → Syntax: ✅ OK<br>";
        } else {
            echo "   → Syntax: ❌ FEHLER - " . implode(', ', $output) . "<br>";
        }
    }
}

// 2. Plugin Header prüfen
echo "<h2>Plugin Header</h2>";
$plugin_file_content = file_get_contents(__DIR__ . '/riman-wireframe-prototype.php');
if (preg_match('/Plugin Name:\s*(.+)/', $plugin_file_content, $matches)) {
    echo "Plugin Name gefunden: " . trim($matches[1]) . " ✅<br>";
} else {
    echo "Plugin Name NICHT gefunden ❌<br>";
}

// 3. Wichtigste Klassen-Definitionen prüfen
echo "<h2>Klassen-Definitionen</h2>";
$classes = array(
    'RIMAN_Wireframe_Prototype' => 'riman-wireframe-prototype.php',
    'RIMAN_Wireframe_Post_Types' => 'includes/class-post-types.php'
);

foreach ($classes as $class => $file) {
    $file_content = file_get_contents(__DIR__ . '/' . $file);
    if (strpos($file_content, "class $class") !== false) {
        echo "$class: ✅ Gefunden in $file<br>";
    } else {
        echo "$class: ❌ NICHT gefunden in $file<br>";
    }
}

echo "<h2>Nächste Schritte</h2>";
echo "1. WordPress Admin öffnen: <a href='http://127.0.0.1:8801/wp-admin/plugins.php' target='_blank'>Plugins-Seite</a><br>";
echo "2. Suche nach 'RIMAN Wireframe Prototype' in der Plugin-Liste<br>";
echo "3. Falls nicht sichtbar: Plugin-Ordner umbenennen und erneut prüfen<br>";
echo "4. Falls aktiviert aber kein Menü: WordPress Error Log prüfen<br>";
?>