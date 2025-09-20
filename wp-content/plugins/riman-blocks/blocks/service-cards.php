<?php
// RIMAN Blocks: Service Cards (dynamic) – shows terms as RIMAN service cards
if (!defined('ABSPATH')) exit;

add_action('init', function() {
    // Editor side script
    wp_register_script(
        'riman-service-cards-editor',
        plugin_dir_url(__FILE__) . '../assets/service-cards-block.js',
        [ 'wp-blocks', 'wp-element', 'wp-components', 'wp-block-editor', 'wp-server-side-render' ],
        '2.0.0',
        true
    );

    register_block_type('riman/service-cards', [
        'editor_script'   => 'riman-service-cards-editor',
        'render_callback' => function($attributes) {
            $a = wp_parse_args($attributes, [
                'source' => 'category',
                'taxonomy' => 'category',
                'parent' => 0,
                'columns' => 3,
                'showDescriptions' => true,
                'shape' => 'ellipse',
                'waveHeight' => 180,
                'waveOffset' => 36,
                'mobileSlider' => false,
                'sliderAutoplay' => false,
                'sliderInterval' => 5000,
                'responsiveWidth' => false,
                'responsiveWidthValue' => '100%',
                'desktopWidth' => 'default',
                'tabletWidth' => 'default',
                'mobileWidth' => 'default',
                'customDesktopWidth' => '100%',
                'customTabletWidth' => '100%',
                'customMobileWidth' => '100%',
                'showChildren' => 'auto', // 'auto', 'always', 'never'
            ]);

            $source = sanitize_key($a['source']);
            $taxonomy = sanitize_key($a['taxonomy']);
            $parent   = intval($a['parent']);
            $columns  = max(1, min(4, intval($a['columns'])));
            $showDesc = !empty($a['showDescriptions']);
            $shape    = in_array($a['shape'], ['ellipse','wave','none'], true) ? $a['shape'] : 'ellipse';
            $wave_h   = max(80, min(240, intval($a['waveHeight'])));
            $wave_off = max(0, min(60, intval($a['waveOffset'])));
            $show_children = in_array($a['showChildren'], ['auto','always','never'], true) ? $a['showChildren'] : 'auto';

            // Mobile Slider Attributes
            $mobile_slider = !empty($a['mobileSlider']);
            $slider_autoplay = !empty($a['sliderAutoplay']);
            $slider_interval = max(1000, intval($a['sliderInterval']));
            $responsive_width = !empty($a['responsiveWidth']);
            $responsive_width_value = sanitize_text_field($a['responsiveWidthValue']);

            // Device-specific responsive settings
            $desktop_width = sanitize_text_field($a['desktopWidth']);
            $tablet_width = sanitize_text_field($a['tabletWidth']);
            $mobile_width = sanitize_text_field($a['mobileWidth']);
            $custom_desktop_width = sanitize_text_field($a['customDesktopWidth']);
            $custom_tablet_width = sanitize_text_field($a['customTabletWidth']);
            $custom_mobile_width = sanitize_text_field($a['customMobileWidth']);

            $has_tablet_override = ($tablet_width && $tablet_width !== 'default');
            $has_mobile_override = ($mobile_width && $mobile_width !== 'default');
            $has_lower_breakpoint_override = $has_tablet_override || $has_mobile_override;

            // Get items based on source
            $items = [];
            if ($source === 'riman') {
                // Get RIMAN Seiten (custom post type)

                // Check if we're on a RIMAN Seite to show its children
                $current_post_id = get_the_ID();
                $current_post_type = get_post_type();

                // Determine whether to show children based on setting
                $should_show_children = false;
                if ($show_children === 'always') {
                    $should_show_children = true;
                } elseif ($show_children === 'auto' && $current_post_type === 'riman_seiten' && $current_post_id) {
                    $should_show_children = true;
                }

                if ($should_show_children && $current_post_id) {
                    // Get children of current RIMAN Seite
                    $items = get_posts([
                        'post_type' => 'riman_seiten',
                        'posts_per_page' => -1,
                        'post_status' => 'publish',
                        'post_parent' => $current_post_id,
                        'orderby' => 'menu_order title',
                        'order' => 'ASC'
                    ]);
                }

                // If no children or not on a RIMAN Seite, get top-level items
                if (empty($items)) {
                    $items = get_posts([
                        'post_type' => 'riman_seiten',
                        'posts_per_page' => -1,
                        'post_status' => 'publish',
                        'post_parent' => 0, // Only top-level
                        'orderby' => 'menu_order title',
                        'order' => 'ASC'
                    ]);
                }

                // If still no items, get ALL riman_seiten
                if (empty($items)) {
                    $items = get_posts([
                        'post_type' => 'riman_seiten',
                        'posts_per_page' => -1,
                        'post_status' => 'publish',
                        'orderby' => 'menu_order title',
                        'order' => 'ASC'
                    ]);
                }

                // Fallback: If still no riman_seiten, try normal pages
                if (empty($items)) {
                    // Check if we're on a normal page to show its children
                    if (get_post_type() === 'page' && $current_post_id) {
                        $items = get_posts([
                            'post_type' => 'page',
                            'posts_per_page' => -1,
                            'post_status' => 'publish',
                            'post_parent' => $current_post_id,
                            'orderby' => 'menu_order title',
                            'order' => 'ASC'
                        ]);
                    }

                    // If no children, show top-level pages
                    if (empty($items)) {
                        $items = get_posts([
                            'post_type' => 'page',
                            'posts_per_page' => 12,
                            'post_status' => 'publish',
                            'post_parent' => 0,
                            'orderby' => 'menu_order title',
                            'order' => 'ASC'
                        ]);
                    }
                }
            } else {
                // Get categories/terms
                $items = get_terms([
                    'taxonomy'   => $taxonomy,
                    'parent'     => $parent,
                    'hide_empty' => false,
                    'orderby'    => 'menu_order',
                ]);
            }

            if (is_wp_error($items) || empty($items)) {
                if (is_admin()) {
                    // Debug output for admin
                    if ($source === 'riman') {
                        // Check if post type exists
                        if (!post_type_exists('riman_seiten')) {
                            return '<p>Post Type "riman_seiten" existiert nicht!</p>';
                        }
                        // Count all riman_seiten posts
                        $all_count = wp_count_posts('riman_seiten');
                        $debug_msg = '<p>Keine RIMAN Seiten gefunden.</p>';
                        $debug_msg .= '<p style="font-size:11px;color:#666;">Debug: ';
                        $debug_msg .= 'Publish: ' . $all_count->publish . ', ';
                        $debug_msg .= 'Draft: ' . $all_count->draft . ', ';
                        $debug_msg .= 'Total: ' . ($all_count->publish + $all_count->draft + $all_count->private) . '</p>';
                        return $debug_msg;
                    }
                    return '<p>Keine Kategorien gefunden.</p>';
                }
                return '';
            }

            // Helper: resolve image for item (term or page)
            $get_item_image = function($item, $is_riman = false) use ($taxonomy) {
                if ($is_riman) {
                    // For RIMAN pages, get featured image
                    $thumb_id = get_post_thumbnail_id($item->ID);
                    if ($thumb_id) {
                        $url = wp_get_attachment_image_url($thumb_id, 'large');
                        if ($url) return $url;
                    }

                    // Try hero metadata
                    $hero_meta = get_post_meta($item->ID, 'hero_metadata', true);
                    if (is_array($hero_meta) && !empty($hero_meta['image_url'])) {
                        return $hero_meta['image_url'];
                    }

                    return '';
                } else {
                    // Original term image logic
                    $term_thumb_id = get_term_meta($item->term_id, '_thumbnail_id', true);
                    if ($term_thumb_id) {
                        $u = wp_get_attachment_image_url($term_thumb_id, 'large');
                        if ($u) return $u;
                    }
                    // 2) linked page (via connector meta)
                    $linked_pages = get_posts([
                        'post_type' => 'page',
                        'meta_key' => '_linked_category_id',
                        'meta_value' => $item->term_id,
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
                                'terms'    => [$item->term_id],
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
                }
            };

            // Ensure minimal frontend CSS is present (theme may override)
            $base_url = plugin_dir_url(__FILE__) . '../assets/';
            $handle = 'riman-service-cards-frontend';
            if (!wp_style_is($handle, 'registered')) {
                wp_register_style($handle, $base_url . 'service-cards.css', [], '2.0.1');
            }
            if (!wp_style_is($handle, 'enqueued')) {
                wp_enqueue_style($handle);
            }

            // Mobile Slider CSS & JS
            if ($mobile_slider) {
                $slider_handle = 'riman-service-cards-mobile-slider';
                if (!wp_style_is($slider_handle, 'registered')) {
                    wp_register_style($slider_handle, $base_url . 'service-cards-mobile-slider.css', [$handle], '1.0.0');
                }
                if (!wp_style_is($slider_handle, 'enqueued')) {
                    wp_enqueue_style($slider_handle);
                }

                $slider_js_handle = 'riman-service-cards-mobile-slider-js';
                if (!wp_script_is($slider_js_handle, 'registered')) {
                    wp_register_script($slider_js_handle, $base_url . 'service-cards-mobile-slider.js', [], '1.0.0', true);
                }
                if (!wp_script_is($slider_js_handle, 'enqueued')) {
                    wp_enqueue_script($slider_js_handle);
                }
            }

            // Enqueue Service Cards CSS
            $base_url = plugins_url('riman-blocks/assets/');
            $service_cards_css_handle = 'riman-service-cards-css';

            if (!wp_style_is($service_cards_css_handle, 'registered')) {
                wp_register_style($service_cards_css_handle, $base_url . 'service-cards.css', [], '2.0.1');
            }
            if (!wp_style_is($service_cards_css_handle, 'enqueued')) {
                wp_enqueue_style($service_cards_css_handle);
            }

            // Enqueue Mobile Slider CSS if needed
            if ($mobile_slider) {
                $slider_css_handle = 'riman-service-cards-mobile-slider-css';
                if (!wp_style_is($slider_css_handle, 'registered')) {
                    wp_register_style($slider_css_handle, $base_url . 'service-cards-mobile-slider.css', [], '1.0.0');
                }
                if (!wp_style_is($slider_css_handle, 'enqueued')) {
                    wp_enqueue_style($slider_css_handle);
                }
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

            // Wrapper with mobile slider data attributes
            $wrapper_classes = ['riman-service-cards-wrap'];
            $wrapper_attrs = [];
            $wrapper_style = '';
            $custom_css_vars = [];

            if ($mobile_slider) {
                $wrapper_attrs[] = 'data-mobile-slider="true"';
                if ($slider_autoplay) {
                    $wrapper_attrs[] = 'data-slider-autoplay="true"';
                    $wrapper_attrs[] = 'data-slider-interval="' . esc_attr($slider_interval) . '"';
                }
            }

            // Handle device-specific responsive settings
            $has_responsive_settings = false;
            $responsive_styles = [];

            // Create unique container ID for this block instance
            $container_id = 'riman-sc-' . uniqid();
            $wrapper_classes[] = $container_id;

            // Desktop settings - handle all options including 'default'
            if ($desktop_width === 'wide') {
                if ($has_lower_breakpoint_override) {
                    $wrapper_classes[] = 'sc-desktop-wide';
                } else {
                    $wrapper_classes[] = 'alignwide';
                }
            } elseif ($desktop_width === 'full') {
                if ($has_lower_breakpoint_override) {
                    $wrapper_classes[] = 'sc-desktop-full';
                } else {
                    $wrapper_classes[] = 'alignfull';
                }
            } elseif ($desktop_width === 'custom' && $custom_desktop_width) {
                $has_responsive_settings = true;
                $desktop_custom_class = sanitize_html_class('sc-desktop-custom');
                if ($desktop_custom_class && !in_array($desktop_custom_class, $wrapper_classes, true)) {
                    $wrapper_classes[] = $desktop_custom_class;
                }
                $custom_css_vars['--sc-desktop-custom-width'] = $custom_desktop_width;
                $responsive_styles[] = '@media screen and (min-width: 1200px) { .' . $container_id . '.sc-responsive.' . $desktop_custom_class . ' { width: var(--sc-desktop-custom-width, ' . esc_attr($custom_desktop_width) . ') !important; max-width: none !important; margin-left: auto !important; margin-right: auto !important; box-sizing: border-box !important; } }';
            } else {
                // Default or explicitly 'default' = Standard Container Width
                $wrapper_classes[] = 'sc-container';
            }

            // Tablet settings - WordPress Standard Breakpoints
            if ($tablet_width && $tablet_width !== 'default') {
                $has_responsive_settings = true;
                $tablet_class = sanitize_html_class('sc-tablet-' . $tablet_width);
                if ($tablet_class && !in_array($tablet_class, $wrapper_classes, true)) {
                    $wrapper_classes[] = $tablet_class;
                }
                if ($tablet_width === 'wide') {
                    $responsive_styles[] = '@media screen and (min-width: 768px) and (max-width: 1199px) { .' . $container_id . '.sc-responsive.' . $tablet_class . ' { width: 100vw !important; max-width: 100vw !important; margin-left: calc(50% - 50vw) !important; margin-right: calc(50% - 50vw) !important; padding-left: calc(var(--wp-padding, 30px) / 2) !important; padding-right: calc(var(--wp-padding, 30px) / 2) !important; box-sizing: border-box !important; } }';
                } elseif ($tablet_width === 'full') {
                    $responsive_styles[] = '@media screen and (min-width: 768px) and (max-width: 1199px) { .' . $container_id . '.sc-responsive.' . $tablet_class . ' { width: 100vw !important; max-width: 100vw !important; margin-left: calc(50% - 50vw) !important; margin-right: calc(50% - 50vw) !important; padding-left: 0 !important; padding-right: 0 !important; box-sizing: border-box !important; } }';
                } elseif ($tablet_width === 'custom' && $custom_tablet_width) {
                    $custom_css_vars['--sc-tablet-custom-width'] = $custom_tablet_width;
                    $responsive_styles[] = '@media screen and (min-width: 768px) and (max-width: 1199px) { .' . $container_id . '.sc-responsive.' . $tablet_class . ' { width: var(--sc-tablet-custom-width, ' . esc_attr($custom_tablet_width) . ') !important; max-width: none !important; margin-left: auto !important; margin-right: auto !important; box-sizing: border-box !important; } }';
                } else {
                    // Default tablet = container width
                    $responsive_styles[] = '@media screen and (min-width: 768px) and (max-width: 1199px) { .' . $container_id . '.sc-responsive.' . $tablet_class . ' { width: 100% !important; max-width: var(--wp--style--global--content-size, 1140px) !important; margin-left: auto !important; margin-right: auto !important; padding-left: var(--wp-padding, 30px) !important; padding-right: var(--wp-padding, 30px) !important; box-sizing: border-box !important; } }';
                }
            }

            // Mobile settings - WordPress Standard Breakpoints
            if ($mobile_width && $mobile_width !== 'default') {
                $has_responsive_settings = true;
                $mobile_class = sanitize_html_class('sc-mobile-' . $mobile_width);
                if ($mobile_class && !in_array($mobile_class, $wrapper_classes, true)) {
                    $wrapper_classes[] = $mobile_class;
                }
                if ($mobile_width === 'wide') {
                    $responsive_styles[] = '@media screen and (max-width: 767px) { .' . $container_id . '.sc-responsive.' . $mobile_class . ' { width: 100vw !important; max-width: 100vw !important; margin-left: calc(50% - 50vw) !important; margin-right: calc(50% - 50vw) !important; padding-left: calc(var(--wp-padding, 30px) / 2) !important; padding-right: calc(var(--wp-padding, 30px) / 2) !important; box-sizing: border-box !important; } }';
                } elseif ($mobile_width === 'full') {
                    $responsive_styles[] = '@media screen and (max-width: 767px) { .' . $container_id . '.sc-responsive.' . $mobile_class . ' { width: 100vw !important; max-width: 100vw !important; margin-left: calc(50% - 50vw) !important; margin-right: calc(50% - 50vw) !important; padding-left: 0 !important; padding-right: 0 !important; box-sizing: border-box !important; } }';
                } elseif ($mobile_width === 'custom' && $custom_mobile_width) {
                    $custom_css_vars['--sc-mobile-custom-width'] = $custom_mobile_width;
                    $responsive_styles[] = '@media screen and (max-width: 767px) { .' . $container_id . '.sc-responsive.' . $mobile_class . ' { width: var(--sc-mobile-custom-width, ' . esc_attr($custom_mobile_width) . ') !important; max-width: none !important; margin-left: auto !important; margin-right: auto !important; box-sizing: border-box !important; } }';
                } else {
                    // Default mobile = container width
                    $responsive_styles[] = '@media screen and (max-width: 767px) { .' . $container_id . '.sc-responsive.' . $mobile_class . ' { width: 100% !important; max-width: none !important; margin-left: auto !important; margin-right: auto !important; padding-left: var(--wp-padding, 30px) !important; padding-right: var(--wp-padding, 30px) !important; box-sizing: border-box !important; } }';
                }
            }

            if ($has_responsive_settings) {
                $wrapper_classes[] = 'sc-responsive';
            }

            // Fallback to old responsiveWidth system if new system not used AND no desktop alignment set
            if (!$has_responsive_settings && $desktop_width === 'default') {
                if ($responsive_width_value === 'wide') {
                    $wrapper_classes[] = 'alignwide';
                    $wrapper_classes[] = 'sc-responsive';
                } elseif ($responsive_width_value === 'full') {
                    $wrapper_classes[] = 'alignfull';
                    $wrapper_classes[] = 'sc-responsive';
                } elseif ($responsive_width_value === 'custom' && $responsive_width) {
                    $wrapper_classes[] = 'sc-responsive';
                    $wrapper_style = 'width: ' . esc_attr($responsive_width) . ';';
                } elseif ($responsive_width_value !== 'default') {
                    $wrapper_classes[] = 'sc-responsive';
                }
            }

            // Output responsive styles if needed
            if (!empty($responsive_styles)) {
                echo '<style>' . implode(' ', $responsive_styles) . '</style>';
            }

            if (!empty($custom_css_vars)) {
                $var_chunks = [];
                foreach ($custom_css_vars as $var_name => $var_value) {
                    $var_chunks[] = $var_name . ':' . $var_value;
                }
                $custom_style = implode(';', $var_chunks) . ';';
                if ($wrapper_style !== '') {
                    $wrapper_style = rtrim($wrapper_style, ';') . ';' . $custom_style;
                } else {
                    $wrapper_style = $custom_style;
                }
            }

            echo '<div class="' . esc_attr(implode(' ', $wrapper_classes)) . '"';
            if (!empty($wrapper_attrs)) {
                echo ' ' . implode(' ', $wrapper_attrs);
            }
            if (!empty($wrapper_style)) {
                echo ' style="' . esc_attr($wrapper_style) . '"';
            }
            echo '>';

            printf('<div class="riman-service-grid columns-%d">', esc_attr($columns));

            $sanitize_meta_text = static function($value) {
                if (is_string($value)) {
                    return function_exists('sanitize_text_field')
                        ? sanitize_text_field($value)
                        : trim(strip_tags($value));
                }
                if (is_scalar($value)) {
                    $string_value = (string) $value;
                    return function_exists('sanitize_text_field')
                        ? sanitize_text_field($string_value)
                        : trim(strip_tags($string_value));
                }
                if (function_exists('wp_json_encode')) {
                    $encoded = wp_json_encode($value);
                    return function_exists('sanitize_text_field')
                        ? sanitize_text_field($encoded)
                        : trim(strip_tags($encoded));
                }
                return '';
            };

            $strip_meta_text = static function($value) {
                if (!is_string($value)) {
                    return '';
                }
                if (function_exists('wp_strip_all_tags')) {
                    return trim(wp_strip_all_tags($value));
                }
                return trim(strip_tags($value));
            };

            foreach ($items as $item) {
                $is_riman = ($source === 'riman');
                $hero_area_label_override = '';
                $hero_card_desc_long = '';
                $hero_card_desc_short = '';
                $hero_card_desc_meta = '';
                $cards_use_short_fallback = false;

                if ($is_riman) {
                    // RIMAN page handling
                    $link = get_permalink($item->ID);
                    $title = $item->post_title;
                    $desc = trim(strip_tags($item->post_excerpt ?: $item->post_content));
                    $item_id = $item->ID;

                    // Get hero metadata for icon
                    $hero_meta = get_post_meta($item_id, 'hero_metadata', true);
                    $icon_cls = '';
                    $icon_svg = '';
                    $icon_id = 0;

                    if (is_array($hero_meta)) {
                        $icon_cls = $hero_meta['icon_class'] ?? '';
                        $icon_svg = $hero_meta['icon_svg'] ?? '';
                        $icon_id = $hero_meta['icon_id'] ?? 0;
                        if (!empty($hero_meta['card_description']) && is_string($hero_meta['card_description'])) {
                            $hero_card_desc_meta = $strip_meta_text($hero_meta['card_description']);
                        }
                    }

                    $hero_meta_box = [];
                    if (class_exists('RIMAN_Hero_Meta')) {
                        $hero_meta_box = RIMAN_Hero_Meta::get_hero_meta($item_id);
                        if (!is_array($hero_meta_box)) {
                            $hero_meta_box = [];
                        }
                    }

                    if (!empty($hero_meta_box)) {
                        if (empty($icon_cls) && !empty($hero_meta_box['icon'])) {
                            $icon_cls = $sanitize_meta_text($hero_meta_box['icon']);
                        }
                        if (!empty($hero_meta_box['area_label'])) {
                            $hero_area_label_override = $sanitize_meta_text($hero_meta_box['area_label']);
                        }
                        if (!empty($hero_meta_box['long_text']) && is_string($hero_meta_box['long_text'])) {
                            $hero_card_desc_long = $strip_meta_text($hero_meta_box['long_text']);
                        }
                        if (!empty($hero_meta_box['subtitle']) && is_string($hero_meta_box['subtitle'])) {
                            $hero_card_desc_short = $strip_meta_text($hero_meta_box['subtitle']);
                        }
                        $cards_use_short_fallback = !empty($hero_meta_box['cards_use_short_fallback']);
                    }
                } else {
                    // Term/category handling
                    // Skip default/uncategorized category
                    if ($taxonomy === 'category') {
                        $tname = is_string($item->name) ? strtolower($item->name) : '';
                        if ((int)$item->term_id === $default_cat_id || $item->slug === 'uncategorized' || $tname === 'uncategorized') {
                            continue;
                        }
                    }
                    $link = get_term_link($item);
                    if (is_wp_error($link)) continue;
                    $title = $item->name;
                    $desc = trim(strip_tags(term_description($item)));
                    $item_id = $item->term_id;

                    $icon_svg = (string) get_term_meta($item_id, '_section_icon_svg', true);
                    $icon_id  = (int) get_term_meta($item_id, '_section_icon_id', true);
                    $icon_cls = (string) get_term_meta($item_id, '_section_icon_class', true);
                }

                if ($is_riman) {
                    $desc_candidates = [];
                    if ($hero_card_desc_meta !== '') {
                        $desc_candidates[] = $hero_card_desc_meta;
                    }
                    if ($cards_use_short_fallback) {
                        if ($hero_card_desc_short !== '') {
                            $desc_candidates[] = $hero_card_desc_short;
                        }
                        if ($hero_card_desc_long !== '') {
                            $desc_candidates[] = $hero_card_desc_long;
                        }
                    } else {
                        if ($hero_card_desc_long !== '') {
                            $desc_candidates[] = $hero_card_desc_long;
                        }
                        if ($hero_card_desc_short !== '') {
                            $desc_candidates[] = $hero_card_desc_short;
                        }
                    }
                    $desc_candidates[] = $desc;

                    foreach ($desc_candidates as $candidate) {
                        if (!is_string($candidate)) {
                            continue;
                        }
                        $candidate = trim($candidate);
                        if ($candidate !== '') {
                            $desc = $candidate;
                            break;
                        }
                    }
                }

                $img = $get_item_image($item, $is_riman);
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
                echo '  <div class="riman-card-media shape-' . esc_attr($shape) . '">';
                if ($img) {
                    echo '    <img src="' . esc_url($img) . '" alt="' . esc_attr($title) . '" loading="lazy" />';
                } else {
                    echo '    <div class="riman-card-media--placeholder"></div>';
                }
                if ($shape === 'wave') {
                    $style = 'height:' . esc_attr($wave_h) . 'px; bottom:-' . esc_attr($wave_off) . 'px;';
                    // Standard RIMAN wave path
                    $d = 'M0,175 L1.82,175 Q313.08,187.6 534.27,165.98 Q944.34,127.23 1199.57,140.98 L1200,140.98 L1200,400 L0,400 Z';
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
                // Bereichs-Label direkt unter dem Icon
                if ($is_riman) {
                    // For RIMAN pages, use a generic label or from hero metadata
                    $card_label = $label_text;
                    if (is_array($hero_meta) && !empty($hero_meta['section_label'])) {
                        $card_label = $sanitize_meta_text($hero_meta['section_label']);
                    } elseif ($hero_area_label_override !== '') {
                        $card_label = $hero_area_label_override;
                    }
                } else {
                    // For terms, use term-specific label
                    $card_label = get_term_meta($item_id, '_section_label', true);
                    if (empty($card_label)) { $card_label = $label_text; }
                }
                echo '  <div class="riman-card-category">' . esc_html($card_label) . '</div>';
                echo '  <div class="riman-card-content">';
                echo '    <h3 class="riman-card-title">' . esc_html($title) . '</h3>';
                if ($showDesc && $desc) {
                    echo '    <p class="riman-card-description">' . esc_html(wp_trim_words($desc, 24)) . '</p>';
                }
                echo '  </div>';
                echo '</a>';
            }
            echo '</div>';
            echo '</div>'; // Close wrapper
            return ob_get_clean();
        },
        'attributes' => [
            'source' => [ 'type' => 'string', 'default' => 'category' ],
            'taxonomy' => [ 'type' => 'string', 'default' => 'category' ],
            'parent' => [ 'type' => 'number', 'default' => 0 ],
            'columns' => [ 'type' => 'number', 'default' => 3 ],
            'showDescriptions' => [ 'type' => 'boolean', 'default' => true ],
            'shape' => [ 'type' => 'string', 'default' => 'ellipse' ],
            'waveHeight' => [ 'type' => 'number', 'default' => 180 ],
            'waveOffset' => [ 'type' => 'number', 'default' => 36 ],
            'mobileSlider' => [ 'type' => 'boolean', 'default' => false ],
            'sliderAutoplay' => [ 'type' => 'boolean', 'default' => false ],
            'sliderInterval' => [ 'type' => 'number', 'default' => 5000 ],
            'responsiveWidth' => [ 'type' => 'boolean', 'default' => false ],
            'responsiveWidthValue' => [ 'type' => 'string', 'default' => '100%' ],
            'desktopWidth' => [ 'type' => 'string', 'default' => 'default' ],
            'tabletWidth' => [ 'type' => 'string', 'default' => 'default' ],
            'mobileWidth' => [ 'type' => 'string', 'default' => 'default' ],
            'customDesktopWidth' => [ 'type' => 'string', 'default' => '100%' ],
            'customTabletWidth' => [ 'type' => 'string', 'default' => '100%' ],
            'customMobileWidth' => [ 'type' => 'string', 'default' => '100%' ],
            'showChildren' => [ 'type' => 'string', 'default' => 'auto' ],
            // allow common block props to pass validation in REST
            'className'  => [ 'type' => 'string' ],
            'align'      => [ 'type' => 'string' ],
            'anchor'     => [ 'type' => 'string' ],
        ],
    ]);
});
