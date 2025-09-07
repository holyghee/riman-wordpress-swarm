<?php
/**
 * Category Template
 * Zeigt Kategorieseiten mit Inhalt aus dem Codex
 */
get_header();

$category = get_queried_object();
$is_parent = $category->parent == 0;
$category_slug = $category->slug;

// Lade Content aus dem Codex für Hauptkategorien
$codex_content = '';
if ($is_parent) {
    $codex_content = riman_get_codex_content($category_slug);
}
?>

<div class="riman-content">
    <div class="riman-container">
        <?php riman_breadcrumb(); ?>
        
        <header class="category-header">
            <h1 class="category-title"><?php echo single_cat_title('', false); ?></h1>
        </header>
        
        <?php if ($is_parent && $codex_content): ?>
            <article class="category-main-content">
                <?php echo $codex_content; ?>
            </article>
        <?php elseif ($category->description): ?>
            <div class="category-description">
                <?php echo wpautop($category->description); ?>
            </div>
        <?php endif; ?>

        <?php if ($is_parent): ?>
            <!-- Service-Bereich mit Unterkategorien -->
            <div class="services-section">
                <div class="section-header">
                    <span class="section-label">DIE LEISTUNGEN</span>
                    <h2>Unsere <em><?php echo single_cat_title('', false); ?></em> im Überblick.</h2>
                    <p>Ein Auszug unserer Kernkompetenzen – präzise, zuverlässig und fachgerecht umgesetzt.</p>
                </div>
                
                <?php
                // Hole Unterkategorien aus WordPress
                $subcategories = get_categories(array(
                    'parent' => $category->term_id,
                    'hide_empty' => false
                ));
                
                // Hole zusätzliche Infos aus dem Codex
                $codex_subcats = riman_get_codex_subcategories($category_slug);
                
                if ($subcategories): ?>
                    <div class="riman-service-grid">
                        <?php foreach ($subcategories as $subcat): 
                            // Extrahiere nur den Haupttitel vor dem Doppelpunkt
                            $title_parts = explode(':', $subcat->name);
                            $title = trim($title_parts[0]);
                            
                            // Icons basierend auf Kategorie
                            $icons = array(
                                'erkundung' => '🔍',
                                'sanierungsplanung' => '📋',
                                'bodensanierung' => '🌍',
                                'grundwassersanierung' => '💧',
                                'monitoring' => '📊',
                                'wohnungsentkernungen' => '🏠',
                                'teilabbruch' => '🔨',
                                'komplettabbruch' => '🏗️',
                                'industrierueckbau' => '🏭',
                                'asbestsanierung' => '⚠️',
                                'pcb-sanierung' => '🧪',
                                'schimmelsanierung' => '🦠',
                                'kmf-sanierung' => '🧱',
                                'sige-koordination' => '🦺',
                                'gefaehrdungsbeurteilung' => '📝',
                                'arbeitsschutzbetreuung' => '👷',
                                'bauberatung' => '📐',
                                'umweltberatung' => '🌿',
                                'konfliktmediation' => '🤝'
                            );
                            
                            $icon = isset($icons[$subcat->slug]) ? $icons[$subcat->slug] : '◆';
                        ?>
                            <div class="riman-service-card">
                                <div class="riman-card-image">
                                    <div style="height: 280px; background: linear-gradient(135deg, #1e4a6d, #b68c2f); position: relative;">
                                        <?php 
                                        // Füge ein Bild-Placeholder hinzu
                                        $image_url = '/wp-content/themes/riman-cholot/assets/images/' . $subcat->slug . '.jpg';
                                        if (!file_exists(ABSPATH . $image_url)) {
                                            // Fallback gradient wenn Bild nicht existiert
                                            echo '<div style="width: 100%; height: 100%; background: linear-gradient(135deg, rgba(30,74,109,0.9), rgba(182,140,47,0.9));"></div>';
                                        } else {
                                            echo '<img src="' . $image_url . '" style="width: 100%; height: 100%; object-fit: cover;" alt="' . esc_attr($title) . '">';
                                        }
                                        ?>
                                    </div>
                                </div>
                                <div class="riman-card-icon">
                                    <span style="font-size: 40px;"><?php echo $icon; ?></span>
                                </div>
                                <div class="riman-card-content">
                                    <span class="card-label">LEISTUNG</span>
                                    <h3 class="riman-card-title"><?php echo esc_html($title); ?></h3>
                                    <p class="riman-card-description">
                                        <?php echo esc_html($subcat->description); ?>
                                    </p>
                                    <a href="<?php echo get_category_link($subcat->term_id); ?>" class="riman-button">
                                        Mehr erfahren →
                                    </a>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
            <?php endif; ?>
            
        <?php else: ?>
            <!-- Unterkategorie: Zeige Posts -->
            <?php if (have_posts()): ?>
                <div class="posts-grid">
                    <?php while (have_posts()): the_post(); ?>
                        <article class="post-card">
                            <h2><a href="<?php the_permalink(); ?>"><?php the_title(); ?></a></h2>
                            <div class="post-meta">
                                Veröffentlicht am <?php echo get_the_date(); ?>
                            </div>
                            <div class="post-excerpt">
                                <?php the_excerpt(); ?>
                            </div>
                            <a href="<?php the_permalink(); ?>" class="read-more">Weiterlesen →</a>
                        </article>
                    <?php endwhile; ?>
                </div>
                
                <div class="pagination">
                    <?php
                    echo paginate_links(array(
                        'prev_text' => '← Zurück',
                        'next_text' => 'Weiter →'
                    ));
                    ?>
                </div>
            <?php else: ?>
                <p>In dieser Kategorie sind noch keine Beiträge vorhanden.</p>
            <?php endif; ?>
        <?php endif; ?>
    </div>
</div>

<style>
.category-header {
    margin-bottom: 40px;
}

.category-title {
    font-size: 2.5rem;
    color: var(--riman-blue);
    margin-bottom: 20px;
}

.category-description {
    font-size: 1.1rem;
    line-height: 1.6;
    color: #666;
    background: #f5f5f5;
    padding: 20px;
    border-radius: 8px;
    border-left: 4px solid var(--riman-gold);
}

.category-main-content {
    margin-bottom: 60px;
    line-height: 1.8;
}

.category-main-content h2 {
    color: var(--riman-blue);
    margin: 30px 0 15px;
    font-size: 1.8rem;
}

.category-main-content h3 {
    color: var(--riman-gold);
    margin: 25px 0 12px;
    font-size: 1.4rem;
}

.category-main-content ul {
    margin: 15px 0 15px 30px;
}

.category-main-content ul li {
    margin-bottom: 8px;
}

.category-main-content strong {
    color: var(--riman-blue);
}

.services-section {
    margin-top: 60px;
    padding-top: 60px;
    border-top: 2px solid #f0f0f0;
}

.section-header {
    text-align: center;
    margin-bottom: 50px;
}

.section-label {
    display: inline-block;
    background: var(--riman-gold);
    color: white;
    padding: 4px 16px;
    border-radius: 20px;
    font-size: 0.85rem;
    font-weight: 600;
    letter-spacing: 1px;
    margin-bottom: 15px;
}

.section-header h2 {
    font-size: 2.2rem;
    color: var(--riman-blue);
    margin-bottom: 10px;
}

.section-header h2 em {
    color: var(--riman-gold);
    font-style: normal;
}

.section-header p {
    color: #666;
    font-size: 1.1rem;
}

.card-label {
    display: inline-block;
    background: rgba(182, 140, 47, 0.1);
    color: var(--riman-gold);
    padding: 3px 10px;
    border-radius: 12px;
    font-size: 0.75rem;
    font-weight: 600;
    letter-spacing: 0.5px;
    margin-bottom: 12px;
}

.posts-grid {
    display: grid;
    gap: 30px;
    margin: 40px 0;
}

.post-card {
    background: white;
    padding: 30px;
    border-radius: 8px;
    box-shadow: 0 2px 10px rgba(0,0,0,0.1);
    transition: transform 0.3s ease;
}

.post-card:hover {
    transform: translateY(-5px);
    box-shadow: 0 5px 20px rgba(0,0,0,0.15);
}

.post-card h2 {
    margin-bottom: 10px;
    font-size: 1.5rem;
}

.post-card h2 a {
    color: var(--riman-blue);
    text-decoration: none;
}

.post-card h2 a:hover {
    color: var(--riman-gold);
}

.post-meta {
    color: #999;
    font-size: 0.9rem;
    margin-bottom: 15px;
}

.post-excerpt {
    line-height: 1.6;
    margin-bottom: 15px;
}

.read-more {
    color: var(--riman-gold);
    text-decoration: none;
    font-weight: 500;
}

.read-more:hover {
    color: var(--riman-blue);
}

.pagination {
    margin: 40px 0;
    text-align: center;
}

.pagination a,
.pagination span {
    padding: 8px 12px;
    margin: 0 5px;
    background: white;
    border: 1px solid #ddd;
    text-decoration: none;
    color: var(--riman-blue);
    border-radius: 4px;
}

.pagination .current {
    background: var(--riman-gold);
    color: white;
    border-color: var(--riman-gold);
}
</style>

<?php get_footer(); ?>
