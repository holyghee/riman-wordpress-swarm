<?php
// RIMAN Blocks: Category Header (dynamic)
if (!defined('ABSPATH')) exit;

add_action('init', function() {
    wp_register_script(
        'riman-category-header-block',
        plugin_dir_url(__FILE__) . '../assets/category-header-block.js',
        ['wp-blocks', 'wp-element', 'wp-editor', 'wp-components'],
        '1.0'
    );

    register_block_type('riman/category-header', [
        'editor_script' => 'riman-category-header-block',
        'render_callback' => function($attributes) {
            // Determine category context
            if (!is_category()) {
                $category_name = 'Beispielkategorie';
                $category_id = null;
            } else {
                $cat = get_queried_object();
                $category_name = $cat->name;
                $category_id = $cat->term_id;
            }

            $label = isset($attributes['label']) ? $attributes['label'] : 'DIE LEISTUNGEN';
            $titleTemplate = isset($attributes['titleTemplate']) ? $attributes['titleTemplate'] : 'Unsere {category} im Überblick.';
            $description = isset($attributes['description']) ? $attributes['description'] : 'Ein Auszug unserer Kernkompetenzen – präzise, zuverlässig und fachgerecht umgesetzt.';

            if ($category_id) {
                $saved_label = get_term_meta($category_id, '_section_label', true);
                $saved_title = get_term_meta($category_id, '_section_title', true);
                $saved_description = get_term_meta($category_id, '_section_description', true);
                if (!empty($saved_label)) $label = $saved_label;
                if (!empty($saved_title)) $titleTemplate = $saved_title;
                if (!empty($saved_description)) $description = $saved_description;
            }

            $title = str_replace('{category}', '<em>' . esc_html($category_name) . '</em>', $titleTemplate);

            // Icon from term meta (optional)
            $icon_svg = $category_id ? (string) get_term_meta($category_id, '_section_icon_svg', true) : '';
            $icon_id  = $category_id ? (int) get_term_meta($category_id, '_section_icon_id', true) : 0;
            $icon_cls = $category_id ? (string) get_term_meta($category_id, '_section_icon_class', true) : '';
            $icon_url = $icon_id ? wp_get_attachment_image_url($icon_id, 'thumbnail') : '';

            // Ensure Font Awesome 7 for FA class icons
            if (!wp_style_is('fontawesome', 'enqueued')) {
                wp_enqueue_style('fontawesome', 'https://cdn.jsdelivr.net/npm/@fortawesome/fontawesome-free@6/css/all.min.css', [], '6');
            }

            // Map FA5 → FA6 prefixes for backward compatibility
            if ($icon_cls) {
                $icon_cls = trim($icon_cls, "\"' \n\r\t");
                $icon_cls = preg_replace('/\bfas\b/', 'fa-solid', $icon_cls);
                $icon_cls = preg_replace('/\bfar\b/', 'fa-regular', $icon_cls);
                $icon_cls = preg_replace('/\bfab\b/', 'fa-brands', $icon_cls);
            }
            // Attempt to decode JSON-wrapped SVG (legacy) and validate
            if (!empty($icon_svg)) {
                $raw = trim($icon_svg);
                if (strpos($raw, '<svg') === false) {
                    $decoded = json_decode($raw, true);
                    if (is_string($decoded)) {
                        $icon_svg = $decoded;
                    } else {
                        $icon_svg = '';
                    }
                }
            }

            ob_start(); ?>
            <div class="riman-category-header">
                <?php if ($category_id && ($icon_svg || $icon_cls || $icon_url)): ?>
                <div class="riman-header-icon" aria-hidden="true">
                    <?php if ($icon_cls) {
                        echo '<i class="' . esc_attr($icon_cls) . '" aria-hidden="true"></i>';
                    } elseif ($icon_svg) {
                        echo wp_kses($icon_svg, [
                            'svg' => [ 'xmlns'=>true, 'viewBox'=>true, 'width'=>true, 'height'=>true, 'fill'=>true, 'stroke'=>true, 'stroke-width'=>true, 'role'=>true, 'aria-hidden'=>true ],
                            'path' => [ 'd'=>true, 'fill'=>true, 'stroke'=>true, 'stroke-width'=>true, 'fill-rule'=>true, 'clip-rule'=>true ],
                            'g' => [ 'fill'=>true, 'stroke'=>true, 'stroke-width'=>true ],
                            'circle' => [ 'cx'=>true, 'cy'=>true, 'r'=>true, 'fill'=>true, 'stroke'=>true, 'stroke-width'=>true ],
                            'rect' => [ 'x'=>true, 'y'=>true, 'width'=>true, 'height'=>true, 'rx'=>true, 'ry'=>true, 'fill'=>true, 'stroke'=>true, 'stroke-width'=>true ],
                            'polygon' => [ 'points'=>true, 'fill'=>true, 'stroke'=>true, 'stroke-width'=>true ],
                            'line' => [ 'x1'=>true, 'y1'=>true, 'x2'=>true, 'y2'=>true, 'stroke'=>true, 'stroke-width'=>true ],
                        ]);
                    } else {
                        echo '<img src="' . esc_url($icon_url) . '" alt="" width="48" height="48" />';
                    } ?>
                </div>
                <?php endif; ?>
                <span class="riman-section-label"><?php echo esc_html($label); ?></span>
                <h2 class="riman-section-title"><?php echo wp_kses_post($title); ?></h2>
                <p class="riman-section-description"><?php echo esc_html($description); ?></p>
            </div>
            <style>
            .riman-category-header { text-align:center; margin:60px 0 40px }
            .riman-header-icon { width:84px; height:84px; margin:0 auto 16px; background:#b68c2f; border-radius:50%; display:flex; align-items:center; justify-content:center; border:4px solid #fff; color:#fff }
            .riman-header-icon img, .riman-header-icon svg { max-width:48px; max-height:48px; display:block }
            .riman-header-icon i { font-size:48px; line-height:1; }
            .riman-section-label { display:inline-block; background:#b68c2f; color:#fff; padding:6px 20px; border-radius:20px; font-size:.85rem; font-weight:600; letter-spacing:1px; text-transform:uppercase; margin-bottom:15px }
            .riman-section-title { font-size:2.5rem!important; margin:20px 0; color:#1e4a6d }
            .riman-section-title em { color:#b68c2f; font-style:italic }
            .riman-section-description { color:#666; font-size:1.1rem; margin:0 }
            </style>
            <?php
            return ob_get_clean();
        },
        'attributes' => [
            'label' => ['type' => 'string', 'default' => 'DIE LEISTUNGEN'],
            'titleTemplate' => ['type' => 'string', 'default' => 'Unsere {category} im Überblick.'],
            'description' => ['type' => 'string', 'default' => 'Ein Auszug unserer Kernkompetenzen – präzise, zuverlässig und fachgerecht umgesetzt.']
        ]
    ]);
});
