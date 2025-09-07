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

            ob_start(); ?>
            <div class="riman-category-header">
                <span class="riman-section-label"><?php echo esc_html($label); ?></span>
                <h2 class="riman-section-title"><?php echo wp_kses_post($title); ?></h2>
                <p class="riman-section-description"><?php echo esc_html($description); ?></p>
            </div>
            <style>
            .riman-category-header { text-align:center; margin:60px 0 40px }
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
