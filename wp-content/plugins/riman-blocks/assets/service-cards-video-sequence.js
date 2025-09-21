/**
 * RIMAN Service Cards - Sequential Video Playback with LCP Optimization
 *
 * Features:
 * - LCP optimized: Poster images load immediately
 * - Lazy video loading: Videos load only when needed
 * - Sequential playback: One video at a time
 * - Slider synchronization: Stop video on slider change
 * - 5-second duration with smooth transitions
 */

(function() {
    'use strict';

    let currentVideoIndex = 0;
    let videoSequenceActive = false;
    let sliderInterval = 5000; // Default 5 seconds
    let videoTimeout = null;
    let sliderObserver = null;

    class ServiceCardsVideoSequence {
        constructor() {
            this.init();
        }

        init() {
            document.addEventListener('DOMContentLoaded', () => {
                this.setupVideoSequence();
                this.observeSliderChanges();
            });
        }

        setupVideoSequence() {
            const serviceCards = document.querySelectorAll('.riman-service-cards-wrap');

            serviceCards.forEach(container => {
                const cards = container.querySelectorAll('.riman-card--has-video');

                if (cards.length === 0) return;

                // Get slider settings from container
                const isSlider = container.dataset.mobileSlider === 'true';
                const autoplay = container.dataset.sliderAutoplay === 'true';
                const interval = parseInt(container.dataset.sliderInterval) || 5000;

                if (isSlider) {
                    sliderInterval = interval;
                }

                // Initialize video sequence
                this.initializeCards(cards, container);

                // Start sequence when container is visible
                this.observeContainer(container, cards);
            });
        }

        initializeCards(cards, container) {
            cards.forEach((card, index) => {
                const video = card.querySelector('.riman-card-video');
                const poster = card.querySelector('.riman-card-poster');

                if (!video || !poster) return;

                // Set up video attributes
                video.dataset.sequenceIndex = index;
                video.dataset.duration = '5'; // 5 seconds

                // Ensure poster is visible initially
                poster.style.cssText = 'position: relative; z-index: 2; opacity: 1; transition: opacity 0.3s ease;';

                // Video event listeners
                video.addEventListener('loadeddata', () => {
                    video.dataset.loaded = 'true';
                });

                video.addEventListener('ended', () => {
                    this.onVideoEnded(video, cards);
                });

                video.addEventListener('timeupdate', () => {
                    // Stop video after 5 seconds regardless of actual duration
                    if (video.currentTime >= 5) {
                        this.stopVideo(video);
                        this.onVideoEnded(video, cards);
                    }
                });

                // Error handling
                video.addEventListener('error', () => {
                    console.warn('Service card video failed to load:', video.dataset.src);
                    this.onVideoEnded(video, cards);
                });
            });
        }

        observeContainer(container, cards) {
            const observer = new IntersectionObserver((entries) => {
                entries.forEach(entry => {
                    if (entry.isIntersecting && !videoSequenceActive) {
                        // Start video sequence when container becomes visible
                        this.startSequence(cards, container);
                    } else if (!entry.isIntersecting && videoSequenceActive) {
                        // Stop sequence when container leaves viewport
                        this.stopSequence(cards);
                    }
                });
            }, {
                threshold: 0.2,
                rootMargin: '50px'
            });

            observer.observe(container);
        }

        observeSliderChanges() {
            // Watch for slider changes (mobile slider)
            const sliderContainers = document.querySelectorAll('[data-mobile-slider="true"]');

            sliderContainers.forEach(container => {
                sliderObserver = new MutationObserver((mutations) => {
                    mutations.forEach(mutation => {
                        if (mutation.type === 'attributes' &&
                            (mutation.attributeName === 'class' ||
                             mutation.attributeName === 'data-current-slide')) {

                            // Slider changed - stop current video and restart sequence
                            const cards = container.querySelectorAll('.riman-card--has-video');
                            this.handleSliderChange(cards);
                        }
                    });
                });

                sliderObserver.observe(container, {
                    attributes: true,
                    subtree: true,
                    attributeFilter: ['class', 'data-current-slide', 'style']
                });
            });
        }

        startSequence(cards, container) {
            if (videoSequenceActive || cards.length === 0) return;

            videoSequenceActive = true;
            currentVideoIndex = 0;

            console.log('Starting service cards video sequence with', cards.length, 'videos');

            this.playNextVideo(cards);
        }

        stopSequence(cards) {
            videoSequenceActive = false;

            if (videoTimeout) {
                clearTimeout(videoTimeout);
                videoTimeout = null;
            }

            // Stop all videos and show posters
            cards.forEach(card => {
                const video = card.querySelector('.riman-card-video');
                if (video) {
                    this.stopVideo(video);
                }
            });

            currentVideoIndex = 0;
        }

        playNextVideo(cards) {
            if (!videoSequenceActive || currentVideoIndex >= cards.length) {
                // Sequence completed - restart or stop
                currentVideoIndex = 0;

                // Auto-restart sequence for continuous playback
                videoTimeout = setTimeout(() => {
                    if (videoSequenceActive) {
                        this.playNextVideo(cards);
                    }
                }, 1000);
                return;
            }

            const currentCard = cards[currentVideoIndex];
            const video = currentCard.querySelector('.riman-card-video');
            const poster = currentCard.querySelector('.riman-card-poster');

            if (!video || !poster) {
                this.moveToNext(cards);
                return;
            }

            // Load video if not already loaded
            if (!video.dataset.loaded) {
                this.loadVideo(video, () => {
                    this.startVideo(video, poster, cards);
                });
            } else {
                this.startVideo(video, poster, cards);
            }
        }

        loadVideo(video, callback) {
            const videoSrc = video.dataset.src;
            const source = video.querySelector('source');

            if (!videoSrc) {
                callback();
                return;
            }

            // Set video source
            video.src = videoSrc;
            if (source) {
                source.src = videoSrc;
            }

            video.addEventListener('loadeddata', callback, { once: true });
            video.addEventListener('error', callback, { once: true });

            // Load the video
            video.load();
        }

        startVideo(video, poster, cards) {
            if (!videoSequenceActive) return;

            // Fade out poster, fade in video
            poster.style.opacity = '0';
            video.style.opacity = '1';

            // Start playback
            video.currentTime = 0;
            video.play().catch(error => {
                console.warn('Video play failed:', error);
                this.onVideoEnded(video, cards);
            });

            // Set maximum duration (5 seconds)
            videoTimeout = setTimeout(() => {
                if (videoSequenceActive && video === cards[currentVideoIndex]?.querySelector('.riman-card-video')) {
                    this.stopVideo(video);
                    this.onVideoEnded(video, cards);
                }
            }, 5000);
        }

        stopVideo(video) {
            if (!video) return;

            video.pause();
            video.currentTime = 0;

            // Show poster again
            const card = video.closest('.riman-card--has-video');
            const poster = card?.querySelector('.riman-card-poster');

            if (poster) {
                poster.style.opacity = '1';
            }
            video.style.opacity = '0';
        }

        onVideoEnded(video, cards) {
            this.stopVideo(video);

            if (videoTimeout) {
                clearTimeout(videoTimeout);
                videoTimeout = null;
            }

            // Show play button after video ends
            this.showPlayButton(video);

            // Move to next video after short delay
            videoTimeout = setTimeout(() => {
                this.moveToNext(cards);
            }, 500);
        }

        moveToNext(cards) {
            currentVideoIndex++;
            this.playNextVideo(cards);
        }

        showPlayButton(video) {
            const card = video.closest('.riman-card--has-video');
            if (!card) return;

            // Remove existing play button
            const existingButton = card.querySelector('.riman-video-play-button');
            if (existingButton) {
                existingButton.remove();
            }

            // Create play button
            const playButton = document.createElement('div');
            playButton.className = 'riman-video-play-button';
            playButton.innerHTML = `
                <div class="riman-play-circle">
                    <svg class="riman-play-icon" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                        <path d="M8 5v14l11-7z" fill="currentColor"/>
                    </svg>
                </div>
            `;

            // Add click handler to navigate to page
            playButton.addEventListener('click', (e) => {
                e.preventDefault();
                e.stopPropagation();

                const href = card.dataset.href || card.querySelector('a')?.href;
                if (href) {
                    window.location.href = href;
                }
            });

            // Add to card media
            const mediaContainer = card.querySelector('.riman-card-media');
            if (mediaContainer) {
                mediaContainer.appendChild(playButton);
            }

            // Fade in play button
            setTimeout(() => {
                playButton.style.opacity = '1';
            }, 100);
        }

        hidePlayButton(card) {
            const playButton = card.querySelector('.riman-video-play-button');
            if (playButton) {
                playButton.style.opacity = '0';
                setTimeout(() => {
                    playButton.remove();
                }, 300);
            }
        }

        handleSliderChange(cards) {
            // Stop current video when slider changes
            if (currentVideoIndex < cards.length) {
                const currentCard = cards[currentVideoIndex];
                const video = currentCard?.querySelector('.riman-card-video');

                if (video) {
                    this.stopVideo(video);
                    // Hide play button when slider changes
                    this.hidePlayButton(currentCard);
                }
            }

            // Reset and restart sequence
            currentVideoIndex = 0;

            if (videoTimeout) {
                clearTimeout(videoTimeout);
                videoTimeout = null;
            }

            // Fix for mobile slider: Check if we're in a mobile slider environment
            const isInMobileSlider = cards[0]?.closest('[data-mobile-slider="true"]');

            if (isInMobileSlider && window.innerWidth <= 780) {
                // In mobile slider: don't restart video sequence automatically
                // Wait for slider to stabilize before checking if video should start
                videoTimeout = setTimeout(() => {
                    const activeSlide = cards[0]?.closest('.riman-service-slider-wrapper')
                        ?.querySelector('.riman-service-slide:not(.clone-slide)');

                    if (activeSlide && videoSequenceActive) {
                        // Only restart if the slide with videos is currently visible
                        const slideCards = activeSlide.querySelectorAll('.riman-card--has-video');
                        if (slideCards.length > 0) {
                            this.playNextVideo(Array.from(slideCards));
                        }
                    }
                }, 800); // Wait longer for slider transition
            } else {
                // Desktop: restart sequence normally
                videoTimeout = setTimeout(() => {
                    if (videoSequenceActive) {
                        this.playNextVideo(cards);
                    }
                }, 600);
            }
        }
    }

    // Initialize when DOM is ready
    new ServiceCardsVideoSequence();

    // Global access for debugging
    window.RimanServiceCardsVideo = ServiceCardsVideoSequence;

})();