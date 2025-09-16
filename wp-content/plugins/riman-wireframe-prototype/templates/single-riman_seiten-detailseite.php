<?php
/**
 * Template für RIMAN Detailseiten
 * Zeigt Detailseiten-spezifische Inhalte mit Video-Info Feldern
 */

// Verhindere direkten Zugriff
if (!defined('ABSPATH')) {
    exit;
}

get_header();

// Hole Meta-Daten für Detailseite
$meta = RIMAN_Wireframe_Meta_Boxes::get_page_meta(get_the_ID(), 'detailseite');
$video_info_fields = $meta['video_info'] ?? array();

// Hole Parent-Seite für Breadcrumb
$parent_id = wp_get_post_parent_id(get_the_ID());
$parent_post = $parent_id ? get_post($parent_id) : null;

// Hole Grandparent (Hauptseite) für vollständige Navigation
$grandparent_id = $parent_post ? wp_get_post_parent_id($parent_post->ID) : null;
$grandparent_post = $grandparent_id ? get_post($grandparent_id) : null;

?>

<div class="riman-wireframe-container">
    <article id="post-<?php the_ID(); ?>" <?php post_class('riman-detailseite'); ?>>
        
        <!-- Breadcrumb Navigation -->
        <nav class="riman-breadcrumb">
            <div class="riman-content-wrapper">
                <?php if ($grandparent_post): ?>
                    <a href="<?php echo get_permalink($grandparent_post->ID); ?>" class="breadcrumb-item">
                        <?php echo esc_html($grandparent_post->post_title); ?>
                    </a>
                    <span class="breadcrumb-separator">/</span>
                <?php endif; ?>
                
                <?php if ($parent_post): ?>
                    <a href="<?php echo get_permalink($parent_post->ID); ?>" class="breadcrumb-item">
                        <?php echo esc_html($parent_post->post_title); ?>
                    </a>
                    <span class="breadcrumb-separator">/</span>
                <?php endif; ?>
                
                <span class="breadcrumb-current"><?php the_title(); ?></span>
            </div>
        </nav>

        <!-- Header Section -->
        <section class="riman-header-section">
            <div class="riman-content-wrapper">
                <header class="entry-header">
                    <h1 class="entry-title"><?php the_title(); ?></h1>
                    
                    <?php if (get_the_excerpt()): ?>
                        <div class="riman-detailseite-excerpt">
                            <p><?php the_excerpt(); ?></p>
                        </div>
                    <?php endif; ?>
                </header>
            </div>
        </section>

        <!-- Hauptinhalt -->
        <?php if (have_posts()): ?>
            <?php while (have_posts()): the_post(); ?>
                <?php if (get_the_content()): ?>
                <section class="riman-content-section">
                    <div class="riman-content-wrapper">
                        <div class="entry-content">
                            <?php the_content(); ?>
                        </div>
                    </div>
                </section>
                <?php endif; ?>
            <?php endwhile; ?>
        <?php endif; ?>

        <!-- Video-Info Felder Section -->
        <?php if (!empty($video_info_fields)): ?>
        <section class="riman-video-info-section">
            <div class="riman-content-wrapper">
                <h2 class="section-title">
                    <?php _e('Video-Informationen', 'riman-wireframe'); ?>
                </h2>
                
                <div class="riman-video-info-grid">
                    <?php foreach ($video_info_fields as $index => $field): ?>
                        <?php if (empty($field['video_url']) && empty($field['ueberschrift']) && empty($field['beschreibung'])) continue; ?>
                        
                        <div class="riman-video-info-item" data-index="<?php echo $index; ?>">
                            <div class="video-info-media">
                                <?php if (!empty($field['video_url'])): ?>
                                    <div class="video-container">
                                        <video class="info-video" 
                                               controls 
                                               preload="metadata"
                                               poster="">
                                            <source src="<?php echo esc_url($field['video_url']); ?>" type="video/mp4">
                                            <p><?php _e('Ihr Browser unterstützt keine HTML5-Videos.', 'riman-wireframe'); ?></p>
                                        </video>
                                        <div class="video-overlay">
                                            <button class="video-play-btn" type="button">
                                                <span class="play-icon">▶</span>
                                            </button>
                                        </div>
                                    </div>
                                <?php else: ?>
                                    <div class="video-placeholder">
                                        <span class="placeholder-icon">📹</span>
                                        <span class="placeholder-text"><?php _e('Kein Video', 'riman-wireframe'); ?></span>
                                    </div>
                                <?php endif; ?>
                            </div>
                            
                            <div class="video-info-content">
                                <?php if (!empty($field['ueberschrift'])): ?>
                                    <h3 class="info-title"><?php echo esc_html($field['ueberschrift']); ?></h3>
                                <?php endif; ?>
                                
                                <?php if (!empty($field['beschreibung'])): ?>
                                    <div class="info-description">
                                        <p><?php echo wp_kses_post(nl2br($field['beschreibung'])); ?></p>
                                    </div>
                                <?php endif; ?>
                                
                                <div class="info-meta">
                                    <span class="info-number"><?php printf(__('Info %d', 'riman-wireframe'), $index + 1); ?></span>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </section>
        <?php else: ?>
        <section class="riman-no-content-section">
            <div class="riman-content-wrapper">
                <div class="no-content-message">
                    <h3><?php _e('Noch keine Video-Informationen verfügbar', 'riman-wireframe'); ?></h3>
                    <p><?php _e('Die Video-Info Felder können im WordPress Admin bearbeitet werden.', 'riman-wireframe'); ?></p>
                    
                    <?php if (current_user_can('edit_post', get_the_ID())): ?>
                    <a href="<?php echo get_edit_post_link(); ?>" class="button-edit-page">
                        <?php _e('Seite bearbeiten', 'riman-wireframe'); ?>
                    </a>
                    <?php endif; ?>
                </div>
            </div>
        </section>
        <?php endif; ?>

        <!-- Navigation Section -->
        <section class="riman-navigation-section">
            <div class="riman-content-wrapper">
                <div class="navigation-links">
                    <?php if ($parent_post): ?>
                        <a href="<?php echo get_permalink($parent_post->ID); ?>" class="nav-link nav-up">
                            <span class="nav-arrow">↑</span>
                            <?php printf(__('Zurück zu %s', 'riman-wireframe'), esc_html($parent_post->post_title)); ?>
                        </a>
                    <?php endif; ?>
                    
                    <?php
                    // Hole Geschwister-Seiten für Vor/Zurück Navigation
                    if ($parent_id) {
                        $sibling_pages = get_children(array(
                            'post_parent' => $parent_id,
                            'post_type' => 'riman_seiten',
                            'post_status' => 'publish',
                            'orderby' => 'menu_order',
                            'order' => 'ASC'
                        ));
                        
                        $current_index = array_search(get_the_ID(), array_keys($sibling_pages));
                        $prev_page = $current_index > 0 ? $sibling_pages[array_keys($sibling_pages)[$current_index - 1]] : null;
                        $next_page = $current_index < (count($sibling_pages) - 1) ? $sibling_pages[array_keys($sibling_pages)[$current_index + 1]] : null;
                    ?>
                    
                    <div class="nav-siblings">
                        <?php if ($prev_page): ?>
                            <a href="<?php echo get_permalink($prev_page->ID); ?>" class="nav-link nav-prev">
                                <span class="nav-arrow">←</span>
                                <?php echo esc_html($prev_page->post_title); ?>
                            </a>
                        <?php endif; ?>
                        
                        <?php if ($next_page): ?>
                            <a href="<?php echo get_permalink($next_page->ID); ?>" class="nav-link nav-next">
                                <?php echo esc_html($next_page->post_title); ?>
                                <span class="nav-arrow">→</span>
                            </a>
                        <?php endif; ?>
                    </div>
                    <?php } ?>
                </div>
            </div>
        </section>

    </article>
</div>

<style>
/* Detailseite Styling */
.riman-wireframe-container {
    margin: 0;
    padding: 0;
    width: 100%;
}

.riman-breadcrumb {
    background: #f8f9fa;
    padding: 1rem 0;
    border-bottom: 1px solid #dee2e6;
    font-size: 0.9rem;
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

.riman-header-section {
    background: linear-gradient(135deg, #f8f9fa, #e9ecef);
    padding: 3rem 0;
}

.entry-header {
    text-align: center;
}

.entry-title {
    font-size: clamp(2rem, 4vw, 3rem);
    color: #333;
    margin-bottom: 1rem;
}

.riman-detailseite-excerpt {
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

.riman-video-info-section {
    padding: 4rem 0;
    background: #f8f9fa;
}

.section-title {
    text-align: center;
    font-size: 2rem;
    margin-bottom: 3rem;
    color: #333;
}

.riman-video-info-grid {
    display: grid;
    gap: 3rem;
}

.riman-video-info-item {
    background: white;
    border-radius: 12px;
    overflow: hidden;
    box-shadow: 0 4px 6px rgba(0, 0, 0, 0.07);
    display: grid;
    grid-template-columns: 1fr 1fr;
    min-height: 300px;
}

.riman-video-info-item:nth-child(even) {
    grid-template-columns: 1fr 1fr;
    direction: rtl;
}

.riman-video-info-item:nth-child(even) .video-info-content {
    direction: ltr;
}

.video-info-media {
    position: relative;
    background: #000;
}

.video-container {
    position: relative;
    width: 100%;
    height: 100%;
}

.info-video {
    width: 100%;
    height: 100%;
    object-fit: cover;
    display: block;
}

.video-overlay {
    position: absolute;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    background: rgba(0, 0, 0, 0.3);
    display: flex;
    align-items: center;
    justify-content: center;
    opacity: 1;
    transition: opacity 0.3s ease;
    pointer-events: none;
}

.video-container:hover .video-overlay {
    opacity: 0;
}

.video-overlay.playing {
    opacity: 0;
    pointer-events: none;
}

.video-play-btn {
    background: rgba(255, 255, 255, 0.9);
    border: none;
    width: 70px;
    height: 70px;
    border-radius: 50%;
    cursor: pointer;
    transition: all 0.3s ease;
    pointer-events: all;
}

.video-play-btn:hover {
    background: white;
    transform: scale(1.1);
}

.play-icon {
    font-size: 1.5rem;
    color: #333;
    margin-left: 3px;
}

.video-placeholder {
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    height: 100%;
    background: linear-gradient(135deg, #e9ecef, #f8f9fa);
    color: #6c757d;
}

.placeholder-icon {
    font-size: 3rem;
    margin-bottom: 1rem;
}

.placeholder-text {
    font-weight: 500;
}

.video-info-content {
    padding: 2rem;
    display: flex;
    flex-direction: column;
    justify-content: center;
}

.info-title {
    font-size: 1.5rem;
    color: #333;
    margin-bottom: 1rem;
    line-height: 1.3;
}

.info-description {
    color: #666;
    line-height: 1.6;
    margin-bottom: 1.5rem;
}

.info-meta {
    border-top: 1px solid #f0f0f0;
    padding-top: 1rem;
    margin-top: auto;
}

.info-number {
    font-size: 0.85rem;
    color: #007cba;
    font-weight: 500;
    text-transform: uppercase;
    letter-spacing: 0.5px;
}

.riman-no-content-section {
    padding: 4rem 0;
    background: #f8f9fa;
}

.no-content-message {
    text-align: center;
    padding: 3rem;
    background: white;
    border-radius: 8px;
    box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
}

.no-content-message h3 {
    color: #666;
    margin-bottom: 1rem;
}

.no-content-message p {
    color: #888;
    margin-bottom: 2rem;
}

.button-edit-page {
    display: inline-block;
    background: #007cba;
    color: white;
    padding: 0.75rem 1.5rem;
    text-decoration: none;
    border-radius: 4px;
    transition: background 0.3s ease;
}

.button-edit-page:hover {
    background: #005a87;
    color: white;
}

.riman-navigation-section {
    padding: 2rem 0;
    background: white;
    border-top: 1px solid #dee2e6;
}

.navigation-links {
    display: flex;
    flex-direction: column;
    gap: 1rem;
}

.nav-siblings {
    display: flex;
    justify-content: space-between;
    gap: 1rem;
}

.nav-link {
    display: inline-flex;
    align-items: center;
    color: #007cba;
    text-decoration: none;
    font-weight: 500;
    padding: 0.75rem 1rem;
    border-radius: 4px;
    border: 1px solid transparent;
    transition: all 0.3s ease;
}

.nav-link:hover {
    color: #005a87;
    text-decoration: none;
    background: #f8f9fa;
    border-color: #dee2e6;
}

.nav-arrow {
    font-size: 1.2rem;
}

.nav-up .nav-arrow {
    margin-right: 0.5rem;
}

.nav-prev .nav-arrow {
    margin-right: 0.5rem;
}

.nav-next .nav-arrow {
    margin-left: 0.5rem;
}

/* Responsive */
@media (max-width: 768px) {
    .riman-video-info-item {
        grid-template-columns: 1fr;
        direction: ltr;
    }
    
    .riman-video-info-item:nth-child(even) {
        grid-template-columns: 1fr;
        direction: ltr;
    }
    
    .video-info-content {
        direction: ltr;
    }
    
    .riman-content-wrapper {
        padding: 0 1rem;
    }
    
    .nav-siblings {
        flex-direction: column;
    }
    
    .riman-breadcrumb {
        font-size: 0.8rem;
    }
}

@media (max-width: 480px) {
    .riman-video-info-grid {
        gap: 2rem;
    }
    
    .video-info-content {
        padding: 1.5rem;
    }
    
    .video-play-btn {
        width: 50px;
        height: 50px;
    }
    
    .play-icon {
        font-size: 1.2rem;
    }
}
</style>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Video Play Button Funktionalität
    const videoContainers = document.querySelectorAll('.video-container');
    
    videoContainers.forEach(container => {
        const video = container.querySelector('.info-video');
        const playBtn = container.querySelector('.video-play-btn');
        const overlay = container.querySelector('.video-overlay');
        
        if (video && playBtn && overlay) {
            playBtn.addEventListener('click', function() {
                if (video.paused) {
                    video.play();
                    overlay.style.opacity = '0';
                    overlay.style.pointerEvents = 'none';
                } else {
                    video.pause();
                    overlay.style.opacity = '1';
                    overlay.style.pointerEvents = 'all';
                }
            });
            
            video.addEventListener('click', function() {
                if (!video.paused) {
                    video.pause();
                    overlay.style.opacity = '1';
                    overlay.style.pointerEvents = 'all';
                }
            });
            
            video.addEventListener('ended', function() {
                overlay.style.opacity = '1';
                overlay.style.pointerEvents = 'all';
            });
        }
    });
});
</script>

<?php
get_footer();
?>
