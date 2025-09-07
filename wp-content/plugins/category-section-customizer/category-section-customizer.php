<?php
/**
 * Plugin Name: Category Section Customizer
 * Description: Erlaubt die Anpassung der Unterkategorien-Überschrift auf Kategorie-Seiten
 * Version: 1.0
 * Author: RIMAN GmbH
 */

if (!defined('ABSPATH')) exit;

/**
 * Register term meta for REST exposure
 */
add_action('init', function() {
    if (function_exists('register_term_meta')) {
        $args = array(
            'type' => 'string',
            'single' => true,
            'show_in_rest' => true,
            'sanitize_callback' => 'sanitize_text_field',
            'auth_callback' => function() { return current_user_can('manage_categories'); }
        );
        register_term_meta('category', '_section_label', $args);
        register_term_meta('category', '_section_title', $args);
        register_term_meta('category', '_section_description', array_merge($args, [ 'sanitize_callback' => 'sanitize_textarea_field' ]));
    }
});

/**
 * Füge Custom Fields zur Kategorie-Bearbeitung hinzu
 */
function csc_add_category_custom_fields($term) {
    // Hole gespeicherte Werte
    $section_label = get_term_meta($term->term_id, '_section_label', true);
    $section_title = get_term_meta($term->term_id, '_section_title', true);
    $section_description = get_term_meta($term->term_id, '_section_description', true);
    
    // Default-Werte
    if (empty($section_label)) $section_label = 'DIE LEISTUNGEN';
    if (empty($section_title)) $section_title = 'Unsere {category} im Überblick.';
    if (empty($section_description)) $section_description = 'Ein Auszug unserer Kernkompetenzen – präzise, zuverlässig und fachgerecht umgesetzt.';
    ?>
    
    <tr class="form-field">
        <th colspan="2">
            <h2>Unterkategorien-Bereich Einstellungen</h2>
            <p style="font-weight: normal;">Diese Einstellungen steuern die Überschrift über den Unterkategorien-Karten.</p>
        </th>
    </tr>
    
    <tr class="form-field">
        <th scope="row">
            <label for="section_label">Bereichs-Label</label>
        </th>
        <td>
            <input name="section_label" id="section_label" type="text" value="<?php echo esc_attr($section_label); ?>" size="40" />
            <p class="description">Z.B. "DIE LEISTUNGEN" - erscheint als kleine Überschrift</p>
        </td>
    </tr>
    
    <tr class="form-field">
        <th scope="row">
            <label for="section_title">Hauptüberschrift</label>
        </th>
        <td>
            <input name="section_title" id="section_title" type="text" value="<?php echo esc_attr($section_title); ?>" size="40" />
            <p class="description">Verwende {category} als Platzhalter für den Kategorienamen.<br>
            Z.B. "Unsere {category} im Überblick." wird zu "Unsere Rückbaumanagement im Überblick."</p>
        </td>
    </tr>
    
    <tr class="form-field">
        <th scope="row">
            <label for="section_description">Beschreibung</label>
        </th>
        <td>
            <textarea name="section_description" id="section_description" rows="5" cols="50"><?php echo esc_textarea($section_description); ?></textarea>
            <p class="description">Untertitel/Beschreibung unter der Hauptüberschrift</p>
        </td>
    </tr>
    
    <?php
}
add_action('category_edit_form_fields', 'csc_add_category_custom_fields', 10, 2);

/**
 * Füge die Felder auch zum "Neue Kategorie" Formular hinzu
 */
function csc_add_category_custom_fields_new() {
    ?>
    <div class="form-field">
        <h3>Unterkategorien-Bereich Einstellungen</h3>
    </div>
    
    <div class="form-field">
        <label for="section_label">Bereichs-Label</label>
        <input name="section_label" id="section_label" type="text" value="DIE LEISTUNGEN" />
        <p>Z.B. "DIE LEISTUNGEN" - erscheint als kleine Überschrift</p>
    </div>
    
    <div class="form-field">
        <label for="section_title">Hauptüberschrift</label>
        <input name="section_title" id="section_title" type="text" value="Unsere {category} im Überblick." />
        <p>Verwende {category} als Platzhalter für den Kategorienamen.</p>
    </div>
    
    <div class="form-field">
        <label for="section_description">Beschreibung</label>
        <textarea name="section_description" id="section_description" rows="5" cols="50">Ein Auszug unserer Kernkompetenzen – präzise, zuverlässig und fachgerecht umgesetzt.</textarea>
        <p>Untertitel/Beschreibung unter der Hauptüberschrift</p>
    </div>
    <?php
}
add_action('category_add_form_fields', 'csc_add_category_custom_fields_new', 10, 2);

/**
 * Speichere die Custom Fields
 */
function csc_save_category_custom_fields($term_id) {
    if (isset($_POST['section_label'])) {
        update_term_meta($term_id, '_section_label', sanitize_text_field($_POST['section_label']));
    }
    
    if (isset($_POST['section_title'])) {
        update_term_meta($term_id, '_section_title', sanitize_text_field($_POST['section_title']));
    }
    
    if (isset($_POST['section_description'])) {
        update_term_meta($term_id, '_section_description', sanitize_textarea_field($_POST['section_description']));
    }
}
add_action('edited_category', 'csc_save_category_custom_fields');
add_action('create_category', 'csc_save_category_custom_fields');

/**
 * Helper-Funktion um die Überschrift zu generieren
 */
function get_category_section_header($category_id = null) {
    if (!$category_id && is_category()) {
        $category = get_queried_object();
        $category_id = $category->term_id;
    }
    
    if (!$category_id) {
        return array(
            'label' => 'DIE LEISTUNGEN',
            'title' => 'Unsere Leistungen im Überblick.',
            'description' => 'Ein Auszug unserer Kernkompetenzen – präzise, zuverlässig und fachgerecht umgesetzt.'
        );
    }
    
    $category = get_category($category_id);
    
    // Hole die Custom Fields
    $label = get_term_meta($category_id, '_section_label', true);
    $title = get_term_meta($category_id, '_section_title', true);
    $description = get_term_meta($category_id, '_section_description', true);
    
    // Defaults
    if (empty($label)) $label = 'DIE LEISTUNGEN';
    if (empty($title)) $title = 'Unsere {category} im Überblick.';
    if (empty($description)) $description = 'Ein Auszug unserer Kernkompetenzen – präzise, zuverlässig und fachgerecht umgesetzt.';
    
    // Ersetze {category} mit dem tatsächlichen Kategorienamen
    $title = str_replace('{category}', '<em>' . $category->name . '</em>', $title);
    
    return array(
        'label' => $label,
        'title' => $title,
        'description' => $description
    );
}

/**
 * Shortcode für die Überschrift
 */
function csc_section_header_shortcode($atts) {
    $header = get_category_section_header();
    
    ob_start();
    ?>
    <div class="section-header">
        <span class="section-label"><?php echo esc_html($header['label']); ?></span>
        <h2><?php echo wp_kses_post($header['title']); ?></h2>
        <p><?php echo esc_html($header['description']); ?></p>
    </div>
    <style>
        .section-header {
            text-align: center;
            margin-bottom: 50px;
        }
        
        .section-label {
            display: inline-block;
            background: #b68c2f;
            color: white;
            padding: 4px 16px;
            border-radius: 20px;
            font-size: 0.85rem;
            font-weight: 600;
            letter-spacing: 1px;
            margin-bottom: 15px;
            text-transform: uppercase;
        }
        
        .section-header h2 {
            font-size: 2.2rem;
            color: #1e4a6d;
            margin: 15px 0;
        }
        
        .section-header h2 em {
            color: #b68c2f;
            font-style: italic;
        }
        
        .section-header p {
            color: #666;
            font-size: 1.1rem;
        }
    </style>
    <?php
    return ob_get_clean();
}
add_shortcode('category_section_header', 'csc_section_header_shortcode');
