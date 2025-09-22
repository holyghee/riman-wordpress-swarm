(() => {
    const markPosterStatus = (video, status) => {
        if (!video) {
            return;
        }

        video.dataset.rimanPosterStatus = status;
        const wrapper = video.closest('.riman-cover--has-poster');
        if (wrapper) {
            wrapper.dataset.rimanPosterStatus = status;
        }
    };

    const ensurePosterReady = (video) => {
        return new Promise((resolve) => {
            if (!video) {
                resolve();
                return;
            }

            const poster = video.getAttribute('poster') || video.dataset.rimanPoster || '';
            if (!poster) {
                markPosterStatus(video, 'missing');
                resolve();
                return;
            }

            if (video.dataset.rimanPosterStatus === 'valid' || video.dataset.rimanPosterStatus === 'invalid') {
                resolve();
                return;
            }

            const testImage = new Image();
            testImage.onload = () => {
                markPosterStatus(video, 'valid');
                resolve();
            };
            testImage.onerror = () => {
                markPosterStatus(video, 'invalid');
                video.removeAttribute('poster');
                resolve();
            };
            testImage.src = poster;
        });
    };

    const updateResponsiveVideoSource = (video) => {
        if (!video || !video.dataset.rimanResponsiveVideo) {
            return;
        }

        const isMobile = window.innerWidth <= 780;
        const mobileSource = video.dataset.srcMobile;
        const desktopSource = video.dataset.srcDesktop || video.dataset.src;

        let targetSource = desktopSource;
        let sourceType = 'desktop';

        // Mobile-Version verwenden falls verfügbar und auf Mobile
        if (isMobile && mobileSource) {
            targetSource = mobileSource;
            sourceType = 'mobile';
        }

        // Source nur ändern wenn nötig
        if (video.dataset.src !== targetSource) {
            console.log(`🎯 Cover Video switching to ${sourceType}:`, targetSource);
            video.dataset.src = targetSource;
        }
    };

    const loadVideoSources = (video) => {
        if (!video || video.dataset.rimanCoverLoaded === 'true') {
            return;
        }

        // Check for responsive video first
        updateResponsiveVideoSource(video);

        const dataSrc = video.getAttribute('data-src');
        if (dataSrc) {
            video.setAttribute('src', dataSrc);
            video.removeAttribute('data-src');
        }

        const sources = video.querySelectorAll('source[data-src]');
        sources.forEach((source) => {
            const sourceSrc = source.getAttribute('data-src');
            if (!sourceSrc) {
                return;
            }
            source.setAttribute('src', sourceSrc);
            source.removeAttribute('data-src');
        });

        video.load();
    };

    const activateVideo = (video) => {
        if (!video) {
            return;
        }

        video.dataset.rimanCoverLoaded = 'true';
        video.classList.add('is-active');

        const wrapper = video.closest('.riman-cover--has-poster');
        if (wrapper) {
            if (wrapper.style.backgroundImage) {
                wrapper.style.backgroundImage = 'none';
            }
        }

        const posterImage = video.parentElement ? video.parentElement.querySelector('.riman-card-poster') : null;
        if (posterImage) {
            if (!posterImage.style.transition) {
                posterImage.style.transition = 'opacity 0.2s ease';
            }
            posterImage.style.opacity = '0';
        }

        if (video.autoplay) {
            const playPromise = video.play();
            if (playPromise && typeof playPromise.then === 'function') {
                playPromise.catch(() => {});
            }
        }
    };

    const prepareVideo = (video, onReady) => {
        if (!video) {
            return;
        }

        const run = () => {
            const card = video.closest('.riman-card-has-video');
            if (card) {
                card.classList.remove('riman-card-video-ended');
            }
            activateVideo(video);
            if (typeof onReady === 'function') {
                onReady(video);
            }
        };

        if (video.readyState >= 2) {
            run();
            return;
        }

        video.addEventListener('loadeddata', run, { once: true });
    };

    const resetVideo = (video) => {
        if (!video) {
            return;
        }

        try {
            video.pause();
        } catch (e) {}

        try {
            video.currentTime = 0;
        } catch (e) {}

        video.classList.remove('is-active');

        const posterImage = video.parentElement ? video.parentElement.querySelector('.riman-card-poster') : null;
        if (posterImage) {
            if (!posterImage.style.transition) {
                posterImage.style.transition = 'opacity 0.2s ease';
            }
            posterImage.style.opacity = '1';
        }

        const card = video.closest('.riman-card-has-video');
        if (card) {
            card.classList.add('riman-card-video-ended');
        }
    };

    const sequentialQueue = [];
    const sequentialPending = [];
    let sequentialUnlocked = false;
    let sequentialListenersAttached = false;
    let sequentialProcessing = false;

    const queueSequentialVideo = (video) => {
        if (!video) {
            return;
        }
        if (video.dataset.rimanQueued === 'true' || video.dataset.rimanPlayed === 'true') {
            return;
        }
        video.dataset.rimanPending = 'false';
        video.dataset.rimanQueued = 'true';
        sequentialQueue.push(video);
    };

    const handleSequentialUnlock = () => {
        if (sequentialUnlocked) {
            return;
        }
        sequentialUnlocked = true;
        if (sequentialListenersAttached) {
            window.removeEventListener('scroll', handleSequentialUnlock);
            window.removeEventListener('pointerdown', handleSequentialUnlock);
            window.removeEventListener('keydown', handleSequentialUnlock);
            sequentialListenersAttached = false;
        }
        while (sequentialPending.length) {
            const video = sequentialPending.shift();
            queueSequentialVideo(video);
        }
        processSequentialQueue();
    };

    const attachSequentialListeners = () => {
        if (sequentialUnlocked || sequentialListenersAttached) {
            return;
        }
        if (window.scrollY > 0) {
            handleSequentialUnlock();
            return;
        }
        sequentialListenersAttached = true;
        window.addEventListener('scroll', handleSequentialUnlock, { once: true, passive: true });
        window.addEventListener('pointerdown', handleSequentialUnlock, { once: true });
        window.addEventListener('keydown', handleSequentialUnlock, { once: true });
    };

    const processSequentialQueue = () => {
        if (sequentialProcessing) {
            return;
        }

        const nextVideo = sequentialQueue.shift();
        if (!nextVideo) {
            return;
        }

        if (!nextVideo.isConnected) {
            nextVideo.dataset.rimanQueued = 'false';
            processSequentialQueue();
            return;
        }

        sequentialProcessing = true;

        ensurePosterReady(nextVideo).then(() => {
            const cleanupAndContinue = () => {
                nextVideo.dataset.rimanQueued = 'false';
                nextVideo.dataset.rimanPending = 'false';
                sequentialProcessing = false;
                processSequentialQueue();
            };

            let timeoutId = null;

            const handleCompletion = () => {
                if (timeoutId !== null) {
                    clearTimeout(timeoutId);
                    timeoutId = null;
                }
                nextVideo.removeEventListener('ended', handleCompletion);
                nextVideo.removeEventListener('error', handleCompletion);
                nextVideo.dataset.rimanPlayed = 'true';
                resetVideo(nextVideo);
                cleanupAndContinue();
            };

            prepareVideo(nextVideo, () => {
                nextVideo.addEventListener('ended', handleCompletion, { once: true });
                nextVideo.addEventListener('error', handleCompletion, { once: true });
                timeoutId = window.setTimeout(handleCompletion, 30000);
            });

            loadVideoSources(nextVideo);
        });
    };

    const scheduleVideo = (video) => {
        if (!video) {
            return;
        }

        const mode = video.dataset.rimanQueue || '';
        if (mode === 'sequential') {
            if (video.dataset.rimanPlayed === 'true' || video.dataset.rimanQueued === 'true') {
                return;
            }
            if (!sequentialUnlocked) {
                attachSequentialListeners();
                if (video.dataset.rimanPending !== 'true') {
                    video.dataset.rimanPending = 'true';
                    sequentialPending.push(video);
                }
                return;
            }
            queueSequentialVideo(video);
            processSequentialQueue();
            return;
        }

        if (video.dataset.rimanScheduled === 'true') {
            return;
        }
        video.dataset.rimanScheduled = 'true';

        ensurePosterReady(video).then(() => {
            loadVideoSources(video);
            prepareVideo(video);
        });
    };

    const init = () => {
        const videos = document.querySelectorAll('video[data-riman-cover-lazy]');
        if (!videos.length) {
            return;
        }

        // Add resize handler for responsive videos
        let resizeTimeout;
        const handleResize = () => {
            clearTimeout(resizeTimeout);
            resizeTimeout = setTimeout(() => {
                const responsiveVideos = document.querySelectorAll('video[data-riman-responsive-video]');
                responsiveVideos.forEach(updateResponsiveVideoSource);
            }, 250);
        };
        window.addEventListener('resize', handleResize);

        if (!('IntersectionObserver' in window)) {
            videos.forEach(scheduleVideo);
            return;
        }

        const observer = new IntersectionObserver((entries) => {
            entries.forEach((entry) => {
                if (!entry.isIntersecting) {
                    return;
                }

                const video = entry.target;
                scheduleVideo(video);
                observer.unobserve(video);
            });
        }, { rootMargin: '200px' });

        videos.forEach((video) => observer.observe(video));
    };

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', init);
    } else {
        init();
    }
})();
