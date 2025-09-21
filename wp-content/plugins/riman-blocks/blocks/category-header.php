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
            // Sonderfall: Wenn wir uns auf einer einzelnen RIMAN Seite befinden,
            // sollen die Daten aus der Hero-Section (Meta) kommen.
            if (is_singular('riman_seiten')) {
                $post_id = get_the_ID();

                // Standardwerte aus Block-Attributen
                $label = isset($attributes['label']) ? $attributes['label'] : 'DIE LEISTUNGEN';
                $mid_heading_text = 'Leistungen & Schwerpunkte';

                // Hero-Metadaten laden, falls Klasse verfügbar
                $hero_title    = '';
                $hero_subtitle = '';
                $hero_icon     = '';
                if (class_exists('RIMAN_Hero_Meta')) {
                    $hero_meta  = RIMAN_Hero_Meta::get_hero_meta($post_id);
                    $hero_title = !empty($hero_meta['title']) ? $hero_meta['title'] : '';
                    $hero_subtitle = !empty($hero_meta['subtitle']) ? $hero_meta['subtitle'] : '';
                    $hero_icon  = !empty($hero_meta['icon']) ? $hero_meta['icon'] : '';
                    if (!empty($hero_meta['area_label'])) {
                        $label = $hero_meta['area_label'];
                    }
                }

                // Subtitle Fallback: Excerpt
                if (empty($hero_subtitle)) {
                    $hero_subtitle = get_the_excerpt($post_id);
                }

                // Titel-Fallback: Seitentitel (ohne Suffix)
                $title_plain = $hero_title ?: str_replace(' - Riman GmbH', '', get_the_title($post_id));

                // Featured Image als Hintergrund (für Parallax)
                $bg_image = '';
                if (has_post_thumbnail($post_id)) {
                    $bg_image = get_the_post_thumbnail_url($post_id, 'full');
                }

                // Font Awesome sicher laden (für Icon-Klassen)
                if (!wp_style_is('fontawesome', 'enqueued')) {
                    wp_enqueue_style('fontawesome', 'https://cdn.jsdelivr.net/npm/@fortawesome/fontawesome-free@6/css/all.min.css', [], '6');
                }

                // FA5 → FA6 Präfixkompatibilität für Hero-Icon
                if (!empty($hero_icon)) {
                    $hero_icon = trim($hero_icon, "\"' \n\r\t");
                    $hero_icon = preg_replace('/\bfas\b/', 'fa-solid', $hero_icon);
                    $hero_icon = preg_replace('/\bfar\b/', 'fa-regular', $hero_icon);
                    $hero_icon = preg_replace('/\bfab\b/', 'fa-brands', $hero_icon);
                }

                ob_start(); ?>
                <section class="riman-category-header riman-cathead-hero" style="position:relative; overflow:hidden; min-height:460px; padding:60px 20px; <?php echo $bg_image ? 'background-image:url(' . esc_url($bg_image) . '); background-size:cover; background-position:center; background-attachment:fixed;' : 'background:linear-gradient(135deg, #667eea 0%, #764ba2 100%);'; ?>">
                    <div class="riman-cathead-overlay" style="position:absolute; inset:0; background: rgba(0,0,0,0.35); z-index:0;"></div>
                    <div class="riman-category-header__inner" style="position:relative; z-index:1; text-align:center; color:#fff;">
                    <?php if (!empty($hero_icon)) : ?>
                        <div class="riman-header-icon" aria-hidden="true">
                            <i class="<?php echo esc_attr($hero_icon); ?>" aria-hidden="true"></i>
                        </div>
                    <?php endif; ?>
                    <span class="riman-section-label"><?php echo esc_html($label); ?></span>
                    <h2 class="riman-section-title" style="color:#fff; margin-top:20px; margin-bottom:10px; "><?php echo esc_html($title_plain); ?></h2>
                    <?php if (!empty($hero_subtitle)) : ?>
                        <p class="riman-section-description" style="color:#fff; opacity:.95; "><?php echo esc_html($hero_subtitle); ?></p>
                    <?php endif; ?>
                    <h3 class="riman-section-subheading" style="color:#fff; opacity:.95; "><?php echo esc_html($mid_heading_text); ?></h3>
                    </div>
                </section>
                <style>
                .riman-category-header { text-align:center; margin:60px 0 40px }
                .riman-header-icon { width:84px; height:84px; margin:0 auto 16px; background:#b68c2f; border-radius:50%; display:flex; align-items:center; justify-content:center; border:4px solid #fff; color:#fff }
                .riman-header-icon img, .riman-header-icon svg { max-width:48px; max-height:48px; display:block }
                .riman-header-icon i { font-size:48px; line-height:1; }
                .riman-section-label { display:inline-block; background:#b68c2f; color:#fff; padding:6px 20px; border-radius:20px; font-size:.85rem; font-weight:600; letter-spacing:1px; text-transform:uppercase; margin-bottom:15px }
                .riman-section-title { font-size:2.5rem!important; margin:20px 0; color:#1e4a6d }
                .riman-section-title em { color:#b68c2f; font-style:italic }
                .riman-section-subheading { font-size:1.25rem; margin:12px 0 0; color:#1e4a6d; font-weight:600; }
                .riman-section-description { color:#666; font-size:1.1rem; margin:0 }
                </style>
                <?php
                return ob_get_clean();
            }

            // Standard: Kategorie-Kontext (Archiv)
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
