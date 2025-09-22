<?php
// RIMAN Blocks: Category Hero Slider (dynamic)
if (!defined('ABSPATH')) exit;

add_action('init', function() {
    // Editor script (simple registration/controls placeholder)
    $base_url  = defined('RIMAN_BLOCKS_URL') ? RIMAN_BLOCKS_URL : plugin_dir_url(__FILE__) . '../';
    $base_path = defined('RIMAN_BLOCKS_DIR') ? RIMAN_BLOCKS_DIR : plugin_dir_path(__FILE__) . '../';
    $editor_rel = 'assets/category-hero-slider-block.js';
    $editor_ver = file_exists($base_path . $editor_rel) ? filemtime($base_path . $editor_rel) : time();

    wp_register_script(
        'riman-category-hero-slider-editor',
        $base_url . $editor_rel,
        ['wp-blocks', 'wp-element', 'wp-block-editor', 'wp-components', 'wp-i18n'],
        $editor_ver,
        true
    );

    register_block_type('riman/category-hero-slider', [
        'editor_script' => 'riman-category-hero-slider-editor',
        'render_callback' => function($attributes) {
            $defaults = [
                'minHeight' => 560,
                'dim' => 30,              // overlay darkness percent 0..100
                'autoPlay' => true,
                'interval' => 8000,
                'showDescription' => true,
                'includeCategories' => [], // array of slugs; empty => all top-level
                'ctaText' => __('Mehr erfahren', 'riman'),
                'animation' => 'fade',    // 'fade' | 'slide'
                'transition' => 700,      // ms
                'dotsStyle' => 'pills',    // 'dots' | 'pills' | 'squares'
                'arrowsShape' => 'round', // 'round' | 'square'
                'arrowsStyle' => 'light', // 'light' | 'dark' | 'ghost'
                'arrowsPosition' => 'inset', // 'inset' | 'outside'
                'parallax' => false,
                'parallaxStrength' => 0.25, // 0..1
                'parallaxMode' => 'transform', // 'transform' | 'fixed' | 'scroll'
            ];
            $a = wp_parse_args($attributes, $defaults);

            // Collect main categories
            $args = [
                'taxonomy' => 'category',
                'parent' => 0,
                'hide_empty' => false,
                'orderby' => 'term_order',
                'order' => 'ASC',
            ];
            $main = get_categories($args);
            if (!is_array($main) || empty($main)) return '';

            // Filter by includeCategories slugs if provided and exclude "uncategorized"
            $include = array_map('sanitize_title', (array) $a['includeCategories']);
            $items = [];
            foreach ($main as $term) {
                if ($term->slug === 'uncategorized') continue;
                if (!empty($include) && !in_array($term->slug, $include, true)) continue;

                // Determine background image (prefer linked page first, term thumbnail last)
                $bg = '';
                // 1) Verknüpfte Seite über Term-Meta _linked_page_id
                $linked_page_id = get_term_meta($term->term_id, '_linked_page_id', true);
                if ($linked_page_id) {
                    $pidThumb = get_post_thumbnail_id($linked_page_id);
                    if ($pidThumb) {
                        $img = wp_get_attachment_image_url($pidThumb, 'full');
                        if ($img) $bg = $img;
                    }
                }
                if (!$bg) {
                    // 2) Verknüpfte Seite via Page-Meta _linked_category_id
                    $linked_pages = get_posts([
                        'post_type' => 'page',
                        'meta_key' => '_linked_category_id',
                        'meta_value' => $term->term_id,
                        'numberposts' => 1,
                        'post_status' => 'publish',
                        'no_found_rows' => true,
                        'suppress_filters' => true,
                    ]);
                    if (!empty($linked_pages)) {
                        $pid = $linked_pages[0]->ID;
                        $pidThumb = get_post_thumbnail_id($pid);
                        if ($pidThumb) {
                            $img = wp_get_attachment_image_url($pidThumb, 'full');
                            if ($img) $bg = $img;
                        }
                    }
                }
                if (!$bg) {
                    // 3) Seite per Pfad (Slug) suchen und Featured Image nutzen
                    $page = get_page_by_path($term->slug);
                    if ($page) {
                        $pidThumb = get_post_thumbnail_id($page->ID);
                        if ($pidThumb) {
                            $img = wp_get_attachment_image_url($pidThumb, 'full');
                            if ($img) $bg = $img;
                        }
                    }
                }
                if (!$bg) {
                    // 4) Kategorie-Thumbnail als letzter Fallback (beide Keys prüfen)
                    $thumb_id = get_term_meta($term->term_id, '_thumbnail_id', true);
                    if (!$thumb_id) { $thumb_id = get_term_meta($term->term_id, 'thumbnail_id', true); }
                    if ($thumb_id) {
                        $img = wp_get_attachment_image_url($thumb_id, 'full');
                        if ($img) $bg = $img;
                    }
                }

                // Description: prefer plugin meta, else term description
                $section_desc = get_term_meta($term->term_id, '_section_description', true);
                $desc = !empty($section_desc) ? $section_desc : trim(strip_tags(term_description($term)));

                // Featured Video lookup (using existing RIMAN system)
                $video_src = ''; $video_mime = '';
                // 1) Verknüpfte Seite über Term-Meta _linked_page_id
                if ($linked_page_id) {
                    $vid_id  = (int) get_post_meta($linked_page_id, '_riman_featured_video_id', true);
                    $vid_url = (string) get_post_meta($linked_page_id, '_riman_featured_video_url', true);
                    
                    if ($vid_id) {
                        $video_src = wp_get_attachment_url($vid_id) ?: '';
                        $video_mime = get_post_mime_type($vid_id) ?: '';
                    } elseif ($vid_url) {
                        $video_src = $vid_url;
                        $video_mime = wp_check_filetype($video_src)['type'] ?? 'video/mp4';
                    }
                }
                if (!$video_src) {
                    // 2) Verknüpfte Seite via Page-Meta _linked_category_id
                    if (!empty($linked_pages)) {
                        $page_id = $linked_pages[0]->ID;
                        $vid_id  = (int) get_post_meta($page_id, '_riman_featured_video_id', true);
                        $vid_url = (string) get_post_meta($page_id, '_riman_featured_video_url', true);
                        
                        if ($vid_id) {
                            $video_src = wp_get_attachment_url($vid_id) ?: '';
                            $video_mime = get_post_mime_type($vid_id) ?: '';
                        } elseif ($vid_url) {
                            $video_src = $vid_url;
                            $video_mime = wp_check_filetype($video_src)['type'] ?? 'video/mp4';
                        }
                    }
                }
                if (!$video_src) {
                    // 3) Seite per Pfad (Slug) suchen
                    if ($page) {
                        $vid_id  = (int) get_post_meta($page->ID, '_riman_featured_video_id', true);
                        $vid_url = (string) get_post_meta($page->ID, '_riman_featured_video_url', true);
                        
                        if ($vid_id) {
                            $video_src = wp_get_attachment_url($vid_id) ?: '';
                            $video_mime = get_post_mime_type($vid_id) ?: '';
                        } elseif ($vid_url) {
                            $video_src = $vid_url;
                            $video_mime = wp_check_filetype($video_src)['type'] ?? 'video/mp4';
                        }
                    }
                }

                $items[] = [
                    'id' => $term->term_id,
                    'name' => $term->name,
                    'slug' => $term->slug,
                    'link' => get_category_link($term->term_id),
                    'bg' => $bg,
                    'video' => $video_src,
                    'video_mime' => $video_mime,
                    'desc' => $desc,
                ];
            }

            if (empty($items)) return '';

            $slider_id = 'riman-hero-slider-' . wp_generate_password(6, false, false);
            $min_h = intval($a['minHeight']);
            $alpha = max(0, min(1, intval($a['dim']) / 100));
            $interval = max(2500, intval($a['interval']));
            $cta = esc_html($a['ctaText']);
            $anim = in_array($a['animation'], ['fade','slide'], true) ? $a['animation'] : 'fade';
            if (!empty($a['parallax']) && isset($a['parallaxMode']) && $a['parallaxMode'] === 'fixed') {
                // Fixed background + transformed parents is buggy; force fade to avoid track transforms
                $anim = 'fade';
            }
            $duration = max(150, intval($a['transition']));
            $dotsStyle = in_array($a['dotsStyle'], ['dots','pills','squares'], true) ? $a['dotsStyle'] : 'pills';
            $arShape = in_array($a['arrowsShape'], ['round','square'], true) ? $a['arrowsShape'] : 'round';
            $arStyle = in_array($a['arrowsStyle'], ['light','dark','ghost'], true) ? $a['arrowsStyle'] : 'light';
            $arPos = in_array($a['arrowsPosition'], ['inset','outside'], true) ? $a['arrowsPosition'] : 'inset';

            // Enqueue frontend assets once
            $base_url = defined('RIMAN_BLOCKS_URL') ? RIMAN_BLOCKS_URL : plugin_dir_url(__FILE__) . '../';
            $base_path = defined('RIMAN_BLOCKS_DIR') ? RIMAN_BLOCKS_DIR : plugin_dir_path(__FILE__) . '../';
            $css_rel = 'assets/category-hero-slider.css';
            $js_rel  = 'assets/category-hero-slider.js';
            $css_ver = file_exists($base_path . $css_rel) ? filemtime($base_path . $css_rel) : time();
            $js_ver  = file_exists($base_path . $js_rel)  ? filemtime($base_path . $js_rel)  : time();
            wp_register_style('riman-category-hero-slider-frontend', $base_url . $css_rel, [], $css_ver);
            wp_enqueue_style('riman-category-hero-slider-frontend');
            wp_register_script('riman-category-hero-slider-frontend', $base_url . $js_rel, [], $js_ver, true);
            wp_enqueue_script('riman-category-hero-slider-frontend');

            // Build per-instance CSS (not filtered by KSES) for bg images and vars
            // Choose a safe overscan based on parallax strength to avoid edge bleed
            $p_strength = floatval($a['parallaxStrength']);
            $overscan_pct = (!empty($a['parallax'])) ? ($p_strength <= 0.2 ? 20 : ($p_strength <= 0.35 ? 30 : 40)) : 0;
            $css_rules = "#{$slider_id}{--riman-hero-min-h: {$min_h}px; --riman-hero-overlay: {$alpha}; --riman-hero-duration: {$duration}ms; --riman-parallax-overscan: {$overscan_pct}%;}\n";
            foreach ($items as $idx => $it) {
                $k = $idx + 1;
                // Nur für Slides OHNE Video ein statisches Hintergrundbild setzen.
                if (empty($it['video']) && !empty($it['bg'])) {
                    $bg = esc_url($it['bg']);
                    $css_rules .= "#{$slider_id} .riman-hero-slide:nth-child({$k}){background-image:url('{$bg}');background-size:cover;background-position:center;}\n";
                }
            }
            if (!empty($css_rules)) {
                wp_add_inline_style('riman-category-hero-slider-frontend', $css_rules);
            }

            ob_start();
            ?>
            <section class="riman-hero-slider alignfull dots-<?php echo esc_attr($dotsStyle); ?> arrows-<?php echo esc_attr($arShape); ?> arrows-<?php echo esc_attr($arStyle); ?> arrows-<?php echo esc_attr($arPos); ?> anim-<?php echo esc_attr($anim); ?><?php echo !empty($a['parallax']) ? ' has-parallax' : ''; ?> parallax-<?php echo esc_attr($a['parallaxMode']); ?>" id="<?php echo esc_attr($slider_id); ?>" data-anim="<?php echo esc_attr($anim); ?>" data-duration="<?php echo esc_attr($duration); ?>" data-interval="<?php echo esc_attr($interval); ?>" data-auto="<?php echo $a['autoPlay'] ? '1' : '0'; ?>" data-parallax="<?php echo !empty($a['parallax']) ? '1' : '0'; ?>" data-parallax-strength="<?php echo esc_attr($a['parallaxStrength']); ?>" data-parallax-mode="<?php echo esc_attr($a['parallaxMode']); ?>">
              <div class="riman-hero-track">
              <?php foreach ($items as $i => $it): ?>
                <?php
                  // Build per-slide inline style
                  $style_inline = "min-height:{$min_h}px;";
                  $has_video = !empty($it['video']);
                  if (!$has_video) {
                    if (empty($a['parallax'])) {
                      if (!empty($it['bg'])) {
                        $style_inline .= "background: linear-gradient(rgba(0,0,0,{$alpha}), rgba(0,0,0,{$alpha})), url('" . esc_url($it['bg']) . "'); background-size:cover;background-position:center;";
                      } else {
                        $style_inline .= "background: linear-gradient(rgba(0,0,0,{$alpha}), rgba(0,0,0,{$alpha})), linear-gradient(135deg,#667eea 0%,#764ba2 100%);";
                      }
                    } elseif (!empty($a['parallax']) && isset($a['parallaxMode']) && $a['parallaxMode'] === 'fixed') {
                      if (!empty($it['bg'])) {
                        $style_inline .= "background: linear-gradient(rgba(0,0,0,{$alpha}), rgba(0,0,0,{$alpha})), url('" . esc_url($it['bg']) . "'); background-size:cover;background-position:center; background-attachment: fixed;";
                      } else {
                        $style_inline .= "background: linear-gradient(rgba(0,0,0,{$alpha}), rgba(0,0,0,{$alpha})), linear-gradient(135deg,#667eea 0%,#764ba2 100%); background-attachment: fixed;";
                      }
                    }
                  }
                ?>
                <div class="riman-hero-slide<?php echo $i === 0 ? ' active' : ''; ?><?php echo !empty($it['video']) ? ' has-video' : ''; ?>" style="<?php echo esc_attr($style_inline); ?>">
                  <?php if (!empty($it['video'])): ?>
                    <!-- Video Background -->
                    <div class="riman-hero-video-container">
                      <video class="riman-hero-video" muted loop playsinline webkit-playsinline preload="metadata" data-parallax="<?php echo !empty($a['parallax']) ? '1' : '0'; ?>" data-parallax-strength="<?php echo esc_attr($a['parallaxStrength']); ?>"<?php echo !empty($it['bg']) ? ' poster="'.esc_url($it['bg']).'"' : ''; ?>>
                        <source src="<?php echo esc_url($it['video']); ?>" type="<?php echo esc_attr($it['video_mime'] ?: 'video/mp4'); ?>">
                      </video>
                    </div>
                  <?php elseif (!empty($a['parallax']) && (!isset($a['parallaxMode']) || $a['parallaxMode'] !== 'fixed')): ?>
                    <!-- Image Parallax Background -->
                    <?php if (!empty($it['bg'])): ?>
                      <div class="riman-hero-bg" style="background-image:url('<?php echo esc_url($it['bg']); ?>');"></div>
                    <?php else: ?>
                      <div class="riman-hero-bg" style="background-image:linear-gradient(135deg,#667eea 0%,#764ba2 100%);"></div>
                    <?php endif; ?>
                  <?php endif; ?>
                  <div class="riman-hero-overlay"></div>
                  <div class="riman-hero-inner">
                    <div class="riman-hero-content" style="color:#fff;">
                      <h2 class="riman-hero-title"><?php echo esc_html($it['name']); ?></h2>
                      <?php if (!empty($a['showDescription']) && !empty($it['desc'])): ?>
                        <p class="riman-hero-desc" style="color:#fff;">
                          <?php echo esc_html($it['desc']); ?>
                        </p>
                      <?php endif; ?>
                      <a class="riman-hero-cta" href="<?php echo esc_url($it['link']); ?>"><?php echo $cta; ?> →</a>
                    </div>
                  </div>
                </div>
              <?php endforeach; ?>
              </div>

              <?php if (count($items) > 1): ?>
                <button class="riman-hero-nav prev" aria-label="Prev">‹</button>
                <button class="riman-hero-nav next" aria-label="Next">›</button>
                <div class="riman-hero-indicators">
                  <?php foreach ($items as $i => $_): ?>
                    <span class="dot<?php echo $i === 0 ? ' active' : ''; ?>" data-index="<?php echo intval($i); ?>"></span>
                  <?php endforeach; ?>
                </div>
              <?php endif; ?>
            </section>
            
            <?php
            return ob_get_clean();
        },
        'attributes' => [
            'minHeight' => ['type' => 'number', 'default' => 560],
            'dim' => ['type' => 'number', 'default' => 30],
            'autoPlay' => ['type' => 'boolean', 'default' => true],
            'interval' => ['type' => 'number', 'default' => 6000],
            'showDescription' => ['type' => 'boolean', 'default' => true],
            'includeCategories' => ['type' => 'array', 'default' => []],
            'ctaText' => ['type' => 'string', 'default' => 'Mehr erfahren'],
            'animation' => ['type' => 'string', 'default' => 'fade'],
            'transition' => ['type' => 'number', 'default' => 700],
            'dotsStyle' => ['type' => 'string', 'default' => 'pills'],
            'arrowsShape' => ['type' => 'string', 'default' => 'round'],
            'arrowsStyle' => ['type' => 'string', 'default' => 'light'],
            'arrowsPosition' => ['type' => 'string', 'default' => 'inset'],
            'parallax' => ['type' => 'boolean', 'default' => false],
            'parallaxStrength' => ['type' => 'number', 'default' => 0.25],
            'parallaxMode' => ['type' => 'string', 'default' => 'transform'],
        ],
        'supports' => [ 'align' => ['full'] ],
    ]);
});
