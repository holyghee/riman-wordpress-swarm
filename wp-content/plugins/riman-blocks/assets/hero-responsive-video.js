/**
 * RIMAN Hero Responsive Video Loading
 * Lädt automatisch die optimale Video-Version für Hero-Sections basierend auf Viewport-Größe
 */

document.addEventListener('DOMContentLoaded', function() {

    // Responsive Video-Auswahl für Hero Videos
    function initHeroResponsiveVideos() {
        const heroVideos = document.querySelectorAll('[data-riman-responsive-video="1"].riman-hero__video');

        console.log('🎬 Initializing hero responsive videos:', heroVideos.length);

        heroVideos.forEach(video => {
            updateHeroVideoSource(video);
        });
    }

    // Hero Video-Source basierend auf Viewport-Größe aktualisieren
    function updateHeroVideoSource(video) {
        const isMobile = window.innerWidth <= 780;
        const mobileSource = video.dataset.srcMobile;
        const desktopSource = video.dataset.srcDesktop || video.dataset.src;

        let targetSource = desktopSource;
        let sourceType = 'desktop';

        // Mobile-Version verwenden falls verfügbar und auf Mobile
        if (isMobile && mobileSource) {
            targetSource = mobileSource;
            sourceType = 'mobile (9:16 Portrait)';
        }

        // Source nur ändern wenn nötig
        if (video.dataset.src !== targetSource) {
            console.log(`🎯 Hero Video switching to ${sourceType}:`, targetSource);

            // Video pausieren vor Source-Wechsel
            if (!video.paused) {
                video.pause();
            }

            // Neue Source setzen - WICHTIG: Vor Cover-Lazy-System
            video.dataset.src = targetSource;

            // Source-Element auch aktualisieren
            const sourceEl = video.querySelector('source');
            if (sourceEl) {
                sourceEl.dataset.src = targetSource;
            }

            // Video-Attribut für CSS/JS markieren
            video.setAttribute('data-current-source', sourceType);

            console.log(`✅ Hero Video data-src updated to: ${targetSource}`);
        } else {
            console.log(`🎯 Hero Video already using ${sourceType}:`, targetSource);
        }
    }

    // Resize-Handler für dynamische Anpassung
    let resizeTimeout;
    function handleResize() {
        clearTimeout(resizeTimeout);
        resizeTimeout = setTimeout(() => {
            const heroVideos = document.querySelectorAll('[data-riman-responsive-video="1"].riman-hero__video');
            heroVideos.forEach(updateHeroVideoSource);
        }, 250);
    }

    // Event-Listener
    window.addEventListener('resize', handleResize);

    // Initialisierung
    initHeroResponsiveVideos();

    // Öffentliche API für dynamisches Hinzufügen von Hero Videos
    window.RimanHeroResponsiveVideo = {
        updateHeroVideoSource: updateHeroVideoSource,
        initHeroResponsiveVideos: initHeroResponsiveVideos
    };

    // Debug-Informationen
    console.log('🎬 RIMAN Hero Responsive Video JS geladen');

});