<?php
/**
 * Repair Markdown formatting in existing riman_seiten posts
 *
 * Usage (admin only):
 *   /wp-content/plugins/riman-wireframe-prototype/repair-md-formatting.php?dry=1
 *   /wp-content/plugins/riman-wireframe-prototype/repair-md-formatting.php?fix=1&limit=100
 *   /wp-content/plugins/riman-wireframe-prototype/repair-md-formatting.php?only=123  (single post ID)
 */

require_once '../../../wp-config.php';
require_once ABSPATH.'wp-load.php';

if (php_sapi_name() !== 'cli'){
  header('Content-Type: text/plain; charset=utf-8');
}
if (!is_user_logged_in() || !current_user_can('manage_options')){
  wp_die('Nur Administratoren.');
}

// --- Small MD → Blocks converter (aligned with importers)
function _riman_md_inline_links($text){
  return preg_replace_callback('/\[(.+?)\]\(([^\)]+)\)/', function($m){
    $label = trim($m[1]);
    $href  = trim($m[2]);
    $is_abs = preg_match('~^(https?:|mailto:|tel:|#|/)~i', $href);
    $url = $is_abs ? esc_url($href) : esc_attr($href);
    return '<a href="'.$url.'">'.esc_html($label).'</a>';
  }, (string)$text);
}
function _riman_apply_emphasis_outside_tags($text){
  $parts = preg_split('/(<[^>]+>)/', (string)$text, -1, PREG_SPLIT_DELIM_CAPTURE);
  foreach ($parts as $i => $seg){
    if ($seg === '' || $seg[0] === '<') continue;
    $seg = preg_replace('/\*\*(.+?)\*\*/s', '<strong>$1</strong>', $seg);
    $seg = preg_replace('/__(.+?)__/s', '<strong>$1</strong>', $seg);
    $seg = preg_replace('/(?<!\*)\*(?!\*)(.+?)(?<!\*)\*(?!\*)/s', '<em>$1</em>', $seg);
    $seg = preg_replace('/(?<!_)_(?!_)(.+?)(?<!_)_(?!_)/s', '<em>$1</em>', $seg);
    $parts[$i] = $seg;
  }
  return implode('', $parts);
}
function _riman_md_inline($text){
  $t = (string)$text;
  $t = preg_replace_callback('/`([^`]+)`/', function($m){ return '<code>'.esc_html($m[1]).'</code>'; }, $t);
  $t = _riman_md_inline_links($t);
  $t = _riman_apply_emphasis_outside_tags($t);
  return $t;
}
function _riman_md_to_blocks($md){
  $lines = preg_split("/\r?\n/", (string)$md);
  foreach ($lines as &$__l){
    $__l = preg_replace('/[\x{00A0}\x{2007}\x{202F}]/u',' ', $__l);
    $__l = preg_replace('/[\x{2010}\x{2011}\x{2012}\x{2013}\x{2014}\x{2212}]/u','-', $__l);
  } unset($__l);
  $out=[]; $para=[]; $ul=[]; $ol=[]; $quote=[];
  $flush_para = function() use (&$para,&$out){ if ($para){ $joined=implode("\n",$para); $joined=preg_replace('/  \n/',"<br>\n",$joined); $txt=_riman_md_inline($joined); $safe=wp_kses($txt,['a'=>['href'=>true],'strong'=>[],'em'=>[],'code'=>[],'br'=>[]]); $out[]="<!-- wp:paragraph --><p>$safe</p><!-- /wp:paragraph -->"; $para=[]; } };
  $flush_ul = function() use (&$ul,&$out){ if ($ul){ $lis=array_map(function($it){ $txt=_riman_md_inline($it); $safe=wp_kses($txt,['a'=>['href'=>true],'strong'=>[],'em'=>[],'code'=>[],'br'=>[]]); return "<li>$safe</li>"; },$ul); $out[]="<!-- wp:list --><ul>".implode('', $lis)."</ul><!-- /wp:list -->"; $ul=[]; } };
  $flush_ol = function() use (&$ol,&$out){ if ($ol){ $lis=array_map(function($it){ $txt=_riman_md_inline($it); $safe=wp_kses($txt,['a'=>['href'=>true],'strong'=>[],'em'=>[],'code'=>[],'br'=>[]]); return "<li>$safe</li>"; },$ol); $out[]="<!-- wp:list {\"ordered\":true} --><ol>".implode('', $lis)."</ol><!-- /wp:list -->"; $ol=[]; } };
  $flush_quote = function() use (&$quote,&$out){ if ($quote){ $txt=_riman_md_inline(trim(implode("\n",$quote))); $safe=wp_kses($txt,['a'=>['href'=>true],'strong'=>[],'em'=>[],'code'=>[],'br'=>[]]); $out[]="<!-- wp:quote --><blockquote class=\"wp-block-quote\"><p>$safe</p></blockquote><!-- /wp:quote -->"; $quote=[]; } };
  foreach ($lines as $ln){
    if (preg_match('/^\s*#\s+(.+)$/', $ln, $m)){
      $flush_ul(); $flush_ol(); $flush_para(); $flush_quote();
      $t = wp_kses(_riman_md_inline($m[1]), ['a'=>['href'=>true],'strong'=>[],'em'=>[],'code'=>[]]);
      $out[] = "<!-- wp:heading --><h2>$t</h2><!-- /wp:heading -->";
    } elseif (preg_match('/^\s*\*\*[^*]+\*\*\s*:\s+.+$/u', $ln)){
      $ol && $flush_ol(); $quote && $flush_quote();
      $ul[] = trim($ln);
    } elseif (preg_match('/^\s*[-*]\s+(.+)$/', $ln, $m)){
      $para && $flush_para(); $ol && $flush_ol(); $quote && $flush_quote();
      $ul[] = $m[1];
    } elseif (preg_match('/^\s*\d+[\.)]\s+(.+)$/', $ln, $m)){
      $para && $flush_para(); $ul && $flush_ul(); $quote && $flush_quote();
      $ol[] = $m[1];
    } elseif (trim($ln) === ''){
      if ($quote){ $quote[] = "\n\n"; continue; }
      $flush_ul(); $flush_ol(); $flush_para();
    } else {
      $ul && $flush_ul(); $ol && $flush_ol(); $quote && $flush_quote();
      $para[] = $ln;
    }
  }
  $flush_ul(); $flush_ol(); $flush_para(); $flush_quote();
  return implode("\n", $out);
}

function looks_like_markdown($html){
  if (strpos($html, '<!-- wp:') !== false) return false; // already blocks
  $txt = strip_tags($html);
  if (preg_match('/\*\*[^*]+\*\*/u', $txt)) return true;
  if (preg_match('/\[[^\]]+\]\([^\)]+\)/u', $txt)) return true;
  if (preg_match('/^\s*[-*]\s+/m', $txt)) return true;
  if (preg_match('/^\s*#\s+/m', $txt)) return true;
  return false;
}

function fix_anchor_emphasis_html($html){
  $src = (string)$html;
  // Operiere nur innerhalb von <p>, <li>, <h1..h6> Blöcken, um Block-Kommentare/Layout nicht zu beschädigen
  $src = preg_replace_callback('/<((?:p|li|h[1-6]))\b([^>]*)>(.*?)<\/\1>/is', function($m){
    $tag = $m[1]; $attrs = $m[2]; $inner = $m[3];
    // Emphasis um <a> nur einzeilig behandeln
    $inner = preg_replace('/\*\*\s*(<a\b[^>]*>)(.*?)<\/a>\s*\*\*/i', '$1<strong>$2<\/strong></a>', $inner);
    $inner = preg_replace('/__\s*(<a\b[^>]*>)(.*?)<\/a>\s*__/i', '$1<strong>$2<\/strong></a>', $inner);
    $inner = preg_replace('/(?<!\*)\*\s*(<a\b[^>]*>)(.*?)<\/a>\s*\*(?!\*)/i', '$1<em>$2<\/em></a>', $inner);
    $inner = preg_replace('/(?<!_)_\s*(<a\b[^>]*>)(.*?)<\/a>\s*_(?!_)/i', '$1<em>$2<\/em></a>', $inner);
    // Falls <a><strong>...</a> ohne </strong> vorkommt → schließe strong vor </a>
    $inner = preg_replace('/<a\b([^>]*)><strong>((?:(?!<\/strong>).)*?)<\/a>/is', '<a$1><strong>$2<\/strong></a>', $inner);
    // Falls <a><em>...</a> ohne </em> vorkommt → schließe em vor </a>
    $inner = preg_replace('/<a\b([^>]*)><em>((?:(?!<\/em>).)*?)<\/a>/is', '<a$1><em>$2<\/em></a>', $inner);
    return '<'.$tag.$attrs.'>'.$inner.'</'.$tag.'>';
  }, $src);
  // Catch‑all: falls außerhalb dieser Tags <a><strong>…</a> vorkommt, ebenfalls schließen
  $src = preg_replace('/<a\b([^>]*)><strong>((?:(?!<\/strong>).)*?)<\/a>/is', '<a$1><strong>$2<\/strong></a>', $src);
  $src = preg_replace('/<a\b([^>]*)><em>((?:(?!<\/em>).)*?)<\/a>/is', '<a$1><em>$2<\/em></a>', $src);
  return $src;
}

$dry = !empty($_GET['dry']) && !isset($_GET['fix']);
$only = isset($_GET['only']) ? intval($_GET['only']) : 0;
$limit = isset($_GET['limit']) ? max(0, intval($_GET['limit'])) : 0;

$args = [
  'post_type' => 'riman_seiten',
  'post_status' => ['publish','draft','pending'],
  'posts_per_page' => $only ? 1 : -1,
];
if ($only){ $args['p'] = $only; }

$q = new WP_Query($args);
$done=0; $skipped=0; $fixed=0;
echo "=== Repair Markdown in riman_seiten ===\n\n";
while ($q->have_posts()){
  $q->the_post();
  $pid = get_the_ID();
  $title = get_the_title();
  $content = get_post_field('post_content', $pid);
  // Pass A: fix emphasis around anchors even inside existing blocks
  $fixed_html = fix_anchor_emphasis_html($content);
  if ($fixed_html !== $content){
    if ($dry){ echo "🔎 Would normalize anchor emphasis #$pid – $title\n"; }
    else { wp_update_post(['ID'=>$pid, 'post_content'=>$fixed_html]); echo "✅ Normalized anchor emphasis #$pid – $title\n"; }
  } else if (isset($_GET['debug'])){
    // Debug: zeige ob kaputte Muster im Original existieren
    $c1 = preg_match_all('/<a\b[^>]*><strong>((?:(?!<\/strong>).)*?)<\/a>/is', $content, $m1);
    $c2 = preg_match_all('/<a\b[^>]*><em>((?:(?!<\/em>).)*?)<\/a>/is', $content, $m2);
    echo "ℹ️ No structural change for #$pid – $title. Broken patterns: strong=$c1, em=$c2\n";
  }
  // Pass B: full MD→Blocks only if raw markdown
  if (looks_like_markdown($content)){
    $blocks = _riman_md_to_blocks($content);
    if ($dry){
      echo "🔎 Would convert MD→Blocks #$pid – $title\n";
    } else {
      wp_update_post(['ID'=>$pid, 'post_content'=>$blocks]);
      echo "✅ Converted MD→Blocks #$pid – $title\n";
    }
  } else {
    $skipped++;
  }
  $done++;
  if ($limit && $done >= $limit) break;
}
wp_reset_postdata();

echo "\nSummary: fixed $fixed, skipped $skipped\n";
echo "Done.\n";
