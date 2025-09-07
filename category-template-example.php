<?php
/**
 * Category Template mit Featured Image von verknüpfter Seite
 */

// In deinem category.php oder archive.php Template:

// Hole aktuelle Kategorie
$category = get_queried_object();

// METHODE 1: Direkter Weg - Hole Featured Image von verknüpfter Seite
$featured_image_url = false;

// Versuche zuerst Term Meta (falls gesetzt)
$image_id = get_term_meta($category->term_id, '_thumbnail_id', true);

if (!$image_id) {
    // Hole von verknüpfter Seite mit gleichem Slug
    $linked_page = get_page_by_path($category->slug);
    
    if (!$linked_page) {
        // Alternative: Über Meta-Verknüpfung
        $linked_pages = get_posts([
            'post_type' => 'page',
            'meta_key' => '_linked_category_id',
            'meta_value' => $category->term_id,
            'numberposts' => 1
        ]);
        $linked_page = !empty($linked_pages) ? $linked_pages[0] : null;
    }
    
    if ($linked_page) {
        $image_id = get_post_thumbnail_id($linked_page->ID);
    }
}

// Hole URL wenn Image ID vorhanden
if ($image_id) {
    $featured_image_url = wp_get_attachment_image_url($image_id, 'full');
}

?>

<!-- Hero Section mit Featured Image -->
<section class="hero-section" <?php if($featured_image_url): ?>style="background-image: url('<?php echo esc_url($featured_image_url); ?>');"<?php endif; ?>>
    <div class="hero-content">
        <h1><?php echo esc_html($category->name); ?></h1>
        <p><?php echo esc_html($category->description); ?></p>
    </div>
</section>

<!-- ODER als IMG Tag -->
<?php if($image_id): ?>
    <div class="category-featured-image">
        <?php echo wp_get_attachment_image($image_id, 'full', false, ['class' => 'hero-image']); ?>
    </div>
<?php endif; ?>

<!-- METHODE 2: Mit Helper-Funktion (nach Installation des Fix-Scripts) -->
<?php if(function_exists('get_category_featured_image_url')): ?>
    <?php $hero_image = get_category_featured_image_url('full'); ?>
    <?php if($hero_image): ?>
        <section class="hero-section" style="background-image: url('<?php echo esc_url($hero_image); ?>');">
            <!-- Hero Content -->
        </section>
    <?php endif; ?>
<?php endif; ?>

<!-- METHODE 3: Fallback mit Placeholder -->
<?php
$hero_image = false;

// Versuche Featured Image zu holen
if (function_exists('get_category_featured_image_url')) {
    $hero_image = get_category_featured_image_url('full');
} else {
    // Manueller Fallback
    $linked_page = get_page_by_path($category->slug);
    if ($linked_page) {
        $hero_image = get_the_post_thumbnail_url($linked_page->ID, 'full');
    }
}

// Fallback zu Placeholder
if (!$hero_image) {
    $hero_image = get_template_directory_uri() . '/assets/images/default-hero.jpg';
}
?>

<section class="hero-section" style="background-image: url('<?php echo esc_url($hero_image); ?>');">
    <div class="container">
        <h1><?php single_cat_title(); ?></h1>
        <?php if (category_description()): ?>
            <div class="category-description">
                <?php echo category_description(); ?>
            </div>
        <?php endif; ?>
    </div>
</section>
