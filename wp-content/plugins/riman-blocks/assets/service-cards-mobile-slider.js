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

            const grid = container.querySelector('.riman-service-grid');
            if (!grid) {
                console.log('❌ No .riman-service-grid found in container');
                return;
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

        // Clone erste Slide für infinite loop
        const firstCardClone = cards[0].cloneNode(true);
        const lastCardClone = cards[cards.length - 1].cloneNode(true);

        // Letzte Slide als Clone am Anfang
        const lastSlideClone = document.createElement('div');
        lastSlideClone.className = 'riman-service-slide clone-slide';
        lastSlideClone.appendChild(lastCardClone);
        sliderTrack.appendChild(lastSlideClone);

        // Original Cards in Slider-Track verschieben
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
        });

        // Erste Slide als Clone am Ende
        const firstSlideClone = document.createElement('div');
        firstSlideClone.className = 'riman-service-slide clone-slide';
        firstSlideClone.appendChild(firstCardClone);
        sliderTrack.appendChild(firstSlideClone);

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

        sliderWrapper.appendChild(trackContainer);

        // Navigation erstellen und über Slider positionieren
        const navigation = createSliderNavigation(cards.length);
        trackContainer.appendChild(navigation);

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

    // Slider-Navigation erstellen (Dots + Arrows)
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
            this.currentSlide = 1; // Startposition (nach dem Clone)
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
            // Check if slides have proper dimensions
            const checkDimensions = () => {
                const slideWidth = this.getSlideWidth();
                console.log('🔍 Checking slide dimensions:', {
                    slideWidth: slideWidth,
                    slideElement: this.allSlides[0],
                    slideOffsetWidth: this.allSlides[0] ? this.allSlides[0].offsetWidth : 'no slides',
                    containerWidth: this.container.offsetWidth,
                    trackWidth: this.track.offsetWidth
                });

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

            // Try immediately first
            if (checkDimensions()) {
                return;
            }

            // If dimensions not ready, wait and retry
            console.log('⏳ Dimensions not ready, waiting...');
            let attempts = 0;
            const maxAttempts = 20;
            const retryInterval = setInterval(() => {
                attempts++;
                console.log(`🔄 Retry ${attempts}/${maxAttempts} checking dimensions...`);

                if (checkDimensions() || attempts >= maxAttempts) {
                    clearInterval(retryInterval);
                    if (attempts >= maxAttempts) {
                        console.warn('⚠️ Max attempts reached, forcing initialization with fallback');
                        this.forceInitWithFallback();
                    }
                }
            }, 50);
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
            this.initialTransform = this.getTransformX();

            this.pauseAutoPlay();
            this.track.style.transition = 'none';
        }

        onTouchMove(e) {
            if (!this.isDragging || e.touches.length > 1) return;

            e.preventDefault();

            this.currentX = e.touches[0].clientX;
            const deltaX = this.currentX - this.startX;
            const deltaY = Math.abs(e.touches[0].clientY - this.startY);

            // Nur horizontal scrollen wenn mehr horizontal als vertikal
            if (Math.abs(deltaX) > deltaY) {
                const newTransform = this.initialTransform + deltaX;
                this.track.style.transform = `translateX(${newTransform}px)`;
            }
        }

        onTouchEnd(e) {
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

            // Auto-Play nach Swipe wieder starten
            if (this.options.autoPlay) {
                setTimeout(() => this.startAutoPlay(), 3000);
            }
        }

        // Mouse Events (für Desktop-Testing)
        onMouseDown(e) {
            this.isDragging = true;
            this.startX = e.clientX;
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

            if (this.options.autoPlay) {
                setTimeout(() => this.startAutoPlay(), 3000);
            }
        }

        // Slider Navigation
        nextSlide() {
            if (this.isTransitioning) return;

            this.isTransitioning = true;
            this.currentSlide++;
            this.updateSlider();

            // Wenn wir beim ersten Clone sind, springe ohne Animation zur ersten echten Slide
            if (this.currentSlide > this.slideCount) {
                setTimeout(() => {
                    this.track.style.transition = 'none';
                    this.currentSlide = 1;
                    this.updateSlider();

                    // Animation wieder aktivieren
                    setTimeout(() => {
                        this.track.style.transition = 'transform 0.3s ease';
                        this.isTransitioning = false;
                        // Handle video playback after loop reset
                        this.handleVideoPlayback();
                    }, 50); // Longer delay for stability
                }, 300);
            } else {
                setTimeout(() => {
                    this.isTransitioning = false;
                    // Handle video playback for normal transitions
                    this.handleVideoPlayback();
                }, 300);
            }
        }

        previousSlide() {
            if (this.isTransitioning) return;

            this.isTransitioning = true;
            this.currentSlide--;
            this.updateSlider();

            // Wenn wir beim letzten Clone sind, springe ohne Animation zur letzten echten Slide
            if (this.currentSlide < 1) {
                setTimeout(() => {
                    this.track.style.transition = 'none';
                    this.currentSlide = this.slideCount;
                    this.updateSlider();

                    // Animation wieder aktivieren
                    setTimeout(() => {
                        this.track.style.transition = 'transform 0.3s ease';
                        this.isTransitioning = false;
                    }, 10);
                }, 300);
            } else {
                setTimeout(() => {
                    this.isTransitioning = false;
                }, 300);
            }

            this.handleVideoPlayback();
        }

        goToSlide(index) {
            if (index >= 0 && index < this.slideCount) {
                this.currentSlide = index + 1; // +1 wegen Clone am Anfang
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

            // CRITICAL FIX: Correct transform calculation
            // Slide structure: [clone-last, real-1, real-2, clone-first]
            // When currentSlide=1 (first real slide), we want transform=0 to show it
            const transform = -(this.currentSlide - 1) * slideWidth;

            console.log('🎯 UpdateSlider:', {
                currentSlide: this.currentSlide,
                slideWidth: slideWidth,
                transform: transform,
                slideCount: this.slideCount,
                calculation: `-(${this.currentSlide} - 1) * ${slideWidth} = ${transform}`
            });

            this.track.style.transform = `translateX(${transform}px)`;

            // Dots aktualisieren - auf echte Slides basiert (0-indexiert)
            const realSlideIndex = this.currentSlide - 1; // -1 wegen Clone am Anfang
            this.dots.forEach((dot, index) => {
                dot.classList.toggle('active', index === realSlideIndex);
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

                // Try container width
                if (this.container.offsetWidth > 0) {
                    slideWidth = this.container.offsetWidth;
                    console.log('📐 Using container width as fallback:', slideWidth);
                }
                // Try track width
                else if (this.track.offsetWidth > 0) {
                    slideWidth = this.track.offsetWidth;
                    console.log('📐 Using track width as fallback:', slideWidth);
                }
                // Try window width as last resort
                else {
                    slideWidth = window.innerWidth;
                    console.log('📐 Using window width as fallback:', slideWidth);
                }

                // Force the slide to have proper width
                if (slideWidth > 0) {
                    this.allSlides.forEach(slide => {
                        slide.style.width = '100%';
                        slide.style.flexBasis = '100%';
                        slide.style.minWidth = `${slideWidth}px`;
                    });
                    console.log('🔧 Applied fallback width to all slides:', slideWidth);
                }
            }

            console.log('📏 Final slide width:', slideWidth);
            return slideWidth;
        }

        getTransformX() {
            const transform = this.track.style.transform;
            const match = transform.match(/translateX\\(([^)]+)\\)/);
            return match ? parseFloat(match[1]) : 0;
        }

        // Auto-Play Funktionalität - ERST nach allen Videos bereit
        startAutoPlay() {
            if (!this.options.autoPlay || this.autoPlayTimer) return;

            // Prüfe ALLE Videos bevor Auto-Play startet
            const allVideos = Array.from(this.container.querySelectorAll('.riman-card-video'));
            let videosLoaded = 0;
            const totalVideos = allVideos.length;

            if (totalVideos === 0) {
                // Keine Videos - starte normalen Auto-Play
                this.startDelayedAutoPlay();
                return;
            }

            const checkAllVideosLoaded = () => {
                videosLoaded++;
                console.log(`📹 Video loaded: ${videosLoaded}/${totalVideos}`);

                if (videosLoaded >= totalVideos) {
                    console.log('✅ All videos loaded - starting auto-play');
                    this.startDelayedAutoPlay();
                }
            };

            // Warte auf alle Videos
            allVideos.forEach(video => {
                if (video.readyState >= 2) {
                    // Video bereits geladen
                    checkAllVideosLoaded();
                } else {
                    // Warte auf Video
                    video.addEventListener('loadeddata', checkAllVideosLoaded, { once: true });
                    video.addEventListener('error', checkAllVideosLoaded, { once: true }); // Auch bei Fehler weitermachen
                }
            });

            // Backup: Starte nach 10 Sekunden trotzdem
            setTimeout(() => {
                if (!this.autoPlayTimer) {
                    console.log('⏰ Timeout reached - starting auto-play anyway');
                    this.startDelayedAutoPlay();
                }
            }, 10000);
        }

        startDelayedAutoPlay() {
            this.autoPlayTimer = setTimeout(() => {
                this.autoPlayTimer = setInterval(() => {
                    if (!this.isDragging && !document.hidden) {
                        this.nextSlide();
                    }
                }, this.options.interval);
            }, 3000); // Längere Pause nach Video-Loading
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

            // Finde aktuelle Slide (echte Slide, keine Clone)
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

                    // Auto-advance nach kurzer Pause wenn autoplay aktiv
                    if (this.options.autoPlay) {
                        this.videoAdvanceTimer = setTimeout(() => {
                            this.nextSlide();
                        }, 2000);
                    }

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

                // Bei Fehler: Advance nach längerer Pause
                if (this.options.autoPlay) {
                    this.videoAdvanceTimer = setTimeout(() => this.nextSlide(), 3000);
                }
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