<?php
// RIMAN Blocks: Category Hero (dynamic)
if (!defined('ABSPATH')) exit;

add_action('init', function() {
    wp_register_script(
        'riman-category-hero-block',
        (defined('RIMAN_BLOCKS_URL') ? RIMAN_BLOCKS_URL : plugin_dir_url(__FILE__) . '../') . 'assets/category-hero-block.js',
        ['wp-blocks', 'wp-element', 'wp-editor', 'wp-block-editor', 'wp-components'],
        '1.0'
    );

    register_block_type('riman/category-hero', [
        'editor_script' => 'riman-category-hero-block',
        'render_callback' => function($attributes) {
            $defaults = [
                'minHeight' => 600,
                'dim' => 40,
                'showDescription' => true,
                'useLinkedPageTitle' => true,
                'overlapHeader' => false,
                'titleMode' => 'category', // 'category' | 'page' | 'both'
                'debug' => false,
            ];
            $attributes = wp_parse_args($attributes, $defaults);

            $is_cat = is_category();
            $title_text = 'Beispielkategorie';
            $subtitle = '';
            $image_url = '';
            $description = '';

            $catName = $title_text;
            $pageTitle = '';
            if ($is_cat) {
                $category = get_queried_object();
                // Bevorzugt die Unterkategorien-Bereich Beschreibung, sonst Term-Beschreibung
                $section_desc = get_term_meta($category->term_id, '_section_description', true);
                $description = !empty($section_desc) ? $section_desc : strip_tags(category_description($category));

                // Verknüpfte Seite finden
                $linked_pages = get_posts([
                    'post_type' => 'page',
                    'meta_key' => '_linked_category_id',
                    'meta_value' => $category->term_id,
                    'numberposts' => 1,
                    'post_status' => 'publish'
                ]);
                if (empty($linked_pages)) {
                    $page = get_page_by_path($category->slug);
                    if ($page) { $linked_pages = [$page]; }
                }

                if (!empty($linked_pages)) {
                    $page = $linked_pages[0];
                    $catName = $category->name;
                    $pageTitle = $page->post_title;
                    $thumb_id = get_post_thumbnail_id($page->ID);
                    if ($thumb_id) {
                        $img = wp_get_attachment_image_url($thumb_id, 'full');
                        if ($img) $image_url = $img;
                    }
                } else {
                    $catName = $category->name;
                }
            }

            // Title selection
            $titleMode = in_array($attributes['titleMode'], ['category','page','both'], true) ? $attributes['titleMode'] : 'category';
            $title_text = $catName;
            $subtitle = '';
            if ($titleMode === 'page' && !empty($pageTitle)) {
                $title_text = $pageTitle;
            } elseif ($titleMode === 'both' && !empty($pageTitle) && $pageTitle !== $catName) {
                $subtitle = $pageTitle;
            } elseif ($attributes['useLinkedPageTitle'] && !empty($pageTitle) && $pageTitle !== $catName) {
                // Backward compatibility: optional subtitle when titleMode=category
                $subtitle = $pageTitle;
            }

            // Fail-safe: wenn kein Titel ermittelt wurde, nutze Single Cat Title oder generischen Fallback
            if (empty($title_text)) {
                if ($is_cat) {
                    $sc = single_cat_title('', false);
                    $title_text = !empty($sc) ? $sc : __('Kategorie', 'riman');
                } else {
                    $title_text = __('Kategorie', 'riman');
                }
            }

            if (!empty($attributes['debug'])) {
                echo "<!-- RIMAN Hero Debug: is_cat=" . ($is_cat ? '1' : '0') .
                     " title='" . esc_html($title_text) . "' subtitle='" . esc_html($subtitle) .
                     "' cat='" . esc_html($catName) . "' page='" . esc_html($pageTitle) .
                     "' image='" . esc_url($image_url) . "' -->";
            }

            $min_h = intval($attributes['minHeight']);
            $dim = max(0, min(100, intval($attributes['dim'])));

            ob_start();
            $margin_top = !empty($attributes['overlapHeader']) ? '-60px' : '0';
            $alpha = max(0, min(1, intval($attributes['dim']) / 100));
            if ($image_url) { ?>
                <section class="riman-hero alignfull" style="min-height:<?php echo esc_attr($min_h); ?>px;margin-top:<?php echo esc_attr($margin_top); ?>;background:
                    linear-gradient(rgba(0,0,0,<?php echo esc_attr($alpha); ?>), rgba(0,0,0,<?php echo esc_attr($alpha); ?>)),
                    url('<?php echo esc_url($image_url); ?>');
                    background-size:cover;background-position:center;display:flex;align-items:center;">
                    <div class="riman-hero-content" style="width:100%;max-width:var(--wp--style--global--content-size,1200px);margin:0 auto;padding:40px 20px;text-align:center;color:#fff;">
                        <h1 style="margin:0 0 10px 0;font-size:clamp(2.2rem,5vw,4rem);color:#fff;display:block;"><?php echo esc_html($title_text); ?></h1>
                        <?php if (!empty($subtitle)): ?>
                            <h3 style="margin:0 0 10px 0;font-size:1.6rem;font-weight:500;color:#fff;display:block;">
                                <?php echo esc_html($subtitle); ?>
                            </h3>
                        <?php endif; ?>
                        <?php if ($attributes['showDescription'] && !empty($description)): ?>
                            <p style="margin:10px 0 0 0;font-size:1.2rem;line-height:1.6;color:#fff;display:block;">
                                <?php echo esc_html($description); ?>
                            </p>
                        <?php endif; ?>
                    </div>
                </section>
            <?php } else { ?>
                <section class="riman-hero alignfull" style="min-height:<?php echo esc_attr($min_h); ?>px;margin-top:<?php echo esc_attr($margin_top); ?>;background:
                    linear-gradient(135deg,#667eea 0%,#764ba2 100%);display:flex;align-items:center;">
                    <div class="riman-hero-content" style="width:100%;max-width:var(--wp--style--global--content-size,1200px);margin:0 auto;padding:40px 20px;text-align:center;color:#fff;">
                        <h1 style="margin:0 0 10px 0;font-size:clamp(2.2rem,5vw,4rem);color:#fff;display:block;"><?php echo esc_html($title_text); ?></h1>
                        <?php if (!empty($subtitle)): ?>
                            <h3 style="margin:0 0 10px 0;font-size:1.6rem;font-weight:500;color:#fff;display:block;">
                                <?php echo esc_html($subtitle); ?>
                            </h3>
                        <?php endif; ?>
                        <?php if ($attributes['showDescription'] && !empty($description)): ?>
                            <p style="margin:10px 0 0 0;font-size:1.2rem;line-height:1.6;color:#fff;display:block;">
                                <?php echo esc_html($description); ?>
                            </p>
                        <?php endif; ?>
                    </div>
                </section>
            <?php }
            return ob_get_clean();
        },
        'attributes' => [
            'minHeight' => ['type' => 'number', 'default' => 600],
            'dim' => ['type' => 'number', 'default' => 40],
            'showDescription' => ['type' => 'boolean', 'default' => true],
            'useLinkedPageTitle' => ['type' => 'boolean', 'default' => true],
            'overlapHeader' => ['type' => 'boolean', 'default' => false],
            'titleMode' => ['type' => 'string', 'default' => 'category'],
            'debug' => ['type' => 'boolean', 'default' => false],
        ],
        'supports' => [
            'align' => ['full'],
        ],
    ]);
});
