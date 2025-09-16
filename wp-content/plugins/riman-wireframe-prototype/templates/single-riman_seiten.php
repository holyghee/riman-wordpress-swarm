<?php
/**
 * Fallback Template für RIMAN Seiten
 * Wird verwendet wenn kein spezifisches Template für den Seitentyp gefunden wird
 */

// Verhindere direkten Zugriff
if (!defined('ABSPATH')) {
    exit;
}

get_header();

// Hole Seitentyp
$seitentyp_terms = wp_get_post_terms(get_the_ID(), 'seitentyp');
$seitentyp = '';
$seitentyp_name = '';

if (!empty($seitentyp_terms) && !is_wp_error($seitentyp_terms)) {
    $seitentyp = $seitentyp_terms[0]->slug;
    $seitentyp_name = $seitentyp_terms[0]->name;
}

?>

<div class="riman-wireframe-container">
    <article id="post-<?php the_ID(); ?>" <?php post_class('riman-generic-page'); ?>>
        
        <!-- Header Section -->
        <section class="riman-header-section">
            <div class="riman-content-wrapper">
                <header class="entry-header">
                    <?php if ($seitentyp_name): ?>
                        <div class="page-type-indicator">
                            <span class="page-type-badge seitentyp-<?php echo esc_attr($seitentyp); ?>">
                                <?php echo esc_html($seitentyp_name); ?>
                            </span>
                        </div>
                    <?php endif; ?>
                    
                    <h1 class="entry-title"><?php the_title(); ?></h1>
                    
                    <?php if (get_the_excerpt()): ?>
                        <div class="entry-excerpt">
                            <p><?php the_excerpt(); ?></p>
                        </div>
                    <?php endif; ?>
                </header>
            </div>
        </section>

        <!-- Content Section -->
        <section class="riman-content-section">
            <div class="riman-content-wrapper">
                <?php if (have_posts()): ?>
                    <?php while (have_posts()): the_post(); ?>
                        <div class="entry-content">
                            <?php the_content(); ?>
                        </div>
                    <?php endwhile; ?>
                <?php endif; ?>
            </div>
        </section>

        <!-- Meta Information -->
        <section class="riman-meta-section">
            <div class="riman-content-wrapper">
                <div class="entry-meta">
                    <?php if ($seitentyp_name): ?>
                        <div class="meta-item">
                            <strong><?php _e('Seitentyp:', 'riman-wireframe'); ?></strong>
                            <span class="seitentyp-<?php echo esc_attr($seitentyp); ?>">
                                <?php echo esc_html($seitentyp_name); ?>
                            </span>
                        </div>
                    <?php endif; ?>
                    
                    <div class="meta-item">
                        <strong><?php _e('Letzte Aktualisierung:', 'riman-wireframe'); ?></strong>
                        <time datetime="<?php echo get_the_modified_date('c'); ?>">
                            <?php echo get_the_modified_date(); ?>
                        </time>
                    </div>
                </div>
            </div>
        </section>

        <!-- Admin Notice -->
        <?php if (current_user_can('edit_post', get_the_ID())): ?>
        <section class="riman-admin-section">
            <div class="riman-content-wrapper">
                <div class="admin-notice">
                    <h3><?php _e('Administrator-Hinweis', 'riman-wireframe'); ?></h3>
                    <p>
                        <?php _e('Diese Seite verwendet das generische RIMAN Template, da kein spezifisches Template für den Seitentyp gefunden wurde.', 'riman-wireframe'); ?>
                        <?php if ($seitentyp): ?>
                        <?php printf(
                            __('Erwartetes Template: %s', 'riman-wireframe'), 
                            '<code>single-riman_seiten-' . esc_html($seitentyp) . '.php</code>'
                        ); ?>
                        <?php endif; ?>
                    </p>
                    <div class="admin-actions">
                        <a href="<?php echo get_edit_post_link(); ?>" class="button-edit">
                            <?php _e('Seite bearbeiten', 'riman-wireframe'); ?>
                        </a>
                        <a href="<?php echo admin_url('edit.php?post_type=riman_seiten&page=riman-wireframe-overview'); ?>" class="button-overview">
                            <?php _e('Wireframe Übersicht', 'riman-wireframe'); ?>
                        </a>
                    </div>
                </div>
            </div>
        </section>
        <?php endif; ?>

        <!-- Navigation -->
        <?php 
        $parent_id = wp_get_post_parent_id(get_the_ID());
        $parent_post = $parent_id ? get_post($parent_id) : null;
        
        if ($parent_post): ?>
        <section class="riman-navigation-section">
            <div class="riman-content-wrapper">
                <a href="<?php echo get_permalink($parent_post->ID); ?>" class="back-to-parent">
                    <span class="nav-arrow">←</span>
                    <?php printf(__('Zurück zu %s', 'riman-wireframe'), esc_html($parent_post->post_title)); ?>
                </a>
            </div>
        </section>
        <?php endif; ?>

    </article>
</div>

<style>
/* Generic RIMAN Page Styling */
.riman-wireframe-container {
    margin: 0;
    padding: 0;
    width: 100%;
}

.riman-header-section {
    background: linear-gradient(135deg, #f8f9fa, #e9ecef);
    padding: 3rem 0;
}

.entry-header {
    text-align: center;
}

.page-type-indicator {
    margin-bottom: 1rem;
}

.page-type-badge {
    display: inline-block;
    padding: 0.5rem 1rem;
    border-radius: 20px;
    font-size: 0.85rem;
    font-weight: 600;
    text-transform: uppercase;
    letter-spacing: 0.5px;
    color: white;
}

.page-type-badge.seitentyp-hauptseite {
    background: #d63638;
}

.page-type-badge.seitentyp-unterseite {
    background: #00a32a;
}

.page-type-badge.seitentyp-detailseite {
    background: #2271b1;
}

.entry-title {
    font-size: clamp(2rem, 4vw, 3rem);
    color: #333;
    margin-bottom: 1rem;
}

.entry-excerpt {
    font-size: 1.1rem;
    color: #666;
    line-height: 1.6;
    max-width: 600px;
    margin: 0 auto;
}

.riman-content-section {
    padding: 3rem 0;
    background: white;
}

.riman-content-wrapper {
    max-width: 1200px;
    margin: 0 auto;
    padding: 0 2rem;
}

.entry-content {
    font-size: 1.1rem;
    line-height: 1.7;
    color: #333;
}

.riman-meta-section {
    padding: 2rem 0;
    background: #f8f9fa;
    border-top: 1px solid #dee2e6;
}

.entry-meta {
    display: flex;
    flex-wrap: wrap;
    gap: 2rem;
    justify-content: center;
    font-size: 0.9rem;
    color: #666;
}

.meta-item strong {
    color: #333;
}

.riman-admin-section {
    padding: 2rem 0;
    background: #fff3cd;
    border-top: 3px solid #ffc107;
}

.admin-notice {
    background: white;
    border: 1px solid #ffc107;
    border-radius: 8px;
    padding: 1.5rem;
    text-align: center;
}

.admin-notice h3 {
    color: #856404;
    margin-bottom: 1rem;
}

.admin-notice p {
    color: #856404;
    margin-bottom: 1.5rem;
    line-height: 1.6;
}

.admin-notice code {
    background: #f8f9fa;
    padding: 0.2rem 0.5rem;
    border-radius: 3px;
    font-family: monospace;
    color: #e83e8c;
}

.admin-actions {
    display: flex;
    gap: 1rem;
    justify-content: center;
    flex-wrap: wrap;
}

.button-edit,
.button-overview {
    display: inline-block;
    padding: 0.75rem 1.5rem;
    text-decoration: none;
    border-radius: 4px;
    font-weight: 500;
    transition: all 0.3s ease;
}

.button-edit {
    background: #007cba;
    color: white;
}

.button-edit:hover {
    background: #005a87;
    color: white;
}

.button-overview {
    background: #6c757d;
    color: white;
}

.button-overview:hover {
    background: #545b62;
    color: white;
}

.riman-navigation-section {
    padding: 2rem 0;
    background: white;
    border-top: 1px solid #dee2e6;
}

.back-to-parent {
    display: inline-flex;
    align-items: center;
    color: #007cba;
    text-decoration: none;
    font-weight: 500;
    transition: color 0.3s ease;
}

.back-to-parent:hover {
    color: #005a87;
    text-decoration: none;
}

.nav-arrow {
    margin-right: 0.5rem;
    font-size: 1.2rem;
}

/* Responsive */
@media (max-width: 768px) {
    .riman-content-wrapper {
        padding: 0 1rem;
    }
    
    .entry-meta {
        flex-direction: column;
        gap: 1rem;
        text-align: center;
    }
    
    .admin-actions {
        flex-direction: column;
        align-items: center;
    }
    
    .button-edit,
    .button-overview {
        min-width: 200px;
    }
}
</style>

<?php
get_footer();
?>