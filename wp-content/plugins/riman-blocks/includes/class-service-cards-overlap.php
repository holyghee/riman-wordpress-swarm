<?php
/**
 * Service Cards Overlap Meta Box
 */

if (!defined('ABSPATH')) {
    exit;
}

class RIMAN_Service_Cards_Overlap_Meta {

    public function __construct() {
        add_action('add_meta_boxes', [$this, 'add_meta_box']);
        add_action('save_post', [$this, 'save_meta']);
    }

    public function add_meta_box() {
        // Meta box disabled - functionality moved to block attributes
        $post_types = [];
        foreach ($post_types as $ptype) {
            if (post_type_exists($ptype)) {
                add_meta_box(
                    'riman_service_cards_overlap',
                    __('Service Cards Überlappung', 'riman'),
                    [$this, 'render_meta_box'],
                    $ptype,
                    'side',
                    'high'
                );
            }
        }
    }

    public function render_meta_box($post) {
        wp_nonce_field('riman_service_cards_overlap_nonce', 'riman_service_cards_overlap_nonce');
        $value = absint(get_post_meta($post->ID, '_riman_service_cards_offset', true));
        $content_offset = absint(get_post_meta($post->ID, '_riman_service_cards_content_offset', true));
        ?>
        <p>
            <label for="riman_service_cards_offset">
                <?php esc_html_e('Versatz nach oben (in Pixel)', 'riman'); ?>
            </label>
        </p>
        <input type="number"
               name="riman_service_cards_offset"
               id="riman_service_cards_offset"
               value="<?php echo esc_attr($value); ?>"
               min="0"
               max="400"
               step="5"
               style="width:100%;" />
        <p class="description">
            <?php esc_html_e('0 = kein Versatz. Positive Werte ziehen die Service Cards näher an den Hero (Überlappung).', 'riman'); ?>
        </p>

        <hr style="margin: 20px 0;">

        <p>
            <label for="riman_service_cards_content_offset">
                <?php esc_html_e('Inhalt nach unten versetzen (in Pixel)', 'riman'); ?>
            </label>
        </p>
        <input type="number"
               name="riman_service_cards_content_offset"
               id="riman_service_cards_content_offset"
               value="<?php echo esc_attr($content_offset); ?>"
               min="0"
               max="100"
               step="5"
               style="width:100%;" />
        <p class="description">
            <?php esc_html_e('Verschiebt den Inhalt (ab Icon) nach unten. 0 = Standard-Position.', 'riman'); ?>
        </p>
        <?php
    }

    public function save_meta($post_id) {
        if (!isset($_POST['riman_service_cards_overlap_nonce']) ||
            !wp_verify_nonce($_POST['riman_service_cards_overlap_nonce'], 'riman_service_cards_overlap_nonce')) {
            return;
        }

        if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
            return;
        }

        $post_type = get_post_type($post_id);
        if (!in_array($post_type, ['riman_seiten', 'page'], true)) {
            return;
        }

        if (!current_user_can('edit_post', $post_id)) {
            return;
        }

        $value = isset($_POST['riman_service_cards_offset']) ? absint($_POST['riman_service_cards_offset']) : 0;
        if ($value > 0) {
            update_post_meta($post_id, '_riman_service_cards_offset', $value);
        } else {
            delete_post_meta($post_id, '_riman_service_cards_offset');
        }

        $content_offset = isset($_POST['riman_service_cards_content_offset']) ? absint($_POST['riman_service_cards_content_offset']) : 0;
        if ($content_offset > 0) {
            update_post_meta($post_id, '_riman_service_cards_content_offset', $content_offset);
        } else {
            delete_post_meta($post_id, '_riman_service_cards_content_offset');
        }
    }
}

// Meta box functionality moved to Service Cards block attributes
// new RIMAN_Service_Cards_Overlap_Meta();
