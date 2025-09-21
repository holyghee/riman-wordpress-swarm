<?php
/**
 * Taxonomy Archive for 'seitentyp' (hauptseite/unterseite/detailseite)
 * Nutzt ein klassisches Archiv-Layout ähnlich Beitrags-Kategorie.
 */
if (!defined('ABSPATH')) exit;

get_header();
?>

<main id="primary" class="site-main">
  <header class="page-header" style="margin-bottom:2rem">
    <h1 class="page-title"><?php single_term_title(); ?></h1>
    <?php if (term_description()) : ?>
      <div class="archive-description"><?php echo wp_kses_post(term_description()); ?></div>
    <?php endif; ?>
  </header>

  <?php if (have_posts()) : ?>
    <div class="riman-archive-grid" style="display:grid;grid-template-columns:repeat(auto-fit,minmax(280px,1fr));gap:24px;">
      <?php while (have_posts()) : the_post(); ?>
        <article id="post-<?php the_ID(); ?>" <?php post_class('riman-archive-card'); ?> style="border:1px solid var(--wp--preset--color--contrast-2,#e5e7eb);border-radius:10px;overflow:hidden;background:#fff;">
          <a href="<?php the_permalink(); ?>" class="riman-card-media" style="display:block;line-height:0;">
            <?php if (has_post_thumbnail()) : ?>
              <?php the_post_thumbnail('large', [ 'style' => 'width:100%;height:auto;display:block;' ]); ?>
            <?php else : ?>
              <div style="aspect-ratio:16/9;background:#f3f4f6;"></div>
            <?php endif; ?>
          </a>
          <div class="riman-card-content" style="padding:16px 18px 20px;">
            <h2 class="entry-title" style="margin:0 0 .4rem;">
              <a href="<?php the_permalink(); ?>" rel="bookmark"><?php the_title(); ?></a>
            </h2>
            <div class="entry-summary" style="color:#555;">
              <?php the_excerpt(); ?>
            </div>
            <p style="margin:.6rem 0 0"><a class="more-link" href="<?php the_permalink(); ?>"><?php esc_html_e('Weiterlesen', 'riman-wireframe'); ?> →</a></p>
          </div>
        </article>
      <?php endwhile; ?>
    </div>

    <nav class="navigation posts-navigation" style="margin-top:2rem">
      <div class="nav-links">
        <div class="nav-previous" style="float:left"><?php next_posts_link(__('Ältere Einträge', 'riman-wireframe')); ?></div>
        <div class="nav-next" style="float:right"><?php previous_posts_link(__('Neuere Einträge', 'riman-wireframe')); ?></div>
        <div style="clear:both"></div>
      </div>
    </nav>

  <?php else : ?>
    <p><?php esc_html_e('Keine Einträge gefunden.', 'riman-wireframe'); ?></p>
  <?php endif; ?>
</main>

<?php get_footer(); ?>

