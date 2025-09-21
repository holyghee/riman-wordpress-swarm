<?php
/**
 * RIMAN Wireframe Prototype - Post Types
 * Verwaltet die Registrierung von Custom Post Types
 */

// Verhindere direkten Zugriff
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Post Types Klasse
 * Registriert Custom Post Type 'riman_seiten'
 */
class RIMAN_Wireframe_Post_Types {

    /**
     * Konstruktor
     */
    public function __construct() {
        add_action('init', array($this, 'register_post_type'));
        add_filter('template_include', array($this, 'load_custom_templates'));
    }

    /**
     * Registriere Custom Post Type 'riman_seiten'
     */
    public function register_post_type() {
        $labels = array(
            'name'               => __('RIMAN Seiten', 'riman-wireframe'),
            'singular_name'      => __('RIMAN Seite', 'riman-wireframe'),
            'menu_name'          => __('RIMAN Seiten', 'riman-wireframe'),
            'name_admin_bar'     => __('RIMAN Seite', 'riman-wireframe'),
            'add_new'            => __('Neu hinzufügen', 'riman-wireframe'),
            'add_new_item'       => __('Neue RIMAN Seite hinzufügen', 'riman-wireframe'),
            'new_item'           => __('Neue RIMAN Seite', 'riman-wireframe'),
            'edit_item'          => __('RIMAN Seite bearbeiten', 'riman-wireframe'),
            'view_item'          => __('RIMAN Seite ansehen', 'riman-wireframe'),
            'all_items'          => __('Alle RIMAN Seiten', 'riman-wireframe'),
            'search_items'       => __('RIMAN Seiten suchen', 'riman-wireframe'),
            'parent_item_colon'  => __('Übergeordnete RIMAN Seite:', 'riman-wireframe'),
            'not_found'          => __('Keine RIMAN Seiten gefunden.', 'riman-wireframe'),
            'not_found_in_trash' => __('Keine RIMAN Seiten im Papierkorb gefunden.', 'riman-wireframe'),
        );

        $args = array(
            'labels'              => $labels,
            'public'              => true,
            'publicly_queryable'  => true,
            'show_ui'             => true,
            'show_in_menu'        => true,
            'query_var'           => true,
            'rewrite'             => array('slug' => 'riman-seiten'),
            'capability_type'     => 'page',
            'has_archive'         => true,
            'hierarchical'        => true,
            'menu_position'       => 5,
            'menu_icon'           => 'dashicons-networking',
            'supports'            => array(
                'title',
                'editor',
                'excerpt',
                'author',
                'revisions',
                'thumbnail',
                'page-attributes',
                'custom-fields'
            ),
            'taxonomies'          => array('category','post_tag'),
            'show_in_rest'        => true, // Gutenberg Support
            'rest_base'           => 'riman-seiten',
            'description'         => __('Wireframe Prototyp Seiten für neue Website-Struktur', 'riman-wireframe'),
        );

        register_post_type('riman_seiten', $args);
    }

    /**
     * Lade Custom Templates für Frontend
     */
    public function load_custom_templates($template) {
        // Bei Block-Themes (z. B. Twenty Twenty‑Five) NICHT eingreifen,
        // damit die Site-Editor-Templates (HTML) verwendet und bearbeitet werden können.
        if (function_exists('wp_is_block_theme') && wp_is_block_theme()) {
            return $template;
        }
        // Archive des CPT auf Theme-Archiv leiten (Beitragsoptik)
        if (is_post_type_archive('riman_seiten')) {
            $t = locate_template(['archive.php','home.php','index.php']);
            if ($t) return $t;
            $fallback = RIMAN_WIREFRAME_PLUGIN_DIR . 'templates/archive-riman_seiten.php';
            if (file_exists($fallback)) return $fallback;
        }
        // Taxonomie-Archiv (seitentyp) → wie Kategorie-Archiv
        if (is_tax('seitentyp')) {
            $t = locate_template(['taxonomy.php','category.php','archive.php','index.php']);
            if ($t) return $t;
            $fallback = RIMAN_WIREFRAME_PLUGIN_DIR . 'templates/taxonomy-seitentyp.php';
            if (file_exists($fallback)) return $fallback;
        }
        // Single riman_seiten → Theme-Single bevorzugen
        if (is_singular('riman_seiten')) {
            $t = locate_template(['single.php','singular.php','single-post.php','page.php','index.php']);
            if ($t) return $t;
            // Fallback auf unsere spezifischen Templates
            global $post;
            if ($post) {
                $terms = wp_get_post_terms($post->ID, 'seitentyp');
                if (!empty($terms) && !is_wp_error($terms)) {
                    $slug = $terms[0]->slug;
                    $custom = RIMAN_WIREFRAME_PLUGIN_DIR . "templates/single-riman_seiten-{$slug}.php";
                    if (file_exists($custom)) return $custom;
                }
            }
            $fallback = RIMAN_WIREFRAME_PLUGIN_DIR . 'templates/single-riman_seiten.php';
            if (file_exists($fallback)) return $fallback;
        }
        return $template;
    }

    /**
     * Hilfsfunktion: Hole alle RIMAN Seiten mit Hierarchie
     */
    public static function get_hierarchical_pages() {
        $pages = get_posts(array(
            'post_type' => 'riman_seiten',
            'numberposts' => -1,
            'post_status' => 'publish',
            'orderby' => 'menu_order',
            'order' => 'ASC'
        ));

        return self::build_hierarchy($pages);
    }

    /**
     * Baue Hierarchie aus flacher Post-Liste
     */
    private static function build_hierarchy($posts, $parent_id = 0) {
        $hierarchy = array();
        
        foreach ($posts as $post) {
            if ($post->post_parent == $parent_id) {
                $post->children = self::build_hierarchy($posts, $post->ID);
                $hierarchy[] = $post;
            }
        }
        
        return $hierarchy;
    }

    /**
     * Hole Seitentyp eines Posts
     */
    public static function get_page_type($post_id) {
        $terms = wp_get_post_terms($post_id, 'seitentyp');
        
        if (!empty($terms) && !is_wp_error($terms)) {
            return $terms[0]->slug;
        }
        
        return '';
    }
}
