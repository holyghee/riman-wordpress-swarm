<?php
/**
 * RIMAN Page Hero Block - Speziell für FSE Templates
 * Zeigt Hero-Section mit Meta-Daten für riman_seiten an
 */

if (!defined('ABSPATH')) exit;

add_action('init', function() {
    // Editor-Script registrieren
    wp_register_script(
        'riman-page-hero-block',
        RIMAN_BLOCKS_URL . 'assets/page-hero-block.js',
        ['wp-blocks', 'wp-block-editor', 'wp-components', 'wp-element'],
        '1.0.0',
        true
    );

    register_block_type('riman/page-hero', [
        'api_version' => 2,
        'title' => __('RIMAN Page Hero', 'riman'),
        'description' => __('Hero-Section für RIMAN-Seiten mit Meta-Daten', 'riman'),
        'category' => 'riman',
        'icon' => 'cover-image',
        'keywords' => ['hero', 'riman', 'page'],
        'editor_script' => 'riman-page-hero-block',
        'attributes' => [
            'minHeight' => [
                'type' => 'number',
                'default' => 600
            ],
            'overlayOpacity' => [
                'type' => 'number',
                'default' => 40
            ]
        ],
        'supports' => [
            'align' => ['full'],
            'spacing' => [
                'margin' => true,
                'padding' => true
            ]
        ],
        'render_callback' => 'riman_render_page_hero_block'
    ]);
});

/**
 * Render-Funktion für RIMAN Page Hero Block
 */
function riman_render_page_hero_block($attributes, $content, $block) {
    // Nur auf riman_seiten anzeigen
    if (!is_singular('riman_seiten')) {
        return '';
    }

    $post_id = get_the_ID();
    $min_height = isset($attributes['minHeight']) ? (int)$attributes['minHeight'] : 600;
    $overlay_opacity = isset($attributes['overlayOpacity']) ? (int)$attributes['overlayOpacity'] : 40;

    // Hero-Meta-Daten laden
    if (class_exists('RIMAN_Hero_Meta')) {
        $hero_meta = RIMAN_Hero_Meta::get_hero_meta($post_id);
    } else {
        $hero_meta = [];
    }

    // Titel und Untertitel bestimmen
    $title = !empty($hero_meta['title']) ? $hero_meta['title'] :
             str_replace(' - Riman GmbH', '', get_the_title());

    // Subtitle logic: prefer long text if explicitly set for hero
    $subtitle = '';
    if (!empty($hero_meta['use_long_in_hero']) && !empty($hero_meta['long_text'])) {
        $subtitle = $hero_meta['long_text'];
    } elseif (!empty($hero_meta['subtitle'])) {
        $subtitle = $hero_meta['subtitle'];
    } else {
        $subtitle = get_the_excerpt();
    }

    $area_label = $hero_meta['area_label'] ?? '';
    $icon = $hero_meta['icon'] ?? '';

    // Featured Video oder Background Image
    $video_src = '';
    $video_mime = '';
    $poster_url = '';
    $poster_id = 0;
    $bg_image = '';

    // 1. Priorität: Featured Video aus Meta Box
    $vid_id = (int) get_post_meta($post_id, '_riman_featured_video_id', true);
    $vid_url = (string) get_post_meta($post_id, '_riman_featured_video_url', true);

    if ($vid_id) {
        $video_src = wp_get_attachment_url($vid_id) ?: '';
        $video_mime = get_post_mime_type($vid_id) ?: '';
    } elseif ($vid_url) {
        $video_src = $vid_url;
        $video_mime = 'video/mp4'; // Annahme für externe URLs
    }

    // 2. Fallback: Featured Image als Background
    if (!$video_src && has_post_thumbnail()) {
        $bg_image = get_the_post_thumbnail_url($post_id, 'full');
    }

    // 3. Poster für Video (Featured Image)
    if (has_post_thumbnail()) {
        $poster_id = (int) get_post_thumbnail_id($post_id);
        $poster_url = get_the_post_thumbnail_url($post_id, 'full');
    }

    // 4. Mobile-optimierte Video-Versionen suchen
    $hero_mobile_src = '';
    if ($video_src) {
        $video_path = wp_get_upload_dir()['basedir'] . str_replace(wp_get_upload_dir()['baseurl'], '', $video_src);
        $video_dir = dirname($video_path);
        $video_filename = pathinfo($video_path, PATHINFO_FILENAME);

        // Mobile-komprimierte Version (gleiches Format, kleinere Dateigröße)
        // Zuerst nach -mobile.mp4 Version suchen (komprimierte Original-Abmessungen)
        $hero_mobile_path = $video_dir . '/mobile/' . $video_filename . '-mobile.mp4';
        if (file_exists($hero_mobile_path)) {
            $hero_mobile_src = wp_get_upload_dir()['baseurl'] . str_replace(wp_get_upload_dir()['basedir'], '', $hero_mobile_path);
        } else {
            // Fallback: Nach komprimierter Version mit anderem Suffix suchen
            $compressed_path = $video_dir . '/mobile/' . $video_filename . '-compressed.mp4';
            if (file_exists($compressed_path)) {
                $hero_mobile_src = wp_get_upload_dir()['baseurl'] . str_replace(wp_get_upload_dir()['basedir'], '', $compressed_path);
            }
        }
    }

    if ($video_src && function_exists('riman_blocks_cover_lazy_enqueue_assets')) {
        // Hero Responsive Video Script ZUERST - muss vor Cover-Lazy-Video laufen
        wp_enqueue_script(
            'riman-hero-responsive-video',
            plugin_dir_url(__FILE__) . '../assets/hero-responsive-video.js',
            [],
            '1.0.1', // Version erhöht für Cache-Busting
            true
        );

        // Cover-Lazy-Video Script DANACH - abhängig von Hero Responsive
        riman_blocks_cover_lazy_enqueue_assets();
    }

    // CSS-Klassen
    $wrapper_classes = [
        'wp-block-riman-page-hero',
        'riman-page-hero',
        'alignfull'
    ];

    if ($video_src && $poster_url) {
        $wrapper_classes[] = 'riman-cover--has-poster';
    }

    $wrapper_style = "min-height: {$min_height}px; position: relative; overflow: hidden;";
    if ($poster_url && !$video_src) {
        $wrapper_style .= " background-image: url('" . esc_url_raw($poster_url) . "'); background-size: cover; background-position: center;";
    }

    $wrapper_attribute_args = [
        'class' => implode(' ', $wrapper_classes),
        'style' => $wrapper_style,
    ];

    if ($poster_url) {
        $wrapper_attribute_args['data-riman-poster'] = esc_url($poster_url);
    }

    $wrapper_attributes = get_block_wrapper_attributes($wrapper_attribute_args);

    // HTML ausgeben
    ob_start();
    ?>
    <div <?php echo $wrapper_attributes; ?>>
        <?php if ($video_src): ?>
            <!-- Video Background -->
            <div class="riman-hero__media" style="position: absolute; inset: 0; z-index: 0;">
                <video class="riman-hero__video riman-hero-page-video riman-cover-video"
                       autoplay
                       muted
                       loop
                       playsinline
                       preload="none"
                       <?php if ($poster_url): ?>poster="<?php echo esc_url($poster_url); ?>"<?php endif; ?>
                       data-riman-cover-lazy="1"
                       <?php if ($poster_url): ?>data-riman-poster="<?php echo esc_url($poster_url); ?>"<?php endif; ?>
                       <?php if ($poster_id): ?>data-riman-poster-id="<?php echo esc_attr($poster_id); ?>"<?php endif; ?>
                       data-src="<?php echo esc_url($video_src); ?>"
                       data-src-desktop="<?php echo esc_url($video_src); ?>"
                       <?php if ($hero_mobile_src): ?>data-src-mobile="<?php echo esc_url($hero_mobile_src); ?>"<?php endif; ?>
                       data-riman-responsive-video="1"
                       style="width: 100%; height: 100%; object-fit: cover;">
                    <source data-src="<?php echo esc_url($video_src); ?>" type="<?php echo esc_attr($video_mime); ?>">
                    <?php if ($poster_url): ?>
                        <!-- Fallback Image für Browser ohne Video-Support -->
                        <img src="<?php echo esc_url($poster_url); ?>" alt="" style="width: 100%; height: 100%; object-fit: cover;">
                    <?php endif; ?>
                </video>
            </div>
        <?php elseif ($bg_image): ?>
            <!-- Image Background -->
            <div class="riman-hero__media" style="
                position: absolute;
                inset: 0;
                z-index: 0;
                background-image: url(<?php echo esc_url($bg_image); ?>);
                background-size: cover;
                background-position: center;
            "></div>
        <?php endif; ?>

        <div class="riman-hero__overlay" style="
            position: absolute;
            inset: 0;
            z-index: 1;
            background: rgba(0,0,0,<?php echo esc_attr($overlay_opacity / 100); ?>);
        "></div>

        <div class="wp-block-group" style="
            position: relative;
            z-index: 2;
            display: flex;
            align-items: center;
            justify-content: center;
            min-height: <?php echo esc_attr($min_height); ?>px;
            text-align: center;
            padding: 2rem 1rem;
        ">
            <div style="width: 100%; text-align: center;">
                <?php if ($area_label): ?>
                    <div style="text-align: center; margin-bottom: 20px;">
                        <div class="riman-area-label" style="
                            display: inline-flex;
                            align-items: center;
                            gap: 0.5rem;
                            background: rgba(182,140,47,0.25);
                            padding: 0.4rem 1rem;
                            border-radius: 1.25rem;
                            font-size: 0.9rem;
                            color: var(--wp--preset--color--base, #fff);
                            text-transform: uppercase;
                            letter-spacing: 0.05em;
                            font-weight: 500;
                            backdrop-filter: blur(10px);
                            border: 1px solid rgba(182,140,47,0.5);
                        ">
                            <?php if ($icon): ?>
                                <i class="<?php echo esc_attr($icon); ?>" style="
                                    font-size: 1rem;
                                    color: white;
                                    text-shadow: 0 1px 4px rgba(0,0,0,0.3);
                                "></i>
                            <?php endif; ?>
                            <?php echo esc_html($area_label); ?>
                        </div>
                    </div>
                <?php endif; ?>

                <h1 class="wp-block-heading" style="
                    color: white;
                    font-size: clamp(2.2rem, 4vw, 3.5rem);
                    font-weight: 700;
                    line-height: 1.1;
                    margin: 0 auto 1rem auto;
                    text-shadow: 0 2px 8px rgba(0,0,0,0.3);
                    text-align: center;
                ">
                    <?php echo esc_html($title); ?>
                </h1>

                <?php if ($subtitle): ?>
                    <p style="
                        color: white;
                        font-size: clamp(1.1rem, 2.5vw, 1.3rem);
                        font-weight: 400;
                        line-height: 1.6;
                        margin: 0 auto;
                        text-shadow: 0 1px 4px rgba(0,0,0,0.3);
                        max-width: 600px;
                        text-align: center;
                    ">
                        <?php echo esc_html($subtitle); ?>
                    </p>
                <?php endif; ?>
            </div>
        </div>
    </div>
    <?php
    return ob_get_clean();
}
?>
