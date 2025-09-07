<?php
// RIMAN Blocks: Category Content (dynamic)
if (!defined('ABSPATH')) exit;

add_action('init', function() {
    register_block_type('riman/category-content', [
        'render_callback' => function($attributes) {
            if (!is_category()) {
                return '<div style="padding:20px;background:#f0f0f0;text-align:center"><p><strong>Kategorie-Inhalt</strong></p><p>Dieser Block zeigt den verknüpften Inhalt auf Kategorie-Seiten.</p></div>';
            }
            $category = get_queried_object();
            $linked_pages = get_posts([
                'post_type' => 'page',
                'meta_key' => '_linked_category_id',
                'meta_value' => $category->term_id,
                'numberposts' => 1,
                'post_status' => 'publish'
            ]);
            if (empty($linked_pages)) {
                $page = get_page_by_path($category->slug);
                if ($page) $linked_pages = [$page];
            }
            if (empty($linked_pages)) {
                return category_description() ? '<div class="category-description">' . category_description() . '</div>' : '';
            }
            $linked_page = $linked_pages[0];
            global $post; $original = $post; $post = $linked_page; setup_postdata($post);
            $showTitle = !empty($attributes['showTitle']);
            ob_start(); ?>
            <div class="wp-block-riman-category-content">
              <?php if ($showTitle): ?><h2 style="margin-bottom:30px;font-size:2rem;"><?php echo esc_html(get_the_title()); ?></h2><?php endif; ?>
              <div class="category-content-body"><?php the_content(); ?></div>
            </div>
            <?php
            wp_reset_postdata(); $post = $original;
            return ob_get_clean();
        },
        'attributes' => [ 'showTitle' => ['type' => 'boolean', 'default' => false] ]
    ]);
});

