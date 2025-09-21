<?php
// Lightweight path debug (no auth) – for local development only
require_once '../../../wp-config.php';
header('Content-Type: text/plain; charset=utf-8');

$rel = isset($_GET['root']) ? (string)$_GET['root'] : 'wp-content/uploads/riman_new_site_from_transcript';
$abs = $rel;
if ($abs !== '' && $abs[0] !== '/' && !preg_match('~^[A-Za-z]:\\\\~',$abs)) { $abs = ABSPATH . ltrim($abs,'/'); }
$real = @realpath($abs);

echo "ABSPATH: ".ABSPATH."\n";
echo "Input:  $rel\n";
echo "Abs:    $abs\n";
echo "Real:   ".($real?:'(realpath failed)')."\n";
echo "Exists: ".(is_dir($abs)?'DIR':(is_file($abs)?'FILE':'NO'))."\n\n";

$target = is_dir($abs) ? $abs : dirname($abs);
if (is_dir($target)){
  $items = @scandir($target) ?: [];
  $dirs = $files = 0; $sample = [];
  foreach ($items as $e){ if ($e==='.'||$e==='..') continue; $p=$target.'/'.$e; if (is_dir($p)){$dirs++;} else {$files++;} if (count($sample)<20) $sample[] = ($p=== $abs ? '>' : ' ').basename($p).(is_dir($p)?'/':''); }
  echo "Listing of ".$target."\n";
  echo "Dirs: $dirs, Files: $files\n";
  foreach ($sample as $s) echo " - $s\n";
} else {
  echo "Target directory not found.\n";
}
echo "\nTip: pass ?root=content/markdown-struktur or other path.\n";

