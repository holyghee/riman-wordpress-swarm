<?php
// WordPress laden
require_once('/var/www/html/wp-load.php');

// Deaktiviere alte Mega-Menu Plugins
$old_plugins = [
    'mega-menu-navigation.php',
    'navigation-category-posts-block.php',
    'dynamic-menu-block-themes.php',
    'dynamic-category-block.php'
];

foreach ($old_plugins as $plugin) {
    if (is_plugin_active($plugin)) {
        deactivate_plugins($plugin);
        echo "✓ Deaktiviert: $plugin\n";
    }
}

// Aktiviere neues Plugin
if (!is_plugin_active('auto-populate-navigation.php')) {
    activate_plugin('auto-populate-navigation.php');
    echo "✓ Aktiviert: auto-populate-navigation.php\n";
} else {
    echo "Plugin bereits aktiv: auto-populate-navigation.php\n";
}

echo "\nAktive Plugins:\n";
$active_plugins = get_option('active_plugins');
foreach ($active_plugins as $plugin) {
    echo "- $plugin\n";
}