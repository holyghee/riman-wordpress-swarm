<?php
/**
 * RIMAN Hero Meta Box für riman_seiten
 * Titel, Untertitel, Bereichs-Label und Icon für Hero-Section
 */

if (!defined('ABSPATH')) exit;

class RIMAN_Hero_Meta {

    public function __construct() {
        add_action('add_meta_boxes', [$this, 'add_meta_box']);
        add_action('save_post_riman_seiten', [$this, 'save_meta_box']);
        add_action('admin_enqueue_scripts', [$this, 'enqueue_admin_scripts']);
    }

    public function add_meta_box() {
        add_meta_box(
            'riman_hero_meta',
            __('Hero-Section Einstellungen', 'riman'),
            [$this, 'render_meta_box'],
            'riman_seiten',
            'normal',
            'high'
        );
    }

    public function render_meta_box($post) {
        wp_nonce_field('riman_hero_meta_save', 'riman_hero_meta_nonce');

        // Aktuelle Werte laden
        $hero_title = get_post_meta($post->ID, '_riman_hero_title', true);
        $hero_subtitle = get_post_meta($post->ID, '_riman_hero_subtitle', true);
        $hero_area_label = get_post_meta($post->ID, '_riman_hero_area_label', true);
        $hero_icon = get_post_meta($post->ID, '_riman_hero_icon', true);
        $hero_longtext = get_post_meta($post->ID, '_riman_hero_longtext', true);
        $use_long_in_hero = (bool) get_post_meta($post->ID, '_riman_hero_use_long_in_hero', true);
        $cards_use_short_fallback = (bool) get_post_meta($post->ID, '_riman_hero_cards_use_short_fallback', true);
        $service_cards_offset = intval(get_post_meta($post->ID, '_riman_service_cards_offset', true));

        // Fallback-Werte anzeigen
        $default_title = get_the_title($post->ID);
        $default_subtitle = get_the_excerpt($post->ID) ?: '';
        ?>
        <style>
            .riman-hero-meta { margin: 15px 0; }
            .riman-hero-meta label {
                display: block;
                font-weight: 600;
                margin-bottom: 5px;
                color: #1d2327;
            }
            .riman-hero-meta input[type="text"],
            .riman-hero-meta textarea {
                width: 100%;
                margin-bottom: 10px;
            }
            .riman-hero-meta textarea {
                min-height: 80px;
                resize: vertical;
            }
            .riman-hero-preview {
                background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
                color: white;
                padding: 40px 20px;
                margin: 20px 0;
                border-radius: 8px;
                text-align: center;
            }
            .riman-hero-preview h2 {
                font-size: 2.5rem;
                margin: 0 0 10px 0;
                color: white;
            }
            .riman-hero-preview p {
                font-size: 1.1rem;
                margin: 0 0 15px 0;
                opacity: 0.9;
                color: white;
            }
            .riman-hero-preview .area-label {
                display: inline-block;
                background: rgba(182,140,47,0.25);
                padding: 5px 15px;
                border-radius: 20px;
                font-size: 0.9rem;
                margin-bottom: 20px;
            }
            .riman-hero-meta .help-text {
                font-size: 12px;
                color: #666;
                font-style: italic;
                margin-top: 5px;
            }
            .icon-preview {
                font-size: 2rem;
                margin: 10px 0;
                display: block;
            }
        </style>

        <div class="riman-hero-meta">
            <label for="riman_hero_title"><?php _e('Hero-Titel', 'riman'); ?></label>
            <input type="text"
                   id="riman_hero_title"
                   name="riman_hero_title"
                   value="<?php echo esc_attr($hero_title); ?>"
                   placeholder="<?php echo esc_attr($default_title); ?>">
            <div class="help-text">
                <?php _e('Leer lassen für automatischen Seitentitel (ohne "- Riman GmbH")', 'riman'); ?>
            </div>
        </div>

        <div class="riman-hero-meta">
            <label for="riman_hero_subtitle"><?php _e('Hero-Untertitel', 'riman'); ?></label>
            <textarea id="riman_hero_subtitle"
                      name="riman_hero_subtitle"
                      placeholder="<?php echo esc_attr($default_subtitle ?: 'Kurze Beschreibung für die Hero-Section'); ?>"><?php echo esc_textarea($hero_subtitle); ?></textarea>
            <div class="help-text">
                <?php _e('Leer lassen für automatischen Excerpt oder Featured Image Beschreibung', 'riman'); ?>
            </div>
        </div>

        <div class="riman-hero-meta">
            <label for="riman_hero_longtext"><?php _e('Langer Beschreibungstext (optional)', 'riman'); ?></label>
            <?php
            if (function_exists('wp_editor')) {
                ob_start();
                wp_editor(
                    $hero_longtext,
                    'riman_hero_longtext_editor',
                    [
                        'textarea_name' => 'riman_hero_longtext',
                        'media_buttons' => false,
                        'teeny'         => true,
                        'textarea_rows' => 6,
                        'editor_height' => 180,
                        'quicktags'     => [ 'buttons' => 'strong,em,ul,ol,li,code,close' ],
                        'tinymce'       => [
                            'toolbar1'      => 'bold,italic,alignleft,aligncenter,alignright,alignjustify,bullist,numlist,blockquote,undo,redo',
                            'toolbar2'      => '',
                            'plugins'       => 'lists,paste',
                            'menubar'       => false,
                            'statusbar'     => false,
                            'content_style' => 'body.mce-content-body{max-width:60ch;margin:0 auto;hyphens:auto;-webkit-hyphens:auto;word-break:normal;overflow-wrap:anywhere;} p{margin:0 0 0.8em;} ul,ol{margin:0 0 0.8em 1.2em;}',
                        ],
                    ]
                );
                echo ob_get_clean();
            } else {
                ?>
                <textarea id="riman_hero_longtext" name="riman_hero_longtext" rows="5"
                          placeholder="<?php echo esc_attr(__('Ausführlichere Beschreibung (z. B. für Service Cards)', 'riman')); ?>"><?php echo esc_textarea($hero_longtext); ?></textarea>
                <?php
            }
            ?>
            <div class="help-text">
                <?php _e('Wird standardmäßig nur in Service Cards verwendet. Im Hero nur wenn unten aktiviert.', 'riman'); ?>
            </div>
        </div>

        

        

        <div class="riman-hero-meta">
            <label for="riman_hero_area_label"><?php _e('Bereichs-Label', 'riman'); ?></label>
            <input type="text"
                   id="riman_hero_area_label"
                   name="riman_hero_area_label"
                   value="<?php echo esc_attr($hero_area_label); ?>"
                   placeholder="<?php _e('z.B. Sicherheits-Management, Mediation, etc.', 'riman'); ?>">
            <div class="help-text">
                <?php _e('Kleines Label über dem Titel (wie bei Kategorie-Seiten)', 'riman'); ?>
            </div>
        </div>

        <div class="riman-hero-meta">
            <label for="riman_service_cards_offset"><?php _e('Service Cards Überlappung (px)', 'riman'); ?></label>
            <input type="number"
                   id="riman_service_cards_offset"
                   name="riman_service_cards_offset"
                   value="<?php echo esc_attr($service_cards_offset); ?>"
                   min="0"
                   max="400"
                   step="5"
                   placeholder="0">
            <div class="help-text">
                <?php _e('Positiver Wert verschiebt die Service Cards nach oben (Überlappung mit dem Hero). 0 = kein Versatz.', 'riman'); ?>
            </div>
        </div>

        <div class="riman-hero-meta">
            <details>
                <summary style="cursor:pointer;font-weight:600;<?php echo $use_long_in_hero||$cards_use_short_fallback ? 'color:#1e4a6d;' : '' ?>">
                    <?php _e('Erweiterte Anzeige‑Optionen (Overrides)', 'riman'); ?>
                </summary>
                <div class="help-text" style="margin:8px 0 10px;">
                    <?php _e('Standard: Hero zeigt den Untertitel. Service Cards zeigen den langen Text. Aktivieren, nur wenn du hiervon abweichen willst.', 'riman'); ?>
                </div>
                <label style="display:block; margin-top:6px;">
                    <input type="checkbox" id="riman_hero_use_long_in_hero" name="riman_hero_use_long_in_hero" value="1" <?php checked($use_long_in_hero, true); ?> />
                    <?php _e('Override: Im Hero langen Text verwenden', 'riman'); ?>
                </label>
                <label style="display:block; margin-top:6px;">
                    <input type="checkbox" id="riman_hero_cards_use_short_fallback" name="riman_hero_cards_use_short_fallback" value="1" <?php checked($cards_use_short_fallback, true); ?> />
                    <?php _e('Override: In Service Cards Untertitel verwenden', 'riman'); ?>
                </label>
                
            </details>
        </div>

        <div class="riman-hero-meta">
            <label for="riman_hero_icon"><?php _e('Icon (Font Awesome)', 'riman'); ?></label>
            <input type="text"
                   id="riman_hero_icon"
                   name="riman_hero_icon"
                   value="<?php echo esc_attr($hero_icon); ?>"
                   placeholder="<?php _e('z.B. fas fa-shield-alt, fas fa-handshake, etc.', 'riman'); ?>">
            <div class="help-text">
                <?php _e('Font Awesome Klassen (wird neben dem Bereichs-Label angezeigt)', 'riman'); ?>
                <br><a href="https://fontawesome.com/icons" target="_blank"><?php _e('Font Awesome Icons durchsuchen', 'riman'); ?></a>
            </div>
            <div id="icon-preview" class="icon-preview"></div>
        </div>

        <!-- Live-Vorschau -->
        <div class="riman-hero-preview">
            <div id="preview-area-label" class="area-label" style="<?php echo empty($hero_area_label) && empty($hero_icon) ? 'display:none;' : ''; ?>">
                <i id="preview-icon"></i>
                <span id="preview-label-text"></span>
            </div>
            <h2 id="preview-title"><?php echo esc_html($hero_title ?: $default_title); ?></h2>
            <p id="preview-subtitle"><?php echo esc_html($hero_subtitle ?: $default_subtitle ?: 'Hero-Untertitel wird hier angezeigt'); ?></p>
        </div>

        <script>
        (function($) {
            function updatePreview() {
                const title = $('#riman_hero_title').val() || '<?php echo esc_js($default_title); ?>';
                const subtitle = $('#riman_hero_subtitle').val();
                const longtext = $('#riman_hero_longtext').val();
                const useLongInHero = $('#riman_hero_use_long_in_hero').is(':checked');
                const areaLabel = $('#riman_hero_area_label').val();
                const icon = $('#riman_hero_icon').val();

                $('#preview-title').text(title);
                $('#preview-subtitle').text((useLongInHero ? (longtext || subtitle) : (subtitle || longtext)) || '<?php echo esc_js($default_subtitle ?: 'Hero-Untertitel wird hier angezeigt'); ?>');
                $('#preview-label-text').text(areaLabel);

                if (icon) {
                    $('#preview-icon').attr('class', icon).show();
                    $('#icon-preview').attr('class', 'icon-preview ' + icon);
                } else {
                    $('#preview-icon').hide();
                    $('#icon-preview').attr('class', 'icon-preview');
                }

                if (areaLabel || icon) {
                    $('#preview-area-label').show();
                } else {
                    $('#preview-area-label').hide();
                }
            }

            $(document).ready(function() {
                updatePreview();
                $('#riman_hero_title, #riman_hero_subtitle, #riman_hero_longtext, #riman_hero_area_label, #riman_hero_icon, #riman_hero_use_long_in_hero').on('input change', updatePreview);
            });
        })(jQuery);
        </script>
        <?php
    }

    public function save_meta_box($post_id) {
        // Sicherheitsüberprüfungen
        if (!isset($_POST['riman_hero_meta_nonce']) ||
            !wp_verify_nonce($_POST['riman_hero_meta_nonce'], 'riman_hero_meta_save')) {
            return;
        }

        if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) return;
        if (!current_user_can('edit_post', $post_id)) return;

        // Meta-Felder speichern
        $fields = [
            '_riman_hero_title' => 'sanitize_text_field',
            '_riman_hero_subtitle' => 'sanitize_textarea_field',
            '_riman_hero_area_label' => 'sanitize_text_field',
            '_riman_hero_icon' => 'sanitize_text_field',
            '_riman_hero_longtext' => 'sanitize_textarea_field',
            '_riman_service_cards_offset' => 'absint'
        ];

        foreach ($fields as $field => $sanitize_func) {
            $post_field = str_replace('_riman_hero_', 'riman_hero_', $field);
            $raw = isset($_POST[$post_field]) ? $_POST[$post_field] : '';
            if ($field === '_riman_hero_longtext') {
                $allowed = [
                    'p' => ['style'=>true,'class'=>true], 'br' => [], 'strong' => [], 'em' => [], 'b' => [], 'i' => [],
                    'ul'=>['style'=>true,'class'=>true], 'ol'=>['style'=>true,'class'=>true], 'li'=>['style'=>true,'class'=>true],
                    'blockquote'=>['style'=>true,'class'=>true], 'code'=>['class'=>true]
                ];
                $value = wp_kses($raw, $allowed);
            } else {
                $value = $sanitize_func($raw);
            }

            if ($field === '_riman_service_cards_offset') {
                if ($value > 0) {
                    update_post_meta($post_id, $field, $value);
                } else {
                    delete_post_meta($post_id, $field);
                }
            } elseif (!empty($value)) {
                update_post_meta($post_id, $field, $value);
            } else {
                delete_post_meta($post_id, $field);
            }
        }

        // Checkboxen speichern: nur Overrides setzen (kein Eintrag = Standardverhalten)
        if (isset($_POST['riman_hero_use_long_in_hero'])) {
            update_post_meta($post_id, '_riman_hero_use_long_in_hero', 1);
        } else {
            delete_post_meta($post_id, '_riman_hero_use_long_in_hero');
        }
        if (isset($_POST['riman_hero_cards_use_short_fallback'])) {
            update_post_meta($post_id, '_riman_hero_cards_use_short_fallback', 1);
        } else {
            delete_post_meta($post_id, '_riman_hero_cards_use_short_fallback');
        }
        // Ausrichtung: entfernt – Ausrichtung erfolgt per Absatz im Editor
    }

    public function enqueue_admin_scripts($hook) {
        if (in_array($hook, ['post.php', 'post-new.php'])) {
            // Font Awesome für Icon-Vorschau
            wp_enqueue_style('font-awesome', 'https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css', [], '6.4.0');
        }
    }

    /**
     * Hilfsfunktion: Hole Hero-Meta-Daten für eine Seite
     */
    public static function get_hero_meta($post_id) {
        return [
            'title' => get_post_meta($post_id, '_riman_hero_title', true),
            'subtitle' => get_post_meta($post_id, '_riman_hero_subtitle', true),
            'area_label' => get_post_meta($post_id, '_riman_hero_area_label', true),
            'icon' => get_post_meta($post_id, '_riman_hero_icon', true),
            'long_text' => get_post_meta($post_id, '_riman_hero_longtext', true),
            'use_long_in_hero' => (bool) get_post_meta($post_id, '_riman_hero_use_long_in_hero', true),
            'cards_use_short_fallback' => (bool) get_post_meta($post_id, '_riman_hero_cards_use_short_fallback', true),
            'service_cards_overlap' => absint(get_post_meta($post_id, '_riman_service_cards_offset', true))
        ];
    }
}

// Initialisieren
new RIMAN_Hero_Meta();
?>
