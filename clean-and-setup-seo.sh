#!/bin/bash

# CLEAN WordPress Setup (Content-Struktur + SEO-Bilder) - Löscht ALLES vorher!
echo "=== CLEAN WordPress Setup (Content + SEO-Bilder) ==="
echo "⚠️  WARNUNG: Dieses Script löscht ALLE bestehenden Inhalte!"
echo ""

# Configuration
WORDPRESS_CONTAINER="riman-wordpress-swarm-wordpress-1"
# Nutze LLM-generiertes Mapping bevorzugt, sonst das Standard/Fixed
LLM_MAPPING="/Users/holgerbrandt/dev/claude-code/projects/riman-wordpress-swarm/wordpress-enhanced-image-mappings-seo.llm.json"
FIXED_MAPPING="/Users/holgerbrandt/dev/claude-code/projects/riman-wordpress-swarm/wordpress-enhanced-image-mappings-seo.fixed.json"
DEFAULT_MAPPING="/Users/holgerbrandt/dev/claude-code/projects/riman-wordpress-swarm/wordpress-enhanced-image-mappings-seo.json"

if [ -f "$LLM_MAPPING" ]; then
  SEO_MAPPINGS="$LLM_MAPPING"
elif [ -f "$FIXED_MAPPING" ]; then
  SEO_MAPPINGS="$FIXED_MAPPING"
else
  SEO_MAPPINGS="$DEFAULT_MAPPING"
fi
IMAGES_DIR="/Users/holgerbrandt/dev/claude-code/projects/riman-wordpress-swarm/images"
# Content-Verzeichnis (Quelle für Seitenstruktur und Inhalte)
CONTENT_DIR="/Users/holgerbrandt/dev/claude-code/projects/riman-content/riman-website-codex"

# Check Docker
if ! docker ps | grep -q "$WORDPRESS_CONTAINER"; then
    echo "Starting WordPress..."
    docker swarm init 2>/dev/null || true
    docker stack deploy -c docker-compose.yml riman-wordpress-swarm
    sleep 30
fi

echo "1. LÖSCHE ALLE BESTEHENDEN INHALTE..."

# Cleanup Script
cat > /tmp/cleanup-all.php << 'EOF'
<?php
require_once('/var/www/html/wp-load.php');

echo "=== CLEANUP: Lösche ALLES ===\n\n";

// 1. Lösche alle Attachments (Bilder)
$attachments = get_posts(array(
    'post_type' => 'attachment',
    'numberposts' => -1,
    'post_status' => 'any'
));

$deleted_attachments = 0;
foreach ($attachments as $attachment) {
    wp_delete_attachment($attachment->ID, true);
    $deleted_attachments++;
}
echo "✅ Gelöscht: $deleted_attachments Attachments\n";

// 2. Lösche alle Seiten
$pages = get_posts(array(
    'post_type' => 'page',
    'numberposts' => -1,
    'post_status' => 'any'
));

$deleted_pages = 0;
foreach ($pages as $page) {
    wp_delete_post($page->ID, true);
    $deleted_pages++;
}
echo "✅ Gelöscht: $deleted_pages Seiten\n";

// 3. Lösche alle Posts
$posts = get_posts(array(
    'post_type' => 'post',
    'numberposts' => -1,
    'post_status' => 'any'
));

$deleted_posts = 0;
foreach ($posts as $post) {
    wp_delete_post($post->ID, true);
    $deleted_posts++;
}
echo "✅ Gelöscht: $deleted_posts Posts\n";

// 4. Lösche alle Kategorien (außer Uncategorized)
$categories = get_terms('category', array('hide_empty' => false));
$deleted_cats = 0;
foreach ($categories as $category) {
    if ($category->slug !== 'uncategorized') {
        wp_delete_term($category->term_id, 'category');
        $deleted_cats++;
    }
}
echo "✅ Gelöscht: $deleted_cats Kategorien\n";

// 5. Lösche Upload-Verzeichnis
$upload_dir = wp_upload_dir();
$base_dir = $upload_dir['basedir'];
if (is_dir($base_dir)) {
    $iterator = new RecursiveIteratorIterator(
        new RecursiveDirectoryIterator($base_dir, RecursiveDirectoryIterator::SKIP_DOTS),
        RecursiveIteratorIterator::CHILD_FIRST
    );
    
    foreach ($iterator as $file) {
        if ($file->isDir()) {
            rmdir($file->getRealPath());
        } else {
            unlink($file->getRealPath());
        }
    }
}
echo "✅ Upload-Verzeichnis geleert\n";

echo "\n=== CLEANUP KOMPLETT ===\n";
EOF

# Execute cleanup
docker cp /tmp/cleanup-all.php "$WORDPRESS_CONTAINER:/var/www/html/"
docker exec "$WORDPRESS_CONTAINER" php /var/www/html/cleanup-all.php

echo ""
echo "0. Generiere semantisches Bild-Mapping (ohne Duplikate)..."

# Versuche semantische Zuordnung aus Content zu erzeugen
if command -v node >/dev/null 2>&1; then
  if [ -f "create-semantic-mappings.js" ] && [ -f "scripts/convert-semantic-to-seo.js" ]; then
    echo "   → Erzeuge wordpress-semantic-unique-mappings.json"
    node create-semantic-mappings.js >/dev/null 2>&1 || echo "   ⚠️  Semantic Mapping Generation fehlgeschlagen, verwende vorhandenes Mapping"
    echo "   → Konvertiere in wordpress-enhanced-image-mappings-seo.json"
    node scripts/convert-semantic-to-seo.js >/dev/null 2>&1 || echo "   ⚠️  Konvertierung fehlgeschlagen, verwende vorhandenes Mapping"
  fi
else
  echo "   ⚠️  Node.js nicht verfügbar – überspringe semantische Generierung"
fi

echo ""
echo "2. Erstelle Seitenstruktur und Inhalte aus Content-Ordner..."

# Kopiere Setup-Script und Content in den Container und führe Setup aus
docker cp setup-complete-riman.php "$WORDPRESS_CONTAINER:/var/www/html/" 2>/dev/null
if [ -d "$CONTENT_DIR" ]; then
    echo "Kopiere Content-Ordner in den Container..."
    docker cp "$CONTENT_DIR" "$WORDPRESS_CONTAINER:/var/www/html/riman-content" 2>/dev/null
else
    echo "⚠️  Content-Ordner nicht gefunden: $CONTENT_DIR"
fi

echo "Starte vollständiges Seiten-/Kategorien-Setup..."
docker exec "$WORDPRESS_CONTAINER" php /var/www/html/setup-complete-riman.php

echo ""
echo "3. Importiere Bilder mit SEO-Metadaten (KEINE DUPLIKATE!)..."

# Import images with SEO data - NO DUPLICATES!
cat > /tmp/import-clean-seo.php << 'EOF'
<?php
require_once('/var/www/html/wp-load.php');
require_once(ABSPATH . 'wp-admin/includes/image.php');
require_once(ABSPATH . 'wp-admin/includes/file.php');
require_once(ABSPATH . 'wp-admin/includes/media.php');

echo "=== Import Bilder mit SEO (Keine Duplikate!) ===\n\n";

$seo_json = '/tmp/wordpress-enhanced-image-mappings-seo.json';
$images_dir = '/tmp/images/';

if (!file_exists($seo_json)) {
    die("❌ SEO JSON nicht gefunden\n");
}

$seo = json_decode(file_get_contents($seo_json), true);
$imported = 0;
$skipped = 0;
$processed_images = array(); // Track erfolgreich gesetzte, eindeutige Bilder
$image_usage = array();     // Bilddatei -> zugewiesene Seite (Pfad)
$errors = 0;                // Zähler für doppelte Bildzuordnungen

// Übersetze SEO-Slugs zu WordPress-Slugs bzw. Pfaden
function translate_page_slug_to_wp_path($slug) {
    // Standardseiten
    $map = array(
        'about'   => 'ueber-uns',
        'contact' => 'kontakt',
        'home'    => 'startseite',
        'sicherheit' => 'sicherheitskoordination'
    );

    if (isset($map[$slug])) return $map[$slug];

    // Subseiten: SEO nutzt parent-child, WordPress hat parent als Ordner und child-Slug Präfix mit parent-
    // Beispiel: rueckbau-planung => rueckbau/rueckbau-planung
    if (preg_match('/^(rueckbau|altlasten|schadstoffe|beratung|sicherheit)-(.+)$/', $slug, $m)) {
        $parent = $m[1] === 'sicherheit' ? 'sicherheitskoordination' : $m[1];
        $child  = $m[2];
        return $parent . '/' . $parent . '-' . $child;
    }

    // Fallback: unverändert
    return $slug;
}

// Helper function
function import_unique_image($image_filename, $seo_data, $page_slug) {
    global $images_dir, $imported, $skipped, $processed_images, $image_usage, $errors;
    
    // Prüfe auf doppelte Verwendung des gleichen Bildes über mehrere Seiten
    if (isset($image_usage[$image_filename])) {
        $first_path = $image_usage[$image_filename];
        $current_path = translate_page_slug_to_wp_path($page_slug);
        echo "  ❌ Fehler: Bild bereits verwendet von: $first_path → Überspringe Zuordnung zu $current_path\n";
        $errors++;
        return false;
    }
    
    // Finde Bild
    $image_path = $images_dir . $image_filename;
    if (!file_exists($image_path)) {
        echo "  ⚠️  Bild nicht gefunden: $image_filename\n";
        return false;
    }
    
    // Finde Seite (nach Übersetzung zu WP-Pfad)
    $wp_path = translate_page_slug_to_wp_path($page_slug);
    $page = get_page_by_path($wp_path);
    if (!$page) {
        echo "  ⚠️  Seite nicht gefunden: $page_slug (WP-Pfad: $wp_path)\n";
        return false;
    }
    
    // Immer überschreiben: vorhandenes Featured Image wird ersetzt
    
    // Import image - NUR EINMAL!
    $upload = wp_upload_bits($image_filename, null, file_get_contents($image_path));
    
    if ($upload['error']) {
        echo "  ❌ Upload failed: {$upload['error']}\n";
        return false;
    }
    
    // Create attachment mit SEO data
    $attachment_data = array(
        'guid' => $upload['url'],
        'post_mime_type' => mime_content_type($image_path),
        'post_title' => isset($seo_data['title']) ? $seo_data['title'] : pathinfo($image_filename, PATHINFO_FILENAME),
        'post_content' => isset($seo_data['description']) ? $seo_data['description'] : '',
        'post_excerpt' => isset($seo_data['caption']) ? $seo_data['caption'] : '',
        'post_status' => 'inherit'
    );
    
    $attachment_id = wp_insert_attachment($attachment_data, $upload['file'], $page->ID);
    
    if (is_wp_error($attachment_id)) {
        echo "  ❌ Attachment Fehler\n";
        return false;
    }
    
    // Generate metadata
    $attach_metadata = wp_generate_attachment_metadata($attachment_id, $upload['file']);
    wp_update_attachment_metadata($attachment_id, $attach_metadata);
    
    // Set alt text
    if (isset($seo_data['alt'])) {
        update_post_meta($attachment_id, '_wp_attachment_image_alt', $seo_data['alt']);
    }
    
    // Set as featured image (immer überschreiben)
    set_post_thumbnail($page->ID, $attachment_id);
    
    // Mark als verarbeitet
    $processed_images[] = $image_filename;
    $image_usage[$image_filename] = $wp_path;
    
    echo "  ✅ Bild gesetzt mit SEO-Daten\n";
    $imported++;
    return true;
}

// Process main categories
if (isset($seo['main_categories'])) {
    echo "Hauptkategorien:\n";
    foreach ($seo['main_categories'] as $key => $data) {
        if (isset($data['slug']) && isset($data['image'])) {
            echo "→ {$data['slug']}\n";
            import_unique_image($data['image'], $data, $data['slug']);
        }
    }
}

// Process subcategories
if (isset($seo['subcategories'])) {
    echo "\nUnterkategorien:\n";
    foreach ($seo['subcategories'] as $parent => $subcats) {
        foreach ($subcats as $key => $data) {
            if (isset($data['slug']) && isset($data['image'])) {
                echo "→ {$data['slug']}\n";
                import_unique_image($data['image'], $data, $data['slug']);
            }
        }
    }
}

// Process pages
if (isset($seo['pages'])) {
    echo "\nSpezielle Seiten:\n";
    foreach ($seo['pages'] as $key => $data) {
        if (isset($data['slug']) && isset($data['image'])) {
            echo "→ {$data['slug']}\n";
            import_unique_image($data['image'], $data, $data['slug']);
        }
    }
}

echo "\n=== IMPORT ERGEBNIS ===\n";
echo "✅ Importiert: $imported (KEINE Duplikate!)\n";
echo "⏭️  Übersprungen: $skipped\n";
echo "❌ Doppelte Bildzuordnungen: $errors\n";

// Final check
$pages = get_posts(['post_type' => 'page', 'numberposts' => -1]);
$attachments = get_posts(['post_type' => 'attachment', 'numberposts' => -1]);

$with_images = 0;
foreach ($pages as $page) {
    if (get_post_thumbnail_id($page->ID)) {
        $with_images++;
    }
}

echo "\n📊 FINALE STATISTIK:\n";
echo "📄 Seiten total: " . count($pages) . "\n";
echo "🖼️  Bilder total: " . count($attachments) . " (KEINE Duplikate!)\n";
echo "✅ Seiten mit Bildern: $with_images\n";
echo "❌ Seiten ohne Bilder: " . (count($pages) - $with_images) . "\n";
EOF

# Copy files
docker cp "$SEO_MAPPINGS" "$WORDPRESS_CONTAINER:/tmp/wordpress-enhanced-image-mappings-seo.json"
docker cp "$IMAGES_DIR" "$WORDPRESS_CONTAINER:/tmp/"
docker cp /tmp/import-clean-seo.php "$WORDPRESS_CONTAINER:/var/www/html/"

# Improve category descriptions via REST API (using WP_API_* env)
echo "Aktualisiere Kategorie-Beschreibungen via REST API..."

# Defaults für WP API, falls nicht gesetzt
export WP_API_URL="${WP_API_URL:-http://127.0.0.1:8801/}"
export WP_API_USERNAME="${WP_API_USERNAME:-admin}"
# Hinweis: Anwendungs-Passwort kann Leerzeichen enthalten
export WP_API_PASSWORD="${WP_API_PASSWORD:-pYQO u9mN mPXH aDvr 2lux b6eQ}"

if command -v node >/dev/null 2>&1; then
  node scripts/wp-rest-update-categories.js --mapping "$SEO_MAPPINGS" || echo "⚠️  REST-Update fehlgeschlagen – fahre fort"
else
  echo "⚠️  Node.js nicht verfügbar – überspringe REST-Update für Kategorien"
fi

# Ergänze kuratierte Kategorien-Metafelder (Sektionstitel/-beschreibung) innerhalb von WP
cat > /tmp/update-category-metas.php << 'EOF'
<?php
require_once('/var/www/html/wp-load.php');

echo "=== Update Category Section Metas (ohne Description) ===\n\n";

$map_path = '/tmp/wordpress-enhanced-image-mappings-seo.json';
if (!file_exists($map_path)) { die("❌ Mapping not found: $map_path\n"); }
$json = json_decode(file_get_contents($map_path), true);

function map_slug($slug) {
  if ($slug === 'sicherheit') return 'sicherheitskoordination';
  if (strpos($slug, 'sicherheit-') === 0) return 'sicherheitskoordination-' . substr($slug, strlen('sicherheit-'));
  return $slug;
}

function update_cat_meta_for($slug, $data, $type = 'main') {
  $slug = map_slug($slug);
  $term = get_term_by('slug', $slug, 'category');
  if (!$term || is_wp_error($term)) { echo "⚠️  Kategorie nicht gefunden: $slug\n"; return; }

$mapped_label = isset($data['section_label']) ? $data['section_label'] : (isset($data['_section_label']) ? $data['_section_label'] : null);
$mapped_title = isset($data['section_title']) ? $data['section_title'] : (isset($data['_section_title']) ? $data['_section_title'] : null);
$mapped_desc  = isset($data['section_description']) ? $data['section_description'] : (isset($data['_section_description']) ? $data['_section_description'] : null);

$label = $mapped_label ?: 'DIE LEISTUNGEN';
$title = $mapped_title ?: 'Leistungen & Schwerpunkte der {category}';
$section_desc = $mapped_desc ?: (!empty($data['description']) ? $data['description'] : (!empty($data['caption']) ? $data['caption'] : (!empty($data['title']) ? $data['title'] : 'Ein Auszug unserer Kernkompetenzen – präzise, zuverlässig und fachgerecht umgesetzt.')));

  $slug_eff = $term->slug;
  $root = $slug_eff; $leaf = '';
  if (strpos($slug_eff, '-') !== false) { [$root, $leaf] = explode('-', $slug_eff, 2); }

  // Setze Überbegriff-Label je Root-Kategorie, falls im Mapping nicht explizit gesetzt
  if (empty($mapped_label)) {
    if ($root === 'rueckbau') { $label = 'NACHHALTIG'; }
    elseif ($root === 'altlasten') { $label = 'FACHGERECHT'; }
    elseif ($root === 'schadstoffe') { $label = 'KOMPETENT'; }
    elseif ($root === 'sicherheitskoordination') { $label = 'SICHER'; }
    elseif ($root === 'beratung') { $label = 'NEUTRAL'; }
  }

  if ($slug_eff === 'rueckbau') {
    if (!$mapped_title) $title = 'Rückbaumanagement – Leistungen & Schwerpunkte';
    if (!$mapped_desc) $section_desc = 'Planung, Ausschreibung, Durchführung, Entsorgung, Recycling und Dokumentation – sicher und ressourcenschonend.';
  } elseif ($slug_eff === 'altlasten') {
    if (!$mapped_title) $title = 'Altlastensanierung – Leistungen & Schwerpunkte';
    if (!$mapped_desc) $section_desc = 'Erkundung, Sanierungsplanung, Boden- und Grundwassersanierung sowie Monitoring – rechtssicher und nachhaltig.';
  } elseif ($slug_eff === 'schadstoffe') {
    if (!$mapped_title) $title = 'Schadstoff-Management – Leistungen & Schwerpunkte';
    if (!$mapped_desc) $section_desc = 'Asbest, PCB, PAK, KMF und Schwermetalle – Erkundung, Sanierung und Freimessung nach Regelwerk.';
  } elseif ($slug_eff === 'sicherheitskoordination') {
    if (!$mapped_title) $title = 'Sicherheitskoordination – Leistungen & Schwerpunkte';
    if (!$mapped_desc) $section_desc = 'SiGeKo in Planung und Ausführung, Arbeitsschutz, Gefährdungsbeurteilung und Notfallmanagement – normkonform organisiert.';
  } elseif ($slug_eff === 'beratung') {
    if (!$mapped_title) $title = 'Beratung & Mediation – Leistungen & Schwerpunkte';
    if (!$mapped_desc) $section_desc = 'Projektberatung, Mediation, Gutachten, Compliance und Schulungen – praxisnah und lösungsorientiert.';
  }

  if ($leaf) {
    if ($root === 'rueckbau') {
      if ($leaf === 'planung') { if (!$mapped_title) $title = '{category} – Planung & Konzept'; if (!$mapped_desc) $section_desc = 'BIM-gestützte Rückbauplanung: Bestandsaufnahme, Mengenermittlung, LV und Genehmigungen.'; }
      elseif ($leaf === 'ausschreibung') { if (!$mapped_title) $title = '{category} – Ausschreibung & Vergabe'; if (!$mapped_desc) $section_desc = 'Leistungsbeschreibungen, Vergabeunterlagen, Angebotsprüfung und Vergabebegleitung.'; }
      elseif ($leaf === 'durchfuehrung') { if (!$mapped_title) $title = '{category} – Durchführung & Steuerung'; if (!$mapped_desc) $section_desc = 'Sichere Ausführung, Baustellenlogistik, Qualitätssicherung und Monitoring.'; }
      elseif ($leaf === 'entsorgung') { if (!$mapped_title) $title = '{category} – Entsorgung & Stoffstrom'; if (!$mapped_desc) $section_desc = 'Rechtssichere Entsorgung, Nachweisführung und Kreislaufwirtschaft.'; }
      elseif ($leaf === 'recycling') { if (!$mapped_title) $title = '{category} – Recycling & Verwertung'; if (!$mapped_desc) $section_desc = 'Sortenreine Trennung, Wiederverwertung und Dokumentation nach GewAbfV.'; }
      elseif ($leaf === 'dokumentation') { if (!$mapped_title) $title = '{category} – Dokumentation & Nachweise'; if (!$mapped_desc) $section_desc = 'Lückenlose Nachweisführung, Fotodokumentation und Abschlussberichte.'; }
    } elseif ($root === 'altlasten') {
      if ($leaf === 'erkundung') { if (!$mapped_title) $title = '{category} – Erkundung & Bewertung'; if (!$mapped_desc) $section_desc = 'Historische Recherche, Probenahme und Gefährdungsabschätzung gemäß BBodSchV.'; }
      elseif ($leaf === 'sanierungsplanung') { if (!$mapped_title) $title = '{category} – Sanierungsplanung'; if (!$mapped_desc) $section_desc = 'Verfahrenswahl, Kosten- und Genehmigungsplanung, Ausschreibung.'; }
      elseif ($leaf === 'bodensanierung') { if (!$mapped_title) $title = '{category} – Bodensanierung'; if (!$mapped_desc) $section_desc = 'Technisch und rechtssicher: Aushub, Behandlung, Wiedereinbau und Qualitätssicherung.'; }
      elseif ($leaf === 'grundwassersanierung') { if (!$mapped_title) $title = '{category} – Grundwassersanierung'; if (!$mapped_desc) $section_desc = 'Pump & Treat, Barrieren, Begleitmessungen und Betriebskonzepte.'; }
      elseif ($leaf === 'monitoring') { if (!$mapped_title) $title = '{category} – Monitoring & Nachsorge'; if (!$mapped_desc) $section_desc = 'Langzeitüberwachung, Freigabekonzepte und Berichtswesen.'; }
    } elseif ($root === 'schadstoffe') {
      if ($leaf === 'asbest') { if (!$mapped_title) $title = '{category} – Asbestsanierung'; if (!$mapped_desc) $section_desc = 'Erkundung, Sanierung und Freimessung gemäß TRGS 519 – sicher und dokumentiert.'; }
      elseif ($leaf === 'pcb') { if (!$mapped_title) $title = '{category} – PCB‑Sanierung'; if (!$mapped_desc) $section_desc = 'Identifikation, Sanierung und Freigabemessung an Anlagen und Bauteilen.'; }
      elseif ($leaf === 'pak') { if (!$mapped_title) $title = '{category} – PAK‑Sanierung'; if (!$mapped_desc) $section_desc = 'Behandlung PAK‑belasteter Baustoffe – Verfahren und Nachweise.'; }
      elseif ($leaf === 'kmf') { if (!$mapped_title) $title = '{category} – KMF‑Sanierung'; if (!$mapped_desc) $section_desc = 'Sichere Entfernung künstlicher Mineralfasern inklusive Entsorgung.'; }
      elseif ($leaf === 'schwermetalle') { if (!$mapped_title) $title = '{category} – Schwermetalle'; if (!$mapped_desc) $section_desc = 'Analyse, Bewertung und Sanierung schwermetallbelasteter Substrate.'; }
    } elseif ($root === 'sicherheitskoordination') {
      if ($leaf === 'sigeko-planung') { if (!$mapped_title) $title = '{category} – SiGeKo in der Planung'; if (!$mapped_desc) $section_desc = 'SiGe‑Plan, Unterlage für spätere Arbeiten und Koordination mit Planungsteams.'; }
      elseif ($leaf === 'sigeko-ausfuehrung') { if (!$mapped_title) $title = '{category} – SiGeKo in der Ausführung'; if (!$mapped_desc) $section_desc = 'Baustellenkoordination, Unterweisungen und Kontrollen nach BaustellV.'; }
      elseif ($leaf === 'arbeitsschutz') { if (!$mapped_title) $title = '{category} – Arbeitsschutz'; if (!$mapped_desc) $section_desc = 'PSA‑Konzepte, Organisation und Wirksamkeitskontrollen.'; }
      elseif ($leaf === 'gefaehrdungsbeurteilung') { if (!$mapped_title) $title = '{category} – Gefährdungsbeurteilung'; if (!$mapped_desc) $section_desc = 'Risikoanalyse, Maßnahmenplanung und Dokumentation gemäß ArbSchG.'; }
      elseif ($leaf === 'notfallmanagement') { if (!$mapped_title) $title = '{category} – Notfallmanagement'; if (!$mapped_desc) $section_desc = 'Notfallplanung, Alarmierungsketten und Krisenübungen.'; }
    } elseif ($root === 'beratung') {
      if ($leaf === 'baumediation') { if (!$mapped_title) $title = '{category} – Baumediation'; if (!$mapped_desc) $section_desc = 'Strukturiert zu tragfähigen Lösungen – neutral, effizient, ergebnisorientiert.'; }
      elseif ($leaf === 'projektberatung') { if (!$mapped_title) $title = '{category} – Projektberatung'; if (!$mapped_desc) $section_desc = 'Organisation, Vergabe, Qualität und Risiko – praxisnah und umsetzbar.'; }
      elseif ($leaf === 'gutachten') { if (!$mapped_title) $title = '{category} – Gutachten'; if (!$mapped_desc) $section_desc = 'Sachverständige Bewertungen und Entscheidungsgrundlagen – transparent und belastbar.'; }
      elseif ($leaf === 'schulungen') { if (!$mapped_title) $title = '{category} – Schulungen & Seminare'; if (!$mapped_desc) $section_desc = 'Weiterbildung zu Umwelttechnik, Arbeitsschutz und Recht – aus der Praxis.'; }
      elseif ($leaf === 'compliance') { if (!$mapped_title) $title = '{category} – Compliance & Recht'; if (!$mapped_desc) $section_desc = 'Rechtskonformität, Zertifizierung und Dokumentation – sicher und nachvollziehbar.'; }
    }

  update_term_meta($term->term_id, '_section_label', $label);
  update_term_meta($term->term_id, '_section_title', $title);
  update_term_meta($term->term_id, '_section_description', $section_desc);
  if (function_exists('clean_term_cache')) { clean_term_cache($term->term_id, 'category'); }
  echo "✓ Meta aktualisiert: {$slug}\n";
}

if (!empty($json['main_categories'])) {
  foreach ($json['main_categories'] as $key => $mc) {
    $slug = isset($mc['slug']) ? $mc['slug'] : $key;
    update_cat_meta_for($slug, $mc, 'main');
  }
}
if (!empty($json['subcategories'])) {
  foreach ($json['subcategories'] as $parent => $subs) {
    foreach ($subs as $subkey => $sc) {
      $slug = isset($sc['slug']) ? $sc['slug'] : ($parent . '-' . $subkey);
      update_cat_meta_for($slug, $sc, 'sub');
    }
  }
}

echo "\nFertig.\n";
EOF

docker cp /tmp/update-category-metas.php "$WORDPRESS_CONTAINER:/var/www/html/"
docker exec "$WORDPRESS_CONTAINER" php /var/www/html/update-category-metas.php >/dev/null 2>&1 || true

# Execute import
docker exec "$WORDPRESS_CONTAINER" php /var/www/html/import-clean-seo.php

echo ""
echo "4. Führe finalen Featured-Image Fix aus..."
docker cp fix-featured-images-properly.php "$WORDPRESS_CONTAINER:/var/www/html/" 2>/dev/null
docker exec "$WORDPRESS_CONTAINER" php /var/www/html/fix-featured-images-properly.php

echo ""
echo "=== CLEAN SETUP KOMPLETT ==="
echo "👉 http://127.0.0.1:8801"
echo "Admin: admin / admin_password_123"
echo ""
echo "✅ Alle Duplikate wurden vermieden!"

# Optional: REST-Update am Ende erneut (falls nachträgliche Änderungen)
if command -v node >/dev/null 2>&1; then
  node scripts/wp-rest-update-categories.js --mapping "$SEO_MAPPINGS" >/dev/null 2>&1 || true
fi
