// Console Debug Scripts für Video-Poster-Funktionalität
// Kopiere diese Scripts in die Browser-Console auf einer Seite mit Cover Block Videos

// 1. GRUNDLEGENDE ÜBERPRÜFUNG
console.log('=== RIMAN Video-Poster Debug ===');

// Finde alle Cover Blocks mit Videos
const coverBlocks = document.querySelectorAll('.wp-block-cover, .riman-page-hero');
const videoCoverBlocks = Array.from(coverBlocks).filter(block =>
    block.querySelector('video') && block.classList.contains('riman-cover--has-poster')
);

console.log(`Cover Blocks mit Videos gefunden: ${videoCoverBlocks.length}`);
videoCoverBlocks.forEach((block, index) => {
    console.log(`Block ${index + 1}:`, block);

    const video = block.querySelector('video');
    const posterUrl = block.dataset.rimanPoster || video.getAttribute('poster');
    const backgroundImage = getComputedStyle(block).backgroundImage;

    console.log(`  Video:`, video);
    console.log(`  Poster URL:`, posterUrl);
    console.log(`  Background Image:`, backgroundImage);
    console.log(`  Video preload:`, video.getAttribute('preload'));
    console.log(`  Video data-src:`, video.getAttribute('data-src'));
    console.log(`  Video opacity:`, getComputedStyle(video).opacity);
    console.log(`  Video Klassen:`, video.className);
});

// 2. LAZY LOADING STATUS PRÜFEN
console.log('\n=== Lazy Loading Status ===');
const lazyVideos = document.querySelectorAll('video[data-riman-cover-lazy]');
console.log(`Videos mit Lazy Loading: ${lazyVideos.length}`);

lazyVideos.forEach((video, index) => {
    console.log(`Video ${index + 1}:`);
    console.log(`  Poster Status:`, video.dataset.rimanPosterStatus);
    console.log(`  Src geladen:`, !!video.getAttribute('src'));
    console.log(`  Data-src:`, video.getAttribute('data-src'));
    console.log(`  Preload:`, video.getAttribute('preload'));
    console.log(`  Network State:`, video.networkState);
    console.log(`  Ready State:`, video.readyState);
});

// 3. LCP-VERBESSERUNG MESSEN
console.log('\n=== LCP Performance Test ===');

// Performance Observer für LCP
if ('PerformanceObserver' in window) {
    const observer = new PerformanceObserver((list) => {
        const entries = list.getEntries();
        const lastEntry = entries[entries.length - 1];
        console.log('LCP Element:', lastEntry.element);
        console.log('LCP Value:', lastEntry.startTime, 'ms');
        console.log('LCP URL:', lastEntry.url);
    });
    observer.observe({ entryTypes: ['largest-contentful-paint'] });

    // Nach 3 Sekunden stoppen
    setTimeout(() => {
        observer.disconnect();
        console.log('LCP Measurement beendet');
    }, 3000);
}

// 4. NETWORK REQUESTS ÜBERWACHEN
console.log('\n=== Network Monitoring ===');
const originalFetch = window.fetch;
const videoRequests = [];

window.fetch = function(...args) {
    const url = args[0];
    if (typeof url === 'string' && (url.includes('.mp4') || url.includes('.webm') || url.includes('video'))) {
        console.log('Video Request:', url);
        videoRequests.push({ url, timestamp: Date.now() });
    }
    return originalFetch.apply(this, args);
};

// Nach 5 Sekunden Report
setTimeout(() => {
    console.log('\n=== Video Request Report ===');
    console.log(`Video Requests: ${videoRequests.length}`);
    videoRequests.forEach((req, index) => {
        console.log(`${index + 1}. ${req.url} (${req.timestamp})`);
    });

    // Fetch zurücksetzen
    window.fetch = originalFetch;
}, 5000);

// 5. INTERSECTION OBSERVER TEST
console.log('\n=== Intersection Observer Test ===');
lazyVideos.forEach((video, index) => {
    const observer = new IntersectionObserver((entries) => {
        entries.forEach(entry => {
            if (entry.isIntersecting) {
                console.log(`Video ${index + 1} ist sichtbar - sollte jetzt laden`);
                console.log('Video Element:', entry.target);
                console.log('Intersection Ratio:', entry.intersectionRatio);
            }
        });
    }, { threshold: 0.1 });

    observer.observe(video);
});

// 6. POSTER IMAGE LOADING TEST
console.log('\n=== Poster Image Loading Test ===');
videoCoverBlocks.forEach((block, index) => {
    const posterUrl = block.dataset.rimanPoster;
    if (posterUrl) {
        const img = new Image();
        const startTime = performance.now();

        img.onload = () => {
            const loadTime = performance.now() - startTime;
            console.log(`Poster ${index + 1} geladen in ${loadTime.toFixed(2)}ms`);
        };

        img.onerror = () => {
            console.error(`Poster ${index + 1} konnte nicht geladen werden:`, posterUrl);
        };

        img.src = posterUrl;
    }
});

console.log('\n=== Debug Setup komplett ===');
console.log('Warte auf Ergebnisse...');