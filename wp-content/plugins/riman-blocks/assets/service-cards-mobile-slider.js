/**
 * RIMAN Service Cards Mobile Slider
 * Implementiert Swipe-Gesten, Auto-Play und Video-Synchronisation
 */

document.addEventListener('DOMContentLoaded', function() {

    // Initialisiere alle Service Card Slider
    const initServiceCardSliders = () => {
        const sliderContainers = document.querySelectorAll('.riman-service-cards-wrap[data-mobile-slider="true"]');

        sliderContainers.forEach(container => {
            // Nur auf Mobile aktivieren (≤780px)
            if (window.innerWidth > 780) return;

            const grid = container.querySelector('.riman-service-grid');
            const cards = Array.from(grid.querySelectorAll('.riman-service-card'));

            if (cards.length === 0) return;

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
        cards.forEach(card => {
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
        });

        // Erste Slide als Clone am Ende
        const firstSlideClone = document.createElement('div');
        firstSlideClone.className = 'riman-service-slide clone-slide';
        firstSlideClone.appendChild(firstCardClone);
        sliderTrack.appendChild(firstSlideClone);

        sliderWrapper.appendChild(sliderTrack);

        // Navigation erstellen
        const navigation = createSliderNavigation(cards.length);
        sliderWrapper.appendChild(navigation);

        // Original Grid ersetzen
        container.replaceChild(sliderWrapper, grid);

        // Mobile Slider CSS-Klasse hinzufügen
        container.classList.add('riman-mobile-slider-active');
    };

    // Slider-Navigation erstellen
    const createSliderNavigation = (slideCount) => {
        const nav = document.createElement('div');
        nav.className = 'riman-slider-nav';

        for (let i = 0; i < slideCount; i++) {
            const dot = document.createElement('button');
            dot.className = 'riman-slider-dot';
            dot.setAttribute('aria-label', `Slide ${i + 1}`);
            dot.dataset.slide = i;
            if (i === 0) dot.classList.add('active');
            nav.appendChild(dot);
        }

        return nav;
    };

    // Hauptklasse für Service Card Slider
    class ServiceCardSlider {
        constructor(container, options = {}) {
            this.container = container;
            this.track = container.querySelector('.riman-service-slider-track');
            this.allSlides = Array.from(container.querySelectorAll('.riman-service-slide'));
            this.slides = this.allSlides.filter(slide => !slide.classList.contains('clone-slide'));
            this.dots = Array.from(container.querySelectorAll('.riman-slider-dot'));

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
            this.updateSlider();

            if (this.isPlaying) {
                this.startAutoPlay();
            }

            // Videos in aktueller Slide aktivieren
            this.handleVideoPlayback();
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
            const transform = -this.currentSlide * slideWidth;

            this.track.style.transform = `translateX(${transform}px)`;

            // Dots aktualisieren - auf echte Slides basiert (0-indexiert)
            const realSlideIndex = this.currentSlide - 1; // -1 wegen Clone am Anfang
            this.dots.forEach((dot, index) => {
                dot.classList.toggle('active', index === realSlideIndex);
            });
        }

        getSlideWidth() {
            return this.allSlides[0] ? this.allSlides[0].offsetWidth : 0;
        }

        getTransformX() {
            const transform = this.track.style.transform;
            const match = transform.match(/translateX\\(([^)]+)\\)/);
            return match ? parseFloat(match[1]) : 0;
        }

        // Auto-Play Funktionalität
        startAutoPlay() {
            if (!this.options.autoPlay || this.autoPlayTimer) return;

            // Delay first auto-advance for mobile stability
            this.autoPlayTimer = setTimeout(() => {
                this.autoPlayTimer = setInterval(() => {
                    if (!this.isDragging && !document.hidden) {
                        this.nextSlide();
                    }
                }, this.options.interval);
            }, 2000);
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

            // Auf Mobile: Mobile-Version verwenden falls verfügbar
            if (window.innerWidth <= 780 && video.dataset.srcMobile) {
                videoSrc = video.dataset.srcMobile;
                console.log('📱 Using mobile-optimized video:', videoSrc);
            } else if (video.dataset.srcDesktop) {
                videoSrc = video.dataset.srcDesktop;
                console.log('🖥️ Using desktop video:', videoSrc);
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

            // Loading-Indikator hinzufügen
            this.showVideoLoading(currentSlideEl);

            // Video abspielen und Timer starten
            video.play().then(() => {
                console.log('✅ Mobile slider video playing');
                this.hideVideoLoading(currentSlideEl);

                // Video-Ende Handler
                const handleVideoEnd = () => {
                    console.log('📺 Video ended, showing play button and advancing');
                    this.showPlayButton(currentSlideEl);

                    // Auto-advance nach kurzer Pause wenn autoplay aktiv
                    if (this.options.autoPlay) {
                        this.videoAdvanceTimer = setTimeout(() => {
                            this.nextSlide();
                        }, 2000); // 2 Sekunden Pause statt 1
                    }

                    video.removeEventListener('ended', handleVideoEnd);
                };

                video.addEventListener('ended', handleVideoEnd);

                // 6-Sekunden Timer für garantierten Advance (länger als Hero Slider)
                this.videoTimer = setTimeout(() => {
                    if (!video.ended) {
                        video.pause();
                        handleVideoEnd();
                    }
                }, 6000);

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

        // Video Loading-Indikator anzeigen
        showVideoLoading(slide) {
            const card = slide.querySelector('.riman-service-card');
            if (!card) return;

            // Existierenden Loading-Indikator entfernen
            const existingLoader = slide.querySelector('.riman-video-loading');
            if (existingLoader) {
                existingLoader.remove();
            }

            // Loading-Spinner erstellen
            const loadingIndicator = document.createElement('div');
            loadingIndicator.className = 'riman-video-loading';
            loadingIndicator.innerHTML = `
                <div class="riman-loading-spinner">
                    <div class="riman-spinner-circle"></div>
                    <div class="riman-loading-text">Video wird geladen...</div>
                </div>
            `;

            card.appendChild(loadingIndicator);
        }

        // Video Loading-Indikator verstecken
        hideVideoLoading(slide) {
            const loadingIndicator = slide.querySelector('.riman-video-loading');
            if (loadingIndicator) {
                loadingIndicator.remove();
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