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
                'thumbnail',
                'page-attributes',
                'custom-fields'
            ),
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
        global $post;

        // Prüfe ob es sich um einen riman_seiten Post handelt
        if ($post && $post->post_type === 'riman_seiten') {
            // Hole Seitentyp
            $seitentyp_terms = wp_get_post_terms($post->ID, 'seitentyp');
            
            if (!empty($seitentyp_terms) && !is_wp_error($seitentyp_terms)) {
                $seitentyp = $seitentyp_terms[0]->slug;
                
                // Suche nach spezifischem Template
                $custom_template = RIMAN_WIREFRAME_PLUGIN_DIR . "templates/single-riman_seiten-{$seitentyp}.php";
                
                if (file_exists($custom_template)) {
                    return $custom_template;
                }
            }
            
            // Fallback: Generisches riman_seiten Template
            $fallback_template = RIMAN_WIREFRAME_PLUGIN_DIR . 'templates/single-riman_seiten.php';
            if (file_exists($fallback_template)) {
                return $fallback_template;
            }
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