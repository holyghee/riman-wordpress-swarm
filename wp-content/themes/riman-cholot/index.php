<?php get_header(); ?>

<div class="riman-content">
    <div class="riman-container">
        <?php if (is_home() || is_front_page()): ?>
            <h1>Willkommen bei RIMAN GmbH</h1>
            <p>Ihr Partner für nachhaltigen Rückbau, Sanierung und Sicherheit im Bauwesen.</p>
            
            <div class="riman-service-grid">
                <?php
                $services = array(
                    array('id' => 181, 'title' => 'Rückbau & Abbruch', 'icon' => '🏗️'),
                    array('id' => 182, 'title' => 'Altlastensanierung', 'icon' => '🌍'),
                    array('id' => 183, 'title' => 'Schadstoffsanierung', 'icon' => '⚠️'),
                    array('id' => 184, 'title' => 'Arbeitssicherheit', 'icon' => '🦺'),
                    array('id' => 185, 'title' => 'Beratung & Mediation', 'icon' => '🤝')
                );
                
                foreach ($services as $service): ?>
                    <div class="riman-service-card">
                        <div class="riman-card-image">
                            <div style="height: 280px; background: linear-gradient(135deg, #1e4a6d, #4a7c59);"></div>
                        </div>
                        <div class="riman-card-icon">
                            <span style="font-size: 40px;"><?php echo $service['icon']; ?></span>
                        </div>
                        <div class="riman-card-content">
                            <h3 class="riman-card-title"><?php echo esc_html($service['title']); ?></h3>
                            <p class="riman-card-description">Professionelle Leistungen im Bereich <?php echo esc_html($service['title']); ?></p>
                            <a href="<?php echo home_url('/?page_id=' . $service['id']); ?>" class="riman-button">Mehr erfahren</a>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php else: ?>
            <?php if (have_posts()): ?>
                <?php while (have_posts()): the_post(); ?>
                    <?php riman_breadcrumb(); ?>
                    <article <?php post_class(); ?>>
                        <h1><?php the_title(); ?></h1>
                        <?php the_content(); ?>
                    </article>
                <?php endwhile; ?>
            <?php endif; ?>
        <?php endif; ?>
    </div>
</div>

<?php get_footer(); ?>