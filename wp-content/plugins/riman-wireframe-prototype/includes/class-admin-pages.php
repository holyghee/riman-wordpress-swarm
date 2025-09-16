<?php
/**
 * RIMAN Wireframe Prototype - Admin Pages
 * Verwaltet Admin-Seiten und Übersichten
 */

// Verhindere direkten Zugriff
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Admin Pages Klasse
 * Erstellt Custom Admin-Seiten für Wireframe-Übersicht
 */
class RIMAN_Wireframe_Admin_Pages {

    /**
     * Konstruktor
     */
    public function __construct() {
        add_action('admin_menu', array($this, 'add_admin_pages'));
        add_action('admin_enqueue_scripts', array($this, 'enqueue_admin_styles'));
    }

    /**
     * Füge Admin-Seiten hinzu
     */
    public function add_admin_pages() {
        // Hauptmenü ist bereits durch Post Type erstellt
        // Füge Unterseite für Wireframe-Übersicht hinzu
        add_submenu_page(
            'edit.php?post_type=riman_seiten',
            __('Wireframe Übersicht', 'riman-wireframe'),
            __('Wireframe Übersicht', 'riman-wireframe'),
            'edit_posts',
            'riman-wireframe-overview',
            array($this, 'render_wireframe_overview')
        );

        // Füge Einstellungsseite hinzu
        add_submenu_page(
            'edit.php?post_type=riman_seiten',
            __('Einstellungen', 'riman-wireframe'),
            __('Einstellungen', 'riman-wireframe'),
            'manage_options',
            'riman-wireframe-settings',
            array($this, 'render_settings_page')
        );
    }

    /**
     * Render Wireframe Übersicht
     */
    public function render_wireframe_overview() {
        // Hole alle RIMAN Seiten mit Hierarchie
        $hierarchical_pages = RIMAN_Wireframe_Post_Types::get_hierarchical_pages();
        
        ?>
        <div class="wrap">
            <h1><?php _e('RIMAN Wireframe Übersicht', 'riman-wireframe'); ?></h1>
            
            <div class="riman-wireframe-overview">
                <div class="riman-stats">
                    <div class="riman-stat-box">
                        <h3><?php _e('Statistiken', 'riman-wireframe'); ?></h3>
                        <?php
                        $stats = $this->get_wireframe_stats();
                        foreach ($stats as $label => $count):
                        ?>
                        <div class="stat-item">
                            <span class="stat-label"><?php echo esc_html($label); ?>:</span>
                            <span class="stat-count"><?php echo esc_html($count); ?></span>
                        </div>
                        <?php endforeach; ?>
                    </div>
                </div>

                <div class="riman-structure-tree">
                    <h3><?php _e('Wireframe Struktur', 'riman-wireframe'); ?></h3>
                    
                    <?php if (empty($hierarchical_pages)): ?>
                        <div class="no-pages">
                            <p><?php _e('Noch keine RIMAN Seiten erstellt.', 'riman-wireframe'); ?></p>
                            <a href="<?php echo admin_url('post-new.php?post_type=riman_seiten'); ?>" class="button button-primary">
                                <?php _e('Erste Seite erstellen', 'riman-wireframe'); ?>
                            </a>
                        </div>
                    <?php else: ?>
                        <div class="structure-tree">
                            <?php $this->render_page_tree($hierarchical_pages); ?>
                        </div>
                    <?php endif; ?>
                </div>

                <div class="riman-quick-actions">
                    <h3><?php _e('Schnellaktionen', 'riman-wireframe'); ?></h3>
                    <div class="action-buttons">
                        <a href="<?php echo admin_url('post-new.php?post_type=riman_seiten'); ?>" class="button button-primary">
                            <?php _e('Neue RIMAN Seite', 'riman-wireframe'); ?>
                        </a>
                        <a href="<?php echo admin_url('edit.php?post_type=riman_seiten'); ?>" class="button">
                            <?php _e('Alle Seiten', 'riman-wireframe'); ?>
                        </a>
                        <a href="<?php echo admin_url('edit-tags.php?taxonomy=seitentyp&post_type=riman_seiten'); ?>" class="button">
                            <?php _e('Seitentypen', 'riman-wireframe'); ?>
                        </a>
                        <?php if (current_user_can('manage_options')): ?>
                        <a href="<?php echo admin_url('edit.php?post_type=riman_seiten&page=riman-wireframe-settings'); ?>" class="button">
                            <?php _e('Einstellungen', 'riman-wireframe'); ?>
                        </a>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
        <?php
    }

    /**
     * Render Settings Page
     */
    public function render_settings_page() {
        // Handle form submission
        if (isset($_POST['submit']) && wp_verify_nonce($_POST['riman_wireframe_settings_nonce'], 'save_riman_wireframe_settings')) {
            $this->save_settings();
            echo '<div class="notice notice-success"><p>' . __('Einstellungen gespeichert.', 'riman-wireframe') . '</p></div>';
        }

        $delete_on_deactivate = get_option('riman_wireframe_delete_on_deactivate', false);
        ?>
        <div class="wrap">
            <h1><?php _e('RIMAN Wireframe Einstellungen', 'riman-wireframe'); ?></h1>
            
            <form method="post" action="">
                <?php wp_nonce_field('save_riman_wireframe_settings', 'riman_wireframe_settings_nonce'); ?>
                
                <table class="form-table">
                    <tr>
                        <th scope="row"><?php _e('Plugin-Deaktivierung', 'riman-wireframe'); ?></th>
                        <td>
                            <label>
                                <input type="checkbox" 
                                       name="riman_wireframe_delete_on_deactivate" 
                                       value="1" 
                                       <?php checked($delete_on_deactivate); ?>>
                                <?php _e('Alle Plugin-Daten beim Deaktivieren löschen', 'riman-wireframe'); ?>
                            </label>
                            <p class="description">
                                <?php _e('Achtung: Wenn aktiviert, werden alle RIMAN Seiten und Einstellungen unwiderruflich gelöscht, wenn das Plugin deaktiviert wird.', 'riman-wireframe'); ?>
                            </p>
                        </td>
                    </tr>
                </table>
                
                <?php submit_button(); ?>
            </form>

            <div class="riman-dev-tools">
                <h2><?php _e('Entwickler-Tools', 'riman-wireframe'); ?></h2>
                
                <div class="dev-actions">
                    <p>
                        <a href="<?php echo wp_nonce_url(admin_url('edit.php?post_type=riman_seiten&page=riman-wireframe-settings&action=recreate_sample_content'), 'recreate_sample_content'); ?>" 
                           class="button"
                           onclick="return confirm('<?php esc_attr_e('Sind Sie sicher? Dies wird neuen Sample Content erstellen.', 'riman-wireframe'); ?>')">
                            <?php _e('Sample Content neu erstellen', 'riman-wireframe'); ?>
                        </a>
                    </p>
                    
                    <p>
                        <a href="<?php echo wp_nonce_url(admin_url('edit.php?post_type=riman_seiten&page=riman-wireframe-settings&action=flush_rewrite_rules'), 'flush_rewrite_rules'); ?>" 
                           class="button">
                            <?php _e('Permalink-Struktur neu laden', 'riman-wireframe'); ?>
                        </a>
                    </p>
                </div>
            </div>
        </div>

        <?php
        // Handle dev actions
        if (isset($_GET['action']) && wp_verify_nonce($_GET['_wpnonce'], $_GET['action'])) {
            switch ($_GET['action']) {
                case 'recreate_sample_content':
                    $sample_content = new RIMAN_Wireframe_Sample_Content();
                    $sample_content->create_sample_content();
                    echo '<div class="notice notice-success"><p>' . __('Sample Content neu erstellt.', 'riman-wireframe') . '</p></div>';
                    break;
                    
                case 'flush_rewrite_rules':
                    flush_rewrite_rules();
                    echo '<div class="notice notice-success"><p>' . __('Permalink-Struktur neu geladen.', 'riman-wireframe') . '</p></div>';
                    break;
            }
        }
    }

    /**
     * Speichere Einstellungen
     */
    private function save_settings() {
        $delete_on_deactivate = isset($_POST['riman_wireframe_delete_on_deactivate']);
        update_option('riman_wireframe_delete_on_deactivate', $delete_on_deactivate);
    }

    /**
     * Render Page Tree rekursiv
     */
    private function render_page_tree($pages, $level = 0) {
        if (empty($pages)) return;

        echo '<ul class="page-tree level-' . esc_attr($level) . '">';
        
        foreach ($pages as $page) {
            $seitentyp_terms = wp_get_post_terms($page->ID, 'seitentyp');
            $seitentyp = '';
            $seitentyp_class = '';
            
            if (!empty($seitentyp_terms) && !is_wp_error($seitentyp_terms)) {
                $seitentyp = $seitentyp_terms[0]->name;
                $seitentyp_class = 'seitentyp-' . $seitentyp_terms[0]->slug;
            }

            echo '<li class="page-item ' . esc_attr($seitentyp_class) . '">';
            echo '<div class="page-info">';
            echo '<span class="page-title">' . esc_html($page->post_title) . '</span>';
            
            if ($seitentyp) {
                echo '<span class="page-type">' . esc_html($seitentyp) . '</span>';
            }
            
            echo '<div class="page-actions">';
            echo '<a href="' . get_edit_post_link($page->ID) . '" class="action-edit">' . __('Bearbeiten', 'riman-wireframe') . '</a>';
            echo '<a href="' . get_permalink($page->ID) . '" class="action-view" target="_blank">' . __('Ansehen', 'riman-wireframe') . '</a>';
            echo '</div>';
            echo '</div>';
            
            // Render children recursively
            if (!empty($page->children)) {
                $this->render_page_tree($page->children, $level + 1);
            }
            
            echo '</li>';
        }
        
        echo '</ul>';
    }

    /**
     * Hole Wireframe-Statistiken
     */
    private function get_wireframe_stats() {
        $stats = array();
        
        // Gesamtzahl der Seiten
        $total_pages = wp_count_posts('riman_seiten');
        $stats[__('Gesamt Seiten', 'riman-wireframe')] = $total_pages->publish;
        
        // Seiten pro Typ
        $page_types = RIMAN_Wireframe_Taxonomies::get_all_page_types();
        foreach ($page_types as $type) {
            $type_posts = RIMAN_Wireframe_Taxonomies::get_posts_by_type($type->slug);
            $stats[$type->name] = count($type_posts);
        }
        
        return $stats;
    }

    /**
     * Lade Admin-Styles
     */
    public function enqueue_admin_styles($hook) {
        // Nur auf unseren Admin-Seiten laden
        if (strpos($hook, 'riman-wireframe') !== false || 
            (isset($_GET['post_type']) && $_GET['post_type'] === 'riman_seiten')) {
            
            wp_enqueue_style(
                'riman-wireframe-admin',
                RIMAN_WIREFRAME_PLUGIN_URL . 'admin/admin-styles.css',
                array(),
                RIMAN_WIREFRAME_VERSION
            );
        }
    }
}