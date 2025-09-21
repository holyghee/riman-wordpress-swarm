<?php
/**
 * Run MD Import (Browser UI)
 * URL: /wp-content/plugins/riman-wireframe-prototype/run-md-import.php
 */

require_once '../../../wp-config.php';

if (!is_user_logged_in() || !current_user_can('manage_options')) {
    wp_die('Nur Administratoren dürfen diesen Import ausführen.');
}

// ---------- Inline-Importer (ohne Abhängigkeit zu /scripts)
function riman_norm_title($s){
    $s = preg_replace('~^\d+[_-]\s*~','', (string)$s);
    $s = str_replace('_',' ',$s);
    return trim(preg_replace('~\s+~',' ',$s));
}
function riman_read_h1($md){ if (preg_match('/^#\s+(.+)$/m',$md,$m)) return trim($m[1]); return ''; }
function riman_body_no_h1($md){ return trim(preg_replace('/^#\s+.+$/m','',$md,1)); }
function riman_md_to_html_strict($md){
    $lib = __DIR__ . '/lib/Parsedown.php';
    if (file_exists($lib)) { require_once $lib; $pd = new Parsedown(); $pd->setBreaksEnabled(true); return $pd->text($md); }
    return '';
}
function riman_find_cover($dir){ foreach(['cover.jpg','cover.jpeg','cover.png','cover.webp'] as $bn){ $p=rtrim($dir,'/')."/$bn"; if (file_exists($p)) return $p; } return ''; }
function riman_menu_order($name){ return preg_match('~^(\d+)~',$name,$m)?intval($m[1]):9999; }
function riman_slug_from_name($name){
    $base = preg_replace('~\.(md|markdown)$~i','', (string)$name);
    $base = preg_replace('~^\d+[_-]?~','', $base);
    return sanitize_title($base);
}
function riman_slug_from_pathkey($path_key){
    $bn = basename($path_key);
    if (strtolower($bn) === 'index.md'){
        // use folder name
        $bn = basename(trim(dirname($path_key), '/')) ?: 'index';
    }
    return riman_slug_from_name($bn);
}
function riman_set_thumb($post_id,$path){ if(!file_exists($path)||has_post_thumbnail($post_id))return false; require_once ABSPATH.'wp-admin/includes/file.php'; require_once ABSPATH.'wp-admin/includes/media.php'; require_once ABSPATH.'wp-admin/includes/image.php'; $handled=wp_handle_sideload(['name'=>basename($path),'tmp_name'=>$path],['test_form'=>false]); if(!empty($handled['error']))return false; $att=['post_mime_type'=>$handled['type'],'post_title'=>sanitize_file_name(pathinfo($handled['file'],PATHINFO_FILENAME)),'post_content'=>'','post_status'=>'inherit']; $id=wp_insert_attachment($att,$handled['file'],$post_id); if(is_wp_error($id)||!$id)return false; $meta=wp_generate_attachment_metadata($id,$handled['file']); wp_update_attachment_metadata($id,$meta); set_post_thumbnail($post_id,$id); return true; }

// Lade Hero-Metadaten aus allen verfügbaren JSON-Dateien
function riman_load_hero_metadata() {
    static $hero_data = null;
    if ($hero_data === null) {
        $hero_data = [];

        // 1. Lade bestehende erweiterte Hero-Metadaten
        $extended_file = ABSPATH . 'wp-content/uploads/riman-hero-metadata-extended.json';
        if (file_exists($extended_file)) {
            $json = file_get_contents($extended_file);
            $data = json_decode($json, true);
            if ($data && isset($data['site_pages'])) {
                $hero_data = array_merge($hero_data, $data['site_pages']);
            }
        }

        // 2. Lade zusätzliche fehlende Hero-Metadaten
        $missing_file = ABSPATH . 'wp-content/uploads/riman-missing-hero-metadata.json';
        if (file_exists($missing_file)) {
            $json = file_get_contents($missing_file);
            $data = json_decode($json, true);
            if ($data && isset($data['pages'])) {
                // Konvertiere missing format zu extended format
                $all_missing_pages = [];

                if (isset($data['pages']['unterseiten'])) {
                    $all_missing_pages = array_merge($all_missing_pages, $data['pages']['unterseiten']);
                }
                if (isset($data['pages']['detailseiten'])) {
                    $all_missing_pages = array_merge($all_missing_pages, $data['pages']['detailseiten']);
                }
                if (isset($data['pages']['info_seiten'])) {
                    $all_missing_pages = array_merge($all_missing_pages, $data['pages']['info_seiten']);
                }

                // Konvertiere zu path-key format für MD-import
                foreach ($all_missing_pages as $page) {
                    $slug = $page['slug'] ?? '';
                    if ($slug) {
                        // Erzeuge verschiedene mögliche path-keys
                        $possible_paths = [
                            $slug . '/index.md',
                            '01_Sicherheits-Koordination_und_Mediation/' . $slug . '/index.md',
                            '02_Sicherheits-Management_im_Baubereich/' . $slug . '/index.md',
                            '01_Sicherheits-Koordination_und_Mediation/mediation_im_privat-bereich/' . $slug . '/index.md',
                            '01_Sicherheits-Koordination_und_Mediation/mediation_in_unternehmen/' . $slug . '/index.md',
                            '01_Sicherheits-Koordination_und_Mediation/baubereich/' . $slug . '/index.md',
                            '02_Sicherheits-Management_im_Baubereich/abbruch-management/' . $slug . '/index.md',
                            '02_Sicherheits-Management_im_Baubereich/schadstoff-management/' . $slug . '/index.md',
                            '02_Sicherheits-Management_im_Baubereich/entsorgungs-management/' . $slug . '/index.md',
                            '02_Sicherheits-Management_im_Baubereich/altlasten-management/' . $slug . '/index.md'
                        ];

                        // Füge alle möglichen Pfade hinzu
                        foreach ($possible_paths as $path) {
                            if (!isset($hero_data[$path])) {
                                $hero_data[$path] = $page;
                            }
                        }
                    }
                }
            }
        }

        // 3. Fallback: Generiere Hero-Metadaten aus bestehender SEO JSON
        if (empty($hero_data)) {
            $hero_data = riman_generate_hero_from_seo_json();
        }
    }
    return $hero_data;
}

// Generiere Hero-Metadaten aus bestehender SEO JSON-Datei
function riman_generate_hero_from_seo_json() {
    $seo_file = ABSPATH . 'wp-content/plugins/riman-wireframe-prototype/docs/wordpress-enhanced-image-mappings-seo.llm.json';
    if (!file_exists($seo_file)) {
        return [];
    }

    $json = file_get_contents($seo_file);
    $seo_data = json_decode($json, true);
    if (!$seo_data || !isset($seo_data['pageMappings'])) {
        return [];
    }

    $hero_data = [];
    $page_mappings = $seo_data['pageMappings'];

    // Icon-Mapping basierend auf Themen und Seitentitel
    $icon_mapping = [
        'mediation' => 'fas fa-handshake',
        'family' => 'fas fa-home',
        'business' => 'fas fa-briefcase',
        'construction' => 'fas fa-hard-hat',
        'safety' => 'fas fa-shield-alt',
        'management' => 'fas fa-cogs',
        'legal' => 'fas fa-gavel',
        'environmental' => 'fas fa-leaf',
        'laboratory' => 'fas fa-microscope',
        'waste' => 'fas fa-recycle',
        'contaminated' => 'fas fa-exclamation-triangle',
        'planning' => 'fas fa-drafting-compass',
        'execution' => 'fas fa-tools'
    ];

    // Area-Label-Mapping
    $area_mapping = [
        'sicherheits-koordination-mediation' => 'Mediation & Sicherheit',
        'sicherheits-management-baubereich' => 'Bau-Sicherheit',
        'mediation-privat-bereich' => 'Private Mediation',
        'mediation-unternehmen' => 'Unternehmens-Mediation',
        'sicherheits-koordination-baubereich' => 'Baubereich-Koordination',
        'abbruch-management' => 'Rückbau & Abbruch',
        'schadstoff-management' => 'Schadstoff-Sanierung',
        'entsorgungs-management' => 'Entsorgungs-Management',
        'altlasten-management' => 'Altlasten-Sanierung'
    ];

    // Durchlaufe alle Kategorien
    foreach ($page_mappings as $category => $pages) {
        foreach ($pages as $page_key => $page_data) {
            if (!isset($page_data['pageTitle']) || !isset($page_data['assignedImage'])) {
                continue;
            }

            // Baue den MD-Pfad (vereinfacht)
            $md_path = '';
            if ($category === 'hauptseiten') {
                $md_path = sprintf('%02d_%s/index.md',
                    $page_data['priority'] ?? 1,
                    str_replace('-', '_', ucwords($page_key, '-'))
                );
            } elseif ($category === 'unterseiten') {
                $parent_id = $page_data['priority'] ?? 2;
                $md_path = sprintf('%02d_%s/%s/index.md',
                    $parent_id - 1,
                    str_replace('-', '_', ucwords($page_data['parentPage'] ?? $page_key, '-')),
                    str_replace('-', '_', $page_key)
                );
            }

            if (!$md_path) continue;

            // Bestimme Icon basierend auf Theme und Keywords
            $icon = 'fas fa-info-circle'; // Default
            $theme = strtolower($page_data['assignedImage']['theme'] ?? '');
            $keywords = $page_data['assignedImage']['seoKeywords'] ?? [];

            foreach ($icon_mapping as $keyword => $fa_icon) {
                if (strpos($theme, $keyword) !== false ||
                    strpos(strtolower($page_data['pageTitle']), $keyword) !== false ||
                    in_array($keyword, array_map('strtolower', $keywords))) {
                    $icon = $fa_icon;
                    break;
                }
            }

            // Bestimme Area Label
            $area_label = $area_mapping[$page_key] ?? ucwords(str_replace('-', ' ', $page_key));

            // Titel ohne "- Riman GmbH"
            $title = str_replace(' - Riman GmbH', '', $page_data['pageTitle']);

            $hero_data[$md_path] = [
                'hero' => [
                    'title' => $title,
                    'subtitle' => $page_data['assignedImage']['description'] ?? '',
                    'area_label' => $area_label,
                    'icon' => $icon
                ]
            ];
        }
    }

    return $hero_data;
}

// Setze Hero-Metadaten für eine Seite
function riman_set_hero_metadata($post_id, $md_path) {
    $hero_data = riman_load_hero_metadata();
    $hero = null;

    // Prüfe direkt den MD-Pfad
    if (isset($hero_data[$md_path]) && isset($hero_data[$md_path]['hero'])) {
        $hero = $hero_data[$md_path]['hero'];
    } else {
        // Fallback: Prüfe verschiedene path-key Varianten
        $path_variants = [
            $md_path,
            basename(dirname($md_path)) . '/index.md',
            basename($md_path, '.md') . '/index.md'
        ];

        foreach ($path_variants as $variant) {
            if (isset($hero_data[$variant]) && isset($hero_data[$variant]['hero'])) {
                $hero = $hero_data[$variant]['hero'];
                break;
            }
        }

        // Erweiterte Suche basierend auf Slug
        if (!$hero) {
            $slug = riman_slug_from_pathkey($md_path);
            foreach ($hero_data as $path => $data) {
                if (isset($data['hero']) &&
                    (strpos($path, $slug) !== false ||
                     (isset($data['slug']) && $data['slug'] === $slug))) {
                    $hero = $data['hero'];
                    break;
                }
            }
        }
    }

    if ($hero) {
        // Hero-Metadaten setzen
        $updated = 0;
        if (!empty($hero['title'])) {
            update_post_meta($post_id, '_riman_hero_title', sanitize_text_field($hero['title']));
            $updated++;
        }
        if (!empty($hero['subtitle'])) {
            update_post_meta($post_id, '_riman_hero_subtitle', sanitize_textarea_field($hero['subtitle']));
            $updated++;
        }
        if (!empty($hero['area_label'])) {
            update_post_meta($post_id, '_riman_hero_area_label', sanitize_text_field($hero['area_label']));
            $updated++;
        }
        if (!empty($hero['icon'])) {
            update_post_meta($post_id, '_riman_hero_icon', sanitize_text_field($hero['icon']));
            $updated++;
        }

        error_log("Hero metadata set for post $post_id ($md_path): $updated fields updated");
        return $updated > 0;
    }

    error_log("No hero metadata found for post $post_id ($md_path)");
    return false;
}

// Setze Featured Image basierend auf Hero-Metadaten oder Bildmapping
function riman_set_featured_image($post_id, $md_path) {
    // Skip wenn bereits Featured Image vorhanden
    if (has_post_thumbnail($post_id)) {
        return false;
    }

    $hero_data = riman_load_hero_metadata();
    $image_path = null;
    $image_filename = null;

    // 1. Versuche Hero-Metadaten-Bild zu finden
    if (isset($hero_data[$md_path]) && isset($hero_data[$md_path]['image'])) {
        $image_filename = $hero_data[$md_path]['image'];
    }

    // 2. Fallback: Generiere Bildnamen basierend auf Slug
    if (!$image_filename) {
        $slug = riman_slug_from_pathkey($md_path);
        $possible_filenames = [
            "generated_hero_image_{$slug}.png",
            "generated_hero_image_{$slug}.jpg",
            "hero_{$slug}.png",
            "hero_{$slug}.jpg",
            "{$slug}_hero.png",
            "{$slug}_hero.jpg"
        ];

        foreach ($possible_filenames as $filename) {
            $test_path = ABSPATH . 'images/' . $filename;
            if (file_exists($test_path)) {
                $image_filename = $filename;
                $image_path = $test_path;
                break;
            }
        }
    }

    // 3. Suche Bildpfad in images Ordner
    if ($image_filename && !$image_path) {
        $possible_paths = [
            ABSPATH . 'images/' . $image_filename,
            ABSPATH . 'wp-content/uploads/' . $image_filename,
            ABSPATH . 'content/images/' . $image_filename,
            ABSPATH . 'wp-content/uploads/images/' . $image_filename
        ];

        foreach ($possible_paths as $test_path) {
            if (file_exists($test_path)) {
                $image_path = $test_path;
                break;
            }
        }
    }

    // 4. Suche cover.* Dateien im MD-Ordner
    if (!$image_path) {
        $md_dir = dirname($md_path);
        $content_dir = ABSPATH . 'wp-content/uploads/riman_new_site_from_transcript/' . $md_dir;
        if (is_dir($content_dir)) {
            $cover_path = riman_find_cover($content_dir);
            if ($cover_path) {
                $image_path = $cover_path;
            }
        }
    }

    // 5. Setze Featured Image
    if ($image_path && file_exists($image_path)) {
        $success = riman_set_thumb($post_id, $image_path);
        if ($success) {
            error_log("Featured image set for post $post_id: $image_path");
            return true;
        } else {
            error_log("Failed to set featured image for post $post_id: $image_path");
        }
    } else {
        error_log("No featured image found for post $post_id ($md_path)");
    }

    return false;
}
// riman_ensure_post – finale Version wird weiter unten definiert (nach MD→Blocks)
// --- Markdown → Gutenberg Blocks (einfacher Konverter)
function riman_md_inline_links($text){
    // Ersetze [Label](URL) → <a href="URL">Label</a> (Label bleibt fürs Emphasis-Parsing übrig)
    return preg_replace_callback('/\[(.+?)\]\(([^\)]+)\)/', function($m){
        $label = trim($m[1]);
        $href  = trim($m[2]);
        // Absolute/Anchors normal escapen, relative nur als Attribut (damit Resolver später greifen kann)
        $is_abs = preg_match('~^(https?:|mailto:|tel:|#|/)~i', $href);
        $url = $is_abs ? esc_url($href) : esc_attr($href);
        return '<a href="'.$url.'">'.esc_html($label).'</a>';
    }, $text);
}

function riman_apply_emphasis_outside_tags($text){
    // Wende Bold/Italic nur auf Text außerhalb von HTML-Tags an (nicht in Attributen wie href)
    $parts = preg_split('/(<[^>]+>)/', (string)$text, -1, PREG_SPLIT_DELIM_CAPTURE);
    foreach ($parts as $i => $seg){
        if ($seg === '' || $seg[0] === '<') continue; // HTML-Tag oder leer → überspringen

        // Bold: **text** oder __text__ - improved to handle whitespace better
        $seg = preg_replace('/\*\*\s*(.+?)\s*\*\*/s', '<strong>$1</strong>', $seg);
        $seg = preg_replace('/__\s*(.+?)\s*__/s', '<strong>$1</strong>', $seg);

        // Italic: *text* oder _text_ (nicht die schon ersetzten **...**) - improved whitespace handling
        // More robust pattern that handles long text spans
        $seg = preg_replace('/(?<!\*)\*(?!\*)([^*]+?)(?<!\*)\*(?!\*)/s', '<em>$1</em>', $seg);
        $seg = preg_replace('/(?<!_)_(?!_)([^_]+?)(?<!_)_(?!_)/s', '<em>$1</em>', $seg);

        $parts[$i] = $seg;
    }
    return implode('', $parts);
}

function riman_md_inline($text){
    global $RIMAN_INLINE_PARSER;
    $t = (string)$text;
    if (!empty($RIMAN_INLINE_PARSER)){
        $lib = __DIR__ . '/lib/StrictInlineParsedown.php';
        if (file_exists($lib)){
            require_once $lib; $pd = new StrictInlineParsedown();
            // parsedown escapes properly; allow <br> via two spaces logic we do earlier
            $html = $pd->linePublic($t);
            return $html;
        }
    }
    // Inline-Code: `code` zuerst (damit innerhalb von Code keine weiteren Ersetzungen passieren)
    $t = preg_replace_callback('/`([^`]+)`/', function($m){
        return '<code>'.esc_html($m[1]).'</code>';
    }, $t);

    // Zuerst: Handle cross-span bold formatting before processing links
    // Match patterns like **[Link](url) text** where bold spans across link and text
    $t = preg_replace_callback('/\*\*([^*]*?\[([^\]]+)\]\(([^)]+)\)[^*]*?)\*\*/', function($m){
        $fullContent = $m[1];
        // Extract link parts and convert to HTML
        if (preg_match('/^(.*?)\[([^\]]+)\]\(([^)]+)\)(.*)$/', $fullContent, $linkMatch)) {
            $beforeLink = $linkMatch[1];
            $linkLabel = $linkMatch[2];
            $linkUrl = $linkMatch[3];
            $afterLink = $linkMatch[4];

            // Process the URL (relative/absolute)
            $is_abs = preg_match('~^(https?:|mailto:|tel:|#|/)~i', $linkUrl);
            $url = $is_abs ? esc_url($linkUrl) : esc_attr($linkUrl);

            return '<strong>' . esc_html($beforeLink) . '<a href="'.$url.'">' . esc_html($linkLabel) . '</a>' . esc_html($afterLink) . '</strong>';
        }
        return $m[0];
    }, $t);

    // Handle cross-span italic formatting before processing links
    // Match patterns like *[Link](url) text* where italic spans across link and text
    $t = preg_replace_callback('/(?<!\*)\*([^*]*?\[([^\]]+)\]\(([^)]+)\)[^*]*?)\*(?!\*)/', function($m){
        $fullContent = $m[1];
        // Extract link parts and convert to HTML
        if (preg_match('/^(.*?)\[([^\]]+)\]\(([^)]+)\)(.*)$/', $fullContent, $linkMatch)) {
            $beforeLink = $linkMatch[1];
            $linkLabel = $linkMatch[2];
            $linkUrl = $linkMatch[3];
            $afterLink = $linkMatch[4];

            // Process the URL (relative/absolute)
            $is_abs = preg_match('~^(https?:|mailto:|tel:|#|/)~i', $linkUrl);
            $url = $is_abs ? esc_url($linkUrl) : esc_attr($linkUrl);

            return '<em>' . esc_html($beforeLink) . '<a href="'.$url.'">' . esc_html($linkLabel) . '</a>' . esc_html($afterLink) . '</em>';
        }
        return $m[0];
    }, $t);

    // Links: [Label](URL) → <a href>Label</a> (URL bleibt unverändert, Label wird später betont)
    $t = riman_md_inline_links($t);

    // Emphasis um Anker herum: **<a>Label</a>** → <a><strong>Label</strong></a> (einzeilig, kein DOTALL)
    $t = preg_replace('/\*\*\s*(<a\b[^>]*>)(.*?)<\/a>\s*\*\*/i', '$1<strong>$2<\/strong></a>', $t);
    $t = preg_replace('/__\s*(<a\b[^>]*>)(.*?)<\/a>\s*__/i', '$1<strong>$2<\/strong></a>', $t);
    $t = preg_replace('/(?<!\*)\*\s*(<a\b[^>]*>)(.*?)<\/a>\s*\*(?!\*)/i', '$1<em>$2<\/em></a>', $t);
    $t = preg_replace('/(?<!_)_\s*(<a\b[^>]*>)(.*?)<\/a>\s*_(?!_)/i', '$1<em>$2<\/em></a>', $t);

    // Emphasis nur außerhalb von HTML-Tags anwenden (verhindert <em> in href)
    $t = riman_apply_emphasis_outside_tags($t);

    // Schließe ggf. vergessene </strong></a> oder </em></a>
    $t = preg_replace('/<a\b([^>]*)><strong>((?:(?!<\/strong>).)*?)<\/a>/i', '<a$1><strong>$2<\/strong></a>', $t);
    $t = preg_replace('/<a\b([^>]*)><em>((?:(?!<\/em>).)*?)<\/a>/i', '<a$1><em>$2<\/em></a>', $t);
    return $t;
}
function riman_md_to_blocks($md){
    $lines = preg_split("/\r?\n/", (string)$md);
    // Normalisiere Sonderleerzeichen (NBSP, NNBSP etc.) → normales Leerzeichen
    foreach ($lines as &$__l){
        // NBSP‑Varianten → normales Leerzeichen
        $__l = preg_replace('/[\x{00A0}\x{2007}\x{202F}\x{2009}]/u',' ', $__l);
        // Typografische Bindestriche/Minus → normales '-'
        $__l = preg_replace('/[\x{2010}\x{2011}\x{2012}\x{2013}\x{2014}\x{2212}]/u','-', $__l);
        // Unsichtbare Steuerzeichen (Zero‑Width etc.) entfernen
        $__l = preg_replace('/[\x{200B}\x{200C}\x{200D}\x{2060}\x{FEFF}\x{00AD}]/u','', $__l);
    }
    unset($__l);
    $out = [];
    $para = [];
    $ul = [];
    $ul_is_checklist = false;
    $ol = [];
    $quote = [];
    $flush_para = function() use (&$para,&$out){ if ($para){
        // Soft line breaks: two spaces before newline → <br>
        $joined = implode("\n", $para);
        $joined = preg_replace('/  \n/', "<br>\n", $joined);
        $txt = riman_md_inline($joined);
        $safe = wp_kses($txt, ['a'=>['href'=>true],'strong'=>[],'em'=>[],'code'=>[],'br'=>[]]);
        $out[] = "<!-- wp:paragraph --><p>$safe</p><!-- /wp:paragraph -->"; $para = []; } };
    $flush_ul = function() use (&$ul,&$ul_is_checklist,&$out){ if ($ul){ $lis = array_map(function($it){ $txt = riman_md_inline($it); $safe = wp_kses($txt, ['a'=>['href'=>true],'strong'=>[],'em'=>[],'code'=>[],'br'=>[]]); return "<li>$safe</li>"; }, $ul); if ($ul_is_checklist){ $out[] = "<!-- wp:list {\"className\":\"is-checklist\"} --><ul class=\"is-checklist\" style=\"list-style:none;padding-left:0\">".implode('', $lis)."</ul><!-- /wp:list -->"; } else { $out[] = "<!-- wp:list --><ul>".implode('', $lis)."</ul><!-- /wp:list -->"; } $ul = []; $ul_is_checklist=false; } };
    $flush_ol = function() use (&$ol,&$out){ if ($ol){ $lis = array_map(function($it){ $txt = riman_md_inline($it); $safe = wp_kses($txt, ['a'=>['href'=>true],'strong'=>[],'em'=>[],'code'=>[],'br'=>[]]); return "<li>$safe</li>"; }, $ol); $out[] = "<!-- wp:list {\"ordered\":true} --><ol>".implode('', $lis)."</ol><!-- /wp:list -->"; $ol = []; } };
    $flush_quote = function() use (&$quote,&$out){ if ($quote){
        // Build paragraphs inside blockquote
        $paras = [];
        $acc = [];
        foreach ($quote as $ln){
            if ($ln === "\n\n") { if ($acc){ $paras[] = implode("\n", $acc); $acc = []; } continue; }
            $acc[] = $ln;
        }
        if ($acc) $paras[] = implode("\n", $acc);
        $html = '';
        foreach ($paras as $p){
            $p = preg_replace('/  \n/','<br>\n',$p); // soft breaks
            $txt = riman_md_inline($p);
            $safe = wp_kses($txt, ['a'=>['href'=>true],'strong'=>[],'em'=>[],'code'=>[],'br'=>[]]);
            $html .= '<p>'.$safe.'</p>';
        }
        $out[] = "<!-- wp:quote --><blockquote class=\"wp-block-quote\">$html</blockquote><!-- /wp:quote -->";
        $quote = [];
    } };
    foreach ($lines as $ln){
        // ATX Headings: allow up to 3 leading spaces per CommonMark
        if (preg_match('/^\s{0,3}(#{1,6})\s+(.+?)\s*$/', $ln, $m)){
            $flush_para(); $flush_ul(); $flush_ol(); $flush_quote();
            $level = strlen($m[1]);
            $txt = riman_md_inline($m[2]);
            $safe = wp_kses($txt, ['a'=>['href'=>true],'strong'=>[],'em'=>[],'code'=>[],'br'=>[]]);
            $out[] = "<!-- wp:heading {\"level\":$level} --><h$level>$safe</h$level><!-- /wp:heading -->";
        } elseif (preg_match('/^\s{0,3}(?:-{3,}|\*{3,}|_{3,})\s*$/', $ln)){
            // Horizontal rule: --- or *** or ___
            $flush_para(); $flush_ul(); $flush_ol(); $flush_quote();
            $out[] = "<!-- wp:separator --><hr class=\"wp-block-separator\" /><!-- /wp:separator -->";
        } elseif (preg_match('/^\s*>\s?(.*)$/', $ln, $m)){
            // Blockquote line
            $para && $flush_para(); $ul && $flush_ul(); $ol && $flush_ol();
            $quote[] = $m[1];
        } elseif (preg_match('/^\s*\*\*[^*]+\*\*\s*:\s+.+$/u', $ln)){
            // Definition-ähnliche Zeilen ohne '-' Marker → als einzelner Absatz behandeln
            $para && $flush_para(); $ul && $flush_ul(); $ol && $flush_ol(); $quote && $flush_quote();
            $txt = riman_md_inline(trim($ln));
            $safe = wp_kses($txt, ['a'=>['href'=>true],'strong'=>[],'em'=>[],'code'=>[],'br'=>[]]);
            $out[] = "<!-- wp:paragraph --><p>$safe</p><!-- /wp:paragraph -->";
        } elseif (preg_match('/^\s*[-*]\s+(.+)$/', $ln, $m)){
            // Check if this is actually italic text spanning multiple lines, not a list item
            $is_italic_spanning = false;
            $trimmed_line = trim($ln);

            // More comprehensive check for italic text vs list items
            if ($trimmed_line[0] === '*') {
                // Check if this line ends with * (making it italic text, not a list)
                // Also check if there's no space after the first * (italic pattern)
                if (preg_match('/^\*[^\s].*\*\s*$/', $trimmed_line)) {
                    $is_italic_spanning = true;
                }
                // Additional check: if line doesn't start with "* " (space after *), it's likely italic
                elseif (!preg_match('/^\*\s/', $trimmed_line)) {
                    $is_italic_spanning = true;
                }
            }

            if ($is_italic_spanning) {
                // Treat as regular paragraph text (italic will be processed in riman_md_inline)
                $para[] = $ln;
            } else {
                $para && $flush_para(); $ol && $flush_ol(); $quote && $flush_quote();
                $ul[] = $m[1]; $ul_is_checklist = false;
            }
        } elseif (preg_match('/^\s*([\x{2713}\x{2714}\x{2705}])\s+(.+)$/u', $ln, $m)){
            // Single checkmark bullet (✓, ✔, ✅) – preserve symbol in content
            $para && $flush_para(); $ol && $flush_ol(); $quote && $flush_quote();
            $ul[] = trim($m[1].' '.$m[2]); $ul_is_checklist = true;
        } elseif (preg_match_all('/(?:^|\s)([\x{2713}\x{2714}\x{2705}])\s+([^\x{2713}\x{2714}\x{2705}]+?)(?=(?:\s[\x{2713}\x{2714}\x{2705}]\s+|$))/u', $ln, $mm) && count($mm[1]) >= 2){
            // Inline checklist: split one line with multiple ✓ items into list items, keep symbol
            $para && $flush_para(); $ol && $flush_ol(); $quote && $flush_quote();
            $syms = $mm[1]; $texts = $mm[2];
            $n = min(count($syms), count($texts));
            for ($i=0; $i<$n; $i++){ $ul[] = trim($syms[$i].' '.$texts[$i]); } $ul_is_checklist = true;
        } elseif (preg_match('/^\s*\d+[\.)]\s+(.+)$/', $ln, $m)){
            $para && $flush_para(); $ul && $flush_ul(); $quote && $flush_quote();
            $ol[] = $m[1];
        } elseif (preg_match('/^\s*\d+[\.)]\s+/', $ln) && preg_match('/\d+[\.)]\s+/', $ln) && preg_match_all('/\d+[\.)]\s+([^\n]+?)(?=\s+\d+[\.)]\s+|$)/u', $ln, $mm)){
            // one-line ordered list: split into items - but only if line STARTS with a number
            $para && $flush_para(); $ul && $flush_ul(); $quote && $flush_quote();
            foreach ($mm[1] as $item){ $ol[] = trim($item); }
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

// Mapping für Link‑Umschreibung
global $RIMAN_MD_POSTS, $RIMAN_MD_MAP, $RIMAN_TITLE_MAP;
$RIMAN_MD_POSTS = [];
$RIMAN_MD_MAP   = [];
$RIMAN_TITLE_MAP = [];
// Slug-Policy: 'filename' (kurz) oder 'title' (aus H1)
$RIMAN_SLUG_MODE = 'title';
$RIMAN_STRICT_MD = false;
$RIMAN_FORCE_UPDATE = false; // überschreibe auch wenn _riman_mdsite_path nicht passt

function riman_norm_key($s){
    $t = strtolower((string)$s);
    $t = str_replace([' ', '_', '-'], '', $t);
    $t = preg_replace('~[^a-z0-9]~', '', $t);
    return $t;
}

function riman_register_post_map($post_id, $path_key){
    global $RIMAN_MD_POSTS, $RIMAN_MD_MAP;
    $RIMAN_MD_POSTS[] = ['id'=>$post_id, 'path'=>$path_key];
    // map file and directory
    $RIMAN_MD_MAP[$path_key] = get_permalink($post_id);
    $dir = rtrim(dirname($path_key), '/');
    if ($dir && !isset($RIMAN_MD_MAP[$dir])) $RIMAN_MD_MAP[$dir] = get_permalink($post_id);
    // title map
    $title = get_the_title($post_id);
    if ($title){ $RIMAN_TITLE_MAP[riman_norm_key($title)] = get_permalink($post_id); }
}

function riman_ensure_post($parent,$title,$content_md,$typ,$order,$path_key){
    // in Blocks wandeln
    global $RIMAN_STRICT_MD;
    if ($RIMAN_STRICT_MD){
        $html = riman_md_to_html_strict($content_md);
        if ($html !== ''){
            $safe = wp_kses_post($html);
            $content = "<!-- wp:html -->\n".$safe."\n<!-- /wp:html -->";
        } else {
            $content = riman_md_to_blocks($content_md);
        }
    } else {
        $content = riman_md_to_blocks($content_md);
    }
    $exist=get_posts(['post_type'=>'riman_seiten','numberposts'=>1,'post_status'=>['publish','draft','pending'],'meta_key'=>'_riman_mdsite_path','meta_value'=>$path_key,'fields'=>'ids']);
    $data=[
        'post_parent'=>$parent,
        'post_title'=>$title?:'(Ohne Titel)',
        'post_content'=>$content,
        'post_type'=>'riman_seiten',
        'post_status'=>'publish',
        'menu_order'=>$order
    ];
    global $RIMAN_SLUG_MODE;
    if ($RIMAN_SLUG_MODE === 'filename'){
        $slug = riman_slug_from_pathkey($path_key);
        if ($slug) $data['post_name'] = $slug;
    }
    if($exist){
        $data['ID']=(int)$exist[0];
        $pid=wp_update_post($data);
    } else {
        // Optional: Erzwinge Update via Slug/Title-Match, wenn kein _riman_mdsite_path Treffer
        global $RIMAN_FORCE_UPDATE, $RIMAN_SLUG_MODE;
        $pid = 0;
        if ($RIMAN_FORCE_UPDATE){
            // Kandidaten-Slugs: aus Dateiname, aus Titel, Variationen _/-, lowercase
            $cands = [];
            $file_slug = riman_slug_from_pathkey($path_key);
            if ($file_slug) { $cands[] = $file_slug; $cands[] = str_replace('-', '_', $file_slug); $cands[] = str_replace('_', '-', $file_slug); }
            $title_slug = sanitize_title($title);
            if ($title_slug) { $cands[] = $title_slug; $cands[] = str_replace('-', '_', $title_slug); $cands[] = str_replace('_', '-', $title_slug); }
            $cands = array_values(array_unique(array_filter($cands)));
            foreach ($cands as $sl){
                $c = get_posts(['name'=>$sl,'post_type'=>'riman_seiten','post_status'=>['publish','draft','pending'],'numberposts'=>1,'fields'=>'ids']);
                if ($c){ $data['ID']=(int)$c[0]; $pid = wp_update_post($data); if(!is_wp_error($pid)) update_post_meta($data['ID'],'_riman_mdsite_path',$path_key); break; }
            }
            // Letzter Fallback: suche per Titel und nimm exakten Treffer
            if (!$pid){
                $c = get_posts([ 'post_type'=>'riman_seiten','post_status'=>['publish','draft','pending'],'numberposts'=>-1, 's' => wp_strip_all_tags($title) ]);
                foreach ($c as $p){ if (strcasecmp($p->post_title, $title)===0){ $data['ID']=(int)$p->ID; $pid=wp_update_post($data); if(!is_wp_error($pid)) update_post_meta($p->ID,'_riman_mdsite_path',$path_key); break; } }
            }
        }
        if (!$pid){
            $pid=wp_insert_post($data);
            if(!is_wp_error($pid)) update_post_meta($pid,'_riman_mdsite_path',$path_key);
        }
    }
    if(!is_wp_error($pid)&&$pid){
        wp_set_post_terms($pid,[$typ],'seitentyp');
        riman_register_post_map($pid, $path_key);

        // Hero-Metadaten aus JSON setzen
        riman_set_hero_metadata($pid, $path_key);

        // Featured Image automatisch setzen
        riman_set_featured_image($pid, $path_key);
    }
    return $pid; }

function riman_process_dir($root,$dir,$depth,$parent){
  $rel=ltrim(str_replace($root,'',$dir),'/');
  $base=basename($dir);
  $order=riman_menu_order($base);
  // Seitentyp anhand der Hierarchieebene: 1=hauptseite, 2=unterseite, 3=detailseite, >=4=info
  $levelType = function($d){ return $d===1?'hauptseite':($d===2?'unterseite':($d===3?'detailseite':'info')); };
  $typ=$levelType($depth);
  $cur=$parent;
  $idx=rtrim($dir,'/').'/index.md';
  if(file_exists($idx)){
    $md=file_get_contents($idx);
    $t=riman_read_h1($md)?:riman_norm_title($base);
    $body=riman_body_no_h1($md);
    // Falls nach Entfernen der H1 kein Inhalt vorhanden ist → automatische Übersicht generieren
    if ($body === ''){
      $auto = [];
      // Direkt im Ordner liegende .md-Dateien (ohne index.md)
      foreach (glob(rtrim($dir,'/').'/*.md') as $f){
        if (basename($f)==='index.md') continue;
        $c = @file_get_contents($f);
        $tt = riman_read_h1($c) ?: riman_norm_title(pathinfo($f, PATHINFO_FILENAME));
        $auto[] = '- ['. $tt .']('. basename($f) .')';
      }
      // Unterordner mit index.md
      foreach (scandir($dir) as $e){
        if ($e==='.'||$e==='..') continue; $p=$dir.'/'.$e; if (!is_dir($p)) continue;
        $ii = $p.'/index.md'; if (!file_exists($ii)) continue;
        $c = @file_get_contents($ii);
        $tt = riman_read_h1($c) ?: riman_norm_title($e);
        $auto[] = '- ['. $tt .']('. $e .'/index.md)';
      }
      if ($auto){ $body = "## Übersicht\n\n".implode("\n", $auto)."\n"; }
    }
    $cur=riman_ensure_post($parent,$t,$body,$typ,$order,$rel.'/index.md');
    $cov=riman_find_cover($dir); if($cov) riman_set_thumb($cur,$cov);
    echo "📄 $rel/index.md → #$cur $t\n";
  }
  // Wenn es KEIN index.md gibt, aber im Elternordner eine gleichnamige .md existiert
  // (z. B. Ordner "intern/" und Datei nebenan "intern.md"), dann nutze diese
  // Datei als logischen Parent für die folgenden Unterseiten (Info-Ebene).
  if (!file_exists($idx)) {
    $parent_rel = trim(dirname($rel), '/');
    $sibling_rel = ($parent_rel ? ($parent_rel.'/') : '').basename($dir).'.md';
    $maybe = get_posts([
      'post_type'=>'riman_seiten',
      'numberposts'=>1,
      'post_status'=>['publish','draft','pending'],
      'meta_key'=>'_riman_mdsite_path',
      'meta_value'=>$sibling_rel,
      'fields'=>'ids'
    ]);
    if ($maybe){
      $cur = (int)$maybe[0];
    } else {
      // Kein index.md und keine gleichnamige .md → synthetische Übersichtsseite anlegen
      // Titel aus Ordnername ableiten, leerer Body; dient als logischer Parent und Linkziel
      $t = riman_norm_title($base);
      $cur = riman_ensure_post($parent, $t, '', $typ, $order, $rel.'/index.md');
      echo "📄 (auto) $rel/index.md → #$cur $t\n";
    }
  }

  // Einzelne Markdown-Dateien in diesem Ordner → Kinder dieser Ebene
  $files = [];
  foreach (glob(rtrim($dir,'/').'/*.md') as $f){ if (basename($f)==='index.md') continue; $files[] = $f; }
  // Reihenfolge nach numerischem Präfix (z. B. 01_..., 10_...) ansonsten alphabetisch
  usort($files, function($a,$b){
    $na = riman_menu_order(pathinfo($a, PATHINFO_FILENAME));
    $nb = riman_menu_order(pathinfo($b, PATHINFO_FILENAME));
    $cmp = $na <=> $nb;
    if ($cmp !== 0) return $cmp;
    return strnatcasecmp(basename($a), basename($b));
  });
  $i = 0;
  foreach($files as $f){
    $md=file_get_contents($f);
    $t=riman_read_h1($md)?:riman_norm_title(pathinfo($f,PATHINFO_FILENAME));
    $body=riman_body_no_h1($md);
    $relf=$rel?($rel.'/'.basename($f)):basename($f);
    $childType=$levelType($depth+1);
    // Menü-Reihenfolge: aus Dateinamenpräfix ableiten (oder hohe Zahl, falls keins)
    $childOrder = riman_menu_order(pathinfo($f, PATHINFO_FILENAME));
    if ($childOrder === 9999) { $childOrder = 10000 + $i; }
    $child=riman_ensure_post($cur,$t,$body,$childType,$childOrder,$relf);
    $cov=riman_find_cover(dirname($f)); if($cov) riman_set_thumb($child,$cov);
    echo "  ├─ 📄 $relf → #$child $t (order:$childOrder)\n";
    $i++;
  }
  // Unterordner rekursiv
  $subs=[]; foreach(scandir($dir) as $e){ if($e==='.'||$e==='..') continue; $p=$dir.'/'.$e; if(is_dir($p)) $subs[]=$p; }
  usort($subs,function($a,$b){ return riman_menu_order(basename($a))<=>riman_menu_order(basename($b)); });
  foreach($subs as $sd){ riman_process_dir($root,$sd,$depth+1,$cur); }
}

$default_root = is_dir(ABSPATH.'content/riman_new_site_from_transcript')
    ? 'content/riman_new_site_from_transcript'
    : 'wp-content/uploads/riman_new_site_from_transcript';
$chosen_root = isset($_POST['root']) ? sanitize_text_field($_POST['root']) : $default_root;
// Slug-Policy übernehmen
global $RIMAN_SLUG_MODE;
if (isset($_POST['slug_mode'])){
    $m = $_POST['slug_mode'] === 'filename' ? 'filename' : 'title';
    $RIMAN_SLUG_MODE = $m;
}
// Strict Markdown Parser übernehmen
global $RIMAN_STRICT_MD; $RIMAN_STRICT_MD = !empty($_POST['strict_md']);
// Inline parser flag
global $RIMAN_INLINE_PARSER; $RIMAN_INLINE_PARSER = !empty($_POST['inline_parser']);
// Force-Update übernehmen
global $RIMAN_FORCE_UPDATE; $RIMAN_FORCE_UPDATE = !empty($_POST['force_update']);

// Path-Auflösung: absolut oder relativ zu ABSPATH
$abs = $chosen_root;
if ($abs[0] !== '/' && !preg_match('~^[A-Za-z]:\\\\~',$abs)) { $abs = ABSPATH . ltrim($chosen_root,'/'); }
$abs = realpath($abs);

echo '<div style="font-family:system-ui,Segoe UI,Roboto,Arial,sans-serif;max-width:900px;margin:40px auto;padding:0 16px;">';
echo '<h1>RIMAN – Import aus Markdown‑Site</h1>';
echo '<form method="post" style="margin:16px 0;padding:12px;border:1px solid #ddd;border-radius:6px;">';
echo '<label>Wurzelordner (relativ zu WordPress‑Root):<br>'; 
echo '<input type="text" name="root" value="' . esc_attr($chosen_root) . '" style="width:100%;max-width:560px">';
echo '</label><br><small style="color:#666">Standard: '.esc_html($default_root).' (falls vorhanden)</small><br><br>';
echo '<label>Slug-Strategie:&nbsp;';
echo '<select name="slug_mode">';
echo '<option value="title"'.($RIMAN_SLUG_MODE==='title'?' selected':'').'>Aus Titel/H1 (lang)</option>';
echo '<option value="filename"'.($RIMAN_SLUG_MODE==='filename'?' selected':'').'>Aus Dateinamen (kurz)</option>';
echo '</select></label><br><br>';
echo '<label><input type="checkbox" name="inline_parser" value="1"'.(!empty($_POST['inline_parser'])?' checked':'').'> Inline Markdown Parser (Parsedown) – stabilere Links/Fettdruck</label><br><br>';
echo '<label><input type="checkbox" name="strict_md" value="1"'.($RIMAN_STRICT_MD?' checked':'').'> Strict Markdown (Parser) – exakte MD-Ausgabe</label><br><br>';
echo '<fieldset style="margin:10px 0 8px 0"><legend><strong>Bereinigung vor dem Import</strong></legend>';
echo '<label><input type="checkbox" name="purge_all" value="1"> Alle bestehenden RIMAN Seiten (riman_seiten) vorab löschen</label><br>';
echo '<label><input type="checkbox" name="purge_non_md" value="1"> Nur Seiten ohne MD‑Marker löschen (ohne _riman_mdsite_path oder mit _riman_sample_content)</label>';
echo '</fieldset>';
echo '<fieldset style="margin:10px 0 8px 0"><legend><strong>Überschreiben</strong></legend>';
echo '<label><input type="checkbox" name="force_update" value="1"> Erzwinge Überschreiben per Slug/Titel, wenn MD‑Pfad nicht passt</label>';
echo '</fieldset>';
echo '<button type="submit" class="button button-primary">Import starten</button>';
echo '</form>';

if (!empty($_POST)) {
    if (!$abs || !is_dir($abs)) {
        echo '<p style="color:#b00">Wurzelordner nicht gefunden: ' . esc_html($chosen_root) . '</p>';
    } else {
        echo '<pre style="background:#111;color:#eee;padding:12px;border-radius:6px;overflow:auto;max-height:60vh">';
        echo '🔎 Importiere aus: ' . esc_html($abs) . "\n";
        // Optional: Bereinigung
        if (!empty($_POST['purge_all']) || !empty($_POST['purge_non_md'])) {
            $to_delete = [];
            $args = [ 'post_type'=>'riman_seiten', 'numberposts'=>-1, 'post_status'=>'any' ];
            foreach (get_posts($args) as $p) {
                $has_md = get_post_meta($p->ID, '_riman_mdsite_path', true) ? true : false;
                $is_sample = get_post_meta($p->ID, '_riman_sample_content', true) ? true : false;
                if (!empty($_POST['purge_all'])) {
                    $to_delete[] = $p->ID;
                } elseif (!empty($_POST['purge_non_md'])) {
                    if (!$has_md || $is_sample) $to_delete[] = $p->ID;
                }
            }
            $cnt = 0;
            foreach ($to_delete as $id) { if (wp_delete_post($id, true)) { $cnt++; } }
            echo "🧹 Gelöscht: $cnt RIMAN Seiten\n";
        }
        // Top-Level Ordner verarbeiten
        $tops=[]; foreach(scandir($abs) as $e){ if($e==='.'||$e==='..') continue; $p=$abs.'/'.$e; if(is_dir($p)) $tops[]=$p; }
        usort($tops,function($a,$b){ return riman_menu_order(basename($a))<=>riman_menu_order(basename($b)); });
        echo '📁 Top-Level-Ordner: '.count($tops)."\n";
        if ($tops){
            foreach($tops as $td){ riman_process_dir($abs,$td,1,0); }
        } else {
            // Fallback: Root selbst verarbeiten (falls MD-Dateien direkt im Root liegen)
            echo "ℹ️ Keine Unterordner gefunden – verarbeite Root selbst.\n";
            riman_process_dir($abs,$abs,1,0);
        }
        // Nachlauf: Links (href) aus .md auf Permalinks umschreiben
        global $RIMAN_MD_POSTS, $RIMAN_MD_MAP;
        $resolved = 0;
        // Globale Titel-/Slug-Resolver aufbauen (für Ziele, die nicht in diesem Lauf importiert wurden)
        $GLOBAL_TITLE_MAP = [];
        foreach (get_posts(['post_type'=>'riman_seiten','numberposts'=>-1,'post_status'=>'any','fields'=>'ids']) as $pid_all){
            $t = get_the_title($pid_all); if ($t){ $GLOBAL_TITLE_MAP[riman_norm_key($t)] = get_permalink($pid_all); }
        }
        $GLOBAL_SLUG_RESOLVE = function($relPath) {
            $relPath = trim($relPath,'/'); if ($relPath==='') return false;
            $parts = [];
            foreach (explode('/', $relPath) as $seg){
                if ($seg===''||$seg==='.'||$seg==='..') continue;
                $seg = preg_replace('~\\.md$~i','',$seg);
                if (preg_match('~^index$~i',$seg)) continue;
                $seg = preg_replace('~^\\d+[_-]*~','',$seg);
                $parts[] = sanitize_title($seg);
            }
            if (!$parts) return false;
            $path = implode('/', $parts);
            $obj = get_page_by_path($path, OBJECT, 'riman_seiten');
            return $obj ? get_permalink($obj) : false;
        };
        // Helper: resolve md href to permalink
        $resolve = function($href, $basedir) use ($RIMAN_MD_MAP, $RIMAN_TITLE_MAP, $GLOBAL_TITLE_MAP, $GLOBAL_SLUG_RESOLVE){
            // Ignore absolute/protocol and anchors
            if (preg_match('~^(https?:)?//~i',$href) || preg_match('~^(mailto:|tel:|#)~i',$href)) return false;
            $raw = $href;
            // Build normalized relative path
            $path = $href;
            if ($path !== '' && $path[0] !== '/') {
                $path = ($basedir?($basedir.'/'):'').$path;
            } else {
                $path = ltrim($path,'/');
            }
            $parts=[]; foreach (explode('/', $path) as $seg){ if ($seg===''||$seg==='.') continue; if ($seg==='..'){ array_pop($parts); continue;} $parts[]=$seg; }
            $norm = implode('/', $parts);
            $candidates = [];
            if (substr($norm,-3)==='.md') {
                $candidates[] = $norm;
                $candidates[] = preg_replace('~(/?)index\.md$~','', $norm);
            } else {
                $candidates[] = $norm.'.md';
                $candidates[] = rtrim($norm,'/').'/index.md';
                $candidates[] = rtrim($norm,'/');
            }
            foreach ($candidates as $cand){ if (isset($RIMAN_MD_MAP[$cand])) return $RIMAN_MD_MAP[$cand]; }
            // Fallback 1: match candidate without index.md suffix
            $cand2 = preg_replace('~(/?)index\.md$~','', $candidates[0] ?? '');
            if ($cand2 && isset($RIMAN_MD_MAP[$cand2])) return $RIMAN_MD_MAP[$cand2];
            // Fallback 2: underscore/dash-insensitive match
            $norm = function($s){ $s = strtolower($s); $s = str_replace(['_','-'],'',$s); return $s; };
            $want = $norm($candidates[0] ?? $path);
            foreach ($RIMAN_MD_MAP as $k => $perma){ if ($norm($k) === $want) return $perma; }
            // Fallback 2b: match by title (using last segment)
            $lastSeg = $candidates[0] ?? $path;
            $lastSeg = basename($lastSeg);
            $lastSeg = preg_replace('~\.md$~','',$lastSeg);
            $wantTitle = riman_norm_key($lastSeg);
            if (isset($RIMAN_TITLE_MAP[$wantTitle])) return $RIMAN_TITLE_MAP[$wantTitle];
            if (isset($GLOBAL_TITLE_MAP[$wantTitle])) return $GLOBAL_TITLE_MAP[$wantTitle];
            // Fallback 2c: Slug-basierte Auflösung über get_page_by_path
            $siteRel = $candidates[0] ?? $path; if ($basedir){ $siteRel = ltrim(rtrim($basedir,'/').'/'.ltrim($siteRel,'/'),'/'); }
            $slugUrl = is_callable($GLOBAL_SLUG_RESOLVE) ? $GLOBAL_SLUG_RESOLVE($siteRel) : false; if ($slugUrl) return $slugUrl;
            // Fallback 3: best fuzzy match (Levenshtein threshold)
            $best = null; $bestKey = null;
            foreach ($RIMAN_MD_MAP as $k => $perma){ $d = levenshtein($want, $norm($k)); if ($best===null || $d < $best){ $best = $d; $bestKey = $k; } }
            if ($best !== null && $best <= 5) return $RIMAN_MD_MAP[$bestKey];
            // Fallback 4: fuzzy by title map
            $best = null; $bestKey = null;
            foreach (array_merge($RIMAN_TITLE_MAP, $GLOBAL_TITLE_MAP) as $tk => $perma){ $d = levenshtein($wantTitle, $tk); if ($best===null || $d < $best){ $best = $d; $bestKey = $tk; } }
            if ($best !== null && $best <= 5) return $RIMAN_TITLE_MAP[$bestKey] ?? ($GLOBAL_TITLE_MAP[$bestKey] ?? false);
            return false;
        };

        foreach ($RIMAN_MD_POSTS as $info){
            $pid = $info['id']; $p = get_post($pid); if (!$p) continue;
            $dir = trim(dirname($info['path']), '/');
            $content = $p->post_content;
            $content = preg_replace_callback('/href=\"([^\"]+)\"/', function($m) use ($dir, $RIMAN_MD_MAP){
                $href = $m[1];
                // If it's an http link but host looks like a local path (no dot) and points to a .md → resolve
                if (preg_match('~^https?://~i', $href)) {
                    $u = @parse_url($href);
                    $host = $u['host'] ?? '';
                    $path = ltrim($u['path'] ?? '', '/');
                    if ($host && strpos($host, '.') === false) {
                        // Treat as pseudo-local link; stitch host/path
                        $rel = $host . ($path ? '/' . $path : '');
                        global $resolve; $perma = is_callable($resolve) ? $resolve($rel, $dir) : false;
                        if ($perma) return 'href="'.esc_url($perma).'"';
                    }
                    // keep other http(s) links as-is
                    return $m[0];
                }
                if (preg_match('~^#|^(mailto:|tel:)~i', $href)) return $m[0];
                // Versuche zu permalinken aufzulösen (mit/ohne .md, index.md, relativen Pfaden)
                $perma = (function($h,$d) use ($RIMAN_MD_MAP){
                    // duplizierter closure code nicht erlaubt; wird oben als $resolve bereitgestellt
                    return false;
                })($href,$dir);
                // Da PHP ältere Version closures in closures nicht gut mag, rufe globalen $resolve auf
                global $resolve; if (!$perma && is_callable($resolve)) $perma = $resolve($href, $dir);
                if ($perma) return 'href="'.esc_url($perma).'"';
                return $m[0];
            }, $content);

            // Zweiter Durchlauf: nackte .md-Verweise im Text (ohne Link) zu Ankern machen
            $content = preg_replace_callback('/(^|\s|\()([A-Za-z0-9_\.\/-]+\.md)(?=\s|\)|<|$)/', function($m) use ($dir){
                $lead = $m[1]; $path = $m[2];
                global $resolve; $perma = is_callable($resolve) ? $resolve($path, $dir) : false;
                if ($perma) return $lead.'<a href="'.esc_url($perma).'">'.esc_html($path).'</a>';
                return $m[0];
            }, $content);

            // Dritter Durchlauf: verbleibende <a href="...md">Label</a> per Label-Titelmap auflösen
            global $RIMAN_TITLE_MAP; $content = preg_replace_callback('/<a\s+[^>]*href=\"([^\"]+\.md(?:\/index\.md)?)\"[^>]*>([^<]+)<\/a>/i', function($m) use ($RIMAN_TITLE_MAP){
                $label = trim($m[2]); $k = riman_norm_key($label);
                if (isset($RIMAN_TITLE_MAP[$k])){
                    $url = $RIMAN_TITLE_MAP[$k];
                    return '<a href="'.esc_url($url).'">'.esc_html($label).'</a>';
                }
                return $m[0];
            }, $content);
            if ($content !== $p->post_content){
                wp_update_post(['ID'=>$pid, 'post_content'=>$content]);
                $resolved++;
            }
            // Pass 2b: unescape literal anchors &lt;a href=&quot;...md&quot;&gt;Label&lt;/a&gt; → real <a>
            $p = get_post($pid); if (!$p) continue;
            $cnt_before = $resolved;
            $html = $p->post_content;
            $html = preg_replace_callback('/&lt;a\s+[^&]*href=&quot;([^&]+\.md(?:\/index\.md)?)&quot;[^&]*&gt;(.*?)&lt;\/a&gt;/i', function($m){
                $href = html_entity_decode($m[1], ENT_QUOTES|ENT_HTML5, 'UTF-8');
                $href = preg_replace('/<\/?(em|strong)>/i','', $href);
                $href = str_replace(["\xE2\x80\x9C","\xE2\x80\x9D"], '"', $href); // curly → straight
                $label = html_entity_decode($m[2], ENT_QUOTES|ENT_HTML5, 'UTF-8');
                $label = strip_tags($label);
                return '<a href="'.esc_url($href).'">'.esc_html($label).'</a>';
            }, $html);
            if ($html !== $p->post_content){ wp_update_post(['ID'=>$pid,'post_content'=>$html]); $resolved++; }
            // Pass 3: Arrow bullets without links → link by title map
            $p = get_post($pid); if (!$p) continue;
            $html = $p->post_content;
            $html2 = preg_replace_callback('/(➡️|➡)\s+([^\n<]+?)(?=(?:\n|<|$))/', function($m) use ($RIMAN_TITLE_MAP){
                $arrow = $m[1]; $label = trim($m[2]);
                if (preg_match('~<a [^>]*>~i', $label)) return $m[0]; // already linked
                $k = riman_norm_key($label);
                if (isset($RIMAN_TITLE_MAP[$k])){
                    $url = $RIMAN_TITLE_MAP[$k];
                    // Preserve emphasis markup in label (e.g., **Abbruch-Management**)
                    $label_html = riman_apply_emphasis_outside_tags($label);
                    $label_safe = wp_kses($label_html, ['strong'=>[],'em'=>[]]);
                    return $arrow.' <a href="'.esc_url($url).'">'.$label_safe.'</a>';
                }
                return $m[0];
            }, $html);
            if ($html2 !== $html){ wp_update_post(['ID'=>$pid, 'post_content'=>$html2]); }
        }
        // Post‑Pass B: normalize anchors with missing closing emphasis inside links
        $fixedN = 0;
        foreach ($RIMAN_MD_POSTS as $info){
            $pid = $info['id']; $p = get_post($pid); if (!$p) continue;
            $c = $p->post_content;
            // Curly quotes in href → straight quotes
            $c2 = preg_replace('/href=\s*[“”]([^”]+)[”]/u', 'href="$1"', $c);
            // Single quotes as well
            $c2 = preg_replace('/href=\s*\'([^\']*)\'/u', 'href="$1"', $c2);
            // Ensure emphasis closed before </a>
            $c2 = preg_replace('/<a\\b([^>]*)><strong>((?:(?!<\\/strong>).)*?)<\\/a>/is', '<a$1><strong>$2<\/strong></a>', $c2);
            $c2 = preg_replace('/<a\\b([^>]*)><em>((?:(?!<\\/em>).)*?)<\\/a>/is', '<a$1><em>$2<\/em></a>', $c2);
            // Close unclosed anchors within paragraphs
            $c2 = preg_replace_callback('/(<p[^>]*>)(.*?)(<\/p>)/is', function($m){
                $open = substr_count($m[2], '<a');
                $close = substr_count($m[2], '</a>');
                if ($open > $close){ $m[2] .= str_repeat('</a>', $open-$close); }
                return $m[1].$m[2].$m[3];
            }, $c2);
            if ($c2 !== $c){ wp_update_post(['ID'=>$pid,'post_content'=>$c2]); $fixedN++; }
        }
        if ($fixedN){ echo "🧩 Anchor emphasis normalized: $fixedN Seiten\n"; }
        echo "🔗 Links umgeschrieben: $resolved Seiten\n";
        echo "\n✅ Fertig.\n";
        echo '</pre>';
    }
}

echo '</div>';
