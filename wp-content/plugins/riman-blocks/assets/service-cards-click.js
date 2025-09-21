/**
 * Service Cards Click Handler - WordPress FSE Button Integration
 */
document.addEventListener('DOMContentLoaded', function() {
    // Handle clicks on service cards with buttons (rendered as divs)
    const serviceCardsWithButtons = document.querySelectorAll('.riman-service-card[data-href]');

    serviceCardsWithButtons.forEach(function(card) {
        card.addEventListener('click', function(e) {
            // Don't trigger if clicking on the button itself
            if (e.target.closest('.wp-block-button__link')) {
                return;
            }

            const href = card.getAttribute('data-href');
            if (href) {
                // Open in same window (can be changed to window.open() for new window)
                window.location.href = href;
            }
        });
    });
});