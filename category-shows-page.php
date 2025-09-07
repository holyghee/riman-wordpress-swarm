<?php
/**
 * Category Template - Zeigt die verknüpfte Seite mit Hero Section
 * 
 * File: category.php oder archive.php in deinem Theme
 */

// Hole die aktuelle Kategorie
$category = get_queried_object();

// Finde die verknüpfte Seite
$linked_page = null;

// Methode 1: Seite mit gleichem Slug
$linked_page = get_page_by_path($category->slug);

// Methode 2: Über Meta-Verknüpfung
if (!$linked_page) {
    $linked_pages = get_posts([
        'post_type' => 'page',
        'meta_key' => '_linked_category_id',
        'meta_value' => $category->term_id,
        'numberposts' => 1
    ]);
    $linked_page = !empty($linked_pages) ? $linked_pages[0] : null;
}

// Wenn verknüpfte Seite gefunden, zeige deren Inhalt
if ($linked_page) {
    // OPTION 1: Nutze das Page Template der Seite
    // Setup globale $post Variable für Template Tags
    global $post;
    $post = $linked_page;
    setup_postdata($post);
    
    // Lade das Page Template (zeigt Hero Section + Content)
    get_header();
    
    // Du kannst hier das spezifische Page Template laden
    $template = get_page_template_slug($linked_page->ID);
    if ($template) {
        get_template_part(str_replace('.php', '', $template));
    } else {
        // Standard Page Template
        ?>
        <main id="main" class="site-main">
            
            <!-- Hero Section von der Seite -->
            <?php if (has_post_thumbnail()): ?>
                <section class="hero-section" style="background-image: url('<?php echo get_the_post_thumbnail_url(null, 'full'); ?>');">
                    <div class="hero-content">
                        <h1><?php the_title(); ?></h1>
                        <?php if (has_excerpt()): ?>
                            <p class="hero-excerpt"><?php the_excerpt(); ?></p>
                        <?php endif; ?>
                    </div>
                </section>
            <?php endif; ?>
            
            <!-- Page Content -->
            <div class="container">
                <article id="post-<?php the_ID(); ?>" <?php post_class(); ?>>
                    <div class="entry-content">
                        <?php the_content(); ?>
                    </div>
                </article>
            </div>
            
            <!-- Posts der Kategorie (optional) -->
            <div class="category-posts">
                <div class="container">
                    <h2>Artikel in <?php echo esc_html($category->name); ?></h2>
                    
                    <?php
                    // Zeige Posts aus dieser Kategorie
                    $category_posts = new WP_Query([
                        'cat' => $category->term_id,
                        'posts_per_page' => 10,
                        'post_type' => 'post'
                    ]);
                    
                    if ($category_posts->have_posts()): ?>
                        <div class="posts-grid">
                            <?php while ($category_posts->have_posts()): $category_posts->the_post(); ?>
                                <article class="post-card">
                                    <?php if (has_post_thumbnail()): ?>
                                        <a href="<?php the_permalink(); ?>">
                                            <?php the_post_thumbnail('medium'); ?>
                                        </a>
                                    <?php endif; ?>
                                    <h3><a href="<?php the_permalink(); ?>"><?php the_title(); ?></a></h3>
                                    <div class="post-excerpt">
                                        <?php the_excerpt(); ?>
                                    </div>
                                </article>
                            <?php endwhile; ?>
                        </div>
                    <?php else: ?>
                        <p>Noch keine Artikel in dieser Kategorie.</p>
                    <?php endif;
                    wp_reset_postdata();
                    ?>
                </div>
            </div>
            
        </main>
        <?php
    }
    
    get_footer();
    
    // Reset post data
    wp_reset_postdata();
    
} else {
    // Fallback: Standard Kategorie-Ansicht
    get_header(); ?>
    
    <main id="main" class="site-main">
        <div class="container">
            <header class="page-header">
                <h1 class="page-title"><?php single_cat_title(); ?></h1>
                <?php if (category_description()): ?>
                    <div class="archive-description">
                        <?php echo category_description(); ?>
                    </div>
                <?php endif; ?>
            </header>
            
            <?php if (have_posts()): ?>
                <div class="posts-list">
                    <?php while (have_posts()): the_post(); ?>
                        <?php get_template_part('template-parts/content', 'archive'); ?>
                    <?php endwhile; ?>
                </div>
                <?php the_posts_navigation(); ?>
            <?php else: ?>
                <p>Keine Beiträge gefunden.</p>
            <?php endif; ?>
        </div>
    </main>
    
    <?php get_footer();
}
?>
