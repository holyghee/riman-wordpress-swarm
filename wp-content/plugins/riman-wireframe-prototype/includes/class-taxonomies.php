<?php
/**
 * RIMAN Wireframe Prototype - Taxonomies
 * Verwaltet die Registrierung von Custom Taxonomies
 */

// Verhindere direkten Zugriff
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Taxonomies Klasse
 * Registriert Custom Taxonomy 'seitentyp'
 */
class RIMAN_Wireframe_Taxonomies {

    /**
     * Standard-Terms die beim Aktivieren erstellt werden
     */
    private $default_terms = array(
        'hauptseite' => 'Hauptseite',
        'unterseite' => 'Unterseite',
        'detailseite'=> 'Detailseite'
    );

    /**
     * Konstruktor
     */
    public function __construct() {
        add_action('init', array($this, 'register_taxonomy'));
        add_filter('manage_riman_seiten_posts_columns', array($this, 'add_taxonomy_column'));
        add_action('manage_riman_seiten_posts_custom_column', array($this, 'show_taxonomy_column'), 10, 2);
    }

    /**
     * Registriere Custom Taxonomy 'seitentyp'
     */
    public function register_taxonomy() {
        $labels = array(
            'name'              => __('Seitentypen', 'riman-wireframe'),
            'singular_name'     => __('Seitentyp', 'riman-wireframe'),
            'search_items'      => __('Seitentypen suchen', 'riman-wireframe'),
            'all_items'         => __('Alle Seitentypen', 'riman-wireframe'),
            'parent_item'       => null,
            'parent_item_colon' => null,
            'edit_item'         => __('Seitentyp bearbeiten', 'riman-wireframe'),
            'update_item'       => __('Seitentyp aktualisieren', 'riman-wireframe'),
            'add_new_item'      => __('Neuen Seitentyp hinzufügen', 'riman-wireframe'),
            'new_item_name'     => __('Name des neuen Seitentyps', 'riman-wireframe'),
            'menu_name'         => __('Seitentypen', 'riman-wireframe'),
        );

        $args = array(
            'hierarchical'      => false, // Wie Tags, nicht wie Kategorien
            'labels'            => $labels,
            'show_ui'           => true,
            'show_admin_column' => true,
            'query_var'         => true,
            'rewrite'           => array('slug' => 'seitentyp'),
            'show_in_rest'      => true, // Gutenberg Support
            'rest_base'         => 'seitentypen',
        );

        register_taxonomy('seitentyp', array('riman_seiten'), $args);
    }

    /**
     * Füge Seitentyp-Spalte zur Admin-Übersicht hinzu
     */
    public function add_taxonomy_column($columns) {
        $new_columns = array();
        
        foreach ($columns as $key => $value) {
            $new_columns[$key] = $value;
            
            // Füge Seitentyp-Spalte nach Titel hinzu
            if ($key === 'title') {
                $new_columns['seitentyp'] = __('Seitentyp', 'riman-wireframe');
            }
        }
        
        return $new_columns;
    }

    /**
     * Zeige Seitentyp in Admin-Spalte
     */
    public function show_taxonomy_column($column, $post_id) {
        if ($column === 'seitentyp') {
            $terms = wp_get_post_terms($post_id, 'seitentyp');
            
            if (!empty($terms) && !is_wp_error($terms)) {
                $term_names = array();
                foreach ($terms as $term) {
                    $term_names[] = $term->name;
                }
                echo implode(', ', $term_names);
            } else {
                echo '<span style="color: #999;">' . __('Kein Seitentyp', 'riman-wireframe') . '</span>';
            }
        }
    }

    /**
     * Erstelle Standard-Terms bei Plugin-Aktivierung
     */
    public function create_default_terms() {
        foreach ($this->default_terms as $slug => $name) {
            // Prüfe ob Term bereits existiert
            $term = get_term_by('slug', $slug, 'seitentyp');
            
            if (!$term) {
                // Erstelle neuen Term
                $result = wp_insert_term(
                    $name,
                    'seitentyp',
                    array(
                        'slug' => $slug,
                        'description' => sprintf(
                            __('Standard-Seitentyp: %s', 'riman-wireframe'),
                            $name
                        )
                    )
                );
                
                if (is_wp_error($result)) {
                    error_log('RIMAN Wireframe: Fehler beim Erstellen des Terms ' . $slug . ': ' . $result->get_error_message());
                }
            }
        }
    }

    /**
     * Hilfsfunktion: Hole alle verfügbaren Seitentypen
     */
    public static function get_all_page_types() {
        $terms = get_terms(array(
            'taxonomy' => 'seitentyp',
            'hide_empty' => false
        ));
        
        if (is_wp_error($terms)) {
            return array();
        }
        
        return $terms;
    }

    /**
     * Hilfsfunktion: Prüfe ob ein Post einen bestimmten Seitentyp hat
     */
    public static function has_page_type($post_id, $page_type_slug) {
        return has_term($page_type_slug, 'seitentyp', $post_id);
    }

    /**
     * Hilfsfunktion: Hole alle Posts eines bestimmten Seitentyps
     */
    public static function get_posts_by_type($page_type_slug, $args = array()) {
        $default_args = array(
            'post_type' => 'riman_seiten',
            'numberposts' => -1,
            'post_status' => 'publish',
            'tax_query' => array(
                array(
                    'taxonomy' => 'seitentyp',
                    'field'    => 'slug',
                    'terms'    => $page_type_slug,
                ),
            ),
        );
        
        $args = wp_parse_args($args, $default_args);
        
        return get_posts($args);
    }
}
