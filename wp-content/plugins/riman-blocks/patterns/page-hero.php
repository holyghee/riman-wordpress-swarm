<?php
// Register a page hero pattern (featured image cover with centered title)
if (!defined('ABSPATH')) exit;

add_action('init', function() {
    if (!function_exists('register_block_pattern')) return;

    // Ensure pattern category exists
    if (function_exists('register_block_pattern_category')) {
        register_block_pattern_category('riman', [ 'label' => __('RIMAN Patterns', 'riman') ]);
    }

    $content = <<<HTML
<!-- wp:cover {"useFeaturedImage":true,"dimRatio":20,"isUserOverlayColor":true,"minHeight":840,"minHeightUnit":"px","contentPosition":"center center","isDark":false,"align":"full","style":{"spacing":{"padding":{"top":"var:preset|spacing|50","bottom":"var:preset|spacing|50","left":"var:preset|spacing|50","right":"var:preset|spacing|50"},"margin":{"top":"0","bottom":"0"}}},"layout":{"type":"constrained"}} -->
<div class="wp-block-cover alignfull is-light" style="margin-top:0;margin-bottom:0;padding-top:var(--wp--preset--spacing--50);padding-right:var(--wp--preset--spacing--50);padding-bottom:var(--wp--preset--spacing--50);padding-left:var(--wp--preset--spacing--50);min-height:840px"><span aria-hidden="true" class="wp-block-cover__background has-background-dim-20 has-background-dim"></span><div class="wp-block-cover__inner-container"><!-- wp:group {"align":"wide","layout":{"type":"constrained","justifyContent":"center"}} -->
<div class="wp-block-group alignwide"><!-- wp:post-title {"textAlign":"center","level":1} /--></div>
<!-- /wp:group --></div></div>
<!-- /wp:cover -->
HTML;

    register_block_pattern('riman/page-hero-featured', [
        'title'       => __('RIMAN: Page Hero (Featured Image)', 'riman'),
        'description' => __('Vollbreiter Hero mit Beitragsbild und zentriertem Titel', 'riman'),
        'categories'  => ['riman'],
        'content'     => $content,
    ]);
});

