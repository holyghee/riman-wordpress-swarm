<?php
// RIMAN Blocks: Service Cards (dynamic) – shows terms as RIMAN service cards
if (!defined('ABSPATH')) exit;

add_action('init', function() {
    // Editor side script
    wp_register_script(
        'riman-service-cards-editor',
        plugin_dir_url(__FILE__) . '../assets/service-cards-block.js',
        [ 'wp-blocks', 'wp-element', 'wp-components', 'wp-block-editor', 'wp-server-side-render' ],
        '1.0.2',
        true
    );

    register_block_type('riman/service-cards', [
        'editor_script'   => 'riman-service-cards-editor',
        'render_callback' => function($attributes) {
            $a = wp_parse_args($attributes, [
                'taxonomy' => 'category',
                'parent' => 0,
                'columns' => 3,
                'showDescriptions' => true,
                'shape' => 'ellipse',
                'waveHeight' => 180,
                'waveOffset' => 36,
                'waveStyle'  => 'medium',
            ]);

            $taxonomy = sanitize_key($a['taxonomy']);
            $parent   = intval($a['parent']);
            $columns  = max(1, min(4, intval($a['columns'])));
            $showDesc = !empty($a['showDescriptions']);
            $shape    = in_array($a['shape'], ['ellipse','wave','none'], true) ? $a['shape'] : 'ellipse';
            $wave_h   = max(80, min(240, intval($a['waveHeight'])));
            $wave_off = max(0, min(60, intval($a['waveOffset'])));
            $wave_style = in_array(($a['waveStyle'] ?? 'medium'), ['soft','medium','deep'], true) ? $a['waveStyle'] : 'medium';

            $terms = get_terms([
                'taxonomy'   => $taxonomy,
                'parent'     => $parent,
                'hide_empty' => false,
                'orderby'    => 'menu_order',
            ]);
            if (is_wp_error($terms) || empty($terms)) {
                if (is_admin()) return '<p>Keine Kategorien gefunden.</p>';
                return '';
            }

            // Helper: resolve image for a term (re-using logic used in subcategories grid)
            $get_term_image = function($term_id) use ($taxonomy) {
                // 1) explicit thumbnail on term
                $term_thumb_id = get_term_meta($term_id, '_thumbnail_id', true);
                if ($term_thumb_id) {
                    $u = wp_get_attachment_image_url($term_thumb_id, 'large');
                    if ($u) return $u;
                }
                // 2) linked page (via connector meta)
                $linked_pages = get_posts([
                    'post_type' => 'page',
                    'meta_key' => '_linked_category_id',
                    'meta_value' => $term_id,
                    'numberposts' => 1,
                    'post_status' => 'publish',
                    'no_found_rows' => true,
                    'suppress_filters' => true,
                ]);
                if (!empty($linked_pages)) {
                    $tid = get_post_thumbnail_id($linked_pages[0]->ID);
                    if ($tid) {
                        $u = wp_get_attachment_image_url($tid, 'large');
                        if ($u) return $u;
                    }
                }
                // 3) fallback: first post with thumbnail in this term
                if ($taxonomy === 'category') {
                    $posts = get_posts([
                        'posts_per_page' => 1,
                        'meta_key' => '_thumbnail_id',
                        'tax_query' => [[
                            'taxonomy' => 'category',
                            'field'    => 'term_id',
                            'terms'    => [$term_id],
                        ]],
                        'no_found_rows' => true,
                        'suppress_filters' => true,
                    ]);
                    if (!empty($posts)) {
                        $u = get_the_post_thumbnail_url($posts[0]->ID, 'large');
                        if ($u) return $u;
                    }
                }
                return '';
            };

            // Ensure minimal frontend CSS is present (theme may override)
            $base_url = plugin_dir_url(__FILE__) . '../assets/';
            $handle = 'riman-service-cards-frontend';
            if (!wp_style_is($handle, 'registered')) {
                wp_register_style($handle, $base_url . 'service-cards.css', [], '1.0.0');
            }
            if (!wp_style_is($handle, 'enqueued')) {
                wp_enqueue_style($handle);
            }
            // Ensure Font Awesome is present if we render icon classes
            if (!wp_style_is('fontawesome', 'enqueued')) {
                wp_enqueue_style('fontawesome', 'https://cdn.jsdelivr.net/npm/@fortawesome/fontawesome-free@6/css/all.min.css', [], '6');
            }

            ob_start();

            // Default category (e.g., "Uncategorized") to exclude from service cards
            $default_cat_id = 0;
            if ($taxonomy === 'category') {
                $default_cat_id = (int) get_option('default_category');
            }

            // Bereichs-Label (für jede Karte unter dem Icon)
            $label_text = 'LEISTUNG';
            $label_term_id = 0;
            if ($parent > 0) {
                $label_term_id = $parent;
            } elseif (is_category()) {
                $qo = get_queried_object();
                if ($qo && isset($qo->term_id)) $label_term_id = intval($qo->term_id);
            }
            if ($label_term_id) {
                $maybe = get_term_meta($label_term_id, '_section_label', true);
                if (!empty($maybe)) $label_text = $maybe;
            }

            printf('<div class="riman-service-grid columns-%d">', esc_attr($columns));
            foreach ($terms as $term) {
                // Skip default/uncategorized category
                if ($taxonomy === 'category') {
                    $tname = is_string($term->name) ? strtolower($term->name) : '';
                    if ((int)$term->term_id === $default_cat_id || $term->slug === 'uncategorized' || $tname === 'uncategorized') {
                        continue;
                    }
                }
                $link = get_term_link($term);
                if (is_wp_error($link)) continue;
                $img  = $get_term_image($term->term_id);
                $desc = trim(strip_tags(term_description($term)));
                $icon_svg = (string) get_term_meta($term->term_id, '_section_icon_svg', true);
                $icon_id  = (int) get_term_meta($term->term_id, '_section_icon_id', true);
                $icon_cls = (string) get_term_meta($term->term_id, '_section_icon_class', true);
                $icon_cls = trim($icon_cls);
                // Strip stray quotes/newlines
                $icon_cls = trim($icon_cls, "\"' \n\r\t");
                // Map FA5 prefixes to FA7 for compatibility
                if ($icon_cls) {
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
                $icon_url = $icon_id ? wp_get_attachment_image_url($icon_id, 'thumbnail') : '';

                echo '<a class="wp-block-group riman-service-card shape-' . esc_attr($shape) . '" href="' . esc_url($link) . '">';
                echo '  <div class="riman-card-image shape-' . esc_attr($shape) . '">';
                if ($img) {
                    echo '    <img src="' . esc_url($img) . '" alt="' . esc_attr($term->name) . '" loading="lazy" />';
                } else {
                    echo '    <div class="riman-card-image--placeholder"></div>';
                }
                if ($shape === 'wave') {
                    $style = 'height:' . esc_attr($wave_h) . 'px; bottom:-' . esc_attr($wave_off) . 'px;';
                    // choose path by style
                    if ($wave_style === 'soft') {
                        $d = 'M0 200 C 250 150, 950 150, 1200 200 L1200 200 L0 200 Z';
                    } elseif ($wave_style === 'deep') {
                        $d = 'M0 200 C 200 100, 1000 100, 1200 200 L1200 200 L0 200 Z';
                    } else {
                        $d = 'M0 200 C 300 120, 900 120, 1200 200 L1200 200 L0 200 Z';
                    }
                    echo '    <div class="riman-card-wave" aria-hidden="true" style="' . $style . '">'
                        . '<svg viewBox="0 0 1200 200" preserveAspectRatio="none" xmlns="http://www.w3.org/2000/svg">'
                        . '<path d="' . esc_attr($d) . '" fill="#ffffff"></path>'
                        . '</svg>'
                        . '</div>';
                }
                echo '  </div>';
                echo '  <div class="riman-card-icon" aria-hidden="true">';
                if (!empty($icon_cls)) {
                    echo '<i class="' . esc_attr($icon_cls) . '" aria-hidden="true"></i>';
                } elseif (!empty($icon_svg)) {
                    // allowlist minimal svg
                    $allowed = [
                        'svg' => [ 'xmlns'=>true, 'viewBox'=>true, 'width'=>true, 'height'=>true, 'fill'=>true, 'stroke'=>true, 'stroke-width'=>true, 'role'=>true, 'aria-hidden'=>true ],
                        'path' => [ 'd'=>true, 'fill'=>true, 'stroke'=>true, 'stroke-width'=>true, 'fill-rule'=>true, 'clip-rule'=>true ],
                        'g' => [ 'fill'=>true, 'stroke'=>true, 'stroke-width'=>true ],
                        'circle' => [ 'cx'=>true, 'cy'=>true, 'r'=>true, 'fill'=>true, 'stroke'=>true, 'stroke-width'=>true ],
                        'rect' => [ 'x'=>true, 'y'=>true, 'width'=>true, 'height'=>true, 'rx'=>true, 'ry'=>true, 'fill'=>true, 'stroke'=>true, 'stroke-width'=>true ],
                        'polygon' => [ 'points'=>true, 'fill'=>true, 'stroke'=>true, 'stroke-width'=>true ],
                        'line' => [ 'x1'=>true, 'y1'=>true, 'x2'=>true, 'y2'=>true, 'stroke'=>true, 'stroke-width'=>true ],
                    ];
                    echo wp_kses($icon_svg, $allowed);
                } elseif (!empty($icon_url)) {
                    echo '<img src="' . esc_url($icon_url) . '" alt="" width="40" height="40" style="max-width:40px;max-height:40px;" />';
                } else {
                    echo '    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" width="40" height="40" fill="currentColor"><path d="M12 2L2 7v10c0 5.55 3.84 10.74 9 12 5.16-1.26 9-6.45 9-12V7l-10-5z"/></svg>';
                }
                echo '  </div>';
                // Bereichs-Label direkt unter dem Icon (Term-spezifisch)
                $card_label = get_term_meta($term->term_id, '_section_label', true);
                if (empty($card_label)) { $card_label = $label_text; }
                echo '  <div class="riman-card-category">' . esc_html($card_label) . '</div>';
                echo '  <div class="riman-card-content">';
                echo '    <h3 class="riman-card-title">' . esc_html($term->name) . '</h3>';
                if ($showDesc && $desc) {
                    echo '    <p class="riman-card-description">' . esc_html(wp_trim_words($desc, 24)) . '</p>';
                }
                echo '  </div>';
                echo '</a>';
            }
            echo '</div>';
            return ob_get_clean();
        },
        'attributes' => [
            'taxonomy' => [ 'type' => 'string', 'default' => 'category' ],
            'parent' => [ 'type' => 'number', 'default' => 0 ],
            'columns' => [ 'type' => 'number', 'default' => 3 ],
            'showDescriptions' => [ 'type' => 'boolean', 'default' => true ],
            'shape' => [ 'type' => 'string', 'default' => 'ellipse' ],
            'waveHeight' => [ 'type' => 'number', 'default' => 180 ],
            'waveOffset' => [ 'type' => 'number', 'default' => 36 ],
            'waveStyle'  => [ 'type' => 'string', 'default' => 'medium' ],
            // allow common block props to pass validation in REST
            'className'  => [ 'type' => 'string' ],
            'align'      => [ 'type' => 'string' ],
            'anchor'     => [ 'type' => 'string' ],
        ],
    ]);
});
