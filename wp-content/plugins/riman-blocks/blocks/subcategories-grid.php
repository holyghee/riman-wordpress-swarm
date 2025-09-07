<?php
// RIMAN Blocks: Subcategories Grid (dynamic)
if (!defined('ABSPATH')) exit;

add_action('init', function() {
    // Editor script
    wp_register_script(
        'riman-subcategories-grid-editor',
        plugin_dir_url(__FILE__) . '../assets/subcategories-grid-block.js',
        ['wp-blocks', 'wp-element', 'wp-editor', 'wp-components', 'wp-data', 'wp-i18n'],
        '1.0'
    );

    register_block_type('riman/subcategories-grid', [
        'editor_script' => 'riman-subcategories-grid-editor',
        'render_callback' => function($attributes) {
            $defaults = [
                'showPosts' => true,
                'postsPerCategory' => 3,
                'showDescription' => true,
                'showPostCount' => true,
                'columns' => 3,
                'showEmptyCategories' => true,
            ];
            $attributes = wp_parse_args($attributes, $defaults);

            // Kontext bestimmen
            $current_category = null;
            if (is_category()) {
                $current_category = get_queried_object();
            } elseif (is_admin() || defined('REST_REQUEST')) {
                $cats = get_categories(['number' => 1]);
                if (!empty($cats)) $current_category = $cats[0];
            }
            if (!$current_category || !isset($current_category->term_id)) {
                return '<div class="subcategories-block-placeholder">Unterkategorien werden hier angezeigt</div>';
            }

            // Unterkategorien laden
            $subcategories = get_categories([
                'parent' => $current_category->term_id,
                'hide_empty' => !$attributes['showEmptyCategories'],
                'orderby' => 'name',
                'order' => 'ASC'
            ]);
            if (empty($subcategories)) return '';

            $cols = max(1, min(4, intval($attributes['columns'])));
            ob_start(); ?>
            <div class="wp-block-riman-subcategories" data-columns="<?php echo esc_attr($cols); ?>">
              <div class="subcategories-grid" style="display:grid;grid-template-columns:repeat(<?php echo esc_attr($cols); ?>,1fr);gap:20px">
              <?php foreach ($subcategories as $subcategory):
                  $category_link = get_category_link($subcategory->term_id);
                  $post_count = intval($subcategory->count);
                  // optional image (category thumbnail or linked page featured image)
                  $thumb_url = '';
                  $term_thumb_id = get_term_meta($subcategory->term_id, '_thumbnail_id', true);
                  if ($term_thumb_id) {
                      $u = wp_get_attachment_image_url($term_thumb_id, 'medium_large');
                      if ($u) $thumb_url = $u;
                  }
                  if (!$thumb_url) {
                      $linked_pages = get_posts([
                          'post_type' => 'page',
                          'meta_key' => '_linked_category_id',
                          'meta_value' => $subcategory->term_id,
                          'numberposts' => 1,
                          'post_status' => 'publish'
                      ]);
                      if (!empty($linked_pages)) {
                          $tid = get_post_thumbnail_id($linked_pages[0]->ID);
                          if ($tid) $thumb_url = wp_get_attachment_image_url($tid, 'medium_large');
                      }
                  }
              ?>
                <div class="subcategory-card" style="background:#fff;border-radius:6px;overflow:hidden;box-shadow:0 2px 8px rgba(0,0,0,.08)">
                  <?php if ($thumb_url): ?>
                    <div style="height:180px;background-image:url('<?php echo esc_url($thumb_url); ?>');background-size:cover;background-position:center"></div>
                  <?php endif; ?>
                  <div style="padding:16px">
                    <h3 style="margin:0 0 10px 0;font-size:1.2rem"><a href="<?php echo esc_url($category_link); ?>" style="text-decoration:none;color:#1e1e1e"><?php echo esc_html($subcategory->name); ?></a></h3>
                    <?php if (!empty($subcategory->description) && $attributes['showDescription']): ?>
                      <p style="margin:0 0 12px 0;color:#666;font-size:.95rem;line-height:1.5"><?php echo esc_html(wp_trim_words($subcategory->description, 24)); ?></p>
                    <?php endif; ?>
                    <div style="display:flex;gap:16px;align-items:center;margin-bottom:10px">
                      <?php if ($attributes['showPostCount']): ?>
                        <span style="background:#f3f4f6;border-radius:999px;padding:4px 10px;font-size:.85rem;color:#374151"><?php echo $post_count; ?> Beiträge</span>
                      <?php endif; ?>
                    </div>
                    <?php if ($attributes['showPosts']):
                        $posts = get_posts([
                            'category' => $subcategory->term_id,
                            'posts_per_page' => intval($attributes['postsPerCategory']),
                            'orderby' => 'date',
                            'order' => 'DESC'
                        ]);
                        if (!empty($posts)):
                    ?>
                      <div class="subcategory-posts" style="margin-top:8px">
                        <p style="margin:0 0 6px 0;font-weight:600;font-size:.9rem">Aktuelle Beiträge:</p>
                        <ul style="margin:0;padding-left:18px;color:#444;font-size:.95rem">
                          <?php foreach ($posts as $p): ?>
                            <li><a href="<?php echo esc_url(get_permalink($p->ID)); ?>"><?php echo esc_html($p->post_title); ?></a></li>
                          <?php endforeach; ?>
                        </ul>
                      </div>
                    <?php endif; endif; ?>
                    <div style="margin-top:12px">
                      <a href="<?php echo esc_url($category_link); ?>" style="display:inline-block;background:#667eea;color:#fff;padding:8px 14px;border-radius:4px;text-decoration:none;font-weight:600">Mehr erfahren →</a>
                    </div>
                  </div>
                </div>
              <?php endforeach; ?>
              </div>
            </div>
            <?php
            return ob_get_clean();
        },
        'attributes' => [
            'showPosts' => ['type' => 'boolean', 'default' => true],
            'postsPerCategory' => ['type' => 'number', 'default' => 3],
            'showDescription' => ['type' => 'boolean', 'default' => true],
            'showPostCount' => ['type' => 'boolean', 'default' => true],
            'columns' => ['type' => 'number', 'default' => 3],
            'showEmptyCategories' => ['type' => 'boolean', 'default' => true],
        ]
    ]);
});
