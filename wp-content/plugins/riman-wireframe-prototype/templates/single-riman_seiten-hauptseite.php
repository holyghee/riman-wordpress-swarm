<?php
/**
 * Template für RIMAN Hauptseiten
 * Zeigt Hauptseiten-spezifische Inhalte mit großem Video und Beschreibung
 */

// Verhindere direkten Zugriff
if (!defined('ABSPATH')) {
    exit;
}

get_header();

// Hole Meta-Daten für Hauptseite
$meta = RIMAN_Wireframe_Meta_Boxes::get_page_meta(get_the_ID(), 'hauptseite');
$video_url = $meta['video_url'] ?? '';
$beschreibung = $meta['beschreibung'] ?? '';

// Hole Child-Seiten (Unterseiten)
$child_pages = get_children(array(
    'post_parent' => get_the_ID(),
    'post_type' => 'riman_seiten',
    'post_status' => 'publish',
    'orderby' => 'menu_order',
    'order' => 'ASC'
));

?>

<div class="riman-wireframe-container">
    <article id="post-<?php the_ID(); ?>" <?php post_class('riman-hauptseite'); ?>>
        
        <!-- Hero Section mit großem Video -->
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
                    
                    <?php if ($beschreibung): ?>
                        <div class="riman-hauptseite-beschreibung">
                            <p><?php echo wp_kses_post($beschreibung); ?></p>
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

        <!-- Unterseiten Navigation -->
        <?php if (!empty($child_pages)): ?>
        <section class="riman-unterseiten-section">
            <div class="riman-content-wrapper">
                <h2 class="section-title"><?php _e('Bereiche', 'riman-wireframe'); ?></h2>
                
                <div class="riman-unterseiten-grid">
                    <?php foreach ($child_pages as $child_page): ?>
                        <?php
                        $child_meta = RIMAN_Wireframe_Meta_Boxes::get_page_meta($child_page->ID);
                        $child_video_url = $child_meta['video_url'] ?? '';
                        $child_thumbnail = get_the_post_thumbnail_url($child_page->ID, 'large');
                        ?>
                        
                        <div class="riman-unterseite-card">
                            <a href="<?php echo get_permalink($child_page->ID); ?>" class="card-link">
                                <div class="card-media">
                                    <?php if ($child_video_url): ?>
                                        <video class="card-video" muted loop preload="metadata">
                                            <source src="<?php echo esc_url($child_video_url); ?>" type="video/mp4">
                                        </video>
                                    <?php elseif ($child_thumbnail): ?>
                                        <img src="<?php echo esc_url($child_thumbnail); ?>" alt="<?php echo esc_attr($child_page->post_title); ?>">
                                    <?php else: ?>
                                        <div class="card-placeholder">
                                            <span class="dashicons dashicons-format-video"></span>
                                        </div>
                                    <?php endif; ?>
                                </div>
                                
                                <div class="card-content">
                                    <h3 class="card-title"><?php echo esc_html($child_page->post_title); ?></h3>
                                    
                                    <?php if ($child_page->post_excerpt): ?>
                                        <p class="card-excerpt"><?php echo esc_html($child_page->post_excerpt); ?></p>
                                    <?php endif; ?>
                                </div>
                            </a>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </section>
        <?php endif; ?>

    </article>
</div>

<style>
/* Hauptseite Styling */
.riman-wireframe-container {
    margin: 0;
    padding: 0;
    width: 100%;
}

.riman-hero-section {
    position: relative;
    min-height: 70vh;
    display: flex;
    align-items: center;
    justify-content: center;
    color: white;
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
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
    background: rgba(0, 0, 0, 0.3);
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
    font-size: clamp(2.5rem, 5vw, 4rem);
    margin-bottom: 1rem;
    text-shadow: 2px 2px 4px rgba(0, 0, 0, 0.7);
}

.riman-hauptseite-beschreibung {
    font-size: 1.2rem;
    line-height: 1.6;
    opacity: 0.9;
}

.riman-content-section {
    padding: 4rem 0;
    background: white;
}

.riman-content-wrapper {
    max-width: 1200px;
    margin: 0 auto;
    padding: 0 2rem;
}

.riman-unterseiten-section {
    padding: 4rem 0;
    background: #f8f9fa;
}

.section-title {
    text-align: center;
    font-size: 2.5rem;
    margin-bottom: 3rem;
    color: #333;
}

.riman-unterseiten-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(350px, 1fr));
    gap: 2rem;
    margin-top: 2rem;
}

.riman-unterseite-card {
    background: white;
    border-radius: 12px;
    overflow: hidden;
    box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
    transition: transform 0.3s ease, box-shadow 0.3s ease;
}

.riman-unterseite-card:hover {
    transform: translateY(-4px);
    box-shadow: 0 8px 25px rgba(0, 0, 0, 0.15);
}

.card-link {
    text-decoration: none;
    color: inherit;
    display: block;
}

.card-media {
    position: relative;
    height: 200px;
    overflow: hidden;
    background: #f0f0f0;
}

.card-video,
.card-media img {
    width: 100%;
    height: 100%;
    object-fit: cover;
}

.card-video:hover {
    animation: none;
}

.riman-unterseite-card:hover .card-video {
    animation: playPreview 0.3s ease-in-out forwards;
}

@keyframes playPreview {
    from { transform: scale(1); }
    to { transform: scale(1.05); }
}

.card-placeholder {
    display: flex;
    align-items: center;
    justify-content: center;
    height: 100%;
    background: #e9ecef;
    color: #6c757d;
}

.card-placeholder .dashicons {
    font-size: 3rem;
}

.card-content {
    padding: 1.5rem;
}

.card-title {
    font-size: 1.25rem;
    margin-bottom: 0.5rem;
    color: #333;
}

.card-excerpt {
    color: #666;
    line-height: 1.5;
    font-size: 0.95rem;
}

/* Responsive */
@media (max-width: 768px) {
    .riman-hero-content {
        padding: 1rem;
    }
    
    .riman-hero-content .entry-title {
        font-size: 2rem;
    }
    
    .riman-unterseiten-grid {
        grid-template-columns: 1fr;
        gap: 1.5rem;
    }
    
    .riman-content-wrapper {
        padding: 0 1rem;
    }
}

/* Video Hover-Effekt */
.riman-unterseite-card:hover .card-video {
    animation: none;
}

.card-video {
    transition: transform 0.3s ease;
}

.riman-unterseite-card:hover .card-video {
    transform: scale(1.02);
}

/* JavaScript für Video-Hover */
</style>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Video Hover-Effekte für Unterseiten-Cards
    const videoCards = document.querySelectorAll('.riman-unterseite-card');
    
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