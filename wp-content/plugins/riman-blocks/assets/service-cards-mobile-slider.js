/**
 * RIMAN Service Cards Mobile Slider
 * REBUILT FROM SCRATCH - SIMPLE AND RELIABLE
 */

document.addEventListener('DOMContentLoaded', function() {
    console.log('🚀 SIMPLE Slider: Starting...');

    // Find all service card containers
    const containers = document.querySelectorAll('.riman-service-cards-wrap');
    console.log('📱 Found containers:', containers.length);

    containers.forEach((container, index) => {
        // Only on mobile
        if (window.innerWidth > 780) {
            console.log('⏭️ Skipping - desktop mode');
            return;
        }

        console.log(`🏗️ Processing container ${index + 1}`);

        // Find service cards
        const cards = container.querySelectorAll('.riman-service-card');
        console.log('🃏 Found cards:', cards.length);

        if (cards.length < 2) {
            console.log('❌ Not enough cards for slider');
            return;
        }

        // Create simple slider
        createSimpleSlider(container, cards);
    });
});

function createSimpleSlider(container, cards) {
    console.log('🔧 Creating SIMPLE slider...');

    // Hide original grid (cards will be moved to slider)
    const grid = container.querySelector('.riman-service-grid');
    if (grid) {
        grid.style.display = 'none';
        console.log('✅ Original grid hidden');
    } else {
        console.log('❌ Grid not found - selector may be wrong');
    }

    // Create slider wrapper
    const sliderWrapper = document.createElement('div');
    sliderWrapper.className = 'simple-slider-wrapper';
    sliderWrapper.style.cssText = `
        position: relative;
        width: 100%;
        overflow: hidden;
        margin: 20px 0;
    `;

    // Create slider track
    const sliderTrack = document.createElement('div');
    sliderTrack.className = 'simple-slider-track';
    sliderTrack.style.cssText = `
        display: flex;
        width: ${cards.length * 100}%;
        transition: transform 0.3s ease;
        transform: translateX(0%);
    `;

    // Add cards to slider
    cards.forEach((card, index) => {
        const slide = document.createElement('div');
        slide.className = 'simple-slide';
        slide.style.cssText = `
            flex: 0 0 ${100 / cards.length}%;
            width: ${100 / cards.length}%;
            padding: 0 10px;
            box-sizing: border-box;
        `;

        // Clone the card and preserve video data (enhanced from working commit)
        const cardClone = card.cloneNode(true);

        // Preserve ALL video data like the working system
        const originalVideo = card.querySelector('.riman-card-video');
        const clonedVideo = cardClone.querySelector('.riman-card-video');

        if (originalVideo && clonedVideo) {
            // Copy ALL video source data attributes
            const videoDataAttrs = ['src', 'srcMobile', 'srcDesktop', 'poster', 'posterMobile', 'posterDesktop'];
            videoDataAttrs.forEach(attr => {
                if (originalVideo.dataset[attr]) {
                    clonedVideo.dataset[attr] = originalVideo.dataset[attr];
                }
            });

            // Copy video properties
            if (originalVideo.src) clonedVideo.src = originalVideo.src;
            if (originalVideo.poster) clonedVideo.poster = originalVideo.poster;
            if (originalVideo.muted !== undefined) clonedVideo.muted = originalVideo.muted;
            if (originalVideo.loop !== undefined) clonedVideo.loop = originalVideo.loop;
            if (originalVideo.autoplay !== undefined) clonedVideo.autoplay = originalVideo.autoplay;

            // Copy card-level video data
            if (card.dataset.videoSrc) {
                cardClone.dataset.videoSrc = card.dataset.videoSrc;
            }

            // Copy video classes and states
            if (originalVideo.className) {
                clonedVideo.className = originalVideo.className;
            }

            console.log('🎬 Enhanced video data preserved:', {
                src: clonedVideo.dataset.src,
                srcMobile: clonedVideo.dataset.srcMobile,
                srcDesktop: clonedVideo.dataset.srcDesktop,
                cardVideoSrc: cardClone.dataset.videoSrc
            });
        }

        // Initialize responsive video selection for cloned card
        if (cardClone.querySelector('.riman-card-video')) {
            initializeResponsiveVideo(cardClone);
        }

        // CRITICAL: Ensure poster is visible IMMEDIATELY in cloned card
        const clonedPoster = cardClone.querySelector('.riman-card-poster');
        if (clonedPoster) {
            clonedPoster.style.cssText = `
                opacity: 1 !important;
                z-index: 2 !important;
                display: block !important;
                position: relative !important;
                visibility: visible !important;
                width: 100% !important;
                height: 100% !important;
                object-fit: cover !important;
            `;
            console.log('🖼️ Cloned poster forced visible for card:', index);
        }

        cardClone.style.cssText = `
            width: 100%;
            pointer-events: auto;
            cursor: pointer;
        `;

        slide.appendChild(cardClone);
        sliderTrack.appendChild(slide);
    });

    // Create dots
    const dotsContainer = document.createElement('div');
    dotsContainer.className = 'riman-slider-nav';

    for (let i = 0; i < cards.length; i++) {
        const dot = document.createElement('button');
        dot.className = `riman-slider-dot ${i === 0 ? 'active' : ''}`;
        dot.dataset.slide = i;
        dot.setAttribute('aria-label', `Slide ${i + 1}`);
        dotsContainer.appendChild(dot);
    }

    // Assemble slider
    sliderWrapper.appendChild(sliderTrack);
    sliderWrapper.appendChild(dotsContainer);
    container.appendChild(sliderWrapper);

    // Add slider class to container and mark as mobile slider
    container.classList.add('riman-simple-slider-active');
    container.dataset.mobileSlider = 'true'; // Tell video system that slider is active

    // Initialize slider functionality
    const slider = new SimpleSlider(sliderWrapper, sliderTrack, dotsContainer, cards.length);

    console.log('✅ SIMPLE slider created successfully');
}

class SimpleSlider {
    constructor(wrapper, track, dotsContainer, slideCount) {
        this.wrapper = wrapper;
        this.track = track;
        this.dotsContainer = dotsContainer;
        this.slideCount = slideCount;
        this.currentSlide = 0;

        // Touch handling
        this.startX = 0;
        this.currentX = 0;
        this.isDragging = false;
        this.hasMoved = false;

        this.init();
    }

    init() {
        console.log('🎯 Initializing SIMPLE slider functionality...');

        // Touch events
        this.track.addEventListener('touchstart', this.onTouchStart.bind(this), { passive: true });
        this.track.addEventListener('touchmove', this.onTouchMove.bind(this), { passive: false });
        this.track.addEventListener('touchend', this.onTouchEnd.bind(this), { passive: true });

        // Dot clicks
        this.dotsContainer.addEventListener('click', this.onDotClick.bind(this));

        // Auto-play
        this.startAutoPlay();

        // Activate first slide video immediately
        this.activateCurrentSlideVideo();

        console.log('✅ SIMPLE slider initialized');
    }

    onTouchStart(e) {
        if (e.touches.length > 1) return;

        this.isDragging = true;
        this.hasMoved = false;
        this.startX = e.touches[0].clientX;
        this.currentX = this.startX;

        console.log('👆 Touch start at:', this.startX);
    }

    onTouchMove(e) {
        if (!this.isDragging || e.touches.length > 1) return;

        this.currentX = e.touches[0].clientX;
        const deltaX = this.currentX - this.startX;
        const deltaY = Math.abs(e.touches[0].clientY - e.touches[0].clientY);

        // Mark as moved if significant movement
        if (Math.abs(deltaX) > 10) {
            this.hasMoved = true;

            // Prevent default only for horizontal swipes
            if (Math.abs(deltaX) > Math.abs(deltaY)) {
                e.preventDefault();
            }
        }
    }

    onTouchEnd(e) {
        if (!this.isDragging) return;

        this.isDragging = false;
        const deltaX = this.currentX - this.startX;

        console.log('👆 Touch end - deltaX:', deltaX, 'hasMoved:', this.hasMoved);

        // Only process swipes if user actually moved
        if (this.hasMoved && Math.abs(deltaX) > 50) {
            if (deltaX > 0) {
                // Swipe right - go to previous
                this.goToPrevious();
            } else {
                // Swipe left - go to next
                this.goToNext();
            }
        }
    }

    onDotClick(e) {
        if (e.target.classList.contains('riman-slider-dot')) {
            const slideIndex = parseInt(e.target.dataset.slide);
            this.goToSlide(slideIndex);
        }
    }

    goToNext() {
        console.log('➡️ Going to next slide from:', this.currentSlide);
        this.currentSlide = (this.currentSlide + 1) % this.slideCount;
        this.updateSlider();
    }

    goToPrevious() {
        console.log('⬅️ Going to previous slide from:', this.currentSlide);
        this.currentSlide = this.currentSlide === 0 ? this.slideCount - 1 : this.currentSlide - 1;
        this.updateSlider();
    }

    goToSlide(index) {
        console.log('🎯 Going to slide:', index);
        this.currentSlide = index;
        this.updateSlider();
    }

    updateSlider() {
        const translateX = -(this.currentSlide * (100 / this.slideCount));
        this.track.style.transform = `translateX(${translateX}%)`;

        console.log('🔄 Updated slider - slide:', this.currentSlide, 'translateX:', translateX + '%');

        // Update dots
        const dots = this.dotsContainer.querySelectorAll('.riman-slider-dot');
        dots.forEach((dot, index) => {
            if (index === this.currentSlide) {
                dot.classList.add('active');
            } else {
                dot.classList.remove('active');
            }
        });

        // Activate videos on current slide
        this.activateCurrentSlideVideo();
    }

    activateCurrentSlideVideo() {
        // Get all slides
        const slides = this.track.querySelectorAll('.simple-slide');

        slides.forEach((slide, index) => {
            const card = slide.querySelector('.riman-service-card');
            const video = slide.querySelector('.riman-card-video');
            const poster = slide.querySelector('.riman-card-poster');

            if (index === this.currentSlide) {
                // Activate current slide with proper fallback system
                this.ensurePosterThenVideo(slide, index, video, poster, card);
            } else {
                // Deactivate other slides - always show posters
                this.deactivateSlideVideo(slide, index, video, poster, card);
            }
        });
    }

    ensurePosterThenVideo(slide, index, video, poster, card) {
        console.log('🎯 Activating slide with fallback system:', index);

        // Step 1: ALWAYS ensure poster is visible IMMEDIATELY
        if (poster) {
            this.ensurePosterVisible(poster, index);
        }

        // Step 2: Start video immediately (don't wait for poster load)
        console.log('🎬 Starting video immediately on slide:', index);
        this.handleVideoAfterPoster(video, poster, card, index);
    }

    ensurePosterVisible(poster, index) {
        poster.style.cssText = `
            opacity: 1 !important;
            z-index: 2 !important;
            display: block !important;
            position: relative !important;
            visibility: visible !important;
            width: 100% !important;
            height: 100% !important;
            object-fit: cover !important;
        `;
        console.log('🖼️ Poster forced visible on slide:', index);
    }

    handleVideoAfterPoster(video, poster, card, index) {
        if (!video) {
            console.log('🎬 No video on slide:', index);
            return;
        }

        // Step 3: Setup correct video source
        const isMobile = window.innerWidth <= 780;
        if (isMobile && video.dataset.srcMobile && video.src !== video.dataset.srcMobile) {
            console.log('🎬 Setting mobile video source on slide:', index);
            video.src = video.dataset.srcMobile;
        }

        // Step 4: Force video to start - simplified approach
        const forceVideoStart = () => {
            console.log('🎬 Force starting video on slide:', index);
            video.classList.add('is-playing', 'is-active');
            video.style.opacity = '1';
            video.style.zIndex = '3'; // Above poster

            console.log('🎬 Video state:', {
                readyState: video.readyState,
                networkState: video.networkState,
                src: video.src,
                duration: video.duration
            });

            video.currentTime = 0;
            video.play().then(() => {
                console.log('🎬 ✅ Video playing successfully on slide:', index);
                // Hide poster when video plays
                if (poster) {
                    poster.style.opacity = '0.2';
                    poster.style.zIndex = '2';
                }
            }).catch(e => {
                console.log('🎬 ❌ Video play failed - keeping poster on slide:', index, e);
                video.style.opacity = '0';
                if (poster) {
                    poster.style.opacity = '1';
                    poster.style.zIndex = '2';
                }
            });
        };

        // Try immediate start
        console.log('🎬 Attempting immediate video start on slide:', index);
        forceVideoStart();

        // Also try after a short delay in case video needs time
        setTimeout(() => {
            console.log('🎬 Retry video start after delay on slide:', index);
            if (video.readyState < 3) {
                console.log('🎬 Video still not ready, loading...');
                video.load();
            }
            forceVideoStart();
        }, 500);

        if (card && card.classList.contains('riman-card--has-video')) {
            card.classList.add('video-active');
        }
    }

    deactivateSlideVideo(slide, index, video, poster, card) {
        // Always show poster on inactive slides
        if (poster) {
            this.ensurePosterVisible(poster, index);
        }

        // Hide and stop video
        if (video) {
            video.classList.remove('is-playing', 'is-active', 'is-loading');
            video.style.opacity = '0';
            video.style.zIndex = '1';
            video.pause();
            video.currentTime = 0;
            console.log('🎬 Video deactivated on slide:', index);
        }

        if (card && card.classList.contains('riman-card--has-video')) {
            card.classList.remove('video-active');
        }
    }

    startAutoPlay() {
        // Auto-play every 5 seconds
        setInterval(() => {
            this.goToNext();
        }, 5000);

        console.log('⏰ Auto-play started');
    }
}

// Resize handler
window.addEventListener('resize', () => {
    if (window.innerWidth > 780) {
        // Switch to desktop - hide sliders
        document.querySelectorAll('.riman-simple-slider-active').forEach(container => {
            const slider = container.querySelector('.simple-slider-wrapper');
            const grid = container.querySelector('.riman-service-cards-grid');

            if (slider) slider.style.display = 'none';
            if (grid) grid.style.display = '';

            container.classList.remove('riman-simple-slider-active');
            delete container.dataset.mobileSlider; // Remove mobile slider marker
        });
    } else {
        // Switch to mobile - reinitialize if needed
        setTimeout(() => {
            if (document.querySelectorAll('.simple-slider-wrapper').length === 0) {
                location.reload(); // Simple reload to reinitialize
            }
        }, 100);
    }
});

// Initialize responsive video selection for cloned cards
function initializeResponsiveVideo(card) {
    const video = card.querySelector('.riman-card-video');
    if (!video) return;

    // Select appropriate video source based on screen size
    const isMobile = window.innerWidth <= 780;
    let selectedSrc = null;

    if (isMobile && video.dataset.srcMobile) {
        selectedSrc = video.dataset.srcMobile;
        console.log('🎬 Using mobile video source:', selectedSrc);
    } else if (!isMobile && video.dataset.srcDesktop) {
        selectedSrc = video.dataset.srcDesktop;
        console.log('🎬 Using desktop video source:', selectedSrc);
    } else if (video.dataset.src) {
        selectedSrc = video.dataset.src;
        console.log('🎬 Using default video source:', selectedSrc);
    } else if (card.dataset.videoSrc) {
        selectedSrc = card.dataset.videoSrc;
        console.log('🎬 Using card video source:', selectedSrc);
    }

    if (selectedSrc && selectedSrc !== video.src) {
        video.src = selectedSrc;
        console.log('🎬 Video source updated to:', selectedSrc);
    }

    // Set up poster image
    const posterKey = isMobile ? 'posterMobile' : 'posterDesktop';
    if (video.dataset[posterKey]) {
        video.poster = video.dataset[posterKey];
        console.log('🎬 Poster updated to:', video.poster);
    } else if (video.dataset.poster) {
        video.poster = video.dataset.poster;
    }

    // Ensure video is ready for playback
    video.muted = true;
    video.loop = true;
    video.playsInline = true;

    // Load the video
    video.load();
}

console.log('📱 SIMPLE Mobile Slider loaded');