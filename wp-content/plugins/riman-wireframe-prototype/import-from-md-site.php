<?php
/**
 * Web-Loader: Importiert Markdown-Site in RIMAN Seiten über Browser.
 * Aufruf z. B.:
 *   /wp-content/plugins/riman-wireframe-prototype/import-from-md-site.php?root=content/riman_new_site_from_transcript
 */

// WordPress laden
require_once '../../../wp-config.php';

// Root-Parameter durchreichen
if (isset($_GET['root'])) {
    $_GET['root'] = (string) $_GET['root'];
}

// Skript aus /scripts einbinden und ausführen
// Prefer external CLI script if present (WP root /scripts), fallback to internal UI importer
$repo_root = realpath(__DIR__ . '/../../..');
$import_script = $repo_root ? ($repo_root . '/scripts/riman-import-from-md-site.php') : '';

if ($import_script && file_exists($import_script)) {
    require_once $import_script;
    exit;
}

// Fallback: use the plugin's browser importer and trigger a run if query params provided
$ui = __DIR__ . '/run-md-import.php';
if (file_exists($ui)) {
    // Map GET → POST so that the UI importer runs immediately
    if (!empty($_GET)) {
        $_POST = [
            'root' => isset($_GET['root']) ? (string)$_GET['root'] : '',
            'slug_mode' => (isset($_GET['slug']) && $_GET['slug']==='filename') ? 'filename' : 'title',
            'force_update' => !empty($_GET['force']) ? '1' : '',
            'strict_md' => !empty($_GET['strict']) ? '1' : '',
            'inline_parser' => !empty($_GET['inline']) ? '1' : '',
        ];
    }
    require_once $ui;
    exit;
}

header('Content-Type: text/plain; charset=utf-8');
echo "❌ Import-Skript nicht gefunden: $import_script\n";
echo "Tipp: Nutze den UI-Importer: /wp-content/plugins/riman-wireframe-prototype/run-md-import.php\n";
?>
