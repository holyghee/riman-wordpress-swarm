#!/bin/bash

# Script zum Installieren des Category Templates

WORDPRESS_CONTAINER="riman-wordpress-swarm-wordpress-1"

echo "Installiere Category Template..."

# Finde aktives Theme
THEME=$(docker exec $WORDPRESS_CONTAINER php -r "
require_once('/var/www/html/wp-load.php');
echo get_option('stylesheet');
")

echo "Aktives Theme: $THEME"

# Erstelle category.php im Theme
docker exec $WORDPRESS_CONTAINER bash -c "cat > /var/www/html/wp-content/themes/$THEME/category.php" << 'ENDTEMPLATE'
<?php
/**
 * Category Template - Zeigt verknüpfte Seite mit Hero
 */

get_header();

$category = get_queried_object();

// Hole verknüpfte Seite (über dein Plugin)
$linked_pages = get_posts([
    'post_type' => 'page',
    'meta_key' => '_linked_category_id',
    'meta_value' => $category->term_id,
    'numberposts' => 1
]);

if (!empty($linked_pages)) {
    $page = $linked_pages[0];
    
    // Setup post data
    global $post;
    $post = $page;
    setup_postdata($post);
    ?>
    
    <!-- Hero Section der Seite -->
    <?php if (has_post_thumbnail()): ?>
    <section class="hero" style="background-image: url('<?php echo get_the_post_thumbnail_url(null, 'full'); ?>');">
        <div class="hero-content">
            <h1><?php the_title(); ?></h1>
        </div>
    </section>
    <?php endif; ?>
    
    <!-- Content der Seite -->
    <div class="container">
        <div class="content">
            <?php the_content(); ?>
        </div>
    </div>
    
    <?php
    wp_reset_postdata();
}

get_footer();
ENDTEMPLATE

echo "✅ Category Template installiert!"
echo ""
echo "Jetzt zeigen Kategorie-Seiten:"
echo "  ✅ Die Hero Section der verknüpften Seite"
echo "  ✅ Den Content der verknüpften Seite"
echo "  ✅ Alles mit dem richtigen Featured Image"
