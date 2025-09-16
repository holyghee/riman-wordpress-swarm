<?php
/**
 * Template für RIMAN Unterseiten
 * Zeigt Unterseiten-spezifische Inhalte mit Video und verlinkten Detailseiten
 */

// Verhindere direkten Zugriff
if (!defined('ABSPATH')) {
    exit;
}

get_header();

// Hole Meta-Daten für Unterseite
$meta = RIMAN_Wireframe_Meta_Boxes::get_page_meta(get_the_ID(), 'unterseite');
$video_url = $meta['video_url'] ?? '';
$detailseiten_anzahl = intval($meta['detailseiten_anzahl'] ?? 3);

// Hole Parent-Seite für Breadcrumb
$parent_id = wp_get_post_parent_id(get_the_ID());
$parent_post = $parent_id ? get_post($parent_id) : null;

// Hole Child-Seiten (Detailseiten)
$child_pages = get_children(array(
    'post_parent' => get_the_ID(),
    'post_type' => 'riman_seiten',
    'post_status' => 'publish',
    'orderby' => 'menu_order',
    'order' => 'ASC',
    'numberposts' => $detailseiten_anzahl
));

?>

<div class="riman-wireframe-container">
    <article id="post-<?php the_ID(); ?>" <?php post_class('riman-unterseite'); ?>>
        
        <!-- Breadcrumb Navigation -->
        <nav class="riman-breadcrumb">
            <div class="riman-content-wrapper">
                <?php if ($parent_post): ?>
                    <a href="<?php echo get_permalink($parent_post->ID); ?>" class="breadcrumb-parent">
                        <?php echo esc_html($parent_post->post_title); ?>
                    </a>
                    <span class="breadcrumb-separator">/</span>
                <?php endif; ?>
                <span class="breadcrumb-current"><?php the_title(); ?></span>
            </div>
        </nav>

        <!-- Hero Section mit Video -->
        <section class="riman-hero-section">
            <?php if ($video_url): ?>
                <div class="riman-hero-video-container">
                    <video class="riman-hero-video" autoplay muted loop playsinline>
                        <source src="<?php echo esc_url($video_url); ?>" type="video/mp4">
                        <p><?php _e('Ihr Browser unterstützt keine HTML5-Videos.', 'riman-wireframe'); ?></p>
                    </video>
                    <div class="riman-video-overlay"></div>
                </div>
            <?php endif; ?>
            
            <div class="riman-hero-content">
                <header class="entry-header">
                    <h1 class="entry-title"><?php the_title(); ?></h1>
                    
                    <?php if (get_the_excerpt()): ?>
                        <div class="riman-unterseite-excerpt">
                            <p><?php the_excerpt(); ?></p>
                        </div>
                    <?php endif; ?>
                </header>
            </div>
        </section>

        <!-- Hauptinhalt -->
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

        <!-- Detailseiten Navigation -->
        <?php if (!empty($child_pages)): ?>
        <section class="riman-detailseiten-section">
            <div class="riman-content-wrapper">
                <h2 class="section-title">
                    <?php printf(__('Detailbereiche (%d)', 'riman-wireframe'), count($child_pages)); ?>
                </h2>
                
                <div class="riman-detailseiten-grid">
                    <?php foreach ($child_pages as $index => $child_page): ?>
                        <?php
                        $child_meta = RIMAN_Wireframe_Meta_Boxes::get_page_meta($child_page->ID, 'detailseite');
                        $child_video_info = $child_meta['video_info'] ?? array();
                        $first_video = !empty($child_video_info) ? $child_video_info[0]['video_url'] ?? '' : '';
                        $child_thumbnail = get_the_post_thumbnail_url($child_page->ID, 'medium_large');
                        ?>
                        
                        <div class="riman-detailseite-card">
                            <a href="<?php echo get_permalink($child_page->ID); ?>" class="card-link">
                                <div class="card-media">
                                    <?php if ($first_video): ?>
                                        <video class="card-video" muted loop preload="metadata">
                                            <source src="<?php echo esc_url($first_video); ?>" type="video/mp4">
                                        </video>
                                        <div class="video-play-overlay">
                                            <span class="play-icon">▶</span>
                                        </div>
                                    <?php elseif ($child_thumbnail): ?>
                                        <img src="<?php echo esc_url($child_thumbnail); ?>" alt="<?php echo esc_attr($child_page->post_title); ?>">
                                    <?php else: ?>
                                        <div class="card-placeholder">
                                            <span class="card-number"><?php echo $index + 1; ?></span>
                                        </div>
                                    <?php endif; ?>
                                </div>
                                
                                <div class="card-content">
                                    <h3 class="card-title"><?php echo esc_html($child_page->post_title); ?></h3>
                                    
                                    <?php if ($child_page->post_excerpt): ?>
                                        <p class="card-excerpt"><?php echo esc_html($child_page->post_excerpt); ?></p>
                                    <?php endif; ?>
                                    
                                    <?php if (!empty($child_video_info)): ?>
                                        <div class="card-meta">
                                            <span class="video-count">
                                                <?php printf(_n('%d Video-Info', '%d Video-Infos', count($child_video_info), 'riman-wireframe'), count($child_video_info)); ?>
                                            </span>
                                        </div>
                                    <?php endif; ?>
                                </div>
                            </a>
                        </div>
                    <?php endforeach; ?>
                </div>
                
                <?php if (count($child_pages) < $detailseiten_anzahl): ?>
                <div class="riman-missing-detailseiten">
                    <p class="missing-notice">
                        <?php printf(
                            __('Es fehlen noch %d Detailseiten. Diese können im WordPress Admin erstellt werden.', 'riman-wireframe'),
                            $detailseiten_anzahl - count($child_pages)
                        ); ?>
                    </p>
                    
                    <?php if (current_user_can('edit_posts')): ?>
                    <a href="<?php echo admin_url('post-new.php?post_type=riman_seiten&post_parent=' . get_the_ID()); ?>" 
                       class="button-add-detailseite">
                        <?php _e('Detailseite hinzufügen', 'riman-wireframe'); ?>
                    </a>
                    <?php endif; ?>
                </div>
                <?php endif; ?>
            </div>
        </section>
        <?php endif; ?>

        <!-- Back to Parent Navigation -->
        <?php if ($parent_post): ?>
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
/* Unterseite Styling */
.riman-wireframe-container {
    margin: 0;
    padding: 0;
    width: 100%;
}

.riman-breadcrumb {
    background: #f8f9fa;
    padding: 1rem 0;
    border-bottom: 1px solid #dee2e6;
}

.riman-breadcrumb a {
    color: #007cba;
    text-decoration: none;
}

.riman-breadcrumb a:hover {
    text-decoration: underline;
}

.breadcrumb-separator {
    margin: 0 0.5rem;
    color: #6c757d;
}

.breadcrumb-current {
    color: #495057;
    font-weight: 500;
}

.riman-hero-section {
    position: relative;
    min-height: 60vh;
    display: flex;
    align-items: center;
    justify-content: center;
    color: white;
    background: linear-gradient(45deg, #667eea 0%, #764ba2 100%);
    overflow: hidden;
}

.riman-hero-video-container {
    position: absolute;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    z-index: 1;
}

.riman-hero-video {
    width: 100%;
    height: 100%;
    object-fit: cover;
}

.riman-video-overlay {
    position: absolute;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    background: rgba(0, 0, 0, 0.4);
    z-index: 2;
}

.riman-hero-content {
    position: relative;
    z-index: 3;
    text-align: center;
    max-width: 800px;
    margin: 0 auto;
    padding: 2rem;
}

.riman-hero-content .entry-title {
    font-size: clamp(2rem, 4vw, 3.5rem);
    margin-bottom: 1rem;
    text-shadow: 2px 2px 4px rgba(0, 0, 0, 0.7);
}

.riman-unterseite-excerpt {
    font-size: 1.1rem;
    line-height: 1.6;
    opacity: 0.9;
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

.riman-detailseiten-section {
    padding: 4rem 0;
    background: #f8f9fa;
}

.section-title {
    text-align: center;
    font-size: 2rem;
    margin-bottom: 3rem;
    color: #333;
}

.riman-detailseiten-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
    gap: 2rem;
    margin-bottom: 2rem;
}

.riman-detailseite-card {
    background: white;
    border-radius: 8px;
    overflow: hidden;
    box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
    transition: all 0.3s ease;
}

.riman-detailseite-card:hover {
    transform: translateY(-2px);
    box-shadow: 0 4px 20px rgba(0, 0, 0, 0.15);
}

.card-link {
    text-decoration: none;
    color: inherit;
    display: block;
}

.card-media {
    position: relative;
    height: 180px;
    overflow: hidden;
    background: #e9ecef;
}

.card-video,
.card-media img {
    width: 100%;
    height: 100%;
    object-fit: cover;
}

.video-play-overlay {
    position: absolute;
    top: 50%;
    left: 50%;
    transform: translate(-50%, -50%);
    background: rgba(0, 0, 0, 0.7);
    border-radius: 50%;
    width: 50px;
    height: 50px;
    display: flex;
    align-items: center;
    justify-content: center;
    opacity: 0;
    transition: opacity 0.3s ease;
}

.riman-detailseite-card:hover .video-play-overlay {
    opacity: 1;
}

.play-icon {
    color: white;
    font-size: 1.2rem;
    margin-left: 3px;
}

.card-placeholder {
    display: flex;
    align-items: center;
    justify-content: center;
    height: 100%;
    background: linear-gradient(135deg, #e9ecef, #f8f9fa);
}

.card-number {
    font-size: 2rem;
    font-weight: bold;
    color: #6c757d;
    background: white;
    width: 60px;
    height: 60px;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
}

.card-content {
    padding: 1.5rem;
}

.card-title {
    font-size: 1.2rem;
    margin-bottom: 0.5rem;
    color: #333;
}

.card-excerpt {
    color: #666;
    line-height: 1.5;
    font-size: 0.9rem;
    margin-bottom: 1rem;
}

.card-meta {
    border-top: 1px solid #f0f0f0;
    padding-top: 0.75rem;
    margin-top: 1rem;
}

.video-count {
    font-size: 0.85rem;
    color: #007cba;
    font-weight: 500;
}

.riman-missing-detailseiten {
    text-align: center;
    padding: 2rem;
    background: white;
    border: 2px dashed #dee2e6;
    border-radius: 8px;
}

.missing-notice {
    color: #6c757d;
    margin-bottom: 1rem;
}

.button-add-detailseite {
    display: inline-block;
    background: #007cba;
    color: white;
    padding: 0.75rem 1.5rem;
    text-decoration: none;
    border-radius: 4px;
    transition: background 0.3s ease;
}

.button-add-detailseite:hover {
    background: #005a87;
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
    .riman-hero-content {
        padding: 1rem;
    }
    
    .riman-detailseiten-grid {
        grid-template-columns: 1fr;
        gap: 1.5rem;
    }
    
    .riman-content-wrapper {
        padding: 0 1rem;
    }
    
    .riman-breadcrumb {
        font-size: 0.9rem;
    }
}
</style>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Video Hover-Effekte für Detailseiten-Cards
    const videoCards = document.querySelectorAll('.riman-detailseite-card');
    
    videoCards.forEach(card => {
        const video = card.querySelector('.card-video');
        
        if (video) {
            card.addEventListener('mouseenter', () => {
                video.play().catch(() => {
                    // Ignoriere Autoplay-Fehler
                });
            });
            
            card.addEventListener('mouseleave', () => {
                video.pause();
                video.currentTime = 0;
            });
        }
    });
});
</script>

<?php
get_footer();
?>