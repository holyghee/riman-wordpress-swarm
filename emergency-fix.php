<?php
/**
 * Emergency Fix - Deaktiviert problematische Plugins
 */

// WordPress laden
require_once('/var/www/html/wp-load.php');

echo "=== Emergency Fix ===\n\n";

// Deaktiviere alle Plugins außer den essentiellen
$plugins_to_keep = [
    'category-page-connector.php',
    'wordpress-mcp/wordpress-mcp.php'
];

$all_plugins = get_option('active_plugins');
$new_active = [];

foreach ($all_plugins as $plugin) {
    if (in_array($plugin, $plugins_to_keep)) {
        $new_active[] = $plugin;
        echo "✓ Behalte: $plugin\n";
    } else {
        echo "✗ Deaktiviere: $plugin\n";
    }
}

update_option('active_plugins', $new_active);

echo "\n=== Räume auf ===\n";

// Lösche Transients
global $wpdb;
$wpdb->query("DELETE FROM $wpdb->options WHERE option_name LIKE '%_transient_%'");
echo "✓ Transients gelöscht\n";

// Erhöhe Memory Limit
if (!defined('WP_MEMORY_LIMIT')) {
    define('WP_MEMORY_LIMIT', '256M');
}
if (!defined('WP_MAX_MEMORY_LIMIT')) {
    define('WP_MAX_MEMORY_LIMIT', '512M');
}

echo "✓ Memory Limit erhöht\n";
echo "\nFertig! Versuche die Seite neu zu laden.\n";