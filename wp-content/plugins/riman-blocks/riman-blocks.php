<?php
/**
 * Plugin Name: RIMAN Blocks (Core)
 * Description: Zentrale Infrastruktur & Blöcke (Hero inkl. Featured-Video). Lädt Block-Dateien robust (nur wenn vorhanden).
 * Version: 1.2.4
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
 * Cover Poster Editor im Block Editor laden
 */
add_action('enqueue_block_editor_assets', function() {
    $script_path = RIMAN_BLOCKS_DIR . 'assets/cover-poster-editor.js';
    if (file_exists($script_path)) {
        wp_enqueue_script(
            'riman-cover-poster-editor',
            RIMAN_BLOCKS_URL . 'assets/cover-poster-editor.js',
            ['wp-blocks', 'wp-element', 'wp-editor', 'wp-components', 'wp-i18n', 'wp-hooks', 'wp-compose'],
            filemtime($script_path),
            true
        );
    }
});

/**
 * Force local URLs to current host when WP_HOME points to 127.0.0.1
 */
if (!empty($_SERVER['HTTP_HOST'])) {
    $host = $_SERVER['HTTP_HOST'];
    $is_local_host = strpos($host, '127.0.0.1') !== false || strpos($host, 'localhost') !== false;
    if ($is_local_host) {
        $local_home = 'http://' . $host;

        $override_option = function () use ($local_home) {
            return $local_home;
        };

        add_filter('pre_option_home', $override_option, 50);
        add_filter('pre_option_siteurl', $override_option, 50);

        $adjust_url = function ($url) use ($host) {
            $parts = wp_parse_url($url);
            if (!$parts) {
                return $url;
            }
            $scheme = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on') ? 'https' : 'http';
            $new = $scheme . '://' . $host;
            if (!empty($parts['path'])) {
                $new .= $parts['path'];
            }
            if (!empty($parts['query'])) {
                $new .= '?' . $parts['query'];
            }
            if (!empty($parts['fragment'])) {
                $new .= '#' . $parts['fragment'];
            }
            return $new;
        };

        add_filter('home_url', $adjust_url, 50, 1);
        add_filter('site_url', $adjust_url, 50, 1);
    }
}

/**
 * Frontend Styles laden
 */
add_action('wp_enqueue_scripts', function() {
// Service Cards CSS für Frontend
    if (file_exists(RIMAN_BLOCKS_DIR . 'assets/service-cards.css')) {
        wp_enqueue_style(
            'riman-service-cards-frontend',
            RIMAN_BLOCKS_URL . 'assets/service-cards.css',
            [],
            filemtime(RIMAN_BLOCKS_DIR . 'assets/service-cards.css')
        );
    }

    // Mobile Slider CSS
    if (file_exists(RIMAN_BLOCKS_DIR . 'assets/service-cards-mobile-slider.css')) {
        wp_enqueue_style(
            'riman-service-cards-mobile-slider',
            RIMAN_BLOCKS_URL . 'assets/service-cards-mobile-slider.css',
            ['riman-service-cards-frontend'],
            filemtime(RIMAN_BLOCKS_DIR . 'assets/service-cards-mobile-slider.css')
        );
    }

    // Video CSS
    if (file_exists(RIMAN_BLOCKS_DIR . 'assets/service-cards-video.css')) {
        wp_enqueue_style(
            'riman-service-cards-video',
            RIMAN_BLOCKS_URL . 'assets/service-cards-video.css',
            ['riman-service-cards-frontend'],
            filemtime(RIMAN_BLOCKS_DIR . 'assets/service-cards-video.css')
        );
    }
});

/**
 * Breadcrumb Styles (Frontend only)
 */
add_action('wp_enqueue_scripts', function () {
    $handle = 'riman-breadcrumbs-style';
    wp_register_style($handle, false, [], '1.0.0');
    wp_enqueue_style($handle);
    $css = '.wp-block-riman-breadcrumbs,.wp-block-shortcode .riman-breadcrumbs{width:100%;position:relative;z-index:5;}
    .riman-breadcrumbs{font-size:.85rem;letter-spacing:.01em;color:#5a5f71;margin:0 auto 1.5rem;padding:0 clamp(1rem,3vw,1.5rem);max-width:var(--wp--style--global--content-size, 1200px);}
    .wp-block-riman-breadcrumbs.alignwide .riman-breadcrumbs,.wp-block-riman-breadcrumbs.alignfull .riman-breadcrumbs{max-width:var(--wp--style--global--wide-size, 1360px);}
    .riman-breadcrumbs__list{margin:0;padding:0;display:flex;flex-wrap:wrap;gap:.45rem;align-items:center;}
    .riman-breadcrumbs__item{display:flex;align-items:center;gap:.35rem;color:inherit;white-space:nowrap;}
    .riman-breadcrumbs__separator{color:#c0c4d1;}
    .riman-breadcrumbs__link{color:rgb(182,140,47);text-decoration:none;font-weight:600;}
    .riman-breadcrumbs__link:hover{text-decoration:underline;}
    .riman-breadcrumbs__current{font-weight:600;color:#2f3142;}
    @media (max-width:600px){.riman-breadcrumbs{font-size:.78rem;}}
    ';
    wp_add_inline_style($handle, $css);
});

/**
 * Meta Boxes laden
 */
riman_require('includes/class-service-cards-overlap.php');

/**
 * Optionale Patterns
 */
riman_require('patterns/page-hero.php');

/**
 * Registriere Block Patterns
 */
add_action('init', function() {
    // Bottom Funnel Service Cards Pattern
    if (function_exists('register_block_pattern')) {
        register_block_pattern('riman/bottom-funnel-service-cards', array(
            'title'       => __('Bottom of Funnel Service Cards', 'riman'),
            'description' => __('Service Cards optimiert für Bottom-of-the-Funnel Conversion', 'riman'),
            'categories'  => array('riman'),
            'content'     => '<!-- wp:group {"align":"full","style":{"spacing":{"padding":{"top":"var:preset|spacing|60","bottom":"var:preset|spacing|60"},"margin":{"top":"var:preset|spacing|60"}}},"backgroundColor":"contrast","layout":{"type":"constrained"}} -->
<div class="wp-block-group alignfull has-contrast-background-color has-background" style="margin-top:var(--wp--preset--spacing--60);padding-top:var(--wp--preset--spacing--60);padding-bottom:var(--wp--preset--spacing--60)">
    <!-- wp:heading {"textAlign":"center","style":{"spacing":{"margin":{"bottom":"var:preset|spacing|50"}}},"textColor":"base","fontSize":"large"} -->
    <h2 class="wp-block-heading has-text-align-center has-base-color has-text-color has-large-font-size" style="margin-bottom:var(--wp--preset--spacing--50)">Wie können wir Ihnen helfen?</h2>
    <!-- /wp:heading -->

    <!-- wp:riman/service-cards {"source":"custom","showDescriptions":true,"shape":"wave","columns":3,"customCards":[{"title":"Kostenlose Erstberatung","description":"Lernen Sie uns in einem unverbindlichen Gespräch kennen und erfahren Sie, wie wir Ihnen helfen können.","url":"/kontakt","image_url":"","video_url":"","icon_class":"fa-solid fa-comments","icon_svg":"","icon_id":0,"category_label":"BERATUNG","button_text":"Jetzt anfragen","show_button":true,"image_id":0,"video_id":0},{"title":"Individuelle Lösung","description":"Jeder Konflikt ist einzigartig. Wir entwickeln maßgeschneiderte Lösungsansätze für Ihre spezielle Situation.","url":"/leistungen","image_url":"","video_url":"","icon_class":"fa-solid fa-puzzle-piece","icon_svg":"","icon_id":0,"category_label":"LÖSUNG","button_text":"Mehr erfahren","show_button":true,"image_id":0,"video_id":0},{"title":"Direkt buchen","description":"Vereinbaren Sie direkt einen Termin für Ihr Anliegen. Schnell, unkompliziert und flexibel.","url":"/termin-buchen","image_url":"","video_url":"","icon_class":"fa-solid fa-calendar-check","icon_svg":"","icon_id":0,"category_label":"TERMIN","button_text":"Termin buchen","show_button":true,"image_id":0,"video_id":0}]} /-->
</div>
<!-- /wp:group -->'
        ));
    }
});

/**
 * Service Cards Editor CSS Fix - RTL Direction Problem
 */
add_action('enqueue_block_editor_assets', function() {
    $css_path = RIMAN_BLOCKS_DIR . 'assets/service-cards-editor-fix.css';
    if (file_exists($css_path)) {
        wp_enqueue_style(
            'riman-service-cards-editor-fix',
            RIMAN_BLOCKS_URL . 'assets/service-cards-editor-fix.css',
            [],
            filemtime($css_path)
        );
    }
});

/**
 * Breadcrumb shortcode + helpers
 */
function riman_is_breadcrumb_context() {
    return is_singular('riman_seiten') || is_singular('riman_bottom_of_the_funnel');
}

function riman_get_breadcrumb_items($post_id = 0) {
    $items = [];
    if (!$post_id) {
        $post_id = get_the_ID();
    }
    $post = get_post($post_id);
    if (!$post) return $items;

    $items[] = [
        'label' => __('Startseite', 'riman'),
        'url'   => home_url('/')
    ];

    $ancestors = array_reverse(get_post_ancestors($post));
    foreach ($ancestors as $ancestor_id) {
        $items[] = [
            'label' => get_the_title($ancestor_id),
            'url'   => get_permalink($ancestor_id)
        ];
    }

    $items[] = [
        'label' => get_the_title($post),
        'url'   => ''
    ];

    return $items;
}

function riman_render_breadcrumbs_markup() {
    if (!riman_is_breadcrumb_context()) {
        return '';
    }
    $items = riman_get_breadcrumb_items();
    if (count($items) < 2) {
        return '';
    }
    $html = '<nav class="riman-breadcrumbs" aria-label="Breadcrumb"><div class="riman-breadcrumbs__list">';
    $last_index = count($items) - 1;
    foreach ($items as $index => $item) {
        $html .= '<span class="riman-breadcrumbs__item">';
        if (!empty($item['url']) && $index !== $last_index) {
            $html .= '<a class="riman-breadcrumbs__link" href="' . esc_url($item['url']) . '">' . esc_html($item['label']) . '</a>';
        } else {
            $html .= '<span class="riman-breadcrumbs__current">' . esc_html($item['label']) . '</span>';
        }
        if ($index !== $last_index) {
            $html .= '<span class="riman-breadcrumbs__separator">›</span>';
        }
        $html .= '</span>';
    }
    $html .= '</div></nav>';
    return $html;
}

add_shortcode('riman_breadcrumbs', function () {
    return riman_render_breadcrumbs_markup();
});

/**
 * Breadcrumb Block registration
 */
add_action('init', function () {
    $script_path = RIMAN_BLOCKS_DIR . 'assets/riman-breadcrumbs-block.js';
    $script_handle = 'riman-breadcrumbs-block';
    if (file_exists($script_path)) {
        wp_register_script(
            $script_handle,
            RIMAN_BLOCKS_URL . 'assets/riman-breadcrumbs-block.js',
            ['wp-blocks', 'wp-element', 'wp-i18n'],
            filemtime($script_path),
            true
        );
    } else {
        $script_handle = null;
    }

    $block_args = [
        'api_version'    => 2,
        'render_callback'=> 'riman_render_breadcrumbs_markup',
        'style'          => 'riman-breadcrumbs-style',
        'supports'       => [ 'align' => false ],
        'title'          => __('RIMAN Breadcrumbs', 'riman'),
    ];

    if ($script_handle) {
        $block_args['editor_script'] = $script_handle;
    }

    register_block_type('riman/breadcrumbs', $block_args);
});

/* Direkte Admin Head CSS Injection */
add_action('admin_head', function() {
    echo '<style>
    /* Service Cards RTL Fix - Direct Admin Injection */
    textarea, input[type="text"] {
        direction: ltr !important;
        text-align: left !important;
    }

    .service-card-hybrid-editor textarea,
    .service-card-hybrid-editor input[type="text"],
    [data-type="riman/service-cards"] textarea,
    [data-type="riman/service-cards"] input[type="text"] {
        direction: ltr !important;
        text-align: left !important;
        unicode-bidi: embed !important;
        font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif !important;
    }
    </style>';
}, 999);
