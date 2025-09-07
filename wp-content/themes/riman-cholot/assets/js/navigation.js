jQuery(document).ready(function($) {
    // Fix HTML entities in menu items
    $('.menu-item a, .nav-menu a, .wp-block-navigation-item__content').each(function() {
        var text = $(this).html();
        if (text.includes('&amp;') || text.includes('amp;')) {
            text = text.replace(/&amp;amp;/g, '&');
            text = text.replace(/&amp;/g, '&');
            text = text.replace(/amp;/g, '&');
            $(this).html(text);
        }
    });
    
    // Mega menu functionality
    $('.riman-nav-item').hover(
        function() {
            $(this).find('.riman-mega-menu').addClass('active');
        },
        function() {
            $(this).find('.riman-mega-menu').removeClass('active');
        }
    );
    
    // Mobile menu toggle
    $('.mobile-menu-toggle').click(function() {
        $('.riman-nav').toggleClass('mobile-active');
    });
    
    // Smooth scroll for anchor links
    $('a[href*="#"]:not([href="#"])').click(function() {
        if (location.pathname.replace(/^\//,'') == this.pathname.replace(/^\//,'') && location.hostname == this.hostname) {
            var target = $(this.hash);
            target = target.length ? target : $('[name=' + this.hash.slice(1) +']');
            if (target.length) {
                $('html, body').animate({
                    scrollTop: target.offset().top - 100
                }, 800);
                return false;
            }
        }
    });
    
    // Service card hover effects
    $('.riman-service-card').hover(
        function() {
            $(this).find('.riman-play-button').fadeIn(200);
        },
        function() {
            $(this).find('.riman-play-button').fadeOut(200);
        }
    );
    
    // Initialize play buttons as hidden
    $('.riman-play-button').hide();
    
    // Sticky header on scroll
    var header = $('.riman-header');
    var headerHeight = header.outerHeight();
    var scrollPoint = 100;
    
    $(window).scroll(function() {
        if ($(this).scrollTop() > scrollPoint) {
            header.addClass('scrolled');
            $('body').css('padding-top', headerHeight);
        } else {
            header.removeClass('scrolled');
            $('body').css('padding-top', 0);
        }
    });
});