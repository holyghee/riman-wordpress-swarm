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
    const grid = container.querySelector('.riman-service-cards-grid');
    if (grid) {
        grid.style.display = 'none';
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
    dotsContainer.className = 'simple-dots';
    dotsContainer.style.cssText = `
        display: flex;
        justify-content: center;
        gap: 8px;
        margin-top: 20px;
    `;

    for (let i = 0; i < cards.length; i++) {
        const dot = document.createElement('button');
        dot.className = 'simple-dot';
        dot.style.cssText = `
            width: 10px;
            height: 10px;
            border-radius: 50%;
            border: none;
            background: ${i === 0 ? '#B68C2F' : 'rgba(182, 140, 47, 0.3)'};
            cursor: pointer;
            transition: all 0.2s ease;
        `;
        dot.dataset.slide = i;
        dotsContainer.appendChild(dot);
    }

    // Assemble slider
    sliderWrapper.appendChild(sliderTrack);
    sliderWrapper.appendChild(dotsContainer);
    container.appendChild(sliderWrapper);

    // Add slider class to container
    container.classList.add('riman-simple-slider-active');

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
        if (e.target.classList.contains('simple-dot')) {
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
        const dots = this.dotsContainer.querySelectorAll('.simple-dot');
        dots.forEach((dot, index) => {
            dot.style.background = index === this.currentSlide
                ? '#B68C2F'
                : 'rgba(182, 140, 47, 0.3)';
        });
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