<!DOCTYPE html>
<html <?php language_attributes(); ?>>
<head>
    <meta charset="<?php bloginfo('charset'); ?>">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <?php wp_head(); ?>
</head>
<body <?php body_class(); ?>>
<?php wp_body_open(); ?>

<header class="riman-header">
    <div class="riman-header-inner">
        <div class="riman-logo">
            <a href="<?php echo home_url(); ?>"><?php bloginfo('name'); ?></a>
        </div>
        
        <nav class="riman-nav">
            <?php
            // Direkt Menüpunkte ausgeben
            $menu_items = array(
                'Startseite' => home_url(),
                'Rückbau & Abbruch' => home_url('/?page_id=181'),
                'Altlastensanierung' => home_url('/?page_id=182'),
                'Schadstoffsanierung' => home_url('/?page_id=183'),
                'Arbeitssicherheit' => home_url('/?page_id=184'),
                'Beratung & Mediation' => home_url('/?page_id=185')
            );
            
            echo '<ul class="riman-nav-menu">';
            foreach ($menu_items as $title => $url) {
                echo '<li class="riman-nav-item">';
                echo '<a href="' . esc_url($url) . '" class="riman-nav-link">' . esc_html($title) . '</a>';
                echo '</li>';
            }
            echo '</ul>';
            ?>
        </nav>
    </div>
</header>

<main id="main" class="site-main">