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
            // Performance optimizations
            this.cardContainers = new Map();
            this.observerInstances = new Set();
            this.activeTimeouts = new Set();
            this.throttleDelay = 500; // Increased delay for Firefox
            this.lastUpdate = 0;

            this.init();
        }

        init() {
            // Wait for DOM and then fix video visibility
            setTimeout(() => {
                this.fixVideoVisibility();

                // Special mobile handling
                if (window.innerWidth <= 780) {
                    // Check if any containers are sliders first
                    const hasSliders = document.querySelector('[data-mobile-slider="true"]');

                    if (hasSliders) {
                        console.log('Mobile sliders detected - letting slider JS handle all videos');
                        // Don't start sequential system if sliders exist
                        return;
                    } else {
                        console.log('No mobile sliders - starting sequential video system');
                        this.setupMobileVideos();
                        this.observeSliderChanges();
                    }
                }
            }, 1000);
        }

        setupMobileVideos() {
            console.log('Setting up mobile videos...');

            const videoCards = document.querySelectorAll('.riman-card--has-video');

            videoCards.forEach((card, index) => {
                const video = card.querySelector('.riman-card-video');
                const mediaContainer = card.querySelector('.riman-card-media');
                const poster = card.querySelector('.riman-card-poster');

                if (!video || !mediaContainer) {
                    console.log(`Card ${index + 1}: Missing video or media container`);
                    return;
                }

                console.log(`Setting up mobile video ${index + 1}...`);

                // Load video source immediately on mobile
                const videoSrc = video.dataset.src || card.dataset.videoSrc;
                if (videoSrc) {
                    console.log(`Loading video source: ${videoSrc}`);
                    video.src = videoSrc;
                    video.load();

                    // Set video attributes for mobile
                    video.muted = true;
                    video.playsInline = true;
                    video.controls = false;
                    video.autoplay = false;

                    // Position video correctly within clipping mask
                    video.style.cssText = `
                        position: absolute !important;
                        top: 0 !important;
                        left: 0 !important;
                        width: 100% !important;
                        height: 100% !important;
                        z-index: 3 !important;
                        opacity: 1 !important;
                        display: block !important;
                        object-fit: cover !important;
                    `;

                    // Hide poster on mobile since video is visible
                    if (poster) {
                        poster.style.opacity = '0';
                    }

                    // Keep media container design intact
                    mediaContainer.style.position = 'relative';
                    mediaContainer.style.overflow = 'hidden'; // Keep clipping

                    // Add active classes
                    video.classList.add('is-playing');
                    card.classList.add('video-active');

                    console.log(`Mobile video ${index + 1} setup complete and visible`);
                } else {
                    console.log(`Card ${index + 1}: No video source found`);
                }
            });

            // Start video sequence for mobile
            setTimeout(() => {
                console.log('Starting mobile video sequence...');
                this.setupVideoSequence();
            }, 500);
        }

        fixVideoVisibility() {
            console.log('Making videos visible in service cards...');

            // Find all video cards
            const videoCards = document.querySelectorAll('.riman-card--has-video');
            if (videoCards.length === 0) {
                console.log('No video cards found');
                return;
            }

            videoCards.forEach((card, index) => {
                const mediaContainer = card.querySelector('.riman-card-media');
                const video = card.querySelector('.riman-card-video');
                const poster = card.querySelector('.riman-card-poster');
                const videoSrc = card.dataset.videoSrc;

                if (!videoSrc || !mediaContainer || !video) {
                    console.log(`Skipping card ${index + 1} - missing elements`);
                    return;
                }

                // Keep clipping mask but position video above it
                mediaContainer.style.position = 'relative';
                mediaContainer.style.overflow = 'hidden';

                // Position video above clipping mask
                video.style.position = 'absolute';
                video.style.top = '0';
                video.style.left = '0';
                video.style.width = '100%';
                video.style.height = '100%';
                video.style.objectFit = 'cover';
                video.style.zIndex = '10'; // Above clipping mask
                video.style.opacity = '0'; // Initially hidden
                video.style.clipPath = 'none'; // Video itself ignores clipping
                video.style.webkitClipPath = 'none';

                // Set video attributes
                video.src = videoSrc;
                video.muted = true;
                video.playsInline = true;
                video.preload = 'none';
                video.controls = false;

                console.log(`Card ${index + 1} video setup complete`);
            });

            // Start the sequence
            this.setupVideoSequence();

            // Critical: Observe slider changes for video synchronization
            this.observeSliderChanges();
        }

        // Simple mobile video fix - fix clipping mask problem
        simpleMobileVideoFix() {
            console.log('Applying mobile video fix for clipping mask...');

            const allVideos = document.querySelectorAll('.riman-card-video');
            console.log(`Found ${allVideos.length} videos to fix`);

            allVideos.forEach((video, index) => {
                console.log(`\n=== Video ${index + 1} Debug ===`);
                console.log('Video element:', video);
                console.log('Data-src:', video.dataset.src);
                console.log('Current src:', video.src);
                console.log('Network state:', video.networkState);
                console.log('Ready state:', video.readyState);

                // Videos müssen ABSOLUTE sein um im clip-path zu bleiben
                video.style.cssText = `
                    position: absolute !important;
                    top: 0 !important;
                    left: 0 !important;
                    width: 100% !important;
                    height: 100% !important;
                    opacity: 1 !important;
                    z-index: 2 !important;
                    object-fit: cover !important;
                    border: 3px solid lime !important;
                    background: rgba(0,255,0,0.3) !important;
                `;

                // Hide poster when video shows
                const poster = video.parentElement?.querySelector('.riman-card-poster');
                if (poster) {
                    poster.style.opacity = '0';
                    poster.style.zIndex = '1';
                }

                // Ensure media container is properly positioned
                const mediaContainer = video.closest('.riman-card-media');
                if (mediaContainer) {
                    mediaContainer.style.position = 'relative';
                }

                // Force load video source
                const videoSrc = video.dataset.src;
                if (videoSrc) {
                    console.log(`Setting video source: ${videoSrc}`);
                    video.src = videoSrc;

                    // Add all attributes for mobile playback
                    video.muted = true;
                    video.playsInline = true;
                    video.autoplay = false; // Start manually
                    video.preload = 'metadata';
                    video.controls = false; // No controls for clean UI
                    video.loop = false;

                    video.addEventListener('loadeddata', () => {
                        console.log(`Video ${index + 1} loaded successfully`);
                    });

                    video.addEventListener('error', (e) => {
                        console.error(`Video ${index + 1} error:`, e, video.error);
                    });

                    video.load();

                    // Only play first video to start sequence
                    if (index === 0) {
                        setTimeout(() => {
                            console.log(`Starting video sequence with video 1...`);
                            video.play().then(() => {
                                console.log(`✅ Video sequence started!`);

                                // Stop video after 5 seconds and move to next
                                setTimeout(() => {
                                    video.pause();
                                    video.currentTime = 0;
                                    console.log('Video 1 finished, moving to next...');
                                    this.playNextVideoInSequence(allVideos, 1);
                                }, 5000);
                            }).catch(error => {
                                console.error(`❌ Video 1 play failed:`, error);
                            });
                        }, 1000);
                    }
                }
            });
        }

        // Play next video in sequence
        playNextVideoInSequence(allVideos, currentIndex) {
            if (currentIndex >= allVideos.length) {
                // Sequence finished, restart
                console.log('Video sequence completed, restarting...');
                setTimeout(() => {
                    this.playNextVideoInSequence(allVideos, 0);
                }, 1000);
                return;
            }

            const video = allVideos[currentIndex];
            console.log(`Playing video ${currentIndex + 1}...`);

            // Hide previous video
            if (currentIndex > 0) {
                const prevVideo = allVideos[currentIndex - 1];
                prevVideo.style.opacity = '0';
            }

            // Show current video
            video.style.opacity = '1';
            video.play().then(() => {
                console.log(`✅ Video ${currentIndex + 1} playing`);

                // Stop after 5 seconds and move to next
                setTimeout(() => {
                    video.pause();
                    video.currentTime = 0;
                    video.style.opacity = '0';
                    console.log(`Video ${currentIndex + 1} finished`);
                    this.playNextVideoInSequence(allVideos, currentIndex + 1);
                }, 5000);
            }).catch(error => {
                console.error(`Video ${currentIndex + 1} play failed:`, error);
                // Skip to next video if this one fails
                this.playNextVideoInSequence(allVideos, currentIndex + 1);
            });
        }

        // Force video start on mobile
        forceMobileVideoStart() {
            console.log('DISABLED: Video system turned off');
            return;

            // DEBUG: Inspect DOM structure
            const allVideoCards = document.querySelectorAll('.riman-card--has-video');
            console.log('=== DOM STRUCTURE DEBUG ===');
            console.log('Total video cards found:', allVideoCards.length);

            allVideoCards.forEach((card, index) => {
                console.log(`\nCard ${index + 1}:`, card);

                const video = card.querySelector('.riman-card-video');
                const poster = card.querySelector('.riman-card-poster');
                const media = card.querySelector('.riman-card-media');

                console.log('  - Video element:', video);
                console.log('  - Poster element:', poster);
                console.log('  - Media container:', media);

                if (video) {
                    console.log('  - Video styles:', {
                        display: getComputedStyle(video).display,
                        opacity: getComputedStyle(video).opacity,
                        position: getComputedStyle(video).position,
                        width: getComputedStyle(video).width,
                        height: getComputedStyle(video).height,
                        visibility: getComputedStyle(video).visibility
                    });
                }

                if (media) {
                    console.log('  - Media container styles:', {
                        display: getComputedStyle(media).display,
                        position: getComputedStyle(media).position,
                        width: getComputedStyle(media).width,
                        height: getComputedStyle(media).height
                    });
                }
            });
            console.log('=== END DOM DEBUG ===\n');

            const mobileSliders = document.querySelectorAll('[data-mobile-slider="true"]');

            mobileSliders.forEach(container => {
                const cards = container.querySelectorAll('.riman-card--has-video');
                if (cards.length > 0) {
                    console.log('Found', cards.length, 'video cards in mobile slider');
                    videoSequenceActive = true;
                    currentVideoIndex = 0;
                    this.playNextVideo(Array.from(cards));
                }
            });

            // Also try non-slider containers
            const allContainers = document.querySelectorAll('.riman-service-cards-wrap');
            allContainers.forEach(container => {
                if (container.dataset.mobileSlider !== 'true') {
                    const cards = container.querySelectorAll('.riman-card--has-video');
                    if (cards.length > 0 && !videoSequenceActive) {
                        console.log('Found', cards.length, 'video cards in regular container');
                        videoSequenceActive = true;
                        currentVideoIndex = 0;
                        this.playNextVideo(Array.from(cards));
                    }
                }
            });
        }

        // Cleanup method for memory management
        cleanup() {
            // Clear all timeouts
            this.activeTimeouts.forEach(timeout => clearTimeout(timeout));
            this.activeTimeouts.clear();

            // Disconnect all observers
            this.observerInstances.forEach(observer => observer.disconnect());
            this.observerInstances.clear();

            // Clear containers cache
            this.cardContainers.clear();
        }

        setupVideoSequence() {
            // Cache DOM queries for performance
            const serviceCards = document.querySelectorAll('.riman-service-cards-wrap');

            serviceCards.forEach(container => {
                const cards = container.querySelectorAll('.riman-card--has-video');

                if (cards.length === 0) return;

                // Cache container data
                const containerData = {
                    element: container,
                    cards: Array.from(cards),
                    isSlider: container.dataset.mobileSlider === 'true',
                    autoplay: container.dataset.sliderAutoplay === 'true',
                    interval: parseInt(container.dataset.sliderInterval) || 5000
                };

                this.cardContainers.set(container, containerData);

                if (containerData.isSlider) {
                    sliderInterval = containerData.interval;
                }

                // Initialize video sequence
                this.initializeCards(containerData.cards, container);

                // Start sequence when container is visible
                this.observeContainer(container, containerData.cards);
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
            const containerData = this.cardContainers.get(container);

            // Mobile slider: completely disable sequential video system
            if (containerData && containerData.isSlider && window.innerWidth <= 780) {
                console.log('Mobile slider detected - completely disabling sequential video system');
                // Stop any running video sequences
                videoSequenceActive = false;
                this.clearAllTimeouts();
                return;
            }

            // Desktop: use intersection observer
            const observer = new IntersectionObserver((entries) => {
                const entry = entries[0];
                if (!entry) return;

                if (entry.isIntersecting && !videoSequenceActive) {
                    // Shorter delay for desktop
                    this.createTimeout(() => {
                        if (entry.isIntersecting && !videoSequenceActive) {
                            this.startSequence(cards, container);
                        }
                    }, 500);
                } else if (!entry.isIntersecting && videoSequenceActive) {
                    this.stopSequence(cards);
                }
            }, {
                threshold: 0.2,
                rootMargin: '0px'
            });

            this.observerInstances.add(observer);
            observer.observe(container);
        }

        // Throttle utility for performance
        throttle(func, delay) {
            return (...args) => {
                const now = Date.now();
                if (now - this.lastUpdate >= delay) {
                    this.lastUpdate = now;
                    func.apply(this, args);
                }
            };
        }

        observeSliderChanges() {
            // Mobile slider detection
            const sliderContainers = document.querySelectorAll('[data-mobile-slider="true"]');

            sliderContainers.forEach(container => {
                const containerData = this.cardContainers.get(container);
                if (!containerData || !containerData.isSlider) return;

                // Start videos immediately on mobile if slider is present
                if (window.innerWidth <= 780) {
                    // Mobile: Start video sequence for visible slide
                    this.startMobileSliderSequence(container, containerData.cards);
                }

                // Watch for slider changes - simplified
                const observer = new MutationObserver(this.throttle(() => {
                    if (containerData) {
                        this.handleSliderChange(containerData.cards);
                    }
                }, this.throttleDelay));

                this.observerInstances.add(observer);

                observer.observe(container, {
                    attributes: true,
                    subtree: false,
                    attributeFilter: ['class']
                });
            });
        }

        // New method for mobile slider video sequence
        startMobileSliderSequence(container, cards) {
            if (cards.length === 0) return;

            // Check if this is actually a slider container
            const isSlider = container.dataset.mobileSlider === 'true';

            if (isSlider) {
                console.log('Mobile slider video sequence disabled - mobile slider JS handles videos');
                // Let mobile slider JS handle video playback
                return;
            } else {
                console.log('Starting mobile video sequence for non-slider container');
                // For non-slider mobile containers, start normal sequence
                videoSequenceActive = true;
                currentVideoIndex = 0;
                this.playNextVideo(cards);
            }
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

            // Clear all managed timeouts
            this.clearAllTimeouts();

            // Stop all videos and show posters
            cards.forEach(card => {
                const video = card.querySelector('.riman-card-video');
                if (video) {
                    this.stopVideo(video);
                }
            });

            currentVideoIndex = 0;
        }

        // Enhanced timeout management
        createTimeout(callback, delay) {
            const timeoutId = setTimeout(() => {
                this.activeTimeouts.delete(timeoutId);
                callback();
            }, delay);
            this.activeTimeouts.add(timeoutId);
            return timeoutId;
        }

        clearAllTimeouts() {
            // Clear global timeout
            if (videoTimeout) {
                clearTimeout(videoTimeout);
                videoTimeout = null;
            }

            // Clear all managed timeouts
            this.activeTimeouts.forEach(timeout => clearTimeout(timeout));
            this.activeTimeouts.clear();
        }

        playNextVideo(cards) {
            console.log('playNextVideo called with', cards.length, 'cards, currentIndex:', currentVideoIndex, 'active:', videoSequenceActive);

            if (!videoSequenceActive || currentVideoIndex >= cards.length) {
                // Sequence completed - restart or stop
                currentVideoIndex = 0;
                console.log('Sequence completed, restarting...');

                // Auto-restart sequence for continuous playback using managed timeout
                videoTimeout = this.createTimeout(() => {
                    if (videoSequenceActive) {
                        this.playNextVideo(cards);
                    }
                }, 1000);
                return;
            }

            const currentCard = cards[currentVideoIndex];
            const video = currentCard?.querySelector('.riman-card-video');
            const poster = currentCard?.querySelector('.riman-card-poster');

            console.log('Current card:', currentCard, 'Video:', video, 'Poster:', poster);

            if (!video || !poster) {
                console.log('No video or poster found, moving to next');
                this.moveToNext(cards);
                return;
            }

            // Load video if not already loaded
            if (!video.dataset.loaded) {
                console.log('Loading video:', video.dataset.src);
                this.loadVideo(video, () => {
                    this.startVideo(video, poster, cards);
                });
            } else {
                console.log('Video already loaded, starting playback');
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
            if (!videoSequenceActive) {
                console.log('Video sequence not active, aborting');
                return;
            }

            const card = video.closest('.riman-card--has-video');
            console.log('Starting video playback for card:', card);

            // Force mobile video positioning with inline styles
            if (window.innerWidth <= 780) {
                video.style.position = 'relative';
                video.style.top = 'auto';
                video.style.left = 'auto';
                video.style.width = '100%';
                video.style.height = 'auto';
                video.style.minHeight = '200px';
                video.style.zIndex = '4';
                console.log('Applied mobile video styles');
            }

            // Fade out poster, fade in video
            poster.style.opacity = '0';
            video.style.opacity = '1';

            // Add video playing class
            video.classList.add('is-playing');
            if (card) {
                card.classList.add('video-active');
            }

            // Start playback
            video.currentTime = 0;
            video.play().then(() => {
                console.log('Video playback started successfully');
            }).catch(error => {
                console.warn('Video play failed:', error);
                this.onVideoEnded(video, cards);
            });

            // Set maximum duration (5 seconds) using managed timeout
            videoTimeout = this.createTimeout(() => {
                if (videoSequenceActive && video === cards[currentVideoIndex]?.querySelector('.riman-card-video')) {
                    console.log('Video duration timeout reached, stopping video');
                    this.stopVideo(video);
                    this.onVideoEnded(video, cards);
                }
            }, 5000);
        }

        stopVideo(video) {
            if (!video) return;

            video.pause();
            video.currentTime = 0;

            // Remove video playing classes
            video.classList.remove('is-playing');

            // Show poster again
            const card = video.closest('.riman-card--has-video');
            const poster = card?.querySelector('.riman-card-poster');

            if (card) {
                card.classList.remove('video-active');
            }

            if (poster) {
                poster.style.opacity = '1';
            }
            video.style.opacity = '0';
        }

        onVideoEnded(video, cards) {
            this.stopVideo(video);

            this.clearAllTimeouts();

            // Move to next video after short delay using managed timeout
            videoTimeout = this.createTimeout(() => {
                this.moveToNext(cards);
            }, 500);
        }

        moveToNext(cards) {
            currentVideoIndex++;
            this.playNextVideo(cards);
        }

        // Play button functions removed to fix performance issues

        handleSliderChange(cards) {
            // Stop current video when slider changes
            if (currentVideoIndex < cards.length) {
                const currentCard = cards[currentVideoIndex];
                const video = currentCard?.querySelector('.riman-card-video');

                if (video) {
                    this.stopVideo(video);
                }
            }

            // Reset and restart sequence
            currentVideoIndex = 0;
            this.clearAllTimeouts();

            // Mobile vs Desktop handling
            if (window.innerWidth <= 780) {
                // Mobile: Always restart video sequence immediately
                videoTimeout = this.createTimeout(() => {
                    if (videoSequenceActive) {
                        console.log('Mobile: Restarting video sequence after slider change');
                        this.playNextVideo(cards);
                    }
                }, 300); // Shorter delay for mobile
            } else {
                // Desktop: Normal restart
                videoTimeout = this.createTimeout(() => {
                    if (videoSequenceActive) {
                        this.playNextVideo(cards);
                    }
                }, 600);
            }
        }
    }

    // Initialize when DOM is ready
    const videoSequenceInstance = new ServiceCardsVideoSequence();

    // Cleanup on page unload to prevent memory leaks
    window.addEventListener('beforeunload', () => {
        videoSequenceInstance.cleanup();
    });

    // Cleanup on visibility change (mobile backgrounding)
    document.addEventListener('visibilitychange', () => {
        if (document.hidden) {
            videoSequenceInstance.clearAllTimeouts();
            // Stop all videos when page is hidden
            videoSequenceActive = false;
        }
    });

    // Additional Firefox-specific cleanup
    window.addEventListener('blur', () => {
        videoSequenceInstance.clearAllTimeouts();
    });

    // Global access for debugging
    window.RimanServiceCardsVideo = ServiceCardsVideoSequence;
    window.rimanVideoSequenceInstance = videoSequenceInstance;

})();