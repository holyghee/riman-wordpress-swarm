<?php
/**
 * Script zum Aktualisieren der Navigation mit Query Loop Blocks
 * Fügt für jede Hauptkategorie Submenüs mit Query Loops hinzu
 */

// WordPress laden
if (file_exists(__DIR__ . '/wp-load.php')) {
    require_once(__DIR__ . '/wp-load.php');
} else {
    require_once('/var/www/html/wp-load.php');
}

echo "=== Aktualisiere Navigation mit Query Loop Blocks ===\n\n";

// Hole die Haupt-Navigation (ID 151 wie in der URL)
$navigation_post = get_post(151);
if (!$navigation_post) {
    echo "❌ Navigation mit ID 151 nicht gefunden\n";
    exit;
}

echo "✓ Navigation gefunden: " . $navigation_post->post_title . "\n\n";

// Hauptkategorien
$main_categories = [
    'rueckbau' => 'Rückbaumanagement',
    'altlasten' => 'Altlastensanierung', 
    'schadstoffe' => 'Schadstoff-Management',
    'sicherheitskoordination' => 'Sicherheitskoordination',
    'beratung' => 'Beratung & Mediation'
];

// Erstelle neue Navigation-Struktur mit Query Loops
$navigation_blocks = [];

foreach ($main_categories as $slug => $name) {
    $category = get_category_by_slug($slug);
    if (!$category) {
        echo "⚠ Kategorie nicht gefunden: $slug\n";
        continue;
    }
    
    // Hole Unterkategorien
    $subcategories = get_categories([
        'parent' => $category->term_id,
        'hide_empty' => false,
        'orderby' => 'name',
        'order' => 'ASC'
    ]);
    
    // Erstelle Submenu-Struktur
    $submenu_items = [];
    
    // Link zur Hauptkategorie
    $submenu_items[] = sprintf(
        '<!-- wp:navigation-link {"label":"%s","url":"%s","kind":"category","isTopLevelLink":false} /-->',
        'Alle ' . esc_html($name) . ' →',
        esc_url(get_category_link($category->term_id))
    );
    
    // Spacer
    $submenu_items[] = '<!-- wp:spacer {"height":"10px"} -->
<div style="height:10px" aria-hidden="true" class="wp-block-spacer"></div>
<!-- /wp:spacer -->';
    
    // Unterkategorien mit Query Loops
    foreach ($subcategories as $subcat) {
        // Unterkategorie als Überschrift
        $submenu_items[] = sprintf(
            '<!-- wp:navigation-link {"label":"%s","url":"%s","kind":"category","isTopLevelLink":false} /-->',
            esc_html($subcat->name),
            esc_url(get_category_link($subcat->term_id))
        );
        
        // Query Loop für Posts dieser Unterkategorie
        $query_loop = sprintf(
            '<!-- wp:query {"queryId":1,"query":{"perPage":3,"pages":0,"offset":0,"postType":"post","order":"desc","orderBy":"date","author":"","search":"","exclude":[],"sticky":"","inherit":false,"taxQuery":{"category":[%d]},"parents":[]},"tagName":"ul","className":"submenu-posts"} -->
<ul class="submenu-posts">
<!-- wp:post-template -->
<!-- wp:post-title {"level":0,"isLink":true,"fontSize":"small"} /-->
<!-- /wp:post-template -->
</ul>
<!-- /wp:query -->',
            $subcat->term_id
        );
        
        $submenu_items[] = $query_loop;
    }
    
    // Erstelle Navigation Submenu Block
    $navigation_item = sprintf(
        '<!-- wp:navigation-submenu {"label":"%s","url":"%s","kind":"category","isTopLevelItem":true} -->
%s
<!-- /wp:navigation-submenu -->',
        esc_html($name),
        esc_url(get_category_link($category->term_id)),
        implode("\n", $submenu_items)
    );
    
    $navigation_blocks[] = $navigation_item;
    
    echo "✓ Submenu erstellt für: $name\n";
}

// Erstelle finale Navigation-Struktur
$navigation_content = '<!-- wp:navigation {"ref":151,"overlayMenu":"mobile"} -->
' . implode("\n", $navigation_blocks) . '
<!-- /wp:navigation -->';

// Aktualisiere Navigation Post
$update_result = wp_update_post([
    'ID' => 151,
    'post_content' => $navigation_content
]);

if ($update_result) {
    echo "\n✅ Navigation erfolgreich aktualisiert!\n";
    echo "Bitte prüfe die Navigation unter: http://127.0.0.1:8801/wp-admin/site-editor.php?postId=151&postType=wp_navigation&canvas=edit\n";
} else {
    echo "\n❌ Fehler beim Aktualisieren der Navigation\n";
}

// Alternative: Erstelle ein Pattern für die Navigation
$pattern_content = '<!-- wp:group {"layout":{"type":"constrained"}} -->
<div class="wp-block-group">
<!-- wp:heading {"level":3} -->
<h3>Navigation mit Query Loops</h3>
<!-- /wp:heading -->

<!-- wp:paragraph -->
<p>Diese Navigation zeigt automatisch die neuesten Posts pro Kategorie:</p>
<!-- /wp:paragraph -->

' . implode("\n", $navigation_blocks) . '
</div>
<!-- /wp:group -->';

// Speichere als Reusable Block
$reusable_block = wp_insert_post([
    'post_title' => 'Mega Menu mit Query Loops',
    'post_content' => $pattern_content,
    'post_status' => 'publish',
    'post_type' => 'wp_block'
]);

if ($reusable_block) {
    echo "\n✅ Reusable Block erstellt (ID: $reusable_block)\n";
    echo "Du kannst diesen Block im Editor verwenden.\n";
}

echo "\n=== Fertig ===\n";