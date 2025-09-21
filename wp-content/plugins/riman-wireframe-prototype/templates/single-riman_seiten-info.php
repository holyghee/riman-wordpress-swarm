<?php
/**
 * Single Template for RIMAN 'info' pages (Kindseiten der Detailseiten)
 * Fokussiert auf klaren Inhalt + Rücksprung zum Parent.
 */
if (!defined('ABSPATH')) exit;

get_header();

while (have_posts()) : the_post();
  $parent_id = wp_get_post_parent_id(get_the_ID());
  $parent_link = $parent_id ? get_permalink($parent_id) : home_url('/');
?>
<main id="primary" class="site-main">
  <article id="post-<?php the_ID(); ?>" <?php post_class(); ?>>
    <header class="entry-header" style="margin:1rem 0 1.5rem">
      <h1 class="entry-title"><?php the_title(); ?></h1>
      <?php if ($parent_id): ?>
        <p style="margin:.5rem 0 0"><a href="<?php echo esc_url($parent_link); ?>">← <?php echo esc_html(get_the_title($parent_id)); ?></a></p>
      <?php endif; ?>
    </header>

    <?php if (has_post_thumbnail()): ?>
      <div class="post-thumbnail" style="margin-bottom:1rem;line-height:0;">
        <?php the_post_thumbnail('large'); ?>
      </div>
    <?php endif; ?>

    <div class="entry-content">
      <?php the_content(); ?>
    </div>
  </article>
</main>
<?php endwhile; get_footer();

