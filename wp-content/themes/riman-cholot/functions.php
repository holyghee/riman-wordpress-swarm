<?php
/**
 * RIMAN Cholot Theme Functions
 */

if (!defined('ABSPATH')) exit;

function riman_cholot_setup() {
    add_theme_support('post-thumbnails');
    add_theme_support('title-tag');
    add_theme_support('custom-logo');
    add_theme_support('responsive-embeds');
    add_theme_support('html5', array('comment-list', 'comment-form', 'search-form', 'gallery', 'caption'));
    
    register_nav_menus(array(
        'primary' => __('Primary Menu', 'riman-cholot'),
        'footer' => __('Footer Menu', 'riman-cholot'),
    ));
}
add_action('after_setup_theme', 'riman_cholot_setup');

// Fix menu title encoding
function riman_fix_menu_title($title, $item = null, $args = null, $depth = 0) {
    $title = html_entity_decode($title, ENT_QUOTES | ENT_HTML5, 'UTF-8');
    $title = str_replace('&amp;', '&', $title);
    $title = str_replace('&amp;amp;', '&', $title);
    return $title;
}
add_filter('nav_menu_item_title', 'riman_fix_menu_title', 10, 4);
add_filter('the_title', 'riman_fix_menu_title', 10, 2);

// Also fix walker output
function riman_fix_walker_output($item_output, $item, $depth, $args) {
    $item_output = str_replace('&amp;amp;', '&', $item_output);
    $item_output = str_replace('&amp;', '&', $item_output);
    return $item_output;
}
add_filter('walker_nav_menu_start_el', 'riman_fix_walker_output', 10, 4);

function riman_cholot_styles() {
    wp_enqueue_style('parent-style', get_template_directory_uri() . '/style.css');
    wp_enqueue_style('riman-cholot-style', get_stylesheet_uri(), array('parent-style'), '1.0.0');
    wp_enqueue_style('riman-cholot-custom', get_stylesheet_directory_uri() . '/assets/css/cholot.css', array(), '1.0.0');
}
add_action('wp_enqueue_scripts', 'riman_cholot_styles');

function riman_cholot_scripts() {
    wp_enqueue_script('riman-cholot-navigation', get_stylesheet_directory_uri() . '/assets/js/navigation.js', array('jquery'), '1.0.0', true);
}
add_action('wp_enqueue_scripts', 'riman_cholot_scripts');

function riman_create_service_post_type() {
    register_post_type('service',
        array(
            'labels' => array(
                'name' => __('Services'),
                'singular_name' => __('Service')
            ),
            'public' => true,
            'has_archive' => true,
            'hierarchical' => true,
            'supports' => array('title', 'editor', 'thumbnail', 'page-attributes', 'excerpt'),
            'rewrite' => array('slug' => 'leistungen'),
        )
    );
}
add_action('init', 'riman_create_service_post_type');

// Lade Content aus dem Codex
function riman_get_codex_content($service, $file = 'main.md') {
    $base_path = '/Users/holgerbrandt/dev/claude-code/projects/riman-content/riman-website-codex/content/services/';
    
    // Mapping der Kategorien zu Codex-Ordnern
    $mapping = array(
        'altlasten' => 'altlastensanierung',
        'rueckbau' => 'rueckbaumanagement',
        'schadstoffe' => 'schadstoff-management',
        'sicherheit' => 'sicherheitskoordination',
        'beratung' => 'beratung-mediation'
    );
    
    $folder = isset($mapping[$service]) ? $mapping[$service] : $service;
    $file_path = $base_path . $folder . '/' . $file;
    
    if (file_exists($file_path)) {
        $content = file_get_contents($file_path);
        
        // Entferne Meta-Informationen
        $content = preg_replace('/\*\*Meta Description:\*\*.*?\n/s', '', $content);
        $content = preg_replace('/\*\*SEO Keywords:\*\*.*?\n/s', '', $content);
        $content = preg_replace('/---\n/s', '', $content);
        
        // Parse Markdown mit Parsedown (falls verfügbar) oder einfache Konvertierung
        if (class_exists('Parsedown')) {
            $Parsedown = new Parsedown();
            return $Parsedown->text($content);
        }
        
        // Einfache Markdown zu HTML Konvertierung
        $content = preg_replace('/^# (.+)$/m', '<h1>$1</h1>', $content);
        $content = preg_replace('/^## (.+)$/m', '<h2>$1</h2>', $content);
        $content = preg_replace('/^### (.+)$/m', '<h3>$1</h3>', $content);
        $content = preg_replace('/\*\*(.+?)\*\*/s', '<strong>$1</strong>', $content);
        $content = preg_replace('/^- (.+)$/m', '<li>$1</li>', $content);
        $content = str_replace("\n\n", '</p><p>', $content);
        $content = '<p>' . $content . '</p>';
        $content = preg_replace('/<p><h/', '<h', $content);
        $content = preg_replace('/<\/h(\d)><\/p>/', '</h$1>', $content);
        $content = preg_replace('/<p><li>/', '<ul><li>', $content);
        $content = preg_replace('/<\/li><\/p>/', '</li></ul>', $content);
        
        return $content;
    }
    
    return false;
}

// Hole Unterkategorien aus dem Codex
function riman_get_codex_subcategories($service) {
    $base_path = '/Users/holgerbrandt/dev/claude-code/projects/riman-content/riman-website-codex/content/services/';
    
    $mapping = array(
        'altlasten' => 'altlastensanierung',
        'rueckbau' => 'rueckbaumanagement',
        'schadstoffe' => 'schadstoff-management',
        'sicherheit' => 'sicherheitskoordination',
        'beratung' => 'beratung-mediation'
    );
    
    $folder = isset($mapping[$service]) ? $mapping[$service] : $service;
    $dir_path = $base_path . $folder . '/';
    
    $subcategories = array();
    
    if (is_dir($dir_path)) {
        // Suche nach .md Dateien (außer main.md und leistungen.md)
        $files = glob($dir_path . '*.md');
        foreach ($files as $file) {
            $filename = basename($file, '.md');
            if ($filename !== 'main' && $filename !== 'leistungen') {
                // Lade erste Zeilen der Datei für Titel und Beschreibung
                $content = file_get_contents($file);
                $lines = explode("\n", $content);
                
                // Extrahiere Titel aus der ersten Überschrift
                $title = $filename;
                foreach ($lines as $line) {
                    if (preg_match('/^#\s+(.+)$/', $line, $matches)) {
                        $title = $matches[1];
                        break;
                    }
                }
                
                // Extrahiere Beschreibung
                $description = '';
                $in_content = false;
                foreach ($lines as $line) {
                    if ($line === '---') {
                        $in_content = !$in_content;
                        continue;
                    }
                    if ($in_content && !empty(trim($line)) && strpos($line, '#') !== 0) {
                        $description = trim($line);
                        break;
                    }
                }
                
                $subcategories[] = array(
                    'slug' => $filename,
                    'title' => $title,
                    'description' => $description
                );
            }
        }
    }
    
    return $subcategories;
}

function riman_breadcrumb() {
    if (!is_home()) {
        echo '<div class="riman-breadcrumb">';
        echo '<a href="' . home_url() . '">Start</a>';
        echo '<span class="separator">›</span>';
        
        if (is_page()) {
            $ancestors = get_post_ancestors(get_the_ID());
            $ancestors = array_reverse($ancestors);
            
            foreach ($ancestors as $ancestor) {
                echo '<a href="' . get_permalink($ancestor) . '">' . get_the_title($ancestor) . '</a>';
                echo '<span class="separator">›</span>';
            }
            echo '<span>' . get_the_title() . '</span>';
        }
        
        if (is_category()) {
            $category = get_queried_object();
            if ($category->parent) {
                $parent = get_category($category->parent);
                echo '<a href="' . get_category_link($parent->term_id) . '">' . $parent->name . '</a>';
                echo '<span class="separator">›</span>';
            }
            echo '<span>' . single_cat_title('', false) . '</span>';
        }
        
        echo '</div>';
    }
}

function riman_import_initial_pages() {
    if (get_option('riman_pages_imported')) {
        return;
    }
    
    $services = array(
        'rueckbau' => array(
            'title' => 'Rückbau & Abbruch',
            'slug' => 'rueckbau',
            'description' => 'Professioneller Rückbau und Abbruch von Gebäuden und Industrieanlagen',
            'sub_services' => array(
                'Gebäuderückbau' => 'Kompletter Rückbau von Wohn- und Geschäftsgebäuden',
                'Industrierückbau' => 'Spezialisierter Rückbau von Industrieanlagen',
                'Teilrückbau' => 'Selektiver Rückbau einzelner Gebäudeteile',
                'Entkernung' => 'Professionelle Entkernung bei Erhalt der Fassade',
                'Schadstoffrückbau' => 'Sicherer Rückbau kontaminierter Strukturen',
                'Recycling' => 'Nachhaltige Verwertung von Rückbaumaterialien'
            )
        ),
        'altlasten' => array(
            'title' => 'Altlastensanierung',
            'slug' => 'altlasten',
            'description' => 'Fachgerechte Sanierung kontaminierter Böden und Grundwässer',
            'sub_services' => array(
                'Bodensanierung' => 'Dekontamination und Aufbereitung belasteter Böden',
                'Grundwassersanierung' => 'Reinigung kontaminierter Grundwässer',
                'Tankstellensanierung' => 'Spezialsanierung ehemaliger Tankstellen',
                'Deponiesanierung' => 'Sanierung und Sicherung von Altdeponien',
                'In-Situ-Sanierung' => 'Vor-Ort-Sanierung ohne Bodenaushub'
            )
        ),
        'schadstoffe' => array(
            'title' => 'Schadstoffsanierung',
            'slug' => 'schadstoffe',
            'description' => 'Sichere Entfernung und Entsorgung von Schadstoffen',
            'sub_services' => array(
                'Asbestsanierung' => 'Fachgerechte Entfernung von Asbest',
                'PCB-Sanierung' => 'Sanierung PCB-belasteter Materialien',
                'PAK-Sanierung' => 'Entfernung PAK-haltiger Stoffe',
                'Schimmelsanierung' => 'Beseitigung von Schimmelbefall',
                'KMF-Sanierung' => 'Entfernung künstlicher Mineralfasern'
            )
        ),
        'sicherheit' => array(
            'title' => 'Arbeitssicherheit',
            'slug' => 'sicherheit',
            'description' => 'Umfassende Sicherheitskonzepte für Baustellen',
            'sub_services' => array(
                'SiGe-Koordination' => 'Sicherheits- und Gesundheitsschutzkoordination',
                'Gefährdungsbeurteilung' => 'Erstellung von Gefährdungsbeurteilungen',
                'Sicherheitskonzepte' => 'Entwicklung maßgeschneiderter Sicherheitskonzepte',
                'Schulungen' => 'Sicherheitsschulungen für Mitarbeiter',
                'Notfallmanagement' => 'Planung und Umsetzung von Notfallkonzepten'
            )
        ),
        'beratung' => array(
            'title' => 'Beratung & Mediation',
            'slug' => 'beratung',
            'description' => 'Expertberatung und Konfliktlösung im Bauwesen',
            'sub_services' => array(
                'Bauberatung' => 'Umfassende Beratung bei Bauprojekten',
                'Baumediation' => 'Konfliktlösung zwischen Bauparteien',
                'Gutachten' => 'Erstellung unabhängiger Gutachten',
                'Projektmanagement' => 'Professionelles Baustellenmanagement',
                'Umweltberatung' => 'Beratung zu Umweltschutz und Nachhaltigkeit'
            )
        )
    );
    
    foreach ($services as $key => $service) {
        $parent_id = wp_insert_post(array(
            'post_title' => $service['title'],
            'post_name' => $service['slug'],
            'post_content' => $service['description'],
            'post_status' => 'publish',
            'post_type' => 'page',
            'menu_order' => 0,
        ));
        
        if ($parent_id && !is_wp_error($parent_id)) {
            update_post_meta($parent_id, '_wp_page_template', 'page-service.html');
            
            $order = 0;
            foreach ($service['sub_services'] as $sub_title => $sub_desc) {
                $sub_id = wp_insert_post(array(
                    'post_title' => $sub_title,
                    'post_content' => $sub_desc,
                    'post_status' => 'publish',
                    'post_type' => 'page',
                    'post_parent' => $parent_id,
                    'menu_order' => $order++,
                ));
                
                if ($sub_id && !is_wp_error($sub_id)) {
                    for ($i = 1; $i <= 4; $i++) {
                        wp_insert_post(array(
                            'post_title' => $sub_title . ' - Detail ' . $i,
                            'post_content' => 'Detaillierte Informationen zu ' . $sub_title . ' - Bereich ' . $i,
                            'post_status' => 'publish',
                            'post_type' => 'page',
                            'post_parent' => $sub_id,
                            'menu_order' => $i,
                        ));
                    }
                }
            }
        }
    }
    
    $homepage_content = '
    <!-- wp:group {"layout":{"type":"constrained"}} -->
    <div class="wp-block-group">
        <h1>Willkommen bei RIMAN GmbH</h1>
        <p>Ihr Partner für nachhaltigen Rückbau, Sanierung und Sicherheit im Bauwesen.</p>
    </div>
    <!-- /wp:group -->
    
    <!-- wp:group {"className":"riman-service-grid"} -->
    <div class="wp-block-group riman-service-grid">
        <!-- Service cards will be dynamically inserted here -->
    </div>
    <!-- /wp:group -->
    ';
    
    $homepage_id = wp_insert_post(array(
        'post_title' => 'Startseite',
        'post_content' => $homepage_content,
        'post_status' => 'publish',
        'post_type' => 'page',
    ));
    
    if ($homepage_id && !is_wp_error($homepage_id)) {
        update_option('page_on_front', $homepage_id);
        update_option('show_on_front', 'page');
    }
    
    update_option('riman_pages_imported', true);
}
add_action('init', 'riman_import_initial_pages', 20);

function riman_register_block_patterns() {
    register_block_pattern(
        'riman/service-card',
        array(
            'title' => __('Service Card', 'riman-cholot'),
            'description' => _x('A service card with Cholot design', 'Block pattern description', 'riman-cholot'),
            'content' => '
            <!-- wp:group {"className":"riman-service-card"} -->
            <div class="wp-block-group riman-service-card">
                <div class="riman-card-image">
                    <!-- wp:image -->
                    <figure class="wp-block-image"><img src="" alt=""/></figure>
                    <!-- /wp:image -->
                    <div class="riman-play-button"></div>
                </div>
                <div class="riman-card-icon">
                    <svg xmlns="http://www.w3.org/2000/svg" fill="currentColor" viewBox="0 0 24 24">
                        <path d="M12 2L2 7v10c0 5.55 3.84 10.74 9 12 5.16-1.26 9-6.45 9-12V7l-10-5z"/>
                    </svg>
                </div>
                <div class="riman-card-content">
                    <h3 class="riman-card-title">Service Title</h3>
                    <p class="riman-card-description">Service description goes here</p>
                </div>
            </div>
            <!-- /wp:group -->
            ',
        )
    );
}
add_action('init', 'riman_register_block_patterns');

function riman_activate_theme() {
    flush_rewrite_rules();
}
add_action('after_switch_theme', 'riman_activate_theme');
