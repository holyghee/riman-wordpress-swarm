<?php
/**
 * Category Template - Nutzt das bestehende Verknüpfungs-Plugin
 * 
 * Datei: category.php in deinem Theme
 * Zeigt die verknüpfte Seite MIT Hero Section statt der Kategorie-Archiv-Ansicht
 */

get_header();

// Hole die aktuelle Kategorie
$category = get_queried_object();

// Da du bereits ein Plugin hast, das die Verknüpfung macht,
// sollte diese Meta-Abfrage funktionieren:
$linked_pages = get_posts([
    'post_type' => 'page',
    'meta_key' => '_linked_category_id',
    'meta_value' => $category->term_id,
    'numberposts' => 1,
    'post_status' => 'publish'
]);

// Falls das Plugin anders speichert, probiere auch:
if (empty($linked_pages)) {
    // Alternative: Suche nach Seite mit gleichem Slug
    $linked_page_by_slug = get_page_by_path($category->slug);
    if ($linked_page_by_slug) {
        $linked_pages = [$linked_page_by_slug];
    }
}

if (!empty($linked_pages)) {
    $linked_page = $linked_pages[0];
    
    // Setze globale $post Variable für Template-Funktionen
    global $post;
    $original_post = $post;
    $post = $linked_page;
    setup_postdata($post);
    
    ?>
    
    <!-- ZEIGE DIE KOMPLETTE SEITE MIT HERO SECTION -->
    <main id="main" class="site-main category-as-page">
        
        <?php
        // OPTION 1: Inkludiere das Page Template direkt
        // Das zeigt die Seite GENAU wie sie auch unter ihrer URL aussieht
        ?>
        
        <!-- Hero Section von der verknüpften Seite -->
        <?php if (has_post_thumbnail()): ?>
            <section class="hero-section" style="background-image: url('<?php echo get_the_post_thumbnail_url($post->ID, 'full'); ?>');">
                <div class="hero-overlay"></div>
                <div class="hero-content">
                    <div class="container">
                        <h1 class="hero-title"><?php echo get_the_title(); ?></h1>
                        <?php 
                        // Zeige Excerpt oder Description
                        $excerpt = get_the_excerpt();
                        $cat_description = category_description();
                        ?>
                        <?php if ($excerpt): ?>
                            <div class="hero-description"><?php echo $excerpt; ?></div>
                        <?php elseif ($cat_description): ?>
                            <div class="hero-description"><?php echo $cat_description; ?></div>
                        <?php endif; ?>
                    </div>
                </div>
            </section>
        <?php else: ?>
            <!-- Fallback Hero ohne Bild -->
            <section class="hero-section no-image">
                <div class="container">
                    <h1 class="hero-title"><?php echo get_the_title(); ?></h1>
                    <?php if (category_description()): ?>
                        <div class="hero-description"><?php echo category_description(); ?></div>
                    <?php endif; ?>
                </div>
            </section>
        <?php endif; ?>
        
        <!-- Seiten-Content -->
        <section class="page-content">
            <div class="container">
                <article id="post-<?php the_ID(); ?>" <?php post_class('linked-page-content'); ?>>
                    <div class="entry-content">
                        <?php 
                        // Zeige den kompletten Inhalt der Seite
                        the_content(); 
                        ?>
                    </div>
                </article>
            </div>
        </section>
        
        <?php
        // OPTIONAL: Zeige auch die Blog-Posts aus dieser Kategorie
        wp_reset_postdata();
        $post = $original_post;
        
        // Query für Kategorie-Posts
        $args = [
            'category__in' => [$category->term_id],
            'posts_per_page' => 6,
            'post_type' => 'post',
            'post_status' => 'publish'
        ];
        
        $category_posts = new WP_Query($args);
        
        if ($category_posts->have_posts()): ?>
            <section class="category-posts-section">
                <div class="container">
                    <h2 class="section-title">Weitere Artikel zu <?php echo esc_html($category->name); ?></h2>
                    
                    <div class="posts-grid">
                        <?php while ($category_posts->have_posts()): $category_posts->the_post(); ?>
                            <article class="post-card">
                                <?php if (has_post_thumbnail()): ?>
                                    <div class="post-thumbnail">
                                        <a href="<?php the_permalink(); ?>">
                                            <?php the_post_thumbnail('medium'); ?>
                                        </a>
                                    </div>
                                <?php endif; ?>
                                
                                <div class="post-content">
                                    <h3 class="post-title">
                                        <a href="<?php the_permalink(); ?>"><?php the_title(); ?></a>
                                    </h3>
                                    
                                    <div class="post-meta">
                                        <span class="post-date"><?php echo get_the_date(); ?></span>
                                    </div>
                                    
                                    <div class="post-excerpt">
                                        <?php the_excerpt(); ?>
                                    </div>
                                    
                                    <a href="<?php the_permalink(); ?>" class="read-more">
                                        Weiterlesen →
                                    </a>
                                </div>
                            </article>
                        <?php endwhile; ?>
                    </div>
                    
                    <?php if ($category_posts->max_num_pages > 1): ?>
                        <div class="view-all-posts">
                            <a href="<?php echo get_category_link($category->term_id); ?>?show_all=1" class="button">
                                Alle Artikel anzeigen
                            </a>
                        </div>
                    <?php endif; ?>
                </div>
            </section>
        <?php 
        endif;
        wp_reset_postdata();
        ?>
        
    </main>
    
    <?php
    
} else {
    // FALLBACK: Keine verknüpfte Seite gefunden
    // Zeige Standard-Kategorie-Archiv
    ?>
    
    <main id="main" class="site-main">
        <div class="container">
            <header class="archive-header">
                <h1 class="archive-title"><?php single_cat_title(); ?></h1>
                <?php if (category_description()): ?>
                    <div class="archive-description">
                        <?php echo category_description(); ?>
                    </div>
                <?php endif; ?>
            </header>
            
            <?php if (have_posts()): ?>
                <div class="archive-posts">
                    <?php while (have_posts()): the_post(); ?>
                        <article id="post-<?php the_ID(); ?>" <?php post_class(); ?>>
                            <h2><a href="<?php the_permalink(); ?>"><?php the_title(); ?></a></h2>
                            <?php the_excerpt(); ?>
                        </article>
                    <?php endwhile; ?>
                </div>
                
                <?php the_posts_pagination(); ?>
            <?php else: ?>
                <p>Keine Beiträge in dieser Kategorie gefunden.</p>
            <?php endif; ?>
        </div>
    </main>
    
    <?php
}

get_footer();
?>
