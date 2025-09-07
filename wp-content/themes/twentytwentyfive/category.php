<?php
/**
 * Category Template - Zeigt die verknüpfte Seite mit Hero Section
 * 
 * Dieses Template nutzt das "Category Page Content Connector" Plugin
 * um die verknüpfte Seite anzuzeigen statt der Standard-Archiv-Ansicht
 */

get_header();

// Aktuelle Kategorie
$category = get_queried_object();

// Hole die verknüpfte Seite über das Plugin
$linked_pages = get_posts([
    'post_type' => 'page',
    'meta_key' => '_linked_category_id',
    'meta_value' => $category->term_id,
    'numberposts' => 1,
    'post_status' => 'publish'
]);

// Fallback: Seite mit gleichem Slug
if (empty($linked_pages)) {
    $page = get_page_by_path($category->slug);
    if ($page) {
        $linked_pages = [$page];
    }
}

if (!empty($linked_pages)) {
    // Zeige die verknüpfte Seite
    $linked_page = $linked_pages[0];
    
    // Setup post data für Template-Funktionen
    global $post;
    $post = $linked_page;
    setup_postdata($post);
    ?>
    
    <main id="main" class="site-main">
        
        <!-- Hero Section mit Featured Image der Seite -->
        <?php if (has_post_thumbnail()): ?>
            <section class="hero-section alignfull" style="
                background-image: url('<?php echo get_the_post_thumbnail_url(null, 'full'); ?>');
                background-size: cover;
                background-position: center;
                min-height: 400px;
                display: flex;
                align-items: center;
                position: relative;
            ">
                <div style="
                    background: linear-gradient(to bottom, rgba(0,0,0,0.3), rgba(0,0,0,0.5));
                    position: absolute;
                    top: 0;
                    left: 0;
                    right: 0;
                    bottom: 0;
                "></div>
                <div class="wp-block-group has-global-padding is-layout-constrained" style="position: relative; z-index: 1;">
                    <h1 class="wp-block-heading" style="color: white; font-size: 3rem; margin-bottom: 1rem;">
                        <?php the_title(); ?>
                    </h1>
                    <?php if (category_description()): ?>
                        <div style="color: white; font-size: 1.2rem;">
                            <?php echo strip_tags(category_description()); ?>
                        </div>
                    <?php endif; ?>
                </div>
            </section>
        <?php else: ?>
            <!-- Hero ohne Bild -->
            <section class="hero-section alignfull" style="
                background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
                padding: 4rem 0;
            ">
                <div class="wp-block-group has-global-padding is-layout-constrained">
                    <h1 class="wp-block-heading" style="color: white; font-size: 3rem;">
                        <?php the_title(); ?>
                    </h1>
                    <?php if (category_description()): ?>
                        <div style="color: white;">
                            <?php echo category_description(); ?>
                        </div>
                    <?php endif; ?>
                </div>
            </section>
        <?php endif; ?>
        
        <!-- Content der Seite -->
        <div class="wp-block-group has-global-padding is-layout-constrained">
            <article id="post-<?php the_ID(); ?>" <?php post_class(); ?>>
                <div class="entry-content">
                    <?php 
                    // Zeige den kompletten Seiteninhalt
                    the_content(); 
                    ?>
                </div>
            </article>
        </div>
        
        <?php
        // Reset post data
        wp_reset_postdata();
        
        // Optional: Zeige auch Posts aus dieser Kategorie
        $category_posts = new WP_Query([
            'cat' => $category->term_id,
            'posts_per_page' => 6
        ]);
        
        if ($category_posts->have_posts()): ?>
            <section class="category-posts" style="background-color: #f5f5f5; padding: 3rem 0; margin-top: 3rem;">
                <div class="wp-block-group has-global-padding is-layout-constrained">
                    <h2 class="wp-block-heading">Weitere Artikel zu <?php echo esc_html($category->name); ?></h2>
                    
                    <div class="wp-block-query">
                        <ul class="wp-block-post-template is-layout-grid" style="
                            display: grid;
                            grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
                            gap: 2rem;
                            list-style: none;
                            padding: 0;
                        ">
                            <?php while ($category_posts->have_posts()): $category_posts->the_post(); ?>
                                <li>
                                    <article style="background: white; padding: 1.5rem; border-radius: 8px;">
                                        <?php if (has_post_thumbnail()): ?>
                                            <a href="<?php the_permalink(); ?>">
                                                <?php the_post_thumbnail('medium', ['style' => 'width: 100%; height: auto;']); ?>
                                            </a>
                                        <?php endif; ?>
                                        <h3 style="margin: 1rem 0 0.5rem;">
                                            <a href="<?php the_permalink(); ?>" style="text-decoration: none;">
                                                <?php the_title(); ?>
                                            </a>
                                        </h3>
                                        <?php the_excerpt(); ?>
                                        <a href="<?php the_permalink(); ?>" class="wp-element-button">
                                            Weiterlesen →
                                        </a>
                                    </article>
                                </li>
                            <?php endwhile; ?>
                        </ul>
                    </div>
                </div>
            </section>
        <?php 
        endif;
        wp_reset_postdata();
        ?>
        
    </main>
    
    <?php
} else {
    // Fallback: Standard Kategorie-Archiv
    ?>
    <main id="main" class="site-main">
        <div class="wp-block-group has-global-padding is-layout-constrained">
            <header class="page-header">
                <h1 class="page-title"><?php single_cat_title(); ?></h1>
                <?php if (category_description()): ?>
                    <div class="taxonomy-description">
                        <?php echo category_description(); ?>
                    </div>
                <?php endif; ?>
            </header>
            
            <?php if (have_posts()): ?>
                <div class="posts">
                    <?php while (have_posts()): the_post(); ?>
                        <article>
                            <h2><a href="<?php the_permalink(); ?>"><?php the_title(); ?></a></h2>
                            <?php the_excerpt(); ?>
                        </article>
                    <?php endwhile; ?>
                </div>
                <?php the_posts_navigation(); ?>
            <?php else: ?>
                <p>Keine Beiträge gefunden.</p>
            <?php endif; ?>
        </div>
    </main>
    <?php
}

get_footer();
