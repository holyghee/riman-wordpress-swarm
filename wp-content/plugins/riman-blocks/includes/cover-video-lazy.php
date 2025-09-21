<?php
if (!defined('ABSPATH')) {
    exit;
}

function riman_blocks_cover_lazy_get_poster_meta(array $attrs = []) {
    if (!empty($attrs['rimanPosterId'])) {
        $attachment_id = (int) $attrs['rimanPosterId'];
        $url = wp_get_attachment_image_url($attachment_id, 'full');
        if ($url) {
            return [
                'id'  => $attachment_id,
                'url' => $url,
            ];
        }
    }

    if (!empty($attrs['rimanPosterUrl'])) {
        return [
            'id'  => !empty($attrs['rimanPosterId']) ? (int) $attrs['rimanPosterId'] : 0,
            'url' => $attrs['rimanPosterUrl'],
        ];
    }

    if (!empty($attrs['backgroundVideo']) && is_array($attrs['backgroundVideo'])) {
        $background_video = $attrs['backgroundVideo'];

        if (!empty($background_video['posterId'])) {
            $attachment_id = (int) $background_video['posterId'];
            $url = wp_get_attachment_image_url($attachment_id, 'full');
            if ($url) {
                return [
                    'id'  => $attachment_id,
                    'url' => $url,
                ];
            }
        }

        if (!empty($background_video['poster'])) {
            return [
                'id'  => 0,
                'url' => $background_video['poster'],
            ];
        }
    }

    return riman_blocks_cover_lazy_get_fallback_poster();
}

function riman_blocks_cover_lazy_get_fallback_poster() {
    static $poster = null;

    if ($poster !== null) {
        return $poster;
    }

    $relative_path = apply_filters('riman_blocks_cover_poster_path', '2025/09/riman-hero-long-loop-poster.png');
    if (empty($relative_path)) {
        $poster = null;
        return $poster;
    }

    $uploads = wp_get_upload_dir();
    if (empty($uploads['basedir']) || empty($uploads['baseurl'])) {
        $poster = null;
        return $poster;
    }

    $relative_path = ltrim($relative_path, '/');
    $file_path = trailingslashit($uploads['basedir']) . $relative_path;

    if (!file_exists($file_path)) {
        $poster = null;
        return $poster;
    }

    $poster = [
        'path' => $file_path,
        'url'  => trailingslashit($uploads['baseurl']) . $relative_path,
    ];

    return $poster;
}

function riman_blocks_cover_lazy_enqueue_assets() {
    $assets_dir = trailingslashit(RIMAN_BLOCKS_DIR . 'assets');
    $assets_url = trailingslashit(RIMAN_BLOCKS_URL . 'assets');

    $script_file = $assets_dir . 'cover-lazy-video.js';
    $style_file  = $assets_dir . 'cover-lazy-video.css';

    $script_version = file_exists($script_file) ? filemtime($script_file) : '1.0.0';
    $style_version  = file_exists($style_file) ? filemtime($style_file) : '1.0.0';

    wp_enqueue_script(
        'riman-cover-lazy',
        $assets_url . 'cover-lazy-video.js',
        [],
        $script_version,
        true
    );

    wp_enqueue_style(
        'riman-cover-lazy',
        $assets_url . 'cover-lazy-video.css',
        [],
        $style_version
    );
}

function riman_blocks_cover_lazy_filter($html, $block) {
    static $did_enqueue_assets = false;

    if ((is_admin() && !wp_doing_ajax()) || (defined('REST_REQUEST') && REST_REQUEST)) {
        return $html;
    }

    if (empty($block['attrs']['backgroundType']) || $block['attrs']['backgroundType'] !== 'video') {
        return $html;
    }

    if (strpos($html, 'wp-block-cover__video-background') === false) {
        return $html;
    }

    if (strpos($html, 'data-riman-cover-lazy') !== false) {
        return $html;
    }

    $poster = riman_blocks_cover_lazy_get_poster_meta($block['attrs']);
    if (!$poster) {
        return $html;
    }

    $poster_url_attr = esc_url($poster['url']);
    $poster_url_css  = esc_url_raw($poster['url']);
    $background_rule = sprintf("background-image:url('%s');background-size:cover;background-position:center;", $poster_url_css);

    if (!$did_enqueue_assets) {
        add_action('wp_enqueue_scripts', 'riman_blocks_cover_lazy_enqueue_assets');
        $did_enqueue_assets = true;
    }

    $video_pattern = '/<video\b[^>]*>/';
    if (preg_match($video_pattern, $html, $video_match)) {
        $original_video_tag = $video_match[0];
        $modified_video_tag = $original_video_tag;

    if (strpos($modified_video_tag, 'data-riman-cover-lazy') === false) {
        $modified_video_tag = str_replace('<video', '<video data-riman-cover-lazy="1"', $modified_video_tag);
    }

    if (!empty($poster['id']) && strpos($modified_video_tag, 'data-riman-poster-id') === false) {
        $modified_video_tag = str_replace('<video', '<video data-riman-poster-id="' . (int) $poster['id'] . '"', $modified_video_tag);
    }

    if (strpos($modified_video_tag, 'class="') !== false) {
        $modified_video_tag = preg_replace('/class="([^"]*)"/', 'class="$1 riman-cover-video"', $modified_video_tag, 1, $class_replaced);
        if (empty($class_replaced)) {
            $modified_video_tag = str_replace('<video', '<video class="riman-cover-video"', $modified_video_tag);
        }
        } else {
            $modified_video_tag = str_replace('<video', '<video class="riman-cover-video"', $modified_video_tag);
        }

        if (strpos($modified_video_tag, 'poster=') === false) {
            $modified_video_tag = str_replace('<video', '<video poster="' . $poster_url_attr . '"', $modified_video_tag);
        }

        if (strpos($modified_video_tag, 'preload=') !== false) {
            $modified_video_tag = preg_replace('/preload="[^"]*"/', 'preload="none"', $modified_video_tag);
        } else {
            $modified_video_tag = str_replace('<video', '<video preload="none"', $modified_video_tag);
        }

        if (strpos($modified_video_tag, ' data-src=') === false) {
            $modified_video_tag = preg_replace('/\s+src="([^"]+)"/', ' data-src="$1"', $modified_video_tag, 1);
        }

        if (strpos($modified_video_tag, 'playsinline') === false) {
            $modified_video_tag = str_replace('<video', '<video playsinline', $modified_video_tag);
        }

        $html = str_replace($original_video_tag, $modified_video_tag, $html);
    }

    $cover_pattern = '/<div\s+class="wp-block-cover([^>]*)">/';
    if (preg_match($cover_pattern, $html, $cover_match)) {
        $original_cover_tag = $cover_match[0];
        $modified_cover_tag = $original_cover_tag;

        if (strpos($modified_cover_tag, 'class="') !== false) {
            $modified_cover_tag = preg_replace('/class="([^"]*)"/', 'class="$1 riman-cover--has-poster"', $modified_cover_tag, 1, $cover_class_replaced);
            if (empty($cover_class_replaced)) {
                $modified_cover_tag = str_replace('<div', '<div class="riman-cover--has-poster"', $modified_cover_tag);
            }
        } else {
            $modified_cover_tag = str_replace('<div', '<div class="riman-cover--has-poster"', $modified_cover_tag);
        }

        if (strpos($modified_cover_tag, 'data-riman-poster') === false) {
            $modified_cover_tag = str_replace('<div', '<div data-riman-poster="' . $poster_url_attr . '"', $modified_cover_tag);
        }

        if (preg_match('/style="([^"]*)"/', $modified_cover_tag, $style_match)) {
            $existing_style = trim($style_match[1]);
            $existing_style = preg_replace('/background-image:[^;]*;?/i', '', $existing_style);
            $existing_style = rtrim($existing_style, '; ');
            $new_style = $existing_style === '' ? $background_rule : $existing_style . ';' . $background_rule;
            $modified_cover_tag = str_replace($style_match[0], 'style="' . $new_style . '"', $modified_cover_tag);
        } else {
            $style_attr = sprintf('style="%s"', $background_rule);
            $modified_cover_tag = str_replace('<div', '<div ' . $style_attr, $modified_cover_tag);
        }

        $html = str_replace($original_cover_tag, $modified_cover_tag, $html);
    }

    return $html;
}
add_filter('render_block_core/cover', 'riman_blocks_cover_lazy_filter', 10, 2);
