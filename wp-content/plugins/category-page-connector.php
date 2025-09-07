<?php
/**
 * Plugin Name: Category Page Content Connector
 * Description: Verknüpft Seiten mit Kategorien und zeigt den Seiteninhalt automatisch im Kategorie-Template an
 * Version: 3.0
 * Author: RIMAN GmbH
 */

// Sicherheit: Direkten Zugriff verhindern
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Meta-Box zur Seiten-Bearbeitung hinzufügen
 */
function cpc_add_category_metabox() {
    add_meta_box(
        'cpc_category_selector',
        'Kategorie-Verknüpfung',
        'cpc_category_metabox_content',
        'page',
        'side',
        'high'
    );
}
add_action('add_meta_boxes', 'cpc_add_category_metabox');

/**
 * Inhalt der Meta-Box
 */
function cpc_category_metabox_content($post) {
    // Nonce für Sicherheit
    wp_nonce_field('cpc_save_category', 'cpc_category_nonce');
    
    // Aktuelle Verknüpfung abrufen
    $linked_category = get_post_meta($post->ID, '_linked_category_id', true);
    
    // Alle Kategorien abrufen (hierarchisch)
    $categories = get_categories(array(
        'hide_empty' => false,
        'orderby' => 'name',
        'order' => 'ASC'
    ));
    
    ?>
    <div style="margin: 10px 0;">
        <label for="cpc_linked_category" style="display: block; margin-bottom: 5px;">
            <strong>Diese Seite als Content für folgende Kategorie verwenden:</strong>
        </label>
        
        <select name="cpc_linked_category" id="cpc_linked_category" style="width: 100%;">
            <option value="">— Keine Verknüpfung —</option>
            <?php
            // Kategorien hierarchisch anzeigen
            $category_tree = array();
            
            // Hauptkategorien zuerst
            foreach ($categories as $category) {
                if ($category->parent == 0) {
                    $selected = ($linked_category == $category->term_id) ? 'selected="selected"' : '';
                    echo '<option value="' . $category->term_id . '" ' . $selected . '>' . 
                         esc_html($category->name) . '</option>';
                    
                    // Unterkategorien suchen
                    foreach ($categories as $subcategory) {
                        if ($subcategory->parent == $category->term_id) {
                            $selected = ($linked_category == $subcategory->term_id) ? 'selected="selected"' : '';
                            echo '<option value="' . $subcategory->term_id . '" ' . $selected . '>&nbsp;&nbsp;— ' . 
                                 esc_html($subcategory->name) . '</option>';
                                 
                            // Unter-Unterkategorien
                            foreach ($categories as $subsubcategory) {
                                if ($subsubcategory->parent == $subcategory->term_id) {
                                    $selected = ($linked_category == $subsubcategory->term_id) ? 'selected="selected"' : '';
                                    echo '<option value="' . $subsubcategory->term_id . '" ' . $selected . '>&nbsp;&nbsp;&nbsp;&nbsp;— ' . 
                                         esc_html($subsubcategory->name) . '</option>';
                                }
                            }
                        }
                    }
                }
            }
            ?>
        </select>
        
        <p class="description" style="margin-top: 8px;">
            Der Inhalt dieser Seite wird automatisch auf der gewählten Kategorie-Seite angezeigt.
        </p>
        
        <?php if ($linked_category): ?>
            <?php $category = get_category($linked_category); ?>
            <?php if ($category): ?>
                <p style="margin-top: 10px;">
                    <a href="<?php echo get_category_link($category); ?>" target="_blank" class="button button-small">
                        Kategorie-Seite anzeigen →
                    </a>
                </p>
            <?php endif; ?>
        <?php endif; ?>
    </div>
    <?php
}

/**
 * Meta-Box Daten speichern
 */
function cpc_save_category_meta($post_id) {
    // Sicherheitsprüfungen
    if (!isset($_POST['cpc_category_nonce'])) {
        return;
    }
    
    if (!wp_verify_nonce($_POST['cpc_category_nonce'], 'cpc_save_category')) {
        return;
    }
    
    if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
        return;
    }
    
    if (!current_user_can('edit_page', $post_id)) {
        return;
    }
    
    // Kategorie-Verknüpfung speichern
    if (isset($_POST['cpc_linked_category'])) {
        $category_id = intval($_POST['cpc_linked_category']);
        
        if ($category_id > 0) {
            update_post_meta($post_id, '_linked_category_id', $category_id);
            
            // Auch in der Kategorie speichern für schnelleren Zugriff
            update_term_meta($category_id, '_linked_page_id', $post_id);
        } else {
            // Verknüpfung entfernen
            $old_category = get_post_meta($post_id, '_linked_category_id', true);
            if ($old_category) {
                delete_term_meta($old_category, '_linked_page_id');
            }
            delete_post_meta($post_id, '_linked_category_id');
        }
    }
}
add_action('save_post_page', 'cpc_save_category_meta');

/**
 * Shortcode zum Laden des verknüpften Seiteninhalts
 */
function cpc_load_category_content() {
    if (!is_category()) {
        return '';
    }
    
    $current_category = get_queried_object();
    
    if (!$current_category) {
        return '';
    }
    
    // Verknüpfte Seite abrufen
    $linked_page_id = get_term_meta($current_category->term_id, '_linked_page_id', true);
    
    if ($linked_page_id) {
        $page = get_post($linked_page_id);
        
        if ($page && $page->post_status === 'publish') {
            // Content mit Filtern verarbeiten (wichtig für Blöcke, Shortcodes etc.)
            $content = apply_filters('the_content', $page->post_content);
            
            // Bearbeitungslink entfernt - war störend
            // Admins können über das WordPress Backend die verknüpfte Seite bearbeiten
            
            return '<div class="category-page-content">' . $content . '</div>';
        }
    }
    
    // Fallback: Kategorie-Beschreibung
    $description = category_description();
    if ($description) {
        return '<div class="category-description">' . $description . '</div>';
    }
    
    return '';
}
// Registriere den Shortcode nur, wenn RIMAN Blocks (Core) nicht aktiv ist
if (!defined('RIMAN_BLOCKS_CORE_LOADED')) {
    add_shortcode('category_page_content', 'cpc_load_category_content');
}

/**
 * Zeige in der Kategorie-Bearbeitung, welche Seite verknüpft ist
 */
function cpc_show_linked_page_in_category($term) {
    $linked_page_id = get_term_meta($term->term_id, '_linked_page_id', true);
    
    if ($linked_page_id) {
        $page = get_post($linked_page_id);
        if ($page) {
            ?>
            <tr class="form-field">
                <th scope="row">Verknüpfte Inhaltsseite</th>
                <td>
                    <div style="background: #f0f0f1; padding: 10px; border-radius: 4px;">
                        <strong>📄 <?php echo esc_html($page->post_title); ?></strong><br>
                        <a href="<?php echo get_edit_post_link($page->ID); ?>" class="button button-small" style="margin-top: 5px;">
                            Seite bearbeiten
                        </a>
                        <a href="<?php echo get_permalink($page->ID); ?>" target="_blank" class="button button-small" style="margin-top: 5px;">
                            Seite anzeigen
                        </a>
                    </div>
                    <p class="description">
                        Der Inhalt dieser Seite wird automatisch im Kategorie-Template angezeigt.
                    </p>
                </td>
            </tr>
            <?php
        }
    } else {
        ?>
        <tr class="form-field">
            <th scope="row">Verknüpfte Inhaltsseite</th>
            <td>
                <p style="color: #666;">
                    Keine Seite verknüpft. 
                    <a href="<?php echo admin_url('edit.php?post_type=page'); ?>">
                        Bearbeiten Sie eine Seite und wählen Sie diese Kategorie aus.
                    </a>
                </p>
            </td>
        </tr>
        <?php
    }
}
add_action('category_edit_form_fields', 'cpc_show_linked_page_in_category', 10, 1);

/**
 * Spalte in der Seiten-Übersicht hinzufügen
 */
function cpc_add_page_columns($columns) {
    $columns['linked_category'] = 'Verknüpfte Kategorie';
    return $columns;
}
add_filter('manage_page_posts_columns', 'cpc_add_page_columns');

/**
 * Inhalt für die neue Spalte
 */
function cpc_page_column_content($column_name, $post_id) {
    if ($column_name === 'linked_category') {
        $category_id = get_post_meta($post_id, '_linked_category_id', true);
        
        if ($category_id) {
            $category = get_category($category_id);
            if ($category) {
                echo '<a href="' . get_category_link($category) . '" target="_blank">' . 
                     esc_html($category->name) . '</a>';
            }
        } else {
            echo '<span style="color: #999;">—</span>';
        }
    }
}
add_action('manage_page_posts_custom_column', 'cpc_page_column_content', 10, 2);

/**
 * CSS für Admin-Bereich
 */
function cpc_admin_styles() {
    ?>
    <style>
        .column-linked_category { width: 150px; }
        #cpc_category_selector .inside { padding-top: 10px; }
        .cpc-edit-notice { display: none; } /* Verstecke im Editor */
        body:not(.block-editor-page) .cpc-edit-notice { display: block; } /* Zeige nur im Frontend */
    </style>
    <?php
}
add_action('admin_head', 'cpc_admin_styles');
