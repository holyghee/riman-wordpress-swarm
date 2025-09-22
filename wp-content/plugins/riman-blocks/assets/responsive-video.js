/**
 * RIMAN Responsive Video Loading
 * Lädt automatisch die optimale Video-Version basierend auf Viewport-Größe
 */

document.addEventListener('DOMContentLoaded', function() {

    // Responsive Video-Auswahl für alle Videos mit data-riman-responsive-video
    function initResponsiveVideos() {
        const videos = document.querySelectorAll('[data-riman-responsive-video="1"]');

        console.log('📺 Initializing responsive videos:', videos.length);

        videos.forEach(video => {
            updateVideoSource(video);
        });
    }

    // Video-Source basierend auf Viewport-Größe aktualisieren
    function updateVideoSource(video) {
        const isMobile = window.innerWidth <= 780;
        const mobileSource = video.dataset.srcMobile;
        const desktopSource = video.dataset.srcDesktop || video.src;

        let targetSource = desktopSource;
        let sourceType = 'desktop';

        // Mobile-Version verwenden falls verfügbar und auf Mobile
        if (isMobile && mobileSource) {
            targetSource = mobileSource;
            sourceType = 'mobile';
        }

        // Source nur ändern wenn nötig
        if (video.src !== targetSource) {
            console.log(`📱 Switching to ${sourceType} video:`, targetSource);

            // Video pausieren vor Source-Wechsel
            video.pause();
            video.currentTime = 0;

            // Neue Source setzen
            video.src = targetSource;

            // Source-Element auch aktualisieren
            const sourceEl = video.querySelector('source');
            if (sourceEl) {
                sourceEl.src = targetSource;
            }

            // Video neu laden
            video.load();

            // Video-Attribut für CSS/JS markieren
            video.setAttribute('data-current-source', sourceType);
        }
    }

    // Resize-Handler für dynamische Anpassung
    let resizeTimeout;
    function handleResize() {
        clearTimeout(resizeTimeout);
        resizeTimeout = setTimeout(() => {
            const videos = document.querySelectorAll('[data-riman-responsive-video="1"]');
            videos.forEach(updateVideoSource);
        }, 250);
    }

    // Event-Listener
    window.addEventListener('resize', handleResize);

    // Initialisierung
    initResponsiveVideos();

    // Öffentliche API für dynamisches Hinzufügen von Videos
    window.RimanResponsiveVideo = {
        updateVideoSource: updateVideoSource,
        initResponsiveVideos: initResponsiveVideos
    };

});