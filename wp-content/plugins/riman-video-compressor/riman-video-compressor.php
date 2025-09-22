<?php
/**
 * Plugin Name: RIMAN Video Compressor
 * Plugin URI: https://riman-wordpress-swarm.ecomwy.com
 * Description: Automatische Video-Komprimierung für responsive Ausgabe basierend auf php.ecomwy.com FFmpeg-System
 * Version: 1.0.0
 * Author: RIMAN
 * Network: false
 *
 * Komprimiert hochgeladene Videos automatisch in Mobile- und Desktop-Varianten
 * Basiert auf dem bewährten FFmpeg-System von php.ecomwy.com
 */

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

class RimanVideoCompressor {

    // FFmpeg-Pfad im WordPress Plugin (kopiert von php.ecomwy.com)
    private $ffmpeg_path;

    // Video-Formate die komprimiert werden
    private $video_formats = ['mp4', 'mov', 'avi', 'webm', 'mkv'];

    public function __construct() {
        // FFmpeg-Pfad festlegen (Auto-Detection)
        $this->ffmpeg_path = $this->detect_ffmpeg_path();

        // Hook in WordPress Upload-Prozess
        add_filter('wp_handle_upload', [$this, 'process_video_upload'], 10, 2);

        // Admin-Interface
        add_action('admin_menu', [$this, 'add_admin_menu']);
        add_action('admin_enqueue_scripts', [$this, 'enqueue_admin_scripts']);

        // AJAX-Handler für manuelle Komprimierung
        add_action('wp_ajax_compress_video_manual', [$this, 'compress_video_manual']);

        // Background Processing
        add_action('riman_compress_video_background', [$this, 'compress_video_background'], 10, 2);

        // Plugin Activation
        register_activation_hook(__FILE__, [$this, 'plugin_activate']);

        // Admin-Notices
        add_action('admin_notices', [$this, 'admin_notices']);
    }

    /**
     * Plugin-Aktivierung: Prüfe System-Voraussetzungen
     */
    public function plugin_activate() {
        // Prüfe ob FFmpeg verfügbar ist
        if (!$this->is_ffmpeg_available()) {
            add_option('riman_video_compressor_error', 'FFmpeg nicht verfügbar auf diesem Server');
        }

        // Erstelle Ordner für komprimierte Videos
        $this->create_compression_directories();
    }

    /**
     * Erkenne FFmpeg-Pfad automatisch (Lokal vs Live)
     */
    private function detect_ffmpeg_path() {
        // 1. System FFmpeg (macOS Homebrew, Linux Package)
        $system_paths = ['/opt/homebrew/bin/ffmpeg', '/usr/bin/ffmpeg', '/usr/local/bin/ffmpeg'];
        foreach ($system_paths as $path) {
            if (file_exists($path)) {
                return $path;
            }
        }

        // 2. Plugin-eigenes Binary (Live-Server)
        $plugin_binary = plugin_dir_path(__FILE__) . 'bin/ffmpeg';
        if (file_exists($plugin_binary)) {
            return $plugin_binary;
        }

        // 3. which-Kommando versuchen
        $which_result = shell_exec('which ffmpeg 2>/dev/null');
        if (!empty($which_result)) {
            return trim($which_result);
        }

        return false;
    }

    /**
     * Prüfe ob FFmpeg verfügbar ist
     */
    private function is_ffmpeg_available() {
        if (!$this->ffmpeg_path || !file_exists($this->ffmpeg_path)) {
            return false;
        }

        // Teste FFmpeg-Ausführung
        $test_command = escapeshellcmd($this->ffmpeg_path) . ' -version 2>&1';
        $output = shell_exec($test_command);

        return strpos($output, 'ffmpeg version') !== false;
    }

    /**
     * Erstelle Ordner-Struktur für komprimierte Videos
     */
    private function create_compression_directories() {
        $upload_dir = wp_get_upload_dir();
        $base_path = $upload_dir['basedir'];

        $directories = [
            $base_path . '/2025/09/mobile',
            $base_path . '/2025/09/thumbnails'
        ];

        foreach ($directories as $dir) {
            if (!file_exists($dir)) {
                wp_mkdir_p($dir);
            }
        }
    }

    /**
     * WordPress Upload-Hook: Automatische Video-Komprimierung
     */
    public function process_video_upload($upload, $context) {
        // Nur Videos verarbeiten
        if (!$this->is_video_file($upload['file'])) {
            return $upload;
        }

        $file_path = $upload['file'];
        $file_info = pathinfo($file_path);

        // Prüfe Dateigröße (nur große Videos komprimieren)
        $file_size = filesize($file_path);
        if ($file_size < 1048576) { // < 1MB
            return $upload;
        }

        // Starte Background-Komprimierung
        wp_schedule_single_event(time() + 10, 'riman_compress_video_background', [$file_path, $upload]);

        return $upload;
    }

    /**
     * Background-Prozess: Video-Komprimierung
     */
    public function compress_video_background($file_path, $upload_info) {
        if (!file_exists($file_path)) {
            error_log("RIMAN Video Compressor: Datei nicht gefunden: $file_path");
            return;
        }

        $this->compress_video_variants($file_path);
    }

    /**
     * Komprimiere Video in verschiedene Varianten
     * Basiert auf php.ecomwy.com Erfahrung
     */
    private function compress_video_variants($source_path) {
        $file_info = pathinfo($source_path);
        $directory = dirname($source_path);
        $filename = $file_info['filename'];

        // Hero Mobile-Version (9:16 Portrait, 260x464)
        $hero_mobile_path = $directory . '/mobile/' . $filename . '-hero-mobile.mp4';
        $this->compress_for_hero_mobile($source_path, $hero_mobile_path);

        // Service Cards Mobile-Version (Ultra-Breitbild, 400x150)
        $cards_mobile_path = $directory . '/mobile/' . $filename . '-cards-mobile.mp4';
        $this->compress_for_cards_mobile($source_path, $cards_mobile_path);

        // Desktop-optimierte Version (falls Original > 2MB)
        $file_size = filesize($source_path);
        if ($file_size > 2097152) { // > 2MB
            $desktop_path = $directory . '/' . $filename . '-optimized.mp4';
            $this->compress_for_desktop($source_path, $desktop_path);
        }

        // Thumbnail erstellen
        $thumbnail_path = $directory . '/thumbnails/' . $filename . '-thumb.jpg';
        $this->create_video_thumbnail($source_path, $thumbnail_path);

        // WordPress Media Library aktualisieren
        $this->update_wordpress_media_variants($source_path, [
            'hero_mobile' => $hero_mobile_path,
            'cards_mobile' => $cards_mobile_path,
            'desktop' => isset($desktop_path) ? $desktop_path : null,
            'thumbnail' => $thumbnail_path
        ]);
    }

    /**
     * Hero Mobile Video-Komprimierung (9:16 Portrait)
     * Ziel: ~150-400KB für Hero-Sections
     */
    private function compress_for_hero_mobile($source, $output) {
        // Hero 9:16 Format: 260x464 (gerade Zahlen für H.264)
        // Crop zuerst auf 16:9 → 464x464 Quadrat, dann scale auf 260x464
        $command = sprintf(
            '%s -i %s -vf "crop=464:464:(iw-464)/2:0,scale=260:464" -c:v libx264 -preset fast -crf 25 -maxrate 300k -bufsize 150k -c:a aac -b:a 96k -ac 1 -movflags +faststart -pix_fmt yuv420p -t 5 -y %s 2>&1',
            escapeshellcmd($this->ffmpeg_path),
            escapeshellarg($source),
            escapeshellarg($output)
        );

        $result = shell_exec($command);
        $this->log_compression_result($source, $output, $result, 'Hero Mobile');
    }

    /**
     * Service Cards Mobile Video-Komprimierung (16:9 beibehalten)
     * Ziel: <150KB für Service Cards, kompakt aber korrektes Aspect Ratio
     */
    private function compress_for_cards_mobile($source, $output) {
        // Service Cards Mobile: 400x223 (exakte Original-Proportionen 832x464 → 400x223)
        // Einfache Verkleinerung bei Beibehaltung des Original-Seitenverhältnisses
        $command = sprintf(
            '%s -i %s -vf "scale=400:224" -c:v libx264 -preset fast -crf 28 -maxrate 200k -bufsize 100k -c:a aac -b:a 64k -ac 1 -movflags +faststart -pix_fmt yuv420p -t 5 -y %s 2>&1',
            escapeshellcmd($this->ffmpeg_path),
            escapeshellarg($source),
            escapeshellarg($output)
        );

        $result = shell_exec($command);
        $this->log_compression_result($source, $output, $result, 'Service Cards Mobile');
    }

    /**
     * Komprimierungs-Ergebnis loggen
     */
    private function log_compression_result($source, $output, $result, $type) {
        if (file_exists($output)) {
            $original_size = filesize($source);
            $compressed_size = filesize($output);
            $ratio = round(($compressed_size / $original_size) * 100, 1);

            error_log("RIMAN Video Compressor: $type Video erstellt - $ratio% der Originalgröße (" . round($compressed_size/1024) . "KB)");
        } else {
            error_log("RIMAN Video Compressor: $type Komprimierung fehlgeschlagen - $result");
        }
    }

    /**
     * Desktop Video-Optimierung
     */
    private function compress_for_desktop($source, $output) {
        $command = sprintf(
            '%s -i %s -vf "scale=832:464,fps=30" -c:v libx264 -preset medium -crf 25 -maxrate 800k -bufsize 400k -c:a aac -b:a 128k -movflags +faststart -pix_fmt yuv420p -t 6 -y %s 2>&1',
            escapeshellcmd($this->ffmpeg_path),
            escapeshellarg($source),
            escapeshellarg($output)
        );

        shell_exec($command);
    }

    /**
     * Video-Thumbnail erstellen
     * Basierend auf extract-thumbnail.php von php.ecomwy.com
     */
    private function create_video_thumbnail($source, $output) {
        $command = sprintf(
            '%s -y -i %s -vf "select=eq(n\\,0)" -frames:v 1 %s 2>&1',
            escapeshellcmd($this->ffmpeg_path),
            escapeshellarg($source),
            escapeshellarg($output)
        );

        shell_exec($command);
    }

    /**
     * WordPress Media Library mit Video-Varianten aktualisieren
     */
    private function update_wordpress_media_variants($source_path, $variants) {
        // Finde WordPress Attachment anhand Dateipfad
        $upload_dir = wp_get_upload_dir();
        $relative_path = str_replace($upload_dir['basedir'] . '/', '', $source_path);

        global $wpdb;
        $attachment_id = $wpdb->get_var($wpdb->prepare(
            "SELECT post_id FROM {$wpdb->postmeta} WHERE meta_key = '_wp_attached_file' AND meta_value = %s",
            $relative_path
        ));

        if (!$attachment_id) {
            return;
        }

        // Speichere Varianten als Custom Meta
        foreach ($variants as $type => $path) {
            if ($path && file_exists($path)) {
                $relative_variant_path = str_replace($upload_dir['basedir'] . '/', '', $path);
                update_post_meta($attachment_id, "_riman_video_variant_{$type}", $relative_variant_path);
            }
        }
    }

    /**
     * Prüfe ob Datei ein Video ist
     */
    private function is_video_file($file_path) {
        $file_extension = strtolower(pathinfo($file_path, PATHINFO_EXTENSION));
        return in_array($file_extension, $this->video_formats);
    }

    /**
     * Admin-Menü hinzufügen
     */
    public function add_admin_menu() {
        add_submenu_page(
            'upload.php',
            'Video Compressor',
            'Video Compressor',
            'manage_options',
            'riman-video-compressor',
            [$this, 'admin_page']
        );
    }

    /**
     * Admin-Seite rendern
     */
    public function admin_page() {
        ?>
        <div class="wrap">
            <h1>RIMAN Video Compressor</h1>

            <div class="notice notice-info">
                <p><strong>Status:</strong>
                <?php if ($this->is_ffmpeg_available()): ?>
                    ✅ FFmpeg verfügbar - Automatische Komprimierung aktiv
                <?php else: ?>
                    ❌ FFmpeg nicht verfügbar - Komprimierung deaktiviert
                <?php endif; ?>
                </p>
                <p><strong>FFmpeg-Pfad:</strong> <?php echo esc_html($this->ffmpeg_path ?: 'Nicht gefunden'); ?></p>
                <p><strong>Debug-Info:</strong></p>
                <ul style="margin-left: 20px;">
                    <li>System: <?php echo php_uname('s') . ' ' . php_uname('r'); ?></li>
                    <li>PHP User: <?php echo get_current_user(); ?></li>
                    <li>Working Dir: <?php echo getcwd(); ?></li>
                    <li>Exec verfügbar: <?php echo function_exists('shell_exec') ? '✅' : '❌'; ?></li>
                    <?php
                    $test_paths = [
                        '/opt/homebrew/bin/ffmpeg',
                        '/usr/bin/ffmpeg',
                        '/usr/local/bin/ffmpeg',
                        plugin_dir_path(__FILE__) . 'bin/ffmpeg'
                    ];
                    foreach ($test_paths as $path) {
                        echo '<li>' . esc_html($path) . ': ' . (file_exists($path) ? '✅' : '❌') . '</li>';
                    }
                    ?>
                </ul>
            </div>

            <h2>Video-Komprimierungs-Einstellungen</h2>

            <table class="form-table">
                <tr>
                    <th>Original-Videos</th>
                    <td><strong>832x464 Pixel</strong> (16:9 Format)</td>
                </tr>
                <tr>
                    <th>Hero Mobile (9:16)</th>
                    <td><strong>260x464 Pixel</strong> (Center-Crop + Scale) @ 300kbps<br>
                        <small>Portrait-Format für Hero-Sections, ~150-400KB</small></td>
                </tr>
                <tr>
                    <th>Service Cards Mobile</th>
                    <td><strong>400x224 Pixel</strong> (praktisch identische Proportionen) @ 200kbps<br>
                        <small>Einfache Verkleinerung mit Original-Seitenverhältnis, ~100-110KB</small></td>
                </tr>
                <tr>
                    <th>Desktop-Optimierung</th>
                    <td><strong>832x464</strong> (Original-Größe) @ 800kbps Target<br>
                        <small>Nur bei Videos > 2MB zur Dateigröße-Optimierung</small></td>
                </tr>
                <tr>
                    <th>Auto-Komprimierung</th>
                    <td>Videos > 1MB werden automatisch komprimiert</td>
                </tr>
            </table>

            <h2>Bestehende Videos komprimieren</h2>
            <p>
                <button type="button" class="button button-primary" id="compress-existing-videos">
                    Alle Videos komprimieren
                </button>
            </p>

            <div id="compression-progress" style="display:none;">
                <div class="progress-bar">
                    <div class="progress-fill" style="width: 0%;"></div>
                </div>
                <p id="progress-text">Starte Komprimierung...</p>
            </div>
        </div>
        <?php
    }

    /**
     * Admin-Scripts einbinden
     */
    public function enqueue_admin_scripts($hook) {
        if ($hook !== 'media_page_riman-video-compressor') {
            return;
        }

        wp_enqueue_script(
            'riman-video-compressor-admin',
            plugin_dir_url(__FILE__) . 'admin.js',
            ['jquery'],
            '1.0.0',
            true
        );

        wp_localize_script('riman-video-compressor-admin', 'rimanVideoCompressor', [
            'ajax_url' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('riman_video_compressor')
        ]);
    }

    /**
     * AJAX: Manuelle Komprimierung aller Videos
     */
    public function compress_video_manual() {
        check_ajax_referer('riman_video_compressor', 'nonce');

        if (!current_user_can('manage_options')) {
            wp_die('Unauthorized');
        }

        // Finde alle Video-Attachments
        $video_attachments = get_posts([
            'post_type' => 'attachment',
            'post_mime_type' => 'video',
            'posts_per_page' => -1,
            'post_status' => 'inherit'
        ]);

        $processed = 0;
        foreach ($video_attachments as $attachment) {
            $file_path = get_attached_file($attachment->ID);
            if ($file_path && file_exists($file_path)) {
                $this->compress_video_variants($file_path);
                $processed++;
            }
        }

        wp_send_json_success([
            'message' => "Komprimierung abgeschlossen: $processed Videos verarbeitet",
            'processed' => $processed
        ]);
    }

    /**
     * Admin-Notices
     */
    public function admin_notices() {
        $error = get_option('riman_video_compressor_error');
        if ($error) {
            echo '<div class="notice notice-error"><p>RIMAN Video Compressor: ' . esc_html($error) . '</p></div>';
            delete_option('riman_video_compressor_error');
        }
    }
}

// Plugin initialisieren
new RimanVideoCompressor();
?>