<?php
/**
 * Title: Service Card
 * Slug: riman/service-card
 * Categories: featured
 * Description: Cholot design service card with elliptical image
 */
?>

<!-- wp:group {"className":"riman-service-card","layout":{"type":"default"}} -->
<div class="wp-block-group riman-service-card">
    <div class="riman-card-image">
        <!-- wp:image {"sizeSlug":"large"} -->
        <figure class="wp-block-image size-large">
            <img src="<?php echo get_stylesheet_directory_uri(); ?>/assets/images/placeholder.jpg" alt="Service Image"/>
        </figure>
        <!-- /wp:image -->
        <div class="riman-play-button"></div>
    </div>
    
    <div class="riman-card-icon">
        <svg xmlns="http://www.w3.org/2000/svg" width="40" height="40" fill="currentColor" viewBox="0 0 24 24">
            <path d="M12 2L2 7v10c0 5.55 3.84 10.74 9 12 5.16-1.26 9-6.45 9-12V7l-10-5z"/>
        </svg>
    </div>
    
    <div class="riman-card-content">
        <!-- wp:heading {"level":3,"className":"riman-card-title"} -->
        <h3 class="riman-card-title">Service Title</h3>
        <!-- /wp:heading -->
        
        <!-- wp:paragraph {"className":"riman-card-description"} -->
        <p class="riman-card-description">Professional service description with focus on quality and sustainability.</p>
        <!-- /wp:paragraph -->
        
        <!-- wp:buttons {"layout":{"type":"flex","justifyContent":"center"}} -->
        <div class="wp-block-buttons">
            <!-- wp:button {"className":"is-style-outline"} -->
            <div class="wp-block-button is-style-outline">
                <a class="wp-block-button__link wp-element-button" href="#">Learn More</a>
            </div>
            <!-- /wp:button -->
        </div>
        <!-- /wp:buttons -->
    </div>
</div>
<!-- /wp:group -->