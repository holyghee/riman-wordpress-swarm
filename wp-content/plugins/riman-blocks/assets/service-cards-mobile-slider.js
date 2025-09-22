/**
 * RIMAN Service Cards Mobile Slider
 * Implementiert Swipe-Gesten, Auto-Play und Video-Synchronisation
 */

document.addEventListener('DOMContentLoaded', function() {

    // Initialisiere alle Service Card Slider
    const initServiceCardSliders = () => {
        console.log('🚀 Initializing Service Card Sliders...');
        const sliderContainers = document.querySelectorAll('.riman-service-cards-wrap[data-mobile-slider="true"]');
        console.log('📱 Found slider containers:', sliderContainers.length);

        sliderContainers.forEach((container, containerIndex) => {
            console.log(`🏗️ Processing container ${containerIndex + 1}:`, container);

            // Nur auf Mobile aktivieren (≤780px)
            if (window.innerWidth > 780) {
                console.log('⏭️ Skipping - screen too wide:', window.innerWidth);
                return;
            }

            // CRITICAL FIX: Look for multiple possible grid selectors
            let grid = container.querySelector('.riman-service-grid');
            if (!grid) {
                // Fallback: Look for any grid-like container with service cards
                grid = container.querySelector('.wp-block-group:has(.riman-service-card)') ||
                       container.querySelector('[class*="grid"]') ||
                       container.querySelector('[class*="service"]');

                if (!grid) {
                    console.log('❌ No service grid found in container - trying direct card selection');
                    // Last resort: Create temporary grid from direct card children
                    const directCards = container.querySelectorAll('.riman-service-card');
                    if (directCards.length > 0) {
                        grid = container; // Use container itself as grid
                        console.log('✅ Using container as grid with direct cards:', directCards.length);
                    } else {
                        console.log('❌ No service cards found anywhere in container');
                        return;
                    }
                }
            }

            const cards = Array.from(grid.querySelectorAll('.riman-service-card'));
            console.log(`🃏 Found ${cards.length} service cards in grid`);

            if (cards.length === 0) {
                console.log('❌ No service cards found - aborting');
                return;
            }

            // Slider-Container vorbereiten
            setupSliderStructure(container, grid, cards);

            // Slider-Instanz erstellen
            const slider = new ServiceCardSlider(container, {
                autoPlay: container.dataset.sliderAutoplay === 'true',
                interval: parseInt(container.dataset.sliderInterval || '8000', 10),
                cards: cards
            });

            slider.init();
        });
    };

    // Slider-HTML-Struktur erstellen
    const setupSliderStructure = (container, grid, cards) => {
        console.log('🔧 Setting up slider structure:', {
            container: container,
            grid: grid,
            cardsCount: cards.length,
            containerWidth: container.offsetWidth,
            gridWidth: grid ? grid.offsetWidth : 'no grid'
        });

        // Wrapper für Slider erstellen
        const sliderWrapper = document.createElement('div');
        sliderWrapper.className = 'riman-service-slider-wrapper';

        const sliderTrack = document.createElement('div');
        sliderTrack.className = 'riman-service-slider-track';

        // Create slides with 1 card each (original behavior)
        cards.forEach((card, index) => {
            console.log(`📦 Processing card ${index + 1}:`, {
                cardElement: card,
                cardWidth: card.offsetWidth,
                cardHeight: card.offsetHeight,
                cardClasses: card.className
            });

            const slide = document.createElement('div');
            slide.className = 'riman-service-slide';
            const clonedCard = card.cloneNode(true);

            // Ensure video data is preserved in clones
            const originalVideo = card.querySelector('.riman-card-video');
            const clonedVideo = clonedCard.querySelector('.riman-card-video');

            if (originalVideo && clonedVideo) {
                // Copy video source data
                if (originalVideo.dataset.src) {
                    clonedVideo.dataset.src = originalVideo.dataset.src;
                }
                if (card.dataset.videoSrc) {
                    clonedCard.dataset.videoSrc = card.dataset.videoSrc;
                }
                console.log('Preserved video data for slider clone:', clonedVideo.dataset.src || clonedCard.dataset.videoSrc);
            }

            slide.appendChild(clonedCard);
            sliderTrack.appendChild(slide);

            // Debug: Check slide after creation
            console.log(`✅ Created slide ${index + 1}:`, {
                slideElement: slide,
                slideWidth: slide.offsetWidth,
                cardInSlide: slide.querySelector('.riman-service-card'),
                cardVisible: clonedCard.offsetWidth > 0 && clonedCard.offsetHeight > 0
            });

            console.log(`🔍 Slide ${index} card visibility:`, {
                cardElement: clonedCard,
                cardWidth: clonedCard.offsetWidth,
                cardHeight: clonedCard.offsetHeight,
                cardClasses: clonedCard.className,
                cardVisible: clonedCard.offsetWidth > 0 && clonedCard.offsetHeight > 0
            });
        });

        // Slider Track in separaten Container
        const trackContainer = document.createElement('div');
        trackContainer.className = 'riman-slider-track-container';
        trackContainer.style.position = 'relative';
        trackContainer.style.flex = '1';
        trackContainer.style.overflow = 'visible'; // CRITICAL: Must be visible for cards
        trackContainer.style.minHeight = '450px'; // Force minimum height for cards
        trackContainer.style.display = 'flex'; // Force flex display
        trackContainer.style.alignItems = 'center'; // Center cards vertically
        trackContainer.style.width = '100%'; // Full width
        trackContainer.appendChild(sliderTrack);

        console.log('📦 Created track container:', {
            trackContainer: trackContainer,
            sliderTrack: sliderTrack,
            trackChildren: sliderTrack.children.length
        });

        // Nur Arrows erstellen - keine Dots
        const leftArrow = createArrowButton('prev', 'Vorherige Slide');
        const rightArrow = createArrowButton('next', 'Nächste Slide');

        // Slider Wrapper Layout: Track Container mit seitlichen Arrows
        const sliderContent = document.createElement('div');
        sliderContent.className = 'riman-slider-content';
        sliderContent.style.display = 'flex';
        sliderContent.style.alignItems = 'center';
        sliderContent.style.gap = '0'; // Kein Gap für maximalen Card-Platz
        sliderContent.style.width = '100%';

        // Flexbox Layout: Arrow - Track - Arrow (keine Dots)
        sliderContent.appendChild(leftArrow);
        sliderContent.appendChild(trackContainer);
        sliderContent.appendChild(rightArrow);

        sliderWrapper.appendChild(sliderContent);

        // Slider Wrapper direkt am Bildschirmrand positionieren
        sliderWrapper.style.paddingLeft = '0';
        sliderWrapper.style.paddingRight = '0';
        sliderWrapper.style.marginLeft = '0';
        sliderWrapper.style.marginRight = '0';

        // Original Grid ersetzen
        container.replaceChild(sliderWrapper, grid);

        // Mobile Slider CSS-Klasse hinzufügen
        container.classList.add('riman-mobile-slider-active');

        // Debug: Check final structure
        setTimeout(() => {
            console.log('🎯 Final slider structure:', {
                containerHasActiveClass: container.classList.contains('riman-mobile-slider-active'),
                sliderWrapper: sliderWrapper,
                sliderWrapperWidth: sliderWrapper.offsetWidth,
                sliderWrapperHeight: sliderWrapper.offsetHeight,
                trackWidth: sliderTrack.offsetWidth,
                trackHeight: sliderTrack.offsetHeight,
                slidesCount: sliderTrack.children.length,
                firstSlideVisible: sliderTrack.children[0] ? {
                    width: sliderTrack.children[0].offsetWidth,
                    height: sliderTrack.children[0].offsetHeight,
                    hasCard: !!sliderTrack.children[0].querySelector('.riman-service-card')
                } : 'no slides'
            });

            // Check if cards are actually visible
            const allSlides = sliderTrack.querySelectorAll('.riman-service-slide');
            allSlides.forEach((slide, index) => {
                const card = slide.querySelector('.riman-service-card');
                if (card) {
                    console.log(`🔍 Slide ${index} card visibility:`, {
                        cardWidth: card.offsetWidth,
                        cardHeight: card.offsetHeight,
                        cardDisplay: getComputedStyle(card).display,
                        cardVisibility: getComputedStyle(card).visibility,
                        cardOpacity: getComputedStyle(card).opacity
                    });
                }
            });
        }, 100);
    };

    // Arrow Button erstellen
    const createArrowButton = (direction, label) => {
        const arrow = document.createElement('button');
        arrow.className = `riman-slider-arrow riman-slider-${direction}`;
        arrow.setAttribute('aria-label', label);

        if (direction === 'prev') {
            arrow.innerHTML = `
                <svg viewBox="0 0 24 24" fill="currentColor" width="24" height="24">
                    <path d="M15.41 7.41L14 6l-6 6 6 6 1.41-1.41L10.83 12z"/>
                </svg>
            `;
        } else {
            arrow.innerHTML = `
                <svg viewBox="0 0 24 24" fill="currentColor" width="24" height="24">
                    <path d="M10 6L8.59 7.41 13.17 12l-4.58 4.59L10 18l6-6z"/>
                </svg>
            `;
        }

        return arrow;
    };

    // Dots Container erstellen
    const createDotsContainer = (slideCount) => {
        const dotsContainer = document.createElement('div');
        dotsContainer.className = 'riman-slider-dots';

        for (let i = 0; i < slideCount; i++) {
            const dot = document.createElement('button');
            dot.className = 'riman-slider-dot';
            dot.setAttribute('aria-label', `Slide ${i + 1}`);
            dot.dataset.slide = i;
            if (i === 0) dot.classList.add('active');
            dotsContainer.appendChild(dot);
        }

        return dotsContainer;
    };

    // Slider-Navigation erstellen (Dots + Arrows) - Legacy für Kompatibilität
    const createSliderNavigation = (slideCount) => {
        const navContainer = document.createElement('div');
        navContainer.className = 'riman-slider-navigation';

        // Linker Pfeil
        const prevArrow = document.createElement('button');
        prevArrow.className = 'riman-slider-arrow riman-slider-prev';
        prevArrow.setAttribute('aria-label', 'Vorherige Slide');
        prevArrow.innerHTML = `
            <svg viewBox="0 0 24 24" fill="currentColor" width="24" height="24">
                <path d="M15.41 7.41L14 6l-6 6 6 6 1.41-1.41L10.83 12z"/>
            </svg>
        `;

        // Dots Container
        const dotsContainer = document.createElement('div');
        dotsContainer.className = 'riman-slider-dots';

        for (let i = 0; i < slideCount; i++) {
            const dot = document.createElement('button');
            dot.className = 'riman-slider-dot';
            dot.setAttribute('aria-label', `Slide ${i + 1}`);
            dot.dataset.slide = i;
            if (i === 0) dot.classList.add('active');
            dotsContainer.appendChild(dot);
        }

        // Rechter Pfeil
        const nextArrow = document.createElement('button');
        nextArrow.className = 'riman-slider-arrow riman-slider-next';
        nextArrow.setAttribute('aria-label', 'Nächste Slide');
        nextArrow.innerHTML = `
            <svg viewBox="0 0 24 24" fill="currentColor" width="24" height="24">
                <path d="M10 6L8.59 7.41 13.17 12l-4.58 4.59L10 18l6-6z"/>
            </svg>
        `;

        navContainer.appendChild(prevArrow);
        navContainer.appendChild(dotsContainer);
        navContainer.appendChild(nextArrow);

        return navContainer;
    };

    // Hauptklasse für Service Card Slider
    class ServiceCardSlider {
        constructor(container, options = {}) {
            this.container = container;
            this.track = container.querySelector('.riman-service-slider-track');
            this.allSlides = Array.from(container.querySelectorAll('.riman-service-slide'));
            this.slides = this.allSlides.filter(slide => !slide.classList.contains('clone-slide'));
            this.dots = Array.from(container.querySelectorAll('.riman-slider-dot'));
            this.prevArrow = container.querySelector('.riman-slider-prev');
            this.nextArrow = container.querySelector('.riman-slider-next');

            this.options = {
                autoPlay: options.autoPlay || false,
                interval: options.interval || 5000,
                cards: options.cards || []
            };

            this.slideCount = this.slides.length;
            this.currentSlide = 0; // CRITICAL FIX: Start with 0 (first slide)
            this.isPlaying = this.options.autoPlay;
            this.autoPlayTimer = null;
            this.videoTimer = null;
            this.videoAdvanceTimer = null;
            this.isDragging = false;
            this.startX = 0;
            this.startY = 0;
            this.currentX = 0;
            this.initialTransform = 0;
            this.isTransitioning = false;
        }

        init() {
            this.setupEventListeners();

            // CRITICAL FIX: Wait for DOM to render before calculating dimensions
            this.waitForDimensionsAndInit();
        }

        waitForDimensionsAndInit() {
            // CRITICAL FIX: More aggressive initialization approach
            const checkDimensions = () => {
                const slideWidth = this.getSlideWidth();
                console.log('🔍 Checking slide dimensions:', {
                    slideWidth: slideWidth,
                    slideElement: this.allSlides[0],
                    slideOffsetWidth: this.allSlides[0] ? this.allSlides[0].offsetWidth : 'no slides',
                    containerWidth: this.container.offsetWidth,
                    trackWidth: this.track.offsetWidth
                });

                // CRITICAL FIX: Accept any width > 0, don't wait for "perfect" dimensions
                if (slideWidth > 0) {
                    console.log('✅ Dimensions ready, initializing slider');
                    this.updateSlider();

                    if (this.isPlaying) {
                        this.startAutoPlay();
                    }

                    // Videos in aktueller Slide aktivieren
                    this.handleVideoPlayback();
                    return true;
                }
                return false;
            };

            // CRITICAL FIX: Try multiple initialization strategies
            // Strategy 1: Immediate check
            if (checkDimensions()) {
                return;
            }

            // Strategy 2: Force reflow and try again
            this.track.style.display = 'none';
            this.track.offsetHeight; // Force reflow
            this.track.style.display = 'flex';

            if (checkDimensions()) {
                return;
            }

            // Strategy 3: Retry with shorter intervals and fewer attempts
            console.log('⏳ Dimensions not ready, using rapid retry...');
            let attempts = 0;
            const maxAttempts = 10; // Reduced from 20
            const retryInterval = setInterval(() => {
                attempts++;
                console.log(`🔄 Rapid retry ${attempts}/${maxAttempts} checking dimensions...`);

                if (checkDimensions() || attempts >= maxAttempts) {
                    clearInterval(retryInterval);
                    if (attempts >= maxAttempts) {
                        console.warn('⚠️ Rapid retries exhausted, forcing initialization');
                        this.forceInitWithFallback();
                    }
                }
            }, 25); // Faster retry interval

            // Strategy 4: Fallback timer - if still no success after 500ms, force init
            setTimeout(() => {
                if (!this.track.style.transform || this.track.style.transform === 'translateX(0px)') {
                    console.warn('🚨 Emergency fallback: forcing slider initialization');
                    clearInterval(retryInterval);
                    this.forceInitWithFallback();
                }
            }, 500);
        }

        forceInitWithFallback() {
            // Force recalculation of dimensions
            this.track.style.display = 'none';
            this.track.offsetHeight; // Force reflow
            this.track.style.display = 'flex';

            setTimeout(() => {
                console.log('🔧 Force initializing with fallback dimensions');
                this.updateSlider();

                if (this.isPlaying) {
                    this.startAutoPlay();
                }

                this.handleVideoPlayback();
            }, 100);
        }

        setupEventListeners() {
            // Touch Events für Swipe-Gesten
            this.track.addEventListener('touchstart', this.onTouchStart.bind(this), { passive: false });
            this.track.addEventListener('touchmove', this.onTouchMove.bind(this), { passive: false });
            this.track.addEventListener('touchend', this.onTouchEnd.bind(this), { passive: false });

            // Mouse Events für Desktop-Test
            this.track.addEventListener('mousedown', this.onMouseDown.bind(this));
            this.track.addEventListener('mousemove', this.onMouseMove.bind(this));
            this.track.addEventListener('mouseup', this.onMouseUp.bind(this));
            this.track.addEventListener('mouseleave', this.onMouseUp.bind(this));

            // Navigation Dots
            this.dots.forEach(dot => {
                dot.addEventListener('click', (e) => {
                    const slideIndex = parseInt(e.target.dataset.slide);
                    this.goToSlide(slideIndex);
                });
            });

            // Navigation Arrows
            if (this.prevArrow) {
                this.prevArrow.addEventListener('click', () => this.previousSlide());
            }
            if (this.nextArrow) {
                this.nextArrow.addEventListener('click', () => this.nextSlide());
            }

            // Resize Handler
            window.addEventListener('resize', this.onResize.bind(this));

            // Visibility API für Auto-Play Pause
            document.addEventListener('visibilitychange', this.onVisibilityChange.bind(this));
        }

        // Touch Events
        onTouchStart(e) {
            if (e.touches.length > 1) return;

            this.isDragging = true;
            this.startX = e.touches[0].clientX;
            this.startY = e.touches[0].clientY;
            this.currentX = this.startX; // FIX: Initialize currentX to prevent jump bug
            this.initialTransform = this.getTransformX();
            this.hasMoved = false; // Track if user actually moved finger

            this.pauseAutoPlay();
            this.track.style.transition = 'none';
        }

        onTouchMove(e) {
            if (!this.isDragging || e.touches.length > 1) return;

            this.currentX = e.touches[0].clientX;
            const deltaX = this.currentX - this.startX;
            const deltaY = Math.abs(e.touches[0].clientY - this.startY);

            // Mark that user has moved finger
            if (Math.abs(deltaX) > 5 || deltaY > 5) {
                this.hasMoved = true;
            }

            // Nur horizontal scrollen wenn mehr horizontal als vertikal
            if (Math.abs(deltaX) > deltaY) {
                e.preventDefault(); // Only prevent default for horizontal swipes
                const newTransform = this.initialTransform + deltaX;
                this.track.style.transform = `translateX(${newTransform}px)`;
            }
            // Allow vertical scrolling when deltaY > deltaX
        }

        onTouchEnd(e) {
            if (!this.isDragging) return;

            this.isDragging = false;
            this.track.style.transition = 'transform 0.3s ease';

            const deltaX = this.currentX - this.startX;
            const deltaY = Math.abs(e.changedTouches[0] ? e.changedTouches[0].clientY - this.startY : 0);
            const threshold = this.getSlideWidth() * 0.3;

            // If user didn't move much, it's a tap - allow card clicks
            if (!this.hasMoved) {
                this.updateSlider();
                return;
            }

            // Only trigger slide change for horizontal swipes
            if (Math.abs(deltaX) > deltaY && Math.abs(deltaX) > threshold) {
                if (deltaX > 0) {
                    this.previousSlide();
                } else {
                    this.nextSlide();
                }
            } else {
                // Reset to current position for failed swipes or vertical scrolls
                this.updateSlider();
            }

            // TODO: Auto-play restart disabled
        }

        // Mouse Events (für Desktop-Testing)
        onMouseDown(e) {
            this.isDragging = true;
            this.startX = e.clientX;
            this.currentX = this.startX; // FIX: Initialize currentX to prevent jump bug
            this.initialTransform = this.getTransformX();
            this.pauseAutoPlay();
            this.track.style.transition = 'none';
            e.preventDefault();
        }

        onMouseMove(e) {
            if (!this.isDragging) return;

            e.preventDefault();
            this.currentX = e.clientX;
            const deltaX = this.currentX - this.startX;
            const newTransform = this.initialTransform + deltaX;
            this.track.style.transform = `translateX(${newTransform}px)`;
        }

        onMouseUp(e) {
            if (!this.isDragging) return;

            this.isDragging = false;
            this.track.style.transition = 'transform 0.3s ease';

            const deltaX = this.currentX - this.startX;
            const threshold = this.getSlideWidth() * 0.3;

            if (Math.abs(deltaX) > threshold) {
                if (deltaX > 0) {
                    this.previousSlide();
                } else {
                    this.nextSlide();
                }
            } else {
                this.updateSlider();
            }

            // TODO: Auto-play restart disabled
        }

        // Manual Navigation - Next Slide
        nextSlide() {
            if (this.isTransitioning) return;

            console.log('📱 Manual next slide - pausing auto-play temporarily');

            // Pause auto-play during manual navigation
            this.pauseAutoPlay();

            this.isTransitioning = true;
            this.currentSlide++;

            // Loop back to first slide if we exceed the count
            if (this.currentSlide >= this.slideCount) {
                this.currentSlide = 0;
            }

            this.updateSlider();

            setTimeout(() => {
                this.isTransitioning = false;
                this.handleVideoPlayback();

                // Restart auto-play after manual navigation
                if (this.options.autoPlay) {
                    console.log('🔄 Restarting auto-play after manual navigation');
                    setTimeout(() => this.startAutoPlay(), 3000);
                }
            }, 300);
        }

        // Manual Navigation - Previous Slide
        previousSlide() {
            if (this.isTransitioning) return;

            console.log('📱 Manual previous slide - pausing auto-play temporarily');

            // Pause auto-play during manual navigation
            this.pauseAutoPlay();

            this.isTransitioning = true;
            this.currentSlide--;

            // Loop to last slide if we go below 0
            if (this.currentSlide < 0) {
                this.currentSlide = this.slideCount - 1;
            }

            this.updateSlider();

            setTimeout(() => {
                this.isTransitioning = false;
                this.handleVideoPlayback();

                // Restart auto-play after manual navigation
                if (this.options.autoPlay) {
                    console.log('🔄 Restarting auto-play after manual previous');
                    setTimeout(() => this.startAutoPlay(), 3000);
                }
            }, 300);
        }

        goToSlide(index) {
            if (index >= 0 && index < this.slideCount) {
                this.currentSlide = index; // Direct 0-based indexing
                this.updateSlider();
                this.handleVideoPlayback();
                this.pauseAutoPlay();

                if (this.options.autoPlay) {
                    setTimeout(() => this.startAutoPlay(), 3000);
                }
            }
        }

        updateSlider() {
            const slideWidth = this.getSlideWidth();

            // CRITICAL FIX: Correct transform calculation for 0-based indexing
            // currentSlide=0 (first slide) should have transform=0 to be visible
            const transform = -this.currentSlide * slideWidth;

            console.log('🎯 UpdateSlider:', {
                currentSlide: this.currentSlide,
                slideWidth: slideWidth,
                transform: transform,
                slideCount: this.slideCount,
                calculation: `-${this.currentSlide} * ${slideWidth} = ${transform}`
            });

            this.track.style.transform = `translateX(${transform}px)`;

            // Dots aktualisieren - direkt auf currentSlide basiert (0-indexiert)
            this.dots.forEach((dot, index) => {
                dot.classList.toggle('active', index === this.currentSlide);
            });
        }

        getSlideWidth() {
            if (!this.allSlides[0]) {
                console.warn('⚠️ No slides found for width calculation');
                return 0;
            }

            let slideWidth = this.allSlides[0].offsetWidth;

            // If slide width is 0, try various fallback strategies
            if (slideWidth === 0) {
                console.log('🔧 Slide width is 0, trying fallback calculations...');

                // CRITICAL FIX: More robust width calculation
                // 1. Try track container width
                const trackContainer = this.track.parentElement;
                if (trackContainer && trackContainer.offsetWidth > 0) {
                    slideWidth = trackContainer.offsetWidth;
                    console.log('📐 Using track container width as fallback:', slideWidth);
                }
                // 2. Try container width
                else if (this.container.offsetWidth > 0) {
                    slideWidth = this.container.offsetWidth;
                    console.log('📐 Using container width as fallback:', slideWidth);
                }
                // 3. Try track width
                else if (this.track.offsetWidth > 0) {
                    slideWidth = this.track.offsetWidth;
                    console.log('📐 Using track width as fallback:', slideWidth);
                }
                // 4. Calculate from viewport considering padding/margins
                else {
                    slideWidth = window.innerWidth - 30; // Account for padding
                    console.log('📐 Using calculated viewport width as fallback:', slideWidth);
                }

                // CRITICAL FIX: Force immediate reflow and proper slide styling
                if (slideWidth > 0) {
                    this.allSlides.forEach((slide, index) => {
                        slide.style.width = `${slideWidth}px`;
                        slide.style.flexBasis = `${slideWidth}px`;
                        slide.style.minWidth = `${slideWidth}px`;
                        slide.style.maxWidth = `${slideWidth}px`;
                        slide.style.flexShrink = '0';
                        slide.style.display = 'flex';

                        // Force reflow
                        slide.offsetHeight;
                    });

                    // Force track width recalculation
                    this.track.style.width = `${slideWidth * this.allSlides.length}px`;
                    console.log('🔧 Applied robust width to all slides and track:', slideWidth);
                }
            }

            console.log('📏 Final slide width:', slideWidth);
            return slideWidth || window.innerWidth - 30; // Ultimate fallback
        }

        getTransformX() {
            const transform = this.track.style.transform;
            const match = transform.match(/translateX\\(([^)]+)\\)/);
            return match ? parseFloat(match[1]) : 0;
        }

        // Proper Auto-Play Implementation - Sequential right-only movement
        startAutoPlay() {
            if (!this.options.autoPlay || this.autoPlayTimer) return;

            console.log('🎬 Starting proper auto-play with interval:', this.options.interval, 'ms');

            // REAL Auto-Play: Independent of video events, uses meta box interval
            this.autoPlayTimer = setInterval(() => {
                if (this.isPlaying && !this.isTransitioning) {
                    console.log('▶️ Auto-play: moving to next slide (right-only)');
                    this.autoPlayAdvance(); // Custom right-only function
                }
            }, this.options.interval);

            console.log('✅ Auto-play timer created with', this.options.interval, 'ms interval');
        }

        // Auto-Play Advance - Always goes right, cycles at end
        autoPlayAdvance() {
            this.isTransitioning = true;

            // Always move right
            this.currentSlide++;

            // Cycle back to first slide at end
            if (this.currentSlide >= this.slideCount) {
                this.currentSlide = 0;
                console.log('🔄 Auto-play: cycling back to first slide');
            }

            console.log('📍 Auto-play position:', {
                slide: this.currentSlide + 1,
                of: this.slideCount,
                direction: 'right-only'
            });

            this.updateSlider();

            setTimeout(() => {
                this.isTransitioning = false;
                this.handleVideoPlayback();
            }, 300);
        }


        // Dots Update Hilfsfunktion
        updateDots(activeIndex) {
            if (!this.dots) return;

            this.dots.forEach((dot, index) => {
                dot.classList.toggle('active', index === activeIndex);
            });
        }


        pauseAutoPlay() {
            if (this.autoPlayTimer) {
                clearInterval(this.autoPlayTimer);
                this.autoPlayTimer = null;
            }

            // Auch Video-Timer stoppen
            if (this.videoTimer) {
                clearTimeout(this.videoTimer);
                this.videoTimer = null;
            }
            if (this.videoAdvanceTimer) {
                clearTimeout(this.videoAdvanceTimer);
                this.videoAdvanceTimer = null;
            }
        }

        // Video-Synchronisation mit sequentieller Wiedergabe
        handleVideoPlayback() {
            console.log('Mobile slider: handleVideoPlayback called, currentSlide:', this.currentSlide);

            // Stoppe alle laufenden Videos und Timer
            this.pauseAllVideos();

            // Finde aktuelle Slide (0-based indexing)
            const currentSlideEl = this.allSlides[this.currentSlide];
            if (!currentSlideEl) return;

            const video = currentSlideEl.querySelector('.riman-card-video');
            if (!video) return;

            const card = video.closest('.riman-service-card');
            if (!card) return;

            // Responsive Video-Auswahl: Mobile-Version bevorzugen
            let videoSrc = video.src || video.dataset.src || card.dataset.videoSrc;
            const targetSrc = window.innerWidth <= 780 && video.dataset.srcMobile
                ? video.dataset.srcMobile
                : (video.dataset.srcDesktop || videoSrc);

            // Nur Video-Source ändern wenn nötig (verhindert Neuladung)
            if (video.src !== targetSrc) {
                videoSrc = targetSrc;
                video.src = videoSrc;
                if (window.innerWidth <= 780 && video.dataset.srcMobile) {
                    console.log('📱 Using mobile-optimized video:', videoSrc);
                } else {
                    console.log('🖥️ Using desktop video:', videoSrc);
                }
            } else {
                videoSrc = video.src;
            }

            if (!videoSrc) return;

            console.log('Mobile slider: Starting video for slide', this.currentSlide, videoSrc);

            // Video-Klassen für Sequential System setzen
            video.classList.add('is-active', 'is-playing');
            card.classList.add('video-active');

            // Poster verstecken, Video zeigen
            const poster = currentSlideEl.querySelector('.riman-card-poster');
            if (poster) {
                poster.style.opacity = '0';
            }

            // Video-Source immer setzen (auch wenn bereits vorhanden)
            video.src = videoSrc;
            video.muted = true;
            video.playsInline = true;
            video.setAttribute('webkit-playsinline', '');
            video.setAttribute('preload', 'metadata');
            video.controls = false;
            video.currentTime = 0;

            // Video Loop einstellen basierend auf Auto-Play
            if (!this.options.autoPlay) {
                video.loop = true;
                console.log('🔄 Video loop enabled (auto-play disabled)');
            } else {
                video.loop = false;
            }

            // Loading-Indikator hinzufügen
            this.showVideoLoading(currentSlideEl);

            // Video abspielen und Timer starten
            video.play().then(() => {
                console.log('✅ Mobile slider video playing');
                this.hideVideoLoading(currentSlideEl);

                // Video-Ende Handler definieren (für beide Fälle)
                const handleVideoEnd = () => {
                    console.log('📺 Video ended, showing play button and advancing');
                    this.showPlayButton(currentSlideEl);

                    // Video events no longer control auto-play (handled by proper setInterval timer)

                    video.removeEventListener('ended', handleVideoEnd);
                };

                // Video-Ende Handler nur wenn kein Loop
                if (!video.loop) {
                    video.addEventListener('ended', handleVideoEnd);

                    // 6-Sekunden Timer für garantierten Advance (länger als Hero Slider)
                    this.videoTimer = setTimeout(() => {
                        if (!video.ended) {
                            video.pause();
                            handleVideoEnd();
                        }
                    }, 6000);
                } else {
                    console.log('🔄 Video in loop mode - no end handler needed');
                }

            }).catch(e => {
                console.log('❌ Video play failed (mobile autoplay blocked):', e);
                video.setAttribute('data-autoplay-blocked', 'true');
                this.hideVideoLoading(currentSlideEl);
                this.showPlayButton(currentSlideEl);

                // Video errors no longer control auto-play (handled by proper setInterval timer)
            });
        }

        // Alle Videos pausieren und Timer löschen
        pauseAllVideos() {
            // Clear alle Timer
            if (this.videoTimer) {
                clearTimeout(this.videoTimer);
                this.videoTimer = null;
            }
            if (this.videoAdvanceTimer) {
                clearTimeout(this.videoAdvanceTimer);
                this.videoAdvanceTimer = null;
            }

            // Alle Videos pausieren
            this.allSlides.forEach(slide => {
                const video = slide.querySelector('.riman-card-video');
                const card = slide.querySelector('.riman-service-card');
                const poster = slide.querySelector('.riman-card-poster');
                const playButton = slide.querySelector('.riman-video-play-button');

                if (video) {
                    video.pause();
                    video.currentTime = 0;
                    video.classList.remove('is-active', 'is-playing');
                    video.style.opacity = '0';
                }

                if (card) {
                    card.classList.remove('video-active');
                }

                if (poster) {
                    poster.style.opacity = '1';
                }

                if (playButton) {
                    playButton.remove();
                }
            });
        }

        // Dezentes Video-Loading: Nur Poster während Video lädt
        showVideoLoading(slide) {
            const poster = slide.querySelector('.riman-card-poster');
            if (poster) {
                // Poster sichtbar lassen während Video lädt
                poster.style.opacity = '1';
                poster.style.transition = 'opacity 0.3s ease';
            }
        }

        // Video bereit: Smooth Transition zu Video
        hideVideoLoading(slide) {
            const poster = slide.querySelector('.riman-card-poster');
            if (poster) {
                // Sanfter Fade-out des Posters
                poster.style.opacity = '0';
            }
        }

        // Play-Button nach Video-Ende anzeigen
        showPlayButton(slide) {
            const card = slide.querySelector('.riman-service-card');
            if (!card) return;

            // Existierenden Play-Button entfernen
            const existingButton = slide.querySelector('.riman-video-play-button');
            if (existingButton) {
                existingButton.remove();
            }

            // Neuen Play-Button erstellen
            const playButton = document.createElement('div');
            playButton.className = 'riman-video-play-button';
            playButton.innerHTML = `
                <div class="riman-play-circle">
                    <svg class="riman-play-icon" viewBox="0 0 24 24" fill="currentColor">
                        <path d="M8 5v14l11-7z"/>
                    </svg>
                </div>
            `;

            // Click-Handler für Seiten-Navigation
            playButton.addEventListener('click', (e) => {
                e.stopPropagation();
                const cardLink = card.querySelector('a');
                if (cardLink) {
                    window.location.href = cardLink.href;
                }
            });

            card.appendChild(playButton);
        }


        // Resize Handler
        onResize() {
            // Bei Resize auf Desktop: Slider deaktivieren
            if (window.innerWidth > 780) {
                this.destroy();
                return;
            }

            // Slider-Position neu berechnen
            this.updateSlider();
        }

        // Visibility Change Handler
        onVisibilityChange() {
            if (document.hidden) {
                this.pauseAutoPlay();
            } else if (this.options.autoPlay) {
                this.startAutoPlay();
            }
        }

        // Slider zerstören (bei Resize zu Desktop)
        destroy() {
            // Alle Timer stoppen
            this.pauseAutoPlay();
            this.pauseAllVideos();

            // Events entfernen und Original-Layout wiederherstellen
            // Implementation wenn nötig
        }
    }

    // Initialisierung
    initServiceCardSliders();

    // Re-initialisierung bei Resize
    let resizeTimeout;
    window.addEventListener('resize', () => {
        clearTimeout(resizeTimeout);
        resizeTimeout = setTimeout(() => {
            if (window.innerWidth <= 780) {
                initServiceCardSliders();
            }
        }, 250);
    });
});