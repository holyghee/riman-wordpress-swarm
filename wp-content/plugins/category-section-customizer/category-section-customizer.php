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
        // Icon: attachment ID and optional inline SVG
        register_term_meta('category', '_section_icon_id', array(
            'type' => 'integer',
            'single' => true,
            'show_in_rest' => true,
            'sanitize_callback' => 'absint',
            'auth_callback' => function() { return current_user_can('manage_categories'); }
        ));
        register_term_meta('category', '_section_icon_svg', array(
            'type' => 'string',
            'single' => true,
            'show_in_rest' => true,
            'sanitize_callback' => function($val){ return wp_kses_post($val); },
            'auth_callback' => function() { return current_user_can('manage_categories'); }
        ));
        register_term_meta('category', '_section_icon_class', array(
            'type' => 'string',
            'single' => true,
            'show_in_rest' => true,
            'sanitize_callback' => function($val){ return sanitize_text_field($val); },
            'auth_callback' => function() { return current_user_can('manage_categories'); }
        ));
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
    $icon_id = (int) get_term_meta($term->term_id, '_section_icon_id', true);
    $icon_svg = get_term_meta($term->term_id, '_section_icon_svg', true);
    $icon_class = get_term_meta($term->term_id, '_section_icon_class', true);
    $icon_url = $icon_id ? wp_get_attachment_image_url($icon_id, 'thumbnail') : '';
    
    // Default-Werte
    if (empty($section_label)) $section_label = 'DIE LEISTUNGEN';
    if (empty($section_title)) $section_title = 'Unsere {category} im Überblick.';
    if (empty($section_description)) $section_description = 'Ein Auszug unserer Kernkompetenzen – präzise, zuverlässig und fachgerecht umgesetzt.';
    // Load predefined icon set (from this plugin's assets/icons)
    $icons_dir = plugin_dir_path(__FILE__) . 'assets/icons/';
    $icons_url = plugin_dir_url(__FILE__) . 'assets/icons/';
    $icon_files = [];
    if (is_dir($icons_dir)) {
        foreach (glob($icons_dir . '*.svg') as $file) {
            $name = basename($file);
            $svg  = file_get_contents($file);
            // Safe allowlist for preview (admin only)
            $allowed = [
                'svg' => [ 'xmlns'=>true, 'viewBox'=>true, 'width'=>true, 'height'=>true, 'fill'=>true, 'stroke'=>true, 'stroke-width'=>true, 'role'=>true, 'aria-hidden'=>true ],
                'path' => [ 'd'=>true, 'fill'=>true, 'stroke'=>true, 'stroke-width'=>true, 'fill-rule'=>true, 'clip-rule'=>true ],
                'g' => [ 'fill'=>true, 'stroke'=>true, 'stroke-width'=>true ],
                'circle' => [ 'cx'=>true, 'cy'=>true, 'r'=>true, 'fill'=>true, 'stroke'=>true, 'stroke-width'=>true ],
                'rect' => [ 'x'=>true, 'y'=>true, 'width'=>true, 'height'=>true, 'rx'=>true, 'ry'=>true, 'fill'=>true, 'stroke'=>true, 'stroke-width'=>true ],
                'polygon' => [ 'points'=>true, 'fill'=>true, 'stroke'=>true, 'stroke-width'=>true ],
                'line' => [ 'x1'=>true, 'y1'=>true, 'x2'=>true, 'y2'=>true, 'stroke'=>true, 'stroke-width'=>true ],
            ];
            $icon_files[] = [ 'name' => $name, 'svg' => wp_kses($svg, $allowed) ];
        }
    }
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

    <tr class="form-field">
        <th scope="row">
            <label for="section_icon_class">Icon (Font Awesome Klassen)</label>
        </th>
        <td>
            <input name="section_icon_class" id="section_icon_class" type="text" value="<?php echo esc_attr($icon_class); ?>" size="40" placeholder="z.B. fas fa-hard-hat oder fas fa-home" />
            <p class="description">Verwendet das Font Awesome Icon-Set (z.B. “fas fa-hard-hat”, “fas fa-home”). SVG hat Vorrang, dann dieses Feld, dann Bild.</p>
            <details style="margin-top:10px;">
                <summary>Aus Presets wählen (wie auf :8000)</summary>
                <div id="csc-preset-picker" style="margin-top:10px;">
                    <input type="text" id="csc-preset-search" class="regular-text" placeholder="Start typing to search…" style="width:360px;" />
                    <div id="csc-preset-results" style="max-height:260px;overflow:auto;border:1px solid #e5e7eb;border-radius:8px;margin-top:8px;padding:6px;background:#fff;display:grid;grid-template-columns:1fr;gap:6px"></div>
                    <p class="description">Klick auf einen Eintrag setzt die passende Font Awesome Klasse.</p>
                </div>
            </details>
        </td>
    </tr>

    <tr class="form-field">
        <th scope="row">
            <label for="section_icon_id">Icon (Bild)</label>
        </th>
        <td>
            <div id="csc-icon-picker" style="display:flex;align-items:center;gap:12px;">
                <div class="csc-icon-preview" style="width:64px;height:64px;border-radius:50%;background:#f3f4f6;display:flex;align-items:center;justify-content:center;overflow:hidden;">
                    <?php if ($icon_url): ?>
                        <img src="<?php echo esc_url($icon_url); ?>" alt="" style="max-width:100%;max-height:100%;display:block;" />
                    <?php else: ?>
                        <span style="color:#999">Kein Icon</span>
                    <?php endif; ?>
                </div>
                <input type="hidden" name="section_icon_id" id="section_icon_id" value="<?php echo esc_attr($icon_id); ?>" />
                <button type="button" class="button csc-icon-select">Icon auswählen</button>
                <button type="button" class="button csc-icon-remove" <?php echo $icon_id ? '' : 'style="display:none"'; ?>>Entfernen</button>
            </div>
            <p class="description">Quadratisches PNG/SVG/JPG empfiehlt sich. Wird in einem goldenen Kreis angezeigt.</p>
        </td>
    </tr>

    <tr class="form-field">
        <th scope="row">
            <label for="section_icon_svg">Icon (SVG-Markup)</label>
        </th>
        <td>
            <textarea name="section_icon_svg" id="section_icon_svg" rows="6" cols="50" placeholder="&lt;svg ...&gt;...&lt;/svg&gt;"><?php echo esc_textarea($icon_svg); ?></textarea>
            <p class="description">Optional: Direktes SVG (hat Vorrang vor dem Bild). Bitte nur sicheres SVG ohne Skripte einfügen.</p>
            <?php if (!empty($icon_files)): ?>
            <details style="margin-top:10px;">
                <summary>Aus RIMAN Iconset wählen</summary>
                <div class="csc-iconset-grid" style="display:grid;grid-template-columns:repeat(auto-fill,minmax(120px,1fr));gap:12px;margin-top:12px;">
                    <?php foreach ($icon_files as $ic): ?>
                        <div class="csc-iconset-item" style="border:1px solid #e5e7eb;border-radius:8px;padding:10px;text-align:center;background:#fff;">
                            <div class="csc-iconset-preview" style="width:64px;height:64px;border-radius:50%;background:#f3f4f6;margin:0 auto 8px;display:flex;align-items:center;justify-content:center;overflow:hidden;">
                                <?php echo $ic['svg']; ?>
                            </div>
                            <div style="font-size:12px;color:#666;word-break:break-all;margin-bottom:6px;">
                                <?php echo esc_html($ic['name']); ?>
                            </div>
                            <button type="button" class="button button-small csc-choose-from-set" data-svg='<?php echo json_encode($ic['svg']); ?>'>Übernehmen</button>
                        </div>
                    <?php endforeach; ?>
                </div>
            </details>
            <?php endif; ?>
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

    <div class="form-field">
        <label>Service Icon</label>
        <div id="csc-icon-picker" style="display:flex;align-items:center;gap:12px;margin-bottom:8px;">
            <div class="csc-icon-preview" style="width:64px;height:64px;border-radius:50%;background:#f3f4f6;display:flex;align-items:center;justify-content:center;overflow:hidden;">
                <span style="color:#999">Kein Icon</span>
            </div>
            <input name="section_icon_class" id="section_icon_class" type="text" value="" placeholder="z.B. fas fa-hard-hat" />
        </div>
        <details style="margin-top:6px;">
            <summary>Aus Presets wählen</summary>
            <div id="csc-preset-picker" style="margin-top:10px;">
                <input type="text" id="csc-preset-search" class="regular-text" placeholder="Start typing to search…" style="width:360px;" />
                <div id="csc-preset-results" style="max-height:260px;overflow:auto;border:1px solid #e5e7eb;border-radius:8px;margin-top:8px;padding:6px;background:#fff;display:grid;grid-template-columns:1fr;gap:6px"></div>
            </div>
        </details>
        <p>Wir speichern nur die Font Awesome Klasse. SVG/Bild nicht nötig.</p>
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
    if (isset($_POST['section_icon_id'])) {
        $icon_id = absint($_POST['section_icon_id']);
        if ($icon_id) update_term_meta($term_id, '_section_icon_id', $icon_id); else delete_term_meta($term_id, '_section_icon_id');
    }
    if (isset($_POST['section_icon_svg'])) {
        $svg = wp_kses_post($_POST['section_icon_svg']);
        if (!empty($svg)) update_term_meta($term_id, '_section_icon_svg', $svg); else delete_term_meta($term_id, '_section_icon_svg');
    }
    if (isset($_POST['section_icon_class'])) {
        $cls = sanitize_text_field($_POST['section_icon_class']);
        if (!empty($cls)) update_term_meta($term_id, '_section_icon_class', $cls); else delete_term_meta($term_id, '_section_icon_class');
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

/**
 * Admin: Media Picker für Icon
 */
add_action('admin_enqueue_scripts', function($hook) {
    $screen = get_current_screen();
    if (!$screen) return;
    if ($screen->id !== 'edit-category' && $screen->id !== 'term' && $screen->taxonomy !== 'category') return;
    wp_enqueue_script('csc-term-icon', plugin_dir_url(__FILE__) . 'assets/admin-term-icon.js', ['jquery'], '1.2.0', true);
    wp_localize_script('csc-term-icon', 'CSC_ICON', [
        'presetsUrl' => plugin_dir_url(__FILE__) . 'assets/icons/presets.json'
    ]);
    // Load Font Awesome 6 for admin preview of FA classes (stable)
    wp_enqueue_style('fontawesome', 'https://cdn.jsdelivr.net/npm/@fortawesome/fontawesome-free@6/css/all.min.css', [], '6');
});
