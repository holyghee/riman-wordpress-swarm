<?php
/**
 * Plugin Name: RIMAN Blocks (Core)
 * Description: Zentrale Infrastruktur & Blöcke (Hero inkl. Featured-Video). Lädt Block-Dateien robust (nur wenn vorhanden).
 * Version: 1.2.3
 * Author: RIMAN GmbH
 */

if (!defined('ABSPATH')) exit;

if (!defined('RIMAN_BLOCKS_URL')) define('RIMAN_BLOCKS_URL', plugin_dir_url(__FILE__));
if (!defined('RIMAN_BLOCKS_DIR')) define('RIMAN_BLOCKS_DIR', plugin_dir_path(__FILE__));

/**
 * Eigene Block-Kategorie "RIMAN"
 */
add_filter('block_categories_all', function ($cats) {
    $exists = false;
    foreach ($cats as $c) { if (!empty($c['slug']) && $c['slug'] === 'riman') { $exists = true; break; } }
    if (!$exists) {
        array_unshift($cats, [
            'slug'  => 'riman',
            'title' => __('RIMAN', 'riman'),
            'icon'  => null,
        ]);
    }
    return $cats;
}, 10, 1);

/**
 * Minimal-Styles für Hero (zentriert + weiß, Video/Overlay Layer)
 */
add_action('wp_enqueue_scripts', function () {
    $handle = 'riman-hero-style';
    wp_register_style($handle, false, [], '1.2.3');
    wp_enqueue_style($handle);
    $css = '
    .riman-hero{position:relative;overflow:hidden}
    .riman-hero__media{position:absolute;inset:0;z-index:0}
    .riman-hero__overlay{position:absolute;inset:0;z-index:1;pointer-events:none}
    .riman-hero__video,.riman-hero__iframe{width:100%;height:100%;display:block;object-fit:cover}
    .riman-hero .wp-block-group{position:relative;z-index:2}
    .riman-hero, .riman-hero .wp-block-group { text-align:center; }
    .riman-hero .wp-block-heading, .riman-hero p { color: var(--wp--preset--color--base, #fff); }
    ';
    wp_add_inline_style($handle, $css);
});

/**
 * Hilfsfunktion: Datei robust laden
 */
function riman_require($rel) {
    $path = RIMAN_BLOCKS_DIR . ltrim($rel, '/');
    if (file_exists($path)) {
        require_once $path;
        return true;
    } else {
        error_log('[RIMAN Blocks] Datei fehlt: ' . $path);
        return false;
    }
}

/**
 * Blöcke laden (nur wenn vorhanden, damit kein Fatal passiert)
 * → Passe diese Liste an deine tatsächlichen Dateien an.
 */
$maybe_blocks = [
    'blocks/subcategories-slider.php',
    'blocks/category-header.php',
    'blocks/category-linked-content.php',
    'blocks/category-content.php',
    'blocks/subcategories-grid.php',
    'blocks/service-cards.php',
    'blocks/shortcode-category-content.php',
    'blocks/category-hero.php',          // ← WICHTIG: unser Hero mit Video
    'blocks/category-hero-slider.php',
    'blocks/riman-hero-meta.php',        // Meta Box für riman_seiten
    'blocks/riman-page-hero.php',        // RIMAN Page Hero für FSE Templates
];

foreach ($maybe_blocks as $rel) { riman_require($rel); }

/**
 * Includes laden (Video Lazy-Loading Support)
 */
riman_require('includes/cover-video-lazy.php');

/**
 * Meta Boxes laden
 */
riman_require('includes/class-service-cards-overlap.php');

/**
 * Optionale Patterns
 */
riman_require('patterns/page-hero.php');
