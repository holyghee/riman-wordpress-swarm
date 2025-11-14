<?php
/**
 * Plugin Name: Auto-Populate Navigation (Drag & Drop)
 * Description: Fügt automatisch Unterkategorien und Posts zu Kategorie-Links hinzu mit Drag & Drop Sortierung
 * Version: 4.0
 * Author: RIMAN GmbH
 */

if (!defined('ABSPATH')) exit;

class Auto_Populate_Navigation_DragDrop {
    
    public function __construct() {
        // Hook für Navigation Block Rendering
        add_filter('render_block_core/navigation-link', array($this, 'check_category_link'), 10, 3);
        add_filter('render_block_core/navigation-submenu', array($this, 'check_category_submenu'), 10, 3);

        // RIMAN Seiten Support aktiviert
        add_filter('render_block_core/navigation-link', array($this, 'check_riman_link'), 10, 3);
        
        // Admin-Menü
        add_action('admin_menu', array($this, 'add_admin_menu'));
        
        // Admin Scripts und Styles
        add_action('admin_enqueue_scripts', array($this, 'admin_scripts'));
        
        // AJAX Handler für Sortierung
        add_action('wp_ajax_save_category_order', array($this, 'save_category_order'));
        
        // Frontend Styles
        add_action('wp_head', array($this, 'add_navigation_styles'));

        // Frontend Scripts
        add_action('wp_footer', array($this, 'add_navigation_scripts'));

        // Header/Hero sync helper
        add_action('wp_footer', array($this, 'inject_hero_detector'));


        // Style klassische WP Seiten Submenus wie RIMAN Dropdowns
        add_filter('render_block_core/navigation-link', array($this, 'decorate_wp_page_link'), 15, 3);
    }
    
    /**
     * Fügt Admin-Menü hinzu
     */
    public function add_admin_menu() {
        add_menu_page(
            'Navigation Sortierung',
            'Navigation Sortierung',
            'manage_options',
            'navigation-sorting-dragdrop',
            array($this, 'admin_page'),
            'dashicons-sort',
            30
        );
    }
    
    /**
     * Admin Scripts und Styles
     */
    public function admin_scripts($hook) {
        if ($hook !== 'toplevel_page_navigation-sorting-dragdrop') {
            return;
        }
        
        // jQuery UI für Sortable
        wp_enqueue_script('jquery-ui-sortable');
        
        // Eigene Admin Scripts
        wp_add_inline_script('jquery-ui-sortable', $this->get_admin_javascript());
        
        // Admin Styles
        wp_add_inline_style('wp-admin', $this->get_admin_css());
    }
    
    /**
     * Admin CSS
     */
    private function get_admin_css() {
        return '
            .sortable-container {
                background: #fff;
                border: 1px solid #ccd0d4;
                border-radius: 4px;
                margin: 20px 0;
                padding: 0;
                max-width: 800px;
            }
            
            .sortable-header {
                background: #f1f3f4;
                padding: 15px 20px;
                border-bottom: 1px solid #ccd0d4;
                font-weight: 600;
                font-size: 14px;
            }
            
            .sortable-list {
                list-style: none;
                margin: 0;
                padding: 10px;
                min-height: 50px;
            }
            
            .sortable-item {
                background: #f8f9fa;
                border: 1px solid #ddd;
                border-radius: 4px;
                padding: 12px 15px;
                margin: 8px 0;
                cursor: move;
                display: flex;
                align-items: center;
                transition: all 0.2s;
            }
            
            .sortable-item:hover {
                background: #fff;
                box-shadow: 0 2px 4px rgba(0,0,0,0.1);
            }
            
            .sortable-item.ui-sortable-helper {
                background: #007cba;
                color: #fff;
                box-shadow: 0 4px 8px rgba(0,0,0,0.2);
            }
            
            .sortable-item.sortable-placeholder {
                background: #f0f0f1;
                border: 2px dashed #aaa;
                visibility: visible !important;
            }
            
            .drag-handle {
                margin-right: 12px;
                color: #666;
                font-size: 20px;
            }
            
            .category-name {
                flex: 1;
                font-size: 14px;
                font-weight: 500;
            }
            
            .category-slug {
                color: #666;
                font-size: 12px;
                font-family: monospace;
                background: #e8e8e8;
                padding: 2px 6px;
                border-radius: 3px;
            }
            
            /* Unterkategorien */
            .subcategories-container {
                margin-left: 40px;
                margin-top: 10px;
                padding: 10px;
                background: rgba(0,124,186,0.05);
                border-radius: 4px;
                border-left: 3px solid #007cba;
            }
            
            .subcategories-title {
                font-size: 12px;
                color: #666;
                margin-bottom: 8px;
                font-weight: 600;
                text-transform: uppercase;
                letter-spacing: 0.5px;
            }
            
            .sortable-list.subcategories .sortable-item {
                background: #fff;
                font-size: 13px;
            }
            
            /* Buttons */
            .save-order-btn {
                background: #007cba;
                color: #fff;
                border: none;
                padding: 10px 20px;
                font-size: 14px;
                border-radius: 4px;
                cursor: pointer;
                margin-top: 20px;
            }
            
            .save-order-btn:hover {
                background: #005a87;
            }
            
            .save-order-btn:disabled {
                background: #ccc;
                cursor: not-allowed;
            }
            
            .success-message {
                background: #d4edda;
                border: 1px solid #c3e6cb;
                color: #155724;
                padding: 12px 20px;
                border-radius: 4px;
                margin: 20px 0;
                max-width: 800px;
                display: none;
            }
            
            .success-message.show {
                display: block;
            }
            
            /* Loading Spinner */
            .spinner-overlay {
                display: none;
                position: fixed;
                top: 0;
                left: 0;
                right: 0;
                bottom: 0;
                background: rgba(255,255,255,0.8);
                z-index: 9999;
                justify-content: center;
                align-items: center;
            }
            
            .spinner-overlay.active {
                display: flex;
            }
            
            .spinner {
                border: 3px solid #f3f3f3;
                border-top: 3px solid #007cba;
                border-radius: 50%;
                width: 40px;
                height: 40px;
                animation: spin 1s linear infinite;
            }
            
            @keyframes spin {
                0% { transform: rotate(0deg); }
                100% { transform: rotate(360deg); }
            }
        ';
    }
    
    /**
     * Admin JavaScript
     */
    private function get_admin_javascript() {
        return '
            jQuery(document).ready(function($) {
                // Initialisiere Sortable für Hauptkategorien
                $(".sortable-list.main-categories").sortable({
                    handle: ".drag-handle",
                    placeholder: "sortable-placeholder",
                    animation: 200,
                    update: function(event, ui) {
                        enableSaveButton();
                    }
                });
                
                // Initialisiere Sortable für alle Unterkategorien
                $(".sortable-list.subcategories").sortable({
                    handle: ".drag-handle",
                    placeholder: "sortable-placeholder",
                    animation: 200,
                    update: function(event, ui) {
                        enableSaveButton();
                    }
                });
                
                // Save Button aktivieren
                function enableSaveButton() {
                    $("#save-order-btn").prop("disabled", false).text("Sortierung speichern");
                }
                
                // Speichern der Sortierung
                $("#save-order-btn").click(function() {
                    var btn = $(this);
                    btn.prop("disabled", true).text("Speichert...");
                    
                    // Zeige Spinner
                    $(".spinner-overlay").addClass("active");
                    
                    // Sammle Hauptkategorien-Reihenfolge
                    var mainOrder = {};
                    $(".sortable-list.main-categories .sortable-item").each(function(index) {
                        var slug = $(this).data("slug");
                        mainOrder[slug] = index + 1;
                    });
                    
                    // Sammle Unterkategorien-Reihenfolge
                    var subOrder = {};
                    $(".subcategories-container").each(function() {
                        var parentSlug = $(this).data("parent");
                        subOrder[parentSlug] = {};
                        
                        $(this).find(".sortable-item").each(function(index) {
                            var slug = $(this).data("slug");
                            subOrder[parentSlug][slug] = index + 1;
                        });
                    });
                    
                    // Sende AJAX Request
                    $.ajax({
                        url: ajaxurl,
                        type: "POST",
                        data: {
                            action: "save_category_order",
                            main_order: mainOrder,
                            sub_order: subOrder,
                            nonce: "' . wp_create_nonce('save_category_order') . '"
                        },
                        success: function(response) {
                            $(".spinner-overlay").removeClass("active");
                            
                            if (response.success) {
                                $(".success-message").addClass("show");
                                btn.text("Gespeichert!");
                                
                                setTimeout(function() {
                                    $(".success-message").removeClass("show");
                                    btn.prop("disabled", true).text("Sortierung gespeichert");
                                }, 3000);
                            } else {
                                alert("Fehler beim Speichern: " + response.data);
                                btn.prop("disabled", false).text("Sortierung speichern");
                            }
                        },
                        error: function() {
                            $(".spinner-overlay").removeClass("active");
                            alert("Verbindungsfehler. Bitte versuchen Sie es erneut.");
                            btn.prop("disabled", false).text("Sortierung speichern");
                        }
                    });
                });
                
                // Reset Button
                $("#reset-order-btn").click(function() {
                    if (confirm("Möchten Sie wirklich die Standard-Reihenfolge wiederherstellen?")) {
                        $.ajax({
                            url: ajaxurl,
                            type: "POST",
                            data: {
                                action: "save_category_order",
                                reset: true,
                                nonce: "' . wp_create_nonce('save_category_order') . '"
                            },
                            success: function(response) {
                                location.reload();
                            }
                        });
                    }
                });
            });
        ';
    }
    
    /**
     * Admin-Seite
     */
    public function admin_page() {
        ?>
        <div class="wrap">
            <h1>Navigation Sortierung - Drag & Drop</h1>
            <p>Ziehen Sie die Kategorien in die gewünschte Reihenfolge. Die Änderungen werden automatisch im Frontend angewendet.</p>
            
            <div class="success-message">
                ✓ Die Sortierung wurde erfolgreich gespeichert!
            </div>
            
            <!-- Hauptkategorien -->
            <div class="sortable-container">
                <div class="sortable-header">
                    🏠 Hauptkategorien
                </div>
                <ul class="sortable-list main-categories">
                    <?php
                    $main_categories = $this->get_sorted_categories(0);
                    foreach ($main_categories as $cat) {
                        ?>
                        <li class="sortable-item" data-slug="<?php echo esc_attr($cat->slug); ?>">
                            <span class="drag-handle">☰</span>
                            <span class="category-name"><?php echo esc_html($cat->name); ?></span>
                            <span class="category-slug"><?php echo esc_html($cat->slug); ?></span>
                        </li>
                        <?php
                        
                        // Unterkategorien
                        $subcats = $this->get_sorted_categories($cat->term_id);
                        if (!empty($subcats)) {
                            ?>
                            <li style="list-style: none;">
                                <div class="subcategories-container" data-parent="<?php echo esc_attr($cat->slug); ?>">
                                    <div class="subcategories-title">Unterkategorien von <?php echo esc_html($cat->name); ?></div>
                                    <ul class="sortable-list subcategories">
                                        <?php
                                        foreach ($subcats as $subcat) {
                                            ?>
                                            <li class="sortable-item" data-slug="<?php echo esc_attr($subcat->slug); ?>">
                                                <span class="drag-handle">☰</span>
                                                <span class="category-name"><?php echo esc_html($subcat->name); ?></span>
                                                <span class="category-slug"><?php echo esc_html($subcat->slug); ?></span>
                                            </li>
                                            <?php
                                        }
                                        ?>
                                    </ul>
                                </div>
                            </li>
                            <?php
                        }
                    }
                    ?>
                </ul>
            </div>
            
            <button id="save-order-btn" class="save-order-btn" disabled>Sortierung gespeichert</button>
            <button id="reset-order-btn" class="button button-secondary" style="margin-left: 10px;">Standard wiederherstellen</button>
            
            <div class="spinner-overlay">
                <div class="spinner"></div>
            </div>
        </div>
        <?php
    }
    
    /**
     * AJAX Handler zum Speichern der Sortierung
     */
    public function save_category_order() {
        // Prüfe Nonce
        if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'save_category_order')) {
            wp_die('Sicherheitsprüfung fehlgeschlagen');
        }
        
        // Reset Option
        if (isset($_POST['reset']) && $_POST['reset']) {
            delete_option('riman_category_order_dragdrop');
            delete_option('riman_subcategory_order_dragdrop');
            wp_send_json_success('Reset erfolgreich');
            return;
        }
        
        // Speichere Hauptkategorien-Reihenfolge
        if (isset($_POST['main_order'])) {
            update_option('riman_category_order_dragdrop', $_POST['main_order']);
        }
        
        // Speichere Unterkategorien-Reihenfolge
        if (isset($_POST['sub_order'])) {
            update_option('riman_subcategory_order_dragdrop', $_POST['sub_order']);
        }
        
        wp_send_json_success('Sortierung gespeichert');
    }

    /**
     * Holt den Hero-Titel oder falls leer den bereinigten Post-Titel
     */
    private function get_riman_display_title($post_id) {
        // Prüfe ob Hero Meta Klasse verfügbar ist
        if (class_exists('RIMAN_Hero_Meta')) {
            $hero_meta = RIMAN_Hero_Meta::get_hero_meta($post_id);
            if (!empty($hero_meta['title'])) {
                return $hero_meta['title'];
            }
        }

        // Fallback: Bereinigter Post-Titel
        $post_title = get_the_title($post_id);
        return str_replace(' - Riman GmbH', '', $post_title);
    }

    /**
     * Bestimmt die sichtbare Bezeichnung für einen Navigationslink.
     * Bevorzugt den im FSE gespeicherten Label-Text und fällt ansonsten
     * auf die bisherige Hero/Seitentitel-Logik zurück.
     */
    private function get_navigation_label($block, $post_id) {
        if (!empty($block['attrs']['label'])) {
            return $block['attrs']['label'];
        }

        return $this->get_riman_display_title($post_id);
    }

    /**
     * Erstellt ein Submenu für RIMAN Seiten (gleiche Struktur wie Kategorien)
     */
    private function create_riman_submenu($post, $block) {
        $root_label = $this->get_navigation_label($block, $post->ID);

        // Hole Unterseiten der RIMAN Hauptseite
        $subpages = get_posts([
            'post_type' => 'riman_seiten',
            'post_parent' => $post->ID,
            'post_status' => 'publish',
            'numberposts' => -1,
            'orderby' => 'menu_order title',
            'order' => 'ASC'
        ]);

        // Wenn keine Unterseiten vorhanden, einfacher Link
        if (empty($subpages)) {
            return sprintf(
                '<li class="wp-block-navigation-item">
                    <a class="wp-block-navigation-item__content" href="%s">%s</a>
                </li>',
                esc_url($block['attrs']['url']),
                esc_html($root_label)
            );
        }

        // Erstelle Submenu HTML (exakt wie bei Kategorien)
        $submenu_html = '<ul class="auto-populated-dragdrop">';

        // Unterseiten hinzufügen
        foreach ($subpages as $subpage) {
            // Prüfe ob Unterseite weitere Detailseiten hat
            $detailseiten = get_posts([
                'post_type' => 'riman_seiten',
                'post_parent' => $subpage->ID,
                'post_status' => 'publish',
                'numberposts' => -1,
                'orderby' => 'menu_order title',
                'order' => 'ASC'
            ]);

            if (!empty($detailseiten)) {
                // Unterseite MIT Detailseiten als verschachteltes Submenu
                $submenu_html .= sprintf(
                    '<li class="wp-block-navigation-item mega-column subcategory-with-posts">
                        <a class="wp-block-navigation-item__content" href="%s">
                            %s
                        </a>
                        <ul class="subcategory-posts">',
                        esc_url(get_permalink($subpage->ID)),
                        esc_html($this->get_riman_display_title($subpage->ID))
                );

                // Detailseiten der Unterseite
                foreach ($detailseiten as $detailseite) {
                    $submenu_html .= sprintf(
                        '<li class="mega-menu-item">
                            <a class="mega-menu-link" href="%s">%s</a>
                        </li>',
                        get_permalink($detailseite->ID),
                        esc_html($this->get_riman_display_title($detailseite->ID))
                    );
                }

                $submenu_html .= '</ul></li>';
            } else {
                // Unterseite OHNE Detailseiten (einfacher Link)
                $submenu_html .= sprintf(
                    '<li class="wp-block-navigation-item subcategory-link">
                        <a class="wp-block-navigation-item__content" href="%s">%s</a>
                    </li>',
                    esc_url(get_permalink($subpage->ID)),
                    esc_html($this->get_riman_display_title($subpage->ID))
                );
            }
        }

        $submenu_html .= '</ul>';

        // Vollständiger Menüpunkt mit Submenu (exakt wie bei Kategorien)
        return sprintf(
            '<li class="wp-block-navigation-item wp-block-navigation-submenu has-child auto-populated-dd">
                <a class="wp-block-navigation-item__content" href="%s">
                    <span class="wp-block-navigation-item__label">%s</span>
                </a>
                %s
            </li>',
            esc_url(get_permalink($post->ID)),
            esc_html($root_label),
            $submenu_html
        );
    }

    /**
     * Hole sortierte Kategorien
     */
    private function get_sorted_categories($parent = 0) {
        $categories = get_categories([
            'parent' => $parent,
            'hide_empty' => false
        ]);
        
        // Lade gespeicherte Sortierung
        if ($parent === 0) {
            $order = get_option('riman_category_order_dragdrop', []);
        } else {
            $parent_cat = get_category($parent);
            $all_orders = get_option('riman_subcategory_order_dragdrop', []);
            $order = isset($all_orders[$parent_cat->slug]) ? $all_orders[$parent_cat->slug] : [];
        }
        
        // Sortiere Kategorien
        usort($categories, function($a, $b) use ($order) {
            $order_a = isset($order[$a->slug]) ? $order[$a->slug] : 999;
            $order_b = isset($order[$b->slug]) ? $order[$b->slug] : 999;
            
            if ($order_a == $order_b) {
                return strcasecmp($a->name, $b->name);
            }
            return $order_a - $order_b;
        });
        
        return $categories;
    }
    
    /**
     * Prüft ob ein Link ein Kategorie-Link ist
     */
    public function check_category_link($block_content, $block, $instance) {
        if (!empty($block['attrs']['url']) && strpos($block['attrs']['url'], '/category/') !== false) {
            preg_match('/\/category\/([^\/]+)/', $block['attrs']['url'], $matches);
            
            if (!empty($matches[1])) {
                $category_slug = $matches[1];
                $category = get_category_by_slug($category_slug);
                
                if ($category) {
                    return $this->create_category_submenu($category, $block);
                }
            }
        }
        
        return $block_content;
    }

    /**
     * Prüft und erweitert RIMAN Seiten Links
     */
    public function check_riman_link($block_content, $block, $instance) {
        if (!empty($block['attrs']['url'])) {
            // Erkenne RIMAN Seiten durch URL-Pattern oder Post-Type
            $post_id = url_to_postid($block['attrs']['url']);

            if ($post_id) {
                $post = get_post($post_id);

                if ($post && $post->post_type === 'riman_seiten' && $post->post_parent == 0) {
                    return $this->create_riman_submenu($post, $block);
                }
            }
        }

        return $block_content;
    }

    /**
     * Dekoriert klassische WP Seiten Links, damit Dropdown Styles greifen
     */
    public function decorate_wp_page_link($block_content, $block, $instance) {
        if (!$this->is_wp_page_block($block)) {
            return $block_content;
        }

        if (strpos($block_content, 'auto-populated-dd') !== false) {
            return $block_content;
        }

        return preg_replace_callback(
            '/(<li\\b[^>]*class=")([^\"]*)(")/i',
            function($matches) {
                $classes = $matches[2];
                if (strpos($classes, 'auto-populated-dd') === false) {
                    $classes .= ' auto-populated-dd';
                }
                $classes = trim(preg_replace('/\s+/', ' ', $classes));
                return $matches[1] . $classes . $matches[3];
            },
            $block_content,
            1
        );
    }

    /**
     * Verarbeitet bestehende Submenus und passt sie optisch dem Mega-Menü an
     */
    public function check_category_submenu($block_content, $block, $instance) {
        if (!$this->is_wp_page_block($block)) {
            return $block_content;
        }

        if (strpos($block_content, 'auto-populated-dragdrop') !== false) {
            return $block_content;
        }

        // Entferne Standard-Toggle-Button, da Hover/Focus genutzt wird
        $block_content = preg_replace(
            '/<button[^>]*(wp-block-navigation__submenu-icon|wp-block-navigation-item__submenu-icon)[^>]*>.*?<\\/button>/is',
            '',
            $block_content
        );

        // Ergänze Klassen am Container
        $block_content = preg_replace_callback(
            '/(<li\\b[^>]*class=")([^\"]*)(")/i',
            function($matches) {
                $classes = $matches[2];
                if (strpos($classes, 'auto-populated-dd') === false) {
                    $classes .= ' auto-populated-dd';
                }
                $classes = trim(preg_replace('/\s+/', ' ', $classes));
                return $matches[1] . $classes . $matches[3];
            },
            $block_content,
            1
        );

        $block_content = preg_replace_callback(
            '/<ul([^>]*)class="([^\"]*wp-block-navigation__submenu-container[^\"]*)"([^>]*)>/i',
            function($matches) {
                $classes = $matches[2];
                $classes = preg_replace('/\b(has-text-color|has-contrast-color|has-background|has-base-background-color)\b/', '', $classes);
                if (strpos($classes, 'auto-populated-dragdrop') === false) {
                    $classes .= ' auto-populated-dragdrop';
                }
                $classes = trim(preg_replace('/\s+/', ' ', $classes));
                return '<ul' . $matches[1] . 'class="' . $classes . '"' . $matches[3] . '>';
            },
            $block_content,
            1
        );

        return $block_content;
    }

    /**
     * Prüft ob Block ein klassischer Seiten-Link/Submenu ist
     */
    private function is_wp_page_block($block) {
        if (empty($block['attrs'])) {
            return false;
        }

        $attrs = $block['attrs'];

        if (!empty($attrs['type']) && $attrs['type'] === 'page') {
            return true;
        }

        if (!empty($attrs['kind']) && $attrs['kind'] === 'post-type' && !empty($attrs['type']) && $attrs['type'] === 'page') {
            return true;
        }

        if (!empty($attrs['id'])) {
            $post = get_post((int) $attrs['id']);
            if ($post && $post->post_type === 'page') {
                return true;
            }
        }

        if (!empty($attrs['url'])) {
            $post_id = url_to_postid($attrs['url']);
            if ($post_id) {
                $post = get_post($post_id);
                if ($post && $post->post_type === 'page') {
                    return true;
                }
            }
        }

        return false;
    }
    
    /**
     * Erstellt ein Submenu mit sortierten Unterkategorien und Posts
     */
    private function create_category_submenu($category, $block) {
        // Hole sortierte Unterkategorien
        $subcategories = $this->get_sorted_categories($category->term_id);
        
        // Wenn keine Unterkategorien vorhanden
        if (empty($subcategories)) {
            // Hole Posts der Hauptkategorie
            $posts = get_posts([
                'category' => $category->term_id,
                'posts_per_page' => 5,
                'orderby' => 'date',
                'order' => 'DESC',
                'post_status' => 'publish'
            ]);
            
            // Wenn auch keine Posts, dann einfacher Link
            if (empty($posts)) {
                return sprintf(
                    '<li class="wp-block-navigation-item">
                        <a class="wp-block-navigation-item__content" href="%s">%s</a>
                    </li>',
                    esc_url($block['attrs']['url']),
                    esc_html($block['attrs']['label'] ?? $category->name)
                );
            }
            
            // Nur Posts ohne Unterkategorien
            $submenu_html = '<ul class="auto-populated-dragdrop">';
            
            foreach ($posts as $post) {
                $submenu_html .= sprintf(
                    '<li class="wp-block-navigation-item post-link">
                        <a class="wp-block-navigation-item__content" href="%s">%s</a>
                    </li>',
                    get_permalink($post->ID),
                    esc_html($post->post_title)
                );
            }
            
            $submenu_html .= '</ul>';
        } else {
            // Mit Unterkategorien - zeige diese mit ihren Posts als verschachtelte Submenus
            $submenu_html = '<ul class="auto-populated-dragdrop">';
            
            // Sortierte Unterkategorien mit ihren Posts
            foreach ($subcategories as $subcat) {
                // Hole Posts der Unterkategorie
                $subcat_posts = get_posts([
                    'category' => $subcat->term_id,
                    'posts_per_page' => 5,
                    'orderby' => 'date',
                    'order' => 'DESC',
                    'post_status' => 'publish'
                ]);
                
                if (!empty($subcat_posts)) {
                    // Unterkategorie MIT Posts als verschachteltes Submenu
                    $submenu_html .= sprintf(
                        '<li class="wp-block-navigation-item mega-column subcategory-with-posts">
                            <a class="wp-block-navigation-item__content" href="%s">
                                %s
                            </a>
                            <ul class="subcategory-posts">',
                        esc_url(get_category_link($subcat->term_id)),
                        esc_html($subcat->name)
                    );
                    
                    // Posts der Unterkategorie
                    foreach ($subcat_posts as $post) {
                    $submenu_html .= sprintf(
                        '<li class="mega-menu-item">
                            <a class="mega-menu-link" href="%s">%s</a>
                        </li>',
                        get_permalink($post->ID),
                        esc_html($post->post_title)
                    );
                }
                    
                    $submenu_html .= '</ul></li>';
                } else {
                    // Unterkategorie OHNE Posts (einfacher Link)
                    $submenu_html .= sprintf(
                        '<li class="wp-block-navigation-item subcategory-link">
                            <a class="wp-block-navigation-item__content" href="%s">%s</a>
                        </li>',
                        esc_url(get_category_link($subcat->term_id)),
                        esc_html($subcat->name)
                    );
                }
            }
            
            $submenu_html .= '</ul>';
        }
        
        // Komplette Submenu-Struktur
        return sprintf(
            '<li class="wp-block-navigation-item wp-block-navigation-submenu has-child auto-populated-dd">
                <a class="wp-block-navigation-item__content" href="%s">
                    <span class="wp-block-navigation-item__label">%s</span>
                </a>
                %s
            </li>',
            esc_url(get_category_link($category->term_id)),
            esc_html($block['attrs']['label'] ?? $category->name),
            $submenu_html
        );
    }
    
    /**
     * Frontend CSS
     */
    public function add_navigation_styles() {
        ?>
        <style>
            :root {
                --riman-header-height: 96px;
            }
            .wp-site-blocks > .wp-block-template-part {
                position: relative;
                z-index: 2;
                background: transparent !important;
                margin-bottom: 0 !important;
            }

            .riman-header,
            .riman-header .wp-block-group,
            .riman-header-container,
            .riman-header-container > * {
                background: transparent !important;
            }

            @media (max-width: 1280px) {
                .riman-header .riman-header-inner,
                .riman-header-container {
                    display: flex !important;
                    flex-wrap: wrap !important;
                    flex-direction: row !important;
                    align-items: center !important;
                    justify-content: center !important;
                    column-gap: clamp(0.4rem, 0.8vw, 1.2rem) !important;
                    row-gap: clamp(0.4rem, 0.8vw, 1rem) !important;
                }

                .riman-header .riman-nav-container {
                    order: 1 !important;
                    flex: 0 0 100% !important;
                    width: 100% !important;
                    justify-content: flex-end !important;
                }

                .riman-header .wp-block-site-logo {
                    order: 2 !important;
                    display: flex !important;
                    align-items: center !important;
                    justify-content: center !important;
                    flex: 0 0 auto !important;
                }

                .riman-header .riman-logo,
                .riman-header .wp-block-site-title {
                    order: 3 !important;
                    text-align: left !important;
                    flex: 0 0 auto !important;
                }
            }

            .riman-header .wp-block-site-logo,
            .riman-header .wp-block-site-logo img {
                width: clamp(68px, 7vw, 120px) !important;
                height: auto !important;
            }

            .riman-logo,
            .riman-header .wp-block-site-title {
                font-size: clamp(1.2rem, 0.6vw + 1.1rem, 2.2rem) !important;
                letter-spacing: 0.04em;
            }

            body.has-hero-header .wp-block-riman-page-hero:first-of-type,
            body.has-hero-header .wp-block-cover.alignfull:first-of-type {
                margin-top: calc(-1 * var(--riman-header-height)) !important;
                padding-top: calc(var(--riman-header-height) + clamp(0.5rem, 1vw, 1.5rem)) !important;
                position: relative;
                z-index: 1;
            }


            .riman-nav-container {
                flex: 1 1 auto !important;
                display: flex !important;
                align-items: center !important;
                justify-content: flex-start !important;
                min-width: 0 !important;
                gap: clamp(0.75rem, 1.5vw, 1.5rem) !important;
            }

            .riman-nav-container nav.riman-navigation {
                width: 100% !important;
                min-width: 0 !important;
            }

            .riman-nav-container nav.riman-navigation .wp-block-navigation__responsive-container-open,
            .riman-nav-container nav.riman-navigation .wp-block-navigation__responsive-container-close,
            .riman-nav-container nav.riman-navigation .wp-block-navigation__toggle,
            .riman-nav-container nav.riman-navigation .wp-block-navigation__submenu-icon {
                display: none !important;
            }

            .riman-nav-container nav.riman-navigation .wp-block-navigation__responsive-container {
                position: static !important;
                width: 100% !important;
                background: transparent !important;
                box-shadow: none !important;
                opacity: 1 !important;
                visibility: visible !important;
                transform: none !important;
            }

            .riman-nav-container nav.riman-navigation .wp-block-navigation__responsive-container .wp-block-navigation__container {
                display: flex !important;
                flex-wrap: nowrap !important;
                justify-content: center !important;
                align-items: center !important;
                column-gap: clamp(1rem, 2.5vw, 3rem) !important;
                row-gap: 0.75rem !important;
                margin: 0 0 -5px !important;
                padding: 0 clamp(0.5rem, 1vw, 1.5rem) 0 clamp(0.25rem, 1vw, 1rem) !important;
                width: 100% !important;
            }

            .riman-nav-container nav.riman-navigation .wp-block-navigation__responsive-container .wp-block-navigation__container > .wp-block-navigation-item {
                background: transparent !important;
                padding: 0 !important;
                margin: 0 !important;
                box-shadow: none !important;
                min-width: auto !important;
                display: flex !important;
                flex-direction: column;
                gap: 0 !important;
                flex: 0 1 auto !important;
                white-space: nowrap !important;
            }

            .riman-nav-container nav.riman-navigation .wp-block-navigation__responsive-container .wp-block-navigation__container > .wp-block-navigation-item > .wp-block-navigation-item__content {
                font-weight: 300 !important;
                font-size: clamp(0.85rem, 0.35vw + 0.9rem, 1.05rem) !important;
                padding: 0 !important;
                color: rgb(182, 140, 47) !important;
                text-decoration: none !important;
                line-height: 1 !important;
                white-space: nowrap !important;
                text-transform: uppercase !important;
                letter-spacing: 0.05em;
            }

            .riman-nav-container .wp-block-button,
            .riman-nav-container .wp-block-button__link {
                white-space: nowrap !important;
                word-break: normal !important;
                display: inline-flex !important;
                align-items: center !important;
                justify-content: center !important;
            }


            .auto-populated-dd {
                position: relative;
            }

            .riman-navigation > .wp-block-navigation__container > .wp-block-navigation-item > .wp-block-navigation-item__content {
                color: rgb(182, 140, 47) !important;
            }

            .auto-populated-dd::after {
                content: '';
                position: absolute;
                left: 0;
                right: 0;
                top: 100%;
                height: 24px;
            }

            .auto-populated-dragdrop {
                position: absolute !important;
                top: 100% !important;
                left: 50% !important;
                transform: translate(-50%, 16px) !important;
                width: max-content !important;
                min-width: 320px !important;
                max-width: min(90vw, 960px) !important;
                padding: 32px clamp(20px, 3vw, 36px) !important;
                margin: 0 !important;
                list-style: none !important;
                display: grid !important;
                grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
                column-gap: 32px;
                row-gap: 24px;
                justify-items: start;
                align-items: start;
                border-radius: 0;
                background: rgba(255, 255, 255, 0.61);
                backdrop-filter: blur(8px);
                box-shadow: 0 24px 48px rgba(15, 24, 54, 0.18);
                opacity: 0 !important;
                visibility: hidden !important;
                pointer-events: none !important;
                transition: opacity 0.2s ease, transform 0.2s ease;
                z-index: 99999;
                text-align: left;
                border: none !important;
            }

            .auto-populated-dragdrop.is-simple-list {
                display: flex !important;
                flex-direction: column !important;
                width: auto !important;
                min-width: 240px !important;
                max-width: 360px !important;
                padding: 20px 24px !important;
                row-gap: 4px !important;
                column-gap: 0 !important;
            }

            .auto-populated-dd:hover > .auto-populated-dragdrop,
            .auto-populated-dd:focus-within > .auto-populated-dragdrop {
                opacity: 1 !important;
                visibility: visible !important;
                pointer-events: auto !important;
                transform: translate(-50%, 0) !important;
            }

            /* Force mega dropdown styles even when WordPress adds submenu helpers */
            .riman-navigation .wp-block-navigation__container .wp-block-navigation-item > .wp-block-navigation__submenu-container.auto-populated-dragdrop,
            .riman-navigation .wp-block-navigation-item > .wp-block-navigation__submenu-container.auto-populated-dragdrop {
                position: absolute !important;
                top: 100% !important;
                left: 50% !important;
                transform: translate(-50%, 16px) !important;
                width: min(90vw, 960px) !important;
                min-width: 420px !important;
                padding: 32px clamp(20px, 3vw, 36px) !important;
                margin: 0 !important;
                list-style: none !important;
                display: grid !important;
                grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
                column-gap: 32px;
                row-gap: 24px;
                justify-items: start;
                align-items: start;
                border-radius: 0;
                background: rgba(255, 255, 255, 0.61);
                backdrop-filter: blur(8px);
                box-shadow: 0 24px 48px rgba(15, 24, 54, 0.18);
                opacity: 0 !important;
                visibility: hidden !important;
                pointer-events: none !important;
                transition: opacity 0.2s ease, transform 0.2s ease;
                z-index: 99999;
                text-align: left;
            }

            .riman-navigation .wp-block-navigation-item > .wp-block-navigation__submenu-container.auto-populated-dragdrop.is-simple-list {
                display: flex !important;
                flex-direction: column !important;
                width: auto !important;
                min-width: 240px !important;
                max-width: 360px !important;
                padding: 20px 24px !important;
            }

            .riman-navigation .wp-block-navigation-item:hover > .wp-block-navigation__submenu-container.auto-populated-dragdrop,
            .riman-navigation .wp-block-navigation-item:focus-within > .wp-block-navigation__submenu-container.auto-populated-dragdrop {
                opacity: 1 !important;
                visibility: visible !important;
                pointer-events: auto !important;
                transform: translate(-50%, 0) !important;
            }

            .auto-populated-dragdrop > .wp-block-navigation-item {
                border: none;
                padding: 0 !important;
                background: none;
                list-style: none;
                display: flex;
                flex-direction: column;
                gap: 8px;
                text-align: left;
                align-items: flex-start;
            }

            .auto-populated-dragdrop.is-simple-list > .wp-block-navigation-item {
                gap: 0 !important;
            }

            .auto-populated-dragdrop .wp-block-navigation-item {
                list-style: none !important;
            }

            .auto-populated-dragdrop > .wp-block-navigation-item > .wp-block-navigation-item__content {
                font-size: 1.05rem;
                font-weight: 600 !important;
                padding: 0 0 10px !important;
                color: rgb(182, 140, 47);
                display: block;
                text-align: left !important;
                margin: 0 !important;
                text-decoration: none !important;
            }

            .auto-populated-dragdrop > .wp-block-navigation-item.wp-block-navigation-link > .wp-block-navigation-item__content {
                color: rgb(182, 140, 47) !important;
                border: none !important;
            }

            .auto-populated-dragdrop.is-simple-list > .wp-block-navigation-item > .wp-block-navigation-item__content {
                font-size: 1rem !important;
                font-weight: 600 !important;
                color: #4a4f61 !important;
                padding: 6px 0 !important;
            }

            .auto-populated-dragdrop > .wp-block-navigation-item.wp-block-navigation-link > .wp-block-navigation-item__content:hover {
                color: rgb(182, 140, 47) !important;
                background: transparent !important;
                text-decoration: none !important;
            }

            .auto-populated-dragdrop.is-simple-list > .wp-block-navigation-item > .wp-block-navigation-item__content:hover {
                color: #007cba !important;
            }

            .auto-populated-dragdrop .wp-block-navigation-item__content {
                margin: 0 !important;
            }

            .auto-populated-dragdrop .mega-column {
                min-width: 220px;
            }

            .auto-populated-dragdrop .subcategory-with-posts {
                position: static;
                padding: 0;
            }

            .auto-populated-dragdrop .subcategory-link a {
                padding: 6px 0 !important;
                border-radius: 6px;
                color: #4a4f61 !important;
                transition: background 0.15s ease, color 0.15s ease;
                text-align: left;
            }

            .auto-populated-dragdrop .subcategory-link {
                list-style: none;
                margin: 0;
                padding: 0;
                width: 100%;
            }

            .auto-populated-dragdrop .subcategory-link a:hover {
                background: rgba(0, 124, 186, 0.08);
                color: #007cba !important;
            }
            
            .main-category-link,
            .nav-divider,
            .nav-section-title {
                display: none !important;
            }
            
            .subcategory-link a {
                position: relative;
            }

            .mega-menu-item,
            .post-link a,
            .subcategory-posts .post-item a,
            .subcategory-posts .wp-block-navigation-item__content {
                color: #5a5f71 !important;
                font-size: 0.92rem;
                padding: 4px 0 !important;
                border-radius: 4px;
                transition: color 0.15s ease, transform 0.15s ease;
                text-align: left !important;
                padding-left: 0 !important;
                padding-inline-start: 0 !important;
                list-style: none;
                margin: 0;
                width: 100%;
                font-weight: 600;
            }

            .mega-menu-link,
            .mega-menu-item a {
                display: block;
            }

            .mega-menu-item a:hover,
            .post-link a:hover,
            .subcategory-posts .post-item a:hover {
                color: #5a5f71 !important;
                font-weight: 600;
                transform: none;
                background: none;
            }

            .subcategory-toggle {
                display: none !important;
            }

            .auto-populated-dragdrop .subcategory-posts.wp-block-navigation__submenu-container,
            .auto-populated-dragdrop .subcategory-posts {
                position: static;
                opacity: 1;
                visibility: visible;
                transform: none;
                min-width: auto;
                background: transparent;
                border: none;
                border-radius: 0;
                box-shadow: none;
                margin: 0;
                padding: 0 !important;
                padding-left: 0 !important;
                padding-inline-start: 0 !important;
                display: flex;
                flex-direction: column;
                gap: 6px;
                list-style: none !important;
                width: 100%;
                align-items: flex-start;
            }

            .subcategory-posts > li,
            .subcategory-posts > li > a {
                margin: 0 !important;
                padding-left: 0 !important;
            }

            .auto-populated-dragdrop .subcategory-posts .wp-block-navigation-item {
                padding: 0 !important;
                margin: 0 !important;
                width: 100%;
                padding-inline-start: 0 !important;
            }
            
            /* Klickbare Hauptlinks - sauberer Link ohne Button */
            .auto-populated-dd > .wp-block-navigation-item__content {
                pointer-events: auto !important;
                display: block;
                text-decoration: none;
                color: rgb(182, 140, 47) !important;
            }

            .auto-populated-dd > .wp-block-navigation-item__content:hover {
                color: rgb(182, 140, 47) !important;
            }

        </style>
        <?php
    }
    
    /**
     * Frontend JavaScript
     */
    public function add_navigation_scripts() {
        ?>
        <script>
        document.addEventListener('DOMContentLoaded', function() {
            const gutter = 16;

            const resetPanel = (panel) => {
                panel.style.left = '50%';
                panel.style.right = 'auto';
                panel.style.transform = 'translate(-50%, 16px)';
                panel.style.maxWidth = '';
            };

            const setPanelWidth = (panel) => {
                if (!panel) return;
                const hasMegaColumns = panel.querySelector('.mega-column, .subcategory-with-posts');
                const directColumns = panel.querySelectorAll(':scope > .wp-block-navigation-item').length;

                if (!hasMegaColumns && directColumns > 0) {
                    panel.classList.add('is-simple-list');
                    panel.style.setProperty('width', 'auto', 'important');
                    return;
                }

                panel.classList.remove('is-simple-list');
                const columnCount = Math.max(1, hasMegaColumns ? panel.querySelectorAll('.mega-column').length : directColumns);
                const desiredWidth = Math.min(960, Math.max(320, columnCount * 240));
                panel.style.setProperty('width', `${desiredWidth}px`, 'important');
            };

            const clampPanel = (panel) => {
                if (!panel) return;
                setPanelWidth(panel);
                resetPanel(panel);

                // Wait for layout to settle
                requestAnimationFrame(() => {
                    const rect = panel.getBoundingClientRect();
                    const viewportWidth = window.innerWidth;
                    const availableWidth = viewportWidth - gutter * 2;

                    if (rect.width >= availableWidth) {
                        panel.style.left = `${gutter}px`;
                        panel.style.right = `${gutter}px`;
                        panel.style.transform = 'translate(0, 16px)';
                        panel.style.maxWidth = `calc(100vw - ${gutter * 2}px)`;
                        return;
                    }

                    if (rect.right > viewportWidth - gutter) {
                        panel.style.left = 'auto';
                        panel.style.right = `${gutter}px`;
                        panel.style.transform = 'translate(0, 16px)';
                    } else if (rect.left < gutter) {
                        panel.style.left = `${gutter}px`;
                        panel.style.right = 'auto';
                        panel.style.transform = 'translate(0, 16px)';
                    }
                });
            };

            document.querySelectorAll('.subcategory-toggle').forEach(btn => btn.remove());

            const dropdowns = document.querySelectorAll('.auto-populated-dd');
            dropdowns.forEach(dd => {
                const panel = dd.querySelector('.auto-populated-dragdrop');
                if (!panel) return;

                const adjust = () => clampPanel(panel);
                dd.addEventListener('mouseenter', adjust);
                dd.addEventListener('focusin', adjust);
            });

            window.addEventListener('resize', () => {
                document.querySelectorAll('.auto-populated-dragdrop').forEach(panel => clampPanel(panel));
            });
        });
        </script>
        <?php
    }

    /**
     * Adds a small inline script that toggles has-hero-header when hero blocks exist
     */
    public function inject_hero_detector() {
        ?>
        <script>
        document.addEventListener('DOMContentLoaded', function() {
            const selectors = [
                '.wp-block-riman-page-hero',
                '.wp-block-cover.alignfull',
                '.wp-block-cover__background.has-background-dim'
            ];

            const updateHeaderVar = () => {
                const header = document.querySelector('.wp-site-blocks > .wp-block-template-part');
                if (!header) {
                    return;
                }
                const height = header.offsetHeight || 0;
                if (height) {
                    document.documentElement.style.setProperty('--riman-header-height', height + 'px');
                }
            };

            const updateState = () => {
                const hasHero = selectors.some(sel => document.querySelector(sel));
                document.body.classList.toggle('has-hero-header', hasHero);
                updateHeaderVar();
            };

            const observer = new MutationObserver(updateState);
            observer.observe(document.body, { childList: true, subtree: true });

            updateState();
            window.addEventListener('load', () => updateState());
            window.addEventListener('resize', () => requestAnimationFrame(updateHeaderVar));
        });
        </script>
        <?php
    }
}

// Plugin initialisieren
new Auto_Populate_Navigation_DragDrop();
