<?php
/**
 * Plugin Name: RIMAN Wireframe Prototype
 * Plugin URI: https://riman.de
 * Description: Prototyp für eine neue Website-Struktur basierend auf einem Wireframe, ohne die bestehende WordPress-Installation zu beeinträchtigen.
 * Version: 1.0.0
 * Author: RIMAN
 * Author URI: https://riman.de
 * License: GPL v2 or later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain: riman-wireframe
 * Domain Path: /languages
 * Requires at least: 5.0
 * Tested up to: 6.3
 * Requires PHP: 7.4
 */

// Verhindere direkten Zugriff
if (!defined('ABSPATH')) {
    exit;
}

// Plugin-Konstanten definieren
define('RIMAN_WIREFRAME_VERSION', '1.0.0');
define('RIMAN_WIREFRAME_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('RIMAN_WIREFRAME_PLUGIN_URL', plugin_dir_url(__FILE__));
define('RIMAN_WIREFRAME_PLUGIN_FILE', __FILE__);

/**
 * Haupt-Plugin-Klasse
 * Verwaltet die Initialisierung aller Plugin-Komponenten
 */
class RIMAN_Wireframe_Prototype {

    /**
     * Singleton-Instanz
     */
    private static $instance = null;

    /**
     * Plugin-Komponenten
     */
    public $post_types;
    public $taxonomies;
    public $meta_boxes;
    public $admin_pages;
    public $sample_content;

    /**
     * Singleton Pattern - Gibt einzige Instanz zurück
     */
    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    /**
     * Konstruktor - Initialisiert Plugin
     */
    private function __construct() {
        add_action('init', array($this, 'init'));
        add_action('plugins_loaded', array($this, 'load_textdomain'));
        
        // Plugin Aktivierung/Deaktivierung
        register_activation_hook(__FILE__, array($this, 'activate'));
        register_deactivation_hook(__FILE__, array($this, 'deactivate'));
    }

    /**
     * Plugin-Initialisierung
     * Lädt alle Klassen und startet Plugin-Komponenten
     */
    public function init() {
        // Lade Plugin-Klassen
        $this->load_dependencies();
        
        // Initialisiere Komponenten
        $this->post_types = new RIMAN_Wireframe_Post_Types();
        $this->taxonomies = new RIMAN_Wireframe_Taxonomies();
        $this->meta_boxes = new RIMAN_Wireframe_Meta_Boxes();
        $this->admin_pages = new RIMAN_Wireframe_Admin_Pages();
        // Featured video metabox for riman_seiten
        require_once RIMAN_WIREFRAME_PLUGIN_DIR . 'includes/class-featured-video.php';
        new RIMAN_Wireframe_Featured_Video();
        $this->sample_content = new RIMAN_Wireframe_Sample_Content();
        
        // Manuell Post Types und Taxonomies registrieren
        $this->post_types->register_post_type();
        $this->taxonomies->register_taxonomy();
    }

    /**
     * Lade alle Plugin-Abhängigkeiten
     */
    private function load_dependencies() {
        require_once RIMAN_WIREFRAME_PLUGIN_DIR . 'includes/class-post-types.php';
        require_once RIMAN_WIREFRAME_PLUGIN_DIR . 'includes/class-taxonomies.php';
        require_once RIMAN_WIREFRAME_PLUGIN_DIR . 'includes/class-meta-boxes.php';
        require_once RIMAN_WIREFRAME_PLUGIN_DIR . 'includes/class-admin-pages.php';
        require_once RIMAN_WIREFRAME_PLUGIN_DIR . 'includes/class-sample-content.php';
    }

    /**
     * Lade Übersetzungen
     */
    public function load_textdomain() {
        load_plugin_textdomain(
            'riman-wireframe',
            false,
            dirname(plugin_basename(__FILE__)) . '/languages'
        );
    }

    /**
     * Plugin-Aktivierung
     * Erstellt Standard-Terms und Sample Content
     */
    public function activate() {
        // Lade Abhängigkeiten für Aktivierung
        $this->load_dependencies();
        
        // Initialisiere Komponenten
        $post_types = new RIMAN_Wireframe_Post_Types();
        $taxonomies = new RIMAN_Wireframe_Taxonomies();
        $sample_content = new RIMAN_Wireframe_Sample_Content();
        
        // Registriere Post Types und Taxonomies
        $post_types->register_post_type();
        $taxonomies->register_taxonomy();
        
        // Flush Rewrite Rules für saubere URLs
        flush_rewrite_rules();
        
        // Erstelle Standard-Terms
        $taxonomies->create_default_terms();
        
        // Erstelle Sample Content
        $sample_content->create_sample_content();
        
        // Plugin erfolgreich aktiviert - setze Flag
        add_option('riman_wireframe_activated', time());
    }

    /**
     * Plugin-Deaktivierung
     * Optional: Lösche alle Plugin-Daten
     */
    public function deactivate() {
        // Flush Rewrite Rules
        flush_rewrite_rules();
        
        // Optional: Lösche alle Plugin-Daten (nur wenn Option gesetzt)
        if (get_option('riman_wireframe_delete_on_deactivate', false)) {
            $this->cleanup_plugin_data();
        }
        
        // Entferne Aktivierungs-Flag
        delete_option('riman_wireframe_activated');
    }

    /**
     * Lösche alle Plugin-Daten bei Deaktivierung
     */
    private function cleanup_plugin_data() {
        global $wpdb;
        
        // Lösche alle riman_seiten Posts
        $posts = get_posts(array(
            'post_type' => 'riman_seiten',
            'numberposts' => -1,
            'post_status' => 'any'
        ));
        
        foreach ($posts as $post) {
            wp_delete_post($post->ID, true);
        }
        
        // Lösche Taxonomy Terms
        $terms = get_terms(array(
            'taxonomy' => 'seitentyp',
            'hide_empty' => false
        ));
        
        if (!is_wp_error($terms)) {
            foreach ($terms as $term) {
                wp_delete_term($term->term_id, 'seitentyp');
            }
        }
        
        // Lösche Plugin-Optionen
        delete_option('riman_wireframe_version');
        delete_option('riman_wireframe_delete_on_deactivate');
    }

    /**
     * Hilfsfunktion: Prüfe ob Plugin korrekt aktiviert wurde
     */
    public static function is_activated() {
        return get_option('riman_wireframe_activated', false) !== false;
    }
}

/**
 * Plugin starten
 * Gibt Hauptinstanz zurück für externe Zugriffe
 */
function RIMAN_Wireframe_Prototype() {
    return RIMAN_Wireframe_Prototype::get_instance();
}

// Plugin initialisieren
RIMAN_Wireframe_Prototype();
