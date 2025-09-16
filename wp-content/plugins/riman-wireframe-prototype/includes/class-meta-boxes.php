<?php
/**
 * RIMAN Wireframe Prototype - Meta Boxes
 * Verwaltet die Meta Boxes für verschiedene Seitentypen
 */

// Verhindere direkten Zugriff
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Meta Boxes Klasse
 * Erstellt spezifische Meta Boxes je nach Seitentyp
 */
class RIMAN_Wireframe_Meta_Boxes {

    /**
     * Konstruktor
     */
    public function __construct() {
        add_action('add_meta_boxes', array($this, 'add_meta_boxes'));
        add_action('save_post', array($this, 'save_meta_boxes'));
        add_action('admin_enqueue_scripts', array($this, 'enqueue_admin_scripts'));
    }

    /**
     * Füge Meta Boxes hinzu
     */
    public function add_meta_boxes() {
        add_meta_box(
            'riman_wireframe_content',
            __('RIMAN Seitentyp Inhalte', 'riman-wireframe'),
            array($this, 'render_meta_box'),
            'riman_seiten',
            'normal',
            'high'
        );
    }

    /**
     * Render Meta Box basierend auf Seitentyp
     */
    public function render_meta_box($post) {
        // Nonce für Sicherheit
        wp_nonce_field('riman_wireframe_meta_nonce', 'riman_wireframe_nonce');
        
        // Hole aktuellen Seitentyp
        $seitentyp_terms = wp_get_post_terms($post->ID, 'seitentyp');
        $current_seitentyp = '';
        
        if (!empty($seitentyp_terms) && !is_wp_error($seitentyp_terms)) {
            $current_seitentyp = $seitentyp_terms[0]->slug;
        }

        ?>
        <div id="riman-wireframe-meta-container">
            <style>
                .riman-meta-field { margin: 15px 0; }
                .riman-meta-field label { display: block; font-weight: bold; margin-bottom: 5px; }
                .riman-meta-field input[type="text"], 
                .riman-meta-field input[type="url"], 
                .riman-meta-field textarea { width: 100%; }
                .riman-meta-field textarea { height: 80px; resize: vertical; }
                .riman-repeater { border: 1px solid #ddd; padding: 15px; margin: 10px 0; border-radius: 4px; }
                .riman-repeater-item { border: 1px solid #ccc; padding: 10px; margin: 5px 0; background: #f9f9f9; }
                .riman-repeater-header { font-weight: bold; margin-bottom: 10px; cursor: pointer; }
                .riman-repeater-content { display: block; }
                .riman-remove-item { float: right; color: #a00; text-decoration: none; font-weight: bold; }
                .riman-add-item { background: #0073aa; color: white; padding: 8px 15px; border: none; border-radius: 3px; cursor: pointer; margin-top: 10px; }
                .riman-seitentyp-info { background: #e7f3ff; padding: 10px; border-left: 4px solid #0073aa; margin-bottom: 20px; }
            </style>

            <div class="riman-seitentyp-info">
                <strong><?php _e('Aktueller Seitentyp:', 'riman-wireframe'); ?></strong> 
                <span id="current-seitentyp"><?php echo $current_seitentyp ? esc_html($current_seitentyp) : __('Kein Seitentyp gewählt', 'riman-wireframe'); ?></span>
                <p><em><?php _e('Wählen Sie zunächst einen Seitentyp in der rechten Sidebar, um spezifische Felder zu sehen.', 'riman-wireframe'); ?></em></p>
            </div>

            <!-- Hauptseite Felder -->
            <div id="hauptseite-fields" class="seitentyp-fields" style="display: <?php echo $current_seitentyp === 'hauptseite' ? 'block' : 'none'; ?>">
                <h3><?php _e('Hauptseite Inhalte', 'riman-wireframe'); ?></h3>
                
                <div class="riman-meta-field">
                    <label for="hauptseite_video_url"><?php _e('Großes Video URL', 'riman-wireframe'); ?></label>
                    <input type="url" 
                           id="hauptseite_video_url" 
                           name="riman_hauptseite_video_url" 
                           value="<?php echo esc_attr(get_post_meta($post->ID, '_riman_hauptseite_video_url', true)); ?>" 
                           placeholder="https://example.com/video.mp4">
                </div>
                
                <div class="riman-meta-field">
                    <label for="hauptseite_beschreibung"><?php _e('Beschreibung', 'riman-wireframe'); ?></label>
                    <textarea id="hauptseite_beschreibung" 
                              name="riman_hauptseite_beschreibung" 
                              placeholder="<?php esc_attr_e('Beschreibung der Hauptseite...', 'riman-wireframe'); ?>"><?php echo esc_textarea(get_post_meta($post->ID, '_riman_hauptseite_beschreibung', true)); ?></textarea>
                </div>
            </div>

            <!-- Unterseite Felder -->
            <div id="unterseite-fields" class="seitentyp-fields" style="display: <?php echo $current_seitentyp === 'unterseite' ? 'block' : 'none'; ?>">
                <h3><?php _e('Unterseite Inhalte', 'riman-wireframe'); ?></h3>
                
                <div class="riman-meta-field">
                    <label for="unterseite_video_url"><?php _e('Großes Video URL', 'riman-wireframe'); ?></label>
                    <input type="url" 
                           id="unterseite_video_url" 
                           name="riman_unterseite_video_url" 
                           value="<?php echo esc_attr(get_post_meta($post->ID, '_riman_unterseite_video_url', true)); ?>" 
                           placeholder="https://example.com/video.mp4">
                </div>
                
                <div class="riman-meta-field">
                    <label for="unterseite_detailseiten_anzahl"><?php _e('Anzahl verlinkter Detailseiten (2-4)', 'riman-wireframe'); ?></label>
                    <input type="number" 
                           id="unterseite_detailseiten_anzahl" 
                           name="riman_unterseite_detailseiten_anzahl" 
                           value="<?php echo esc_attr(get_post_meta($post->ID, '_riman_unterseite_detailseiten_anzahl', true) ?: '3'); ?>" 
                           min="2" 
                           max="4">
                </div>
            </div>

            <!-- Detailseite Felder -->
            <div id="detailseite-fields" class="seitentyp-fields" style="display: <?php echo $current_seitentyp === 'detailseite' ? 'block' : 'none'; ?>">
                <h3><?php _e('Detailseite Inhalte', 'riman-wireframe'); ?></h3>
                <p><em><?php _e('4 wiederholbare Video-Text Infofelder (nicht verlinkt laut Wireframe)', 'riman-wireframe'); ?></em></p>
                
                <div class="riman-repeater">
                    <div id="video-info-fields">
                        <?php 
                        $video_info_fields = get_post_meta($post->ID, '_riman_detailseite_video_info', true);
                        if (!is_array($video_info_fields)) {
                            $video_info_fields = array(
                                array('video_url' => '', 'ueberschrift' => '', 'beschreibung' => ''),
                                array('video_url' => '', 'ueberschrift' => '', 'beschreibung' => ''),
                                array('video_url' => '', 'ueberschrift' => '', 'beschreibung' => ''),
                                array('video_url' => '', 'ueberschrift' => '', 'beschreibung' => '')
                            );
                        }
                        
                        foreach ($video_info_fields as $index => $field):
                        ?>
                        <div class="riman-repeater-item" data-index="<?php echo $index; ?>">
                            <div class="riman-repeater-header">
                                <?php printf(__('Video-Info Feld %d', 'riman-wireframe'), $index + 1); ?>
                                <?php if ($index >= 4): ?>
                                <a href="#" class="riman-remove-item"><?php _e('Entfernen', 'riman-wireframe'); ?></a>
                                <?php endif; ?>
                            </div>
                            <div class="riman-repeater-content">
                                <div class="riman-meta-field">
                                    <label><?php _e('Video URL', 'riman-wireframe'); ?></label>
                                    <input type="url" 
                                           name="riman_detailseite_video_info[<?php echo $index; ?>][video_url]" 
                                           value="<?php echo esc_attr($field['video_url'] ?? ''); ?>" 
                                           placeholder="https://example.com/video.mp4">
                                </div>
                                <div class="riman-meta-field">
                                    <label><?php _e('Überschrift', 'riman-wireframe'); ?></label>
                                    <input type="text" 
                                           name="riman_detailseite_video_info[<?php echo $index; ?>][ueberschrift]" 
                                           value="<?php echo esc_attr($field['ueberschrift'] ?? ''); ?>" 
                                           placeholder="<?php esc_attr_e('Überschrift des Infofelds', 'riman-wireframe'); ?>">
                                </div>
                                <div class="riman-meta-field">
                                    <label><?php _e('Beschreibung', 'riman-wireframe'); ?></label>
                                    <textarea name="riman_detailseite_video_info[<?php echo $index; ?>][beschreibung]" 
                                              placeholder="<?php esc_attr_e('Beschreibung des Infofelds...', 'riman-wireframe'); ?>"><?php echo esc_textarea($field['beschreibung'] ?? ''); ?></textarea>
                                </div>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                    
                    <button type="button" class="riman-add-item" id="add-video-info-field">
                        <?php _e('Weiteres Video-Info Feld hinzufügen', 'riman-wireframe'); ?>
                    </button>
                </div>
            </div>


            <script type="text/javascript">
            jQuery(document).ready(function($) {
                // Seitentyp-Wechsel überwachen
                function updateMetaFields() {
                    var selectedType = '';
                    
                    // Hole ausgewählten Seitentyp aus der Taxonomy Checkbox
                    $('input[name="tax_input[seitentyp][]"]:checked').each(function() {
                        var termName = $(this).closest('label').text().trim();
                        // Map Labels zu Slugs
                        if (termName === 'Hauptseite') selectedType = 'hauptseite';
                        else if (termName === 'Unterseite') selectedType = 'unterseite';
                        else if (termName === 'Detailseite') selectedType = 'detailseite';
                        // nur Hauptseite/Unterseite/Detailseite verwenden
                });
                    
                    // Aktualisiere Anzeige
                    $('#current-seitentyp').text(selectedType || '<?php _e("Kein Seitentyp gewählt", "riman-wireframe"); ?>');
                    
                    // Zeige/verstecke entsprechende Felder
                    $('.seitentyp-fields').hide();
                    if (selectedType) {
                        $('#' + selectedType + '-fields').show();
                    }
                }
                
                // Initial ausführen
                updateMetaFields();
                
                // Bei Änderung der Taxonomy
                $(document).on('change', 'input[name="tax_input[seitentyp][]"]', updateMetaFields);
                
                // Repeater Funktionalität für Detailseiten
                var videoInfoIndex = <?php echo count($video_info_fields); ?>;
                
                // Feld hinzufügen
                $('#add-video-info-field').on('click', function(e) {
                    e.preventDefault();
                    
                    var newField = '<div class="riman-repeater-item" data-index="' + videoInfoIndex + '">' +
                        '<div class="riman-repeater-header">' +
                            '<?php printf(__("Video-Info Feld %s", "riman-wireframe"), "' + (videoInfoIndex + 1) + '"); ?>' +
                            '<a href="#" class="riman-remove-item"><?php _e("Entfernen", "riman-wireframe"); ?></a>' +
                        '</div>' +
                        '<div class="riman-repeater-content">' +
                            '<div class="riman-meta-field">' +
                                '<label><?php _e("Video URL", "riman-wireframe"); ?></label>' +
                                '<input type="url" name="riman_detailseite_video_info[' + videoInfoIndex + '][video_url]" placeholder="https://example.com/video.mp4">' +
                            '</div>' +
                            '<div class="riman-meta-field">' +
                                '<label><?php _e("Überschrift", "riman-wireframe"); ?></label>' +
                                '<input type="text" name="riman_detailseite_video_info[' + videoInfoIndex + '][ueberschrift]" placeholder="<?php esc_attr_e("Überschrift des Infofelds", "riman-wireframe"); ?>">' +
                            '</div>' +
                            '<div class="riman-meta-field">' +
                                '<label><?php _e("Beschreibung", "riman-wireframe"); ?></label>' +
                                '<textarea name="riman_detailseite_video_info[' + videoInfoIndex + '][beschreibung]" placeholder="<?php esc_attr_e("Beschreibung des Infofelds...", "riman-wireframe"); ?>"></textarea>' +
                            '</div>' +
                        '</div>' +
                    '</div>';
                    
                    $('#video-info-fields').append(newField);
                    videoInfoIndex++;
                });
                
                // Feld entfernen
                $(document).on('click', '.riman-remove-item', function(e) {
                    e.preventDefault();
                    $(this).closest('.riman-repeater-item').remove();
                });
            });
            </script>
        </div>
        <?php
    }

    /**
     * Speichere Meta Box Daten
     */
    public function save_meta_boxes($post_id) {
        // Prüfe Nonce
        if (!isset($_POST['riman_wireframe_nonce']) || 
            !wp_verify_nonce($_POST['riman_wireframe_nonce'], 'riman_wireframe_meta_nonce')) {
            return;
        }

        // Prüfe Autosave
        if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
            return;
        }

        // Prüfe Berechtigungen
        if (!current_user_can('edit_post', $post_id)) {
            return;
        }

        // Prüfe Post Type
        if (get_post_type($post_id) !== 'riman_seiten') {
            return;
        }

        // Speichere Hauptseite Daten
        if (isset($_POST['riman_hauptseite_video_url'])) {
            update_post_meta($post_id, '_riman_hauptseite_video_url', esc_url_raw($_POST['riman_hauptseite_video_url']));
        }
        if (isset($_POST['riman_hauptseite_beschreibung'])) {
            update_post_meta($post_id, '_riman_hauptseite_beschreibung', sanitize_textarea_field($_POST['riman_hauptseite_beschreibung']));
        }

        // Speichere Unterseite Daten
        if (isset($_POST['riman_unterseite_video_url'])) {
            update_post_meta($post_id, '_riman_unterseite_video_url', esc_url_raw($_POST['riman_unterseite_video_url']));
        }
        if (isset($_POST['riman_unterseite_detailseiten_anzahl'])) {
            $anzahl = intval($_POST['riman_unterseite_detailseiten_anzahl']);
            $anzahl = max(2, min(4, $anzahl)); // Begrenze auf 2-4
            update_post_meta($post_id, '_riman_unterseite_detailseiten_anzahl', $anzahl);
        }

        // Speichere Detailseite Daten
        if (isset($_POST['riman_detailseite_video_info']) && is_array($_POST['riman_detailseite_video_info'])) {
            $video_info_fields = array();
            
            foreach ($_POST['riman_detailseite_video_info'] as $field) {
                $video_info_fields[] = array(
                    'video_url' => esc_url_raw($field['video_url'] ?? ''),
                    'ueberschrift' => sanitize_text_field($field['ueberschrift'] ?? ''),
                    'beschreibung' => sanitize_textarea_field($field['beschreibung'] ?? '')
                );
            }
            
            update_post_meta($post_id, '_riman_detailseite_video_info', $video_info_fields);
        }

        // Keine separaten Info-Posttypen – Infofelder verbleiben als Detailseiten-Metafelder
    }

    /**
     * Lade Admin Scripts
     */
    public function enqueue_admin_scripts($hook) {
        // Nur auf riman_seiten Edit-Seiten laden
        if ($hook === 'post.php' || $hook === 'post-new.php') {
            global $post;
            if ($post && $post->post_type === 'riman_seiten') {
                wp_enqueue_script('jquery');
            }
        }
    }

    /**
     * Hilfsfunktion: Hole Meta-Daten für einen Post
     */
    public static function get_page_meta($post_id, $page_type = '') {
        if (!$page_type) {
            $terms = wp_get_post_terms($post_id, 'seitentyp');
            if (!empty($terms) && !is_wp_error($terms)) {
                $page_type = $terms[0]->slug;
            }
        }

        $meta = array();

        switch ($page_type) {
            case 'hauptseite':
                $meta['video_url'] = get_post_meta($post_id, '_riman_hauptseite_video_url', true);
                $meta['beschreibung'] = get_post_meta($post_id, '_riman_hauptseite_beschreibung', true);
                break;

            case 'unterseite':
                $meta['video_url'] = get_post_meta($post_id, '_riman_unterseite_video_url', true);
                $meta['detailseiten_anzahl'] = get_post_meta($post_id, '_riman_unterseite_detailseiten_anzahl', true);
                break;

            case 'detailseite':
                $meta['video_info'] = get_post_meta($post_id, '_riman_detailseite_video_info', true);
                break;
        }

        return $meta;
    }
}
