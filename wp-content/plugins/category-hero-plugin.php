<?php
/**
 * Plugin Name: Category Hero Section
 * Description: Fügt die Hero Section der verknüpften Seite in Kategorie-Templates ein
 * Version: 1.0
 * Author: RIMAN GmbH
 */

// Shortcode für Hero Section
add_shortcode('category_hero', function() {
    if (!is_category()) {
        return '';
    }
    
    $category = get_queried_object();
    
    // Finde verknüpfte Seite (wie dein Connector Plugin)
    $linked_pages = get_posts([
        'post_type' => 'page',
        'meta_key' => '_linked_category_id',
        'meta_value' => $category->term_id,
        'numberposts' => 1,
        'post_status' => 'publish'
    ]);
    
    if (empty($linked_pages)) {
        $page = get_page_by_path($category->slug);
        if ($page) {
            $linked_pages = [$page];
        }
    }
    
    if (!empty($linked_pages)) {
        $page = $linked_pages[0];
        
        // Hole Featured Image
        $thumbnail_id = get_post_thumbnail_id($page->ID);
        
        if ($thumbnail_id) {
            $image_url = wp_get_attachment_image_url($thumbnail_id, 'full');
            
            ob_start();
            ?>
            <div class="wp-block-cover alignfull" style="min-height:600px;margin-top:-60px;">
                <span aria-hidden="true" class="wp-block-cover__background has-background-dim-40 has-background-dim"></span>
                <img class="wp-block-cover__image-background" src="<?php echo esc_url($image_url); ?>" alt="" style="object-fit:cover;" />
                <div class="wp-block-cover__inner-container">
                    <div class="wp-block-group has-global-padding is-layout-constrained">
                        <h1 class="has-text-align-center has-text-color" style="color:white;font-size:clamp(2.5rem, 5vw, 4rem);">
                            <?php echo esc_html($page->post_title); ?>
                        </h1>
                        <?php if (category_description()): ?>
                            <p class="has-text-align-center has-text-color" style="color:white;font-size:1.2rem;">
                                <?php echo strip_tags(category_description()); ?>
                            </p>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
            <?php
            return ob_get_clean();
        }
    }
    
    // Fallback ohne Bild
    ob_start();
    ?>
    <div class="wp-block-group alignfull has-background" style="background:linear-gradient(135deg, #667eea 0%, #764ba2 100%);padding:100px 0;margin-top:-60px;">
        <div class="wp-block-group has-global-padding is-layout-constrained">
            <h1 class="has-text-align-center" style="color:white;font-size:clamp(2.5rem, 5vw, 4rem);">
                <?php single_cat_title(); ?>
            </h1>
            <?php if (category_description()): ?>
                <p class="has-text-align-center" style="color:white;font-size:1.2rem;">
                    <?php echo strip_tags(category_description()); ?>
                </p>
            <?php endif; ?>
        </div>
    </div>
    <?php
    return ob_get_clean();
});
