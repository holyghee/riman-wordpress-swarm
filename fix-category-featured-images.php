<?php
/**
 * Fix: Hole Featured Image von der verknüpften Seite für Kategorie-Ansicht
 * 
 * Dieses Script erweitert WordPress so, dass Kategorien das Featured Image
 * ihrer verknüpften Seite nutzen können
 */

// WordPress laden
require_once('/var/www/html/wp-load.php');

echo "=== Fix Kategorie Featured Images ===\n\n";

// OPTION 1: Setze Featured Images direkt für Kategorien (Term Meta)
echo "OPTION 1: Kopiere Featured Images von Seiten zu Kategorien...\n";

$categories = get_terms([
    'taxonomy' => 'category',
    'hide_empty' => false
]);

$fixed = 0;
$already_set = 0;

foreach ($categories as $category) {
    if ($category->slug === 'uncategorized') continue;
    
    // Prüfe ob Kategorie bereits ein Bild hat
    $existing = get_term_meta($category->term_id, '_thumbnail_id', true);
    
    if ($existing) {
        echo "  ✓ {$category->name}: Hat bereits Bild (ID: $existing)\n";
        $already_set++;
        continue;
    }
    
    // Finde verknüpfte Seite
    // Methode 1: Über Meta-Feld
    $linked_pages = get_posts([
        'post_type' => 'page',
        'meta_key' => '_linked_category_id',
        'meta_value' => $category->term_id,
        'numberposts' => 1
    ]);
    
    // Methode 2: Über gleichen Slug
    if (empty($linked_pages)) {
        $linked_pages = get_posts([
            'post_type' => 'page',
            'name' => $category->slug,
            'numberposts' => 1
        ]);
    }
    
    if (!empty($linked_pages)) {
        $page = $linked_pages[0];
        $page_thumbnail = get_post_thumbnail_id($page->ID);
        
        if ($page_thumbnail) {
            // Kopiere Featured Image zur Kategorie
            update_term_meta($category->term_id, '_thumbnail_id', $page_thumbnail);
            echo "  ✅ {$category->name}: Bild von Seite '{$page->post_title}' kopiert (ID: $page_thumbnail)\n";
            $fixed++;
        } else {
            echo "  ⚠️  {$category->name}: Verknüpfte Seite '{$page->post_title}' hat kein Bild\n";
        }
    } else {
        echo "  ⚠️  {$category->name}: Keine verknüpfte Seite gefunden\n";
    }
}

echo "\nErgebnis:\n";
echo "  ✅ $fixed Kategorien repariert\n";
echo "  ℹ️  $already_set Kategorien hatten bereits Bilder\n";

// OPTION 2: Erstelle Helper-Funktion für Templates
echo "\n=== Erstelle Helper-Funktion für Templates ===\n";

$functions_content = '<?php
/**
 * Helper-Funktion: Hole Featured Image für Kategorie
 * Prüft zuerst Term Meta, dann verknüpfte Seite
 */
function get_category_featured_image_id($term_id = null) {
    if (!$term_id) {
        $term_id = get_queried_object_id();
    }
    
    // 1. Versuche direkt von Kategorie
    $image_id = get_term_meta($term_id, "_thumbnail_id", true);
    
    if ($image_id) {
        return $image_id;
    }
    
    // 2. Hole von verknüpfter Seite
    $term = get_term($term_id);
    if (!$term || is_wp_error($term)) {
        return false;
    }
    
    // Suche Seite mit gleichem Slug
    $page = get_page_by_path($term->slug);
    
    // Oder über Meta-Verknüpfung
    if (!$page) {
        $linked_pages = get_posts([
            "post_type" => "page",
            "meta_key" => "_linked_category_id",
            "meta_value" => $term_id,
            "numberposts" => 1
        ]);
        $page = !empty($linked_pages) ? $linked_pages[0] : null;
    }
    
    if ($page) {
        return get_post_thumbnail_id($page->ID);
    }
    
    return false;
}

/**
 * Helper-Funktion: Zeige Featured Image für Kategorie
 */
function the_category_featured_image($size = "full", $attr = []) {
    $image_id = get_category_featured_image_id();
    
    if ($image_id) {
        echo wp_get_attachment_image($image_id, $size, false, $attr);
        return true;
    }
    
    return false;
}

/**
 * Helper-Funktion: Hole Featured Image URL für Kategorie
 */
function get_category_featured_image_url($size = "full") {
    $image_id = get_category_featured_image_id();
    
    if ($image_id) {
        $image = wp_get_attachment_image_src($image_id, $size);
        return $image ? $image[0] : false;
    }
    
    return false;
}

// Hook um Kategorie-Featured-Images im Admin anzuzeigen
add_action("category_add_form_fields", "add_category_image_field", 10, 2);
add_action("category_edit_form_fields", "edit_category_image_field", 10, 2);
add_action("created_category", "save_category_image", 10, 2);
add_action("edited_category", "save_category_image", 10, 2);

function add_category_image_field($taxonomy) {
    ?>
    <div class="form-field">
        <label for="category-image">Featured Image</label>
        <input type="hidden" id="category-image-id" name="category_image_id" value="">
        <div id="category-image-wrapper"></div>
        <input type="button" class="button" value="Bild auswählen" id="category-image-button">
        <input type="button" class="button" value="Bild entfernen" id="category-image-remove" style="display:none;">
    </div>
    <?php
}

function edit_category_image_field($term, $taxonomy) {
    $image_id = get_term_meta($term->term_id, "_thumbnail_id", true);
    ?>
    <tr class="form-field">
        <th scope="row"><label for="category-image">Featured Image</label></th>
        <td>
            <input type="hidden" id="category-image-id" name="category_image_id" value="<?php echo esc_attr($image_id); ?>">
            <div id="category-image-wrapper">
                <?php if ($image_id) : ?>
                    <?php echo wp_get_attachment_image($image_id, "thumbnail"); ?>
                <?php endif; ?>
            </div>
            <input type="button" class="button" value="Bild auswählen" id="category-image-button">
            <input type="button" class="button" value="Bild entfernen" id="category-image-remove" <?php echo $image_id ? "" : "style=\"display:none;\""; ?>>
        </td>
    </tr>
    <?php
}

function save_category_image($term_id, $tt_id = null) {
    if (isset($_POST["category_image_id"])) {
        update_term_meta($term_id, "_thumbnail_id", absint($_POST["category_image_id"]));
    }
}
';

// Füge zu functions.php hinzu
$theme_dir = '/var/www/html/wp-content/themes/';
$active_theme = get_option('stylesheet');
$functions_file = $theme_dir . $active_theme . '/functions.php';

if (file_exists($functions_file)) {
    $current = file_get_contents($functions_file);
    
    // Prüfe ob bereits vorhanden
    if (strpos($current, 'get_category_featured_image_id') === false) {
        file_put_contents($functions_file, $current . "\n\n" . $functions_content);
        echo_success("Helper-Funktionen zu functions.php hinzugefügt");
    } else {
        echo "  ℹ️  Helper-Funktionen bereits in functions.php vorhanden\n";
    }
} else {
    // Erstelle als Plugin
    $plugin_content = '<?php
/**
 * Plugin Name: Category Featured Images
 * Description: Ermöglicht Featured Images für Kategorien
 * Version: 1.0
 */

' . $functions_content;
    
    $plugin_file = '/var/www/html/wp-content/plugins/category-featured-images.php';
    file_put_contents($plugin_file, $plugin_content);
    
    // Aktiviere Plugin
    activate_plugin('category-featured-images.php');
    echo "  ✅ Plugin 'Category Featured Images' erstellt und aktiviert\n";
}

echo "\n=== FERTIG ===\n";
echo "Kategorien können jetzt Featured Images nutzen!\n\n";
echo "In Templates verwendbar mit:\n";
echo "  - get_category_featured_image_id() - Hole Image ID\n";
echo "  - the_category_featured_image() - Zeige Bild\n";
echo "  - get_category_featured_image_url() - Hole Bild-URL\n";
