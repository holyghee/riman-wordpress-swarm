<?php
/**
 * Plugin Name: RIMAN Blocks (Core)
 * Description: Zentrale Infrastruktur für RIMAN Blöcke (gemeinsame Block-Kategorie, Hilfsfunktionen)
 * Version: 1.0.0
 * Author: RIMAN GmbH
 */

if (!defined('ABSPATH')) exit;

// Signal an andere RIMAN-Plugins, dass der Core aktiv ist (verhindert doppelte Registrierung)
if (!defined('RIMAN_BLOCKS_CORE_LOADED')) {
    define('RIMAN_BLOCKS_CORE_LOADED', true);
}
if (!defined('RIMAN_BLOCKS_URL')) {
    define('RIMAN_BLOCKS_URL', plugin_dir_url(__FILE__));
}

// Einheitliche Block-Kategorie "RIMAN Blocks"
add_filter('block_categories_all', function($categories) {
    // Prüfe, ob bereits vorhanden
    foreach ($categories as $cat) {
        if (!empty($cat['slug']) && $cat['slug'] === 'riman') {
            return $categories; // schon da
        }
    }
    $categories[] = array(
        'slug'  => 'riman',
        'title' => __('RIMAN Blocks', 'riman'),
        'icon'  => null,
    );
    return $categories;
}, 5);

// Blöcke laden
require_once __DIR__ . '/blocks/subcategories-slider.php';
require_once __DIR__ . '/blocks/category-header.php';
require_once __DIR__ . '/blocks/category-linked-content.php';
require_once __DIR__ . '/blocks/category-content.php';
require_once __DIR__ . '/blocks/subcategories-grid.php';
require_once __DIR__ . '/blocks/shortcode-category-content.php';
require_once __DIR__ . '/blocks/category-hero.php';
require_once __DIR__ . '/blocks/category-hero-slider.php';
// Patterns
require_once __DIR__ . '/patterns/page-hero.php';

// Platz für gemeinsame Helper (falls später benötigt)
// z.B. riman_get_category_image_url($term_id) etc.
