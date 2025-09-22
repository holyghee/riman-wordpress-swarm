<?php
/**
 * Plugin Name: RIMAN Media Library Cleanup
 * Description: Räumt WordPress Media Library auf - behält nur die 160 essentiellen Dateien
 * Version: 1.0.0
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

class RimanMediaCleanup {

    public function __construct() {
        add_action('admin_menu', [$this, 'add_admin_menu']);
        add_action('wp_ajax_riman_clean_media', [$this, 'clean_media_library']);
    }

    public function add_admin_menu() {
        add_management_page(
            'RIMAN Media Cleanup',
            'RIMAN Media Cleanup',
            'manage_options',
            'riman-media-cleanup',
            [$this, 'admin_page']
        );
    }

    public function admin_page() {
        // Deployment-Liste laden
        $deployment_list = WP_CONTENT_DIR . '/../docs/riman-local-deployment.txt';
        $keep_files = [];

        if (file_exists($deployment_list)) {
            $keep_files = file($deployment_list, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        }

        $keep_basenames = array_map('basename', $keep_files);

        // Aktuelle Media Library analysieren
        $args = [
            'post_type' => 'attachment',
            'post_status' => 'inherit',
            'posts_per_page' => -1
        ];

        $attachments = get_posts($args);
        $total_attachments = count($attachments);

        $to_keep = [];
        $to_delete = [];

        foreach ($attachments as $attachment) {
            $file_path = get_attached_file($attachment->ID);
            $basename = basename($file_path);

            if (in_array($basename, $keep_basenames)) {
                $to_keep[] = $attachment;
            } else {
                $to_delete[] = $attachment;
            }
        }

        ?>
        <div class="wrap">
            <h1>RIMAN Media Library Cleanup</h1>

            <div class="notice notice-info">
                <p><strong>📊 Analyse der Media Library:</strong></p>
                <ul>
                    <li>Gesamt Attachments: <strong><?php echo $total_attachments; ?></strong></li>
                    <li>Zu behalten: <strong><?php echo count($to_keep); ?></strong></li>
                    <li>Zu löschen: <strong><?php echo count($to_delete); ?></strong></li>
                    <li>Einsparung: <strong><?php echo round((count($to_delete) / $total_attachments) * 100, 1); ?>%</strong></li>
                </ul>
            </div>

            <?php if (count($to_delete) > 0): ?>
            <div class="notice notice-warning">
                <p><strong>⚠️ WARNUNG:</strong> <?php echo count($to_delete); ?> Media-Einträge werden aus der Datenbank gelöscht!</p>
                <p>Diese Aktion kann nicht rückgängig gemacht werden.</p>
            </div>

            <h2>Zu löschende Dateien (erste 20):</h2>
            <ul style="max-height: 200px; overflow-y: auto; border: 1px solid #ccc; padding: 10px;">
                <?php
                $shown = 0;
                foreach ($to_delete as $attachment):
                    if ($shown >= 20) break;
                    $file_path = get_attached_file($attachment->ID);
                    $basename = basename($file_path);
                ?>
                <li><?php echo esc_html($basename); ?> (ID: <?php echo $attachment->ID; ?>)</li>
                <?php
                $shown++;
                endforeach;
                ?>
                <?php if (count($to_delete) > 20): ?>
                <li><em>... und <?php echo count($to_delete) - 20; ?> weitere</em></li>
                <?php endif; ?>
            </ul>

            <p>
                <button id="riman-clean-btn" class="button button-primary button-large" style="background: #dc3232;">
                    🗑️ Media Library aufräumen (<?php echo count($to_delete); ?> Einträge löschen)
                </button>
            </p>
            <?php else: ?>
            <div class="notice notice-success">
                <p><strong>✅ Media Library ist bereits sauber!</strong></p>
                <p>Alle <?php echo count($to_keep); ?> Dateien sind in der Deployment-Liste enthalten.</p>
            </div>
            <?php endif; ?>

            <h2>Zu behaltende Dateien (erste 20):</h2>
            <ul style="max-height: 200px; overflow-y: auto; border: 1px solid #ccc; padding: 10px;">
                <?php
                $shown = 0;
                foreach ($to_keep as $attachment):
                    if ($shown >= 20) break;
                    $file_path = get_attached_file($attachment->ID);
                    $basename = basename($file_path);
                ?>
                <li>✅ <?php echo esc_html($basename); ?> (ID: <?php echo $attachment->ID; ?>)</li>
                <?php
                $shown++;
                endforeach;
                ?>
                <?php if (count($to_keep) > 20): ?>
                <li><em>... und <?php echo count($to_keep) - 20; ?> weitere</em></li>
                <?php endif; ?>
            </ul>
        </div>

        <script>
        document.getElementById('riman-clean-btn')?.addEventListener('click', function() {
            if (!confirm('WARNUNG: <?php echo count($to_delete); ?> Media-Einträge werden permanent gelöscht!\n\nDiese Aktion kann nicht rückgängig gemacht werden.\n\nFortfahren?')) {
                return;
            }

            this.disabled = true;
            this.textContent = '🔄 Räume auf...';

            fetch(ajaxurl, {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: new URLSearchParams({
                    action: 'riman_clean_media',
                    nonce: '<?php echo wp_create_nonce('riman_clean_media'); ?>'
                })
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    alert('✅ Media Library erfolgreich aufgeräumt!\n\nGelöscht: ' + data.data.deleted + ' Einträge\nBehalten: ' + data.data.kept + ' Einträge');
                    location.reload();
                } else {
                    alert('❌ Fehler: ' + data.data);
                    this.disabled = false;
                    this.textContent = '🗑️ Media Library aufräumen';
                }
            })
            .catch(error => {
                alert('❌ Fehler: ' + error.message);
                this.disabled = false;
                this.textContent = '🗑️ Media Library aufräumen';
            });
        });
        </script>
        <?php
    }

    public function clean_media_library() {
        // Sicherheit
        if (!current_user_can('manage_options') || !wp_verify_nonce($_POST['nonce'], 'riman_clean_media')) {
            wp_die('Unauthorized');
        }

        // Deployment-Liste laden
        $deployment_list = WP_CONTENT_DIR . '/../docs/riman-local-deployment.txt';

        if (!file_exists($deployment_list)) {
            wp_send_json_error('Deployment-Liste nicht gefunden');
            return;
        }

        $keep_files = file($deployment_list, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        $keep_basenames = array_map('basename', $keep_files);

        // Alle Attachments holen
        $args = [
            'post_type' => 'attachment',
            'post_status' => 'inherit',
            'posts_per_page' => -1
        ];

        $attachments = get_posts($args);
        $deleted_count = 0;
        $kept_count = 0;

        foreach ($attachments as $attachment) {
            $file_path = get_attached_file($attachment->ID);
            $basename = basename($file_path);

            if (in_array($basename, $keep_basenames)) {
                $kept_count++;
            } else {
                // Lösche Attachment (inklusive Datei)
                if (wp_delete_attachment($attachment->ID, true)) {
                    $deleted_count++;
                }
            }
        }

        wp_send_json_success([
            'deleted' => $deleted_count,
            'kept' => $kept_count,
            'message' => "Media Library aufgeräumt: $deleted_count gelöscht, $kept_count behalten"
        ]);
    }
}

// Plugin initialisieren
new RimanMediaCleanup();
?>