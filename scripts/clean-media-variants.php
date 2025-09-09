<?php
// Cleanup duplicate media attachments for variant files (e.g., -1024x768.jpg, -scaled.jpg)
// Keeps files on disk; removes only the attachment posts and their meta.

if (!defined('ABSPATH')) {
  require_once __DIR__ . '/../wp-load.php';
}

// Do not delete files from disk when deleting attachment posts
remove_action('delete_attachment', 'wp_delete_attachment_files');

$deleted = 0;
$checked = 0;

// Patterns: size-suffixed and scaled
$pat_size   = '/-(\d+)x(\d+)\.[A-Za-z0-9]+$/';
$pat_scaled = '/-scaled\.[A-Za-z0-9]+$/';

// Helper: delete attachment ID safely (no file deletion)
function riman_delete_attachment_post_only($id) {
  if (!$id) return false;
  // Force delete (bypass trash) but with file-delete action removed above
  return (bool) wp_delete_attachment((int) $id, true);
}

// Optional: also delete a clearly wrong attachment that points to the uploads folder itself
function riman_delete_uploads_folder_attachment() {
  $uploads = wp_get_upload_dir();
  $base    = trailingslashit($uploads['basedir']);
  // Find attachments whose _wp_attached_file meta equals '' or resolves to the base dir
  global $wpdb;
  $ids = $wpdb->get_col($wpdb->prepare(
    "SELECT p.ID FROM {$wpdb->posts} p \
      INNER JOIN {$wpdb->postmeta} m ON p.ID = m.post_id \
      WHERE p.post_type='attachment' AND m.meta_key='_wp_attached_file' AND (m.meta_value='' OR m.meta_value=%s)",
    $uploads['basedir']
  ));
  $removed = 0;
  foreach ($ids as $id) {
    if (riman_delete_attachment_post_only($id)) $removed++;
  }
  if ($removed) {
    echo "Removed invalid 'uploads' attachment(s): {$removed}\n";
  }
}

// Run optional invalid attachment cleanup
try { riman_delete_uploads_folder_attachment(); } catch (\Throwable $e) {}

// Iterate all attachment IDs
$q = new WP_Query([
  'post_type'      => 'attachment',
  'post_status'    => 'any',
  'fields'         => 'ids',
  'posts_per_page' => -1,
  'no_found_rows'  => true,
  'orderby'        => 'ID',
  'order'          => 'ASC',
]);

foreach ($q->posts as $id) {
  $checked++;
  $file = (string) get_post_meta($id, '_wp_attached_file', true);
  if ($file === '') continue;
  $base = basename($file);
  if (preg_match($pat_size, $base) || preg_match($pat_scaled, $base)) {
    if (riman_delete_attachment_post_only($id)) {
      $deleted++;
    }
  }
}

echo "Checked: {$checked}; Deleted variant attachments: {$deleted}\n";

