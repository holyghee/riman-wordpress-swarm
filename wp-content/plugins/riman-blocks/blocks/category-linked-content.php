<?php
// RIMAN Blocks: Category Linked Content (dynamic)
if (!defined('ABSPATH')) exit;

add_action('init', function() {
    register_block_type('riman/category-linked-content', [
        'render_callback' => function($attributes) {
            if (!is_category()) {
                return '<div style="padding:20px;background:#f0f0f0;text-align:center"><p><strong>Verknüpfter Kategorie-Inhalt</strong></p><p>Wird auf Kategorie-Seiten angezeigt</p></div>';
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
            if (empty($linked_pages)) return '';

            $linked_page = $linked_pages[0];
            global $post; $original = $post; $post = $linked_page; setup_postdata($post);
            $showTitle = !empty($attributes['showTitle']);
            $showFull = !isset($attributes['showFullContent']) || $attributes['showFullContent'];
            ob_start(); ?>
            <div class="wp-block-riman-category-linked-content">
              <?php if ($showTitle): ?><h2 style="margin-bottom:30px"><?php echo esc_html(get_the_title()); ?></h2><?php endif; ?>
              <?php if ($showFull): ?>
                <div class="linked-content-body"><?php the_content(); ?></div>
              <?php else: ?>
                <div class="linked-content-summary"><?php echo wp_trim_words(get_the_content(), 100); ?><p><a href="<?php echo esc_url(get_permalink()); ?>">Weiterlesen →</a></p></div>
              <?php endif; ?>
            </div>
            <?php
            wp_reset_postdata(); $post = $original;
            return ob_get_clean();
        },
        'attributes' => [
            'showTitle' => ['type' => 'boolean', 'default' => false],
            'showFullContent' => ['type' => 'boolean', 'default' => true]
        ]
    ]);
});

