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
                interval: parseInt(container.dataset.sliderInterval || '5000', 10),
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
            slide.appendChild(card.cloneNode(true));
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
                    }, 10);
                }, 300);
            } else {
                setTimeout(() => {
                    this.isTransitioning = false;
                }, 300);
            }

            this.handleVideoPlayback();
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

            this.autoPlayTimer = setInterval(() => {
                if (!this.isDragging && !document.hidden) {
                    this.nextSlide();
                }
            }, this.options.interval);
        }

        pauseAutoPlay() {
            if (this.autoPlayTimer) {
                clearInterval(this.autoPlayTimer);
                this.autoPlayTimer = null;
            }
        }

        // Video-Synchronisation
        handleVideoPlayback() {
            // Alle Videos pausieren (inklusive Clones)
            this.allSlides.forEach((slide, index) => {
                const video = slide.querySelector('video');
                if (video) {
                    const realIndex = this.currentSlide - 1; // -1 wegen Clone am Anfang
                    const isActiveSlide = index === this.currentSlide;

                    if (isActiveSlide) {
                        // Aktuelles Video aktivieren
                        video.classList.add('is-active');
                        if (video.autoplay || video.dataset.autoplay === 'true') {
                            const playPromise = video.play();
                            if (playPromise) {
                                playPromise.catch(e => console.log('Video autoplay prevented:', e));
                            }
                        }
                    } else {
                        // Andere Videos pausieren und deaktivieren
                        video.classList.remove('is-active');
                        video.pause();
                        video.currentTime = 0;
                    }
                }
            });
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
            this.pauseAutoPlay();
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