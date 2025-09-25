/**
 * RIMAN Service Cards Responsive Carousel
 * True carousel behavior - single card scrolling with multiple visible cards
 */

document.addEventListener('DOMContentLoaded', function() {
    console.log('🎠 Responsive Carousel: Starting...');

    initResponsiveCarousels();

    // Re-initialize on resize with debounce
    let resizeTimeout;
    window.addEventListener('resize', () => {
        clearTimeout(resizeTimeout);
        resizeTimeout = setTimeout(() => {
            console.log('📱 Viewport changed - reinitializing carousels...');
            initResponsiveCarousels();
        }, 250);
    });
});

function initResponsiveCarousels() {
    // Find all service card containers
    const containers = document.querySelectorAll('.riman-service-cards-wrap');
    console.log('📱 Found containers:', containers.length);

    containers.forEach((container, index) => {
        // Get viewport configuration
        const viewportConfig = getViewportConfig();

        console.log(`🏗️ Processing container ${index + 1} for ${viewportConfig.name} (${viewportConfig.visibleCards} visible cards)`);

        // Find service cards
        const cards = container.querySelectorAll('.riman-service-card');
        console.log('🃏 Found cards:', cards.length);

        // Cleanup existing carousels
        cleanupExistingCarousel(container);

        // Decide if carousel is needed
        const needsCarousel = shouldUseCarousel(cards.length, viewportConfig);

        if (needsCarousel) {
            createResponsiveCarousel(container, cards, viewportConfig);
        } else {
            // Show normal grid
            showNormalGrid(container);
        }
    });
}

function getViewportConfig() {
    const width = window.innerWidth;

    if (width <= 767) {
        // Mobile: 1 visible card, scroll 1 at a time
        return {
            name: 'mobile',
            visibleCards: 1,
            scrollAmount: 1,
            useCarousel: true
        };
    } else if (width <= 1199) {
        // Tablet: 2 visible cards, scroll 1 at a time
        return {
            name: 'tablet',
            visibleCards: 2,
            scrollAmount: 1,
            useCarousel: true
        };
    } else {
        // Desktop: 3+ visible cards, scroll 1 at a time, only if many cards
        return {
            name: 'desktop',
            visibleCards: 3,
            scrollAmount: 1,
            useCarousel: false // Only for 6+ cards
        };
    }
}

function shouldUseCarousel(cardCount, config) {
    if (!config.useCarousel && config.name === 'desktop') {
        // Desktop: only use carousel for many cards
        return cardCount > 6;
    }

    // Mobile/Tablet: use carousel if more cards than visible
    return cardCount > config.visibleCards;
}

function cleanupExistingCarousel(container) {
    // Remove existing carousel wrapper
    const existingCarousel = container.querySelector('.responsive-carousel-wrapper');
    if (existingCarousel) {
        existingCarousel.remove();
    }

    // Show original grid
    const grid = container.querySelector('.riman-service-grid');
    if (grid) {
        grid.style.display = '';
    }
}

function showNormalGrid(container) {
    const grid = container.querySelector('.riman-service-grid');
    if (grid) {
        grid.style.display = '';
        console.log('✅ Showing normal grid layout');
    }
}

function createResponsiveCarousel(container, cards, config) {
    console.log(`🔧 Creating ${config.name} carousel with ${config.visibleCards} visible cards...`);

    // Hide original grid
    const grid = container.querySelector('.riman-service-grid');
    if (grid) {
        grid.style.display = 'none';
    }

    // Create carousel wrapper
    const carouselWrapper = createCarouselWrapper();

    // Copy any overlap styles from container to carousel
    const containerStyle = container.getAttribute('style') || '';
    const marginTopMatch = containerStyle.match(/margin-top\s*:\s*-?\d+px/i);
    if (marginTopMatch) {
        const existingStyle = carouselWrapper.getAttribute('style') || '';
        carouselWrapper.setAttribute('style', existingStyle + '; ' + marginTopMatch[0]);
        console.log('🔄 Applied overlap style to carousel:', marginTopMatch[0]);
    }

    const carouselTrack = createCarouselTrack();

    // Calculate track width to fit all cards with gaps
    const totalCards = cards.length;
    const containerWidth = container.offsetWidth;
    const visibleCards = config.visibleCards;

    // Use percentage-based track width for compatibility
    const trackWidthPercent = (totalCards / visibleCards) * 100;
    carouselTrack.style.width = `${trackWidthPercent}%`;

    // Clone all cards into carousel with natural sizing
    const cardElements = [];
    cards.forEach((card, index) => {
        const cardClone = cloneCardWithData(card);
        // Let flexbox handle sizing naturally with CSS gap
        cardClone.style.cssText = `
            flex: 1 1 0;
            min-width: 0;
            box-sizing: border-box;
            transition: transform 0.3s ease;
        `;
        cardElements.push(cardClone);
        carouselTrack.appendChild(cardClone);
    });

    console.log(`🎪 Track setup: ${totalCards} cards, track width: ${trackWidthPercent}%, natural flex sizing with 40px gaps`);

    // Create navigation
    const navigation = createCarouselNavigation(cards.length, config.visibleCards);

    // Assemble carousel
    carouselWrapper.appendChild(carouselTrack);
    carouselWrapper.appendChild(navigation);
    container.appendChild(carouselWrapper);

    // Initialize carousel behavior
    initCarouselBehavior(
        carouselWrapper,
        carouselTrack,
        navigation,
        cardElements,
        config,
        container
    );

    console.log('✅ Responsive carousel created successfully');
}

function createCarouselWrapper() {
    const wrapper = document.createElement('div');
    wrapper.className = 'responsive-carousel-wrapper';
    wrapper.style.cssText = `
        position: relative;
        width: 100%;
        overflow-x: hidden;
        overflow-y: visible;
        margin: 20px 0;
        padding: 60px 0 80px 0;
    `;
    return wrapper;
}

function createCarouselTrack() {
    const track = document.createElement('div');
    track.className = 'responsive-carousel-track';
    // Track width will be set dynamically based on total cards
    track.style.cssText = `
        display: flex;
        transition: transform 0.4s cubic-bezier(0.4, 0, 0.2, 1);
        transform: translateX(0%);
        will-change: transform;
        overflow: visible;
    `;
    return track;
}

function cloneCardWithData(originalCard) {
    const cardClone = originalCard.cloneNode(true);

    // Preserve video data
    const originalVideo = originalCard.querySelector('.riman-card-video');
    const clonedVideo = cardClone.querySelector('.riman-card-video');

    if (originalVideo && clonedVideo) {
        // Copy video attributes and data
        const attrs = ['src', 'poster', 'muted', 'loop', 'preload', 'playsinline'];
        attrs.forEach(attr => {
            if (originalVideo.hasAttribute(attr)) {
                clonedVideo.setAttribute(attr, originalVideo.getAttribute(attr));
            }
        });

        // Copy data attributes
        Object.keys(originalVideo.dataset).forEach(key => {
            clonedVideo.dataset[key] = originalVideo.dataset[key];
        });
    }

    return cardClone;
}

function createCarouselNavigation(totalCards, visibleCards) {
    const nav = document.createElement('div');
    nav.className = 'responsive-carousel-nav';
    nav.style.cssText = `
        display: flex;
        justify-content: center;
        align-items: center;
        gap: 8px;
        margin-top: 60px;
        position: relative;
        z-index: 10;
        clear: both;
    `;

    // Create previous button
    const prevBtn = document.createElement('button');
    prevBtn.className = 'carousel-btn carousel-prev';
    prevBtn.innerHTML = '‹';
    prevBtn.setAttribute('aria-label', 'Previous card');
    prevBtn.style.cssText = `
        width: 32px;
        height: 32px;
        border: 2px solid #B68C2F;
        background: white;
        color: #B68C2F;
        border-radius: 50%;
        cursor: pointer;
        font-size: 18px;
        font-weight: bold;
        display: flex;
        align-items: center;
        justify-content: center;
        transition: all 0.3s ease;
        outline: none;
    `;

    // Create dots (one per card, not per visible set)
    const dotsContainer = document.createElement('div');
    dotsContainer.className = 'carousel-dots';
    dotsContainer.style.cssText = `
        display: flex;
        gap: 6px;
        align-items: center;
    `;

    for (let i = 0; i < totalCards; i++) {
        const dot = document.createElement('button');
        dot.className = `carousel-dot ${i === 0 ? 'active' : ''}`;
        dot.setAttribute('data-card', i);
        dot.setAttribute('aria-label', `Go to card ${i + 1}`);
        dot.style.cssText = `
            width: ${i < visibleCards ? '12px' : '8px'};
            height: ${i < visibleCards ? '12px' : '8px'};
            border-radius: 50%;
            border: none;
            background: ${i === 0 ? '#B68C2F' : 'rgba(182, 140, 47, 0.3)'};
            cursor: pointer;
            transition: all 0.3s ease;
            outline: none;
        `;
        dotsContainer.appendChild(dot);
    }

    // Create next button
    const nextBtn = document.createElement('button');
    nextBtn.className = 'carousel-btn carousel-next';
    nextBtn.innerHTML = '›';
    nextBtn.setAttribute('aria-label', 'Next card');
    nextBtn.style.cssText = prevBtn.style.cssText;

    nav.appendChild(prevBtn);
    nav.appendChild(dotsContainer);
    nav.appendChild(nextBtn);

    return nav;
}

function initCarouselBehavior(wrapper, track, navigation, cardElements, config, container) {
    let currentIndex = 0;
    const totalCards = cardElements.length;
    const visibleCards = config.visibleCards;
    const maxIndex = Math.max(0, totalCards - visibleCards);

    let autoplayInterval = null;
    let userHasInteracted = false;

    // Auto-play configuration
    const autoplayEnabled = container.dataset.sliderAutoplay === 'true';
    const autoplayDelay = parseInt(container.dataset.sliderInterval) || 5000;

    function goToCard(index) {
        // Clamp index to valid range
        index = Math.max(0, Math.min(maxIndex, index));
        currentIndex = index;

        // Calculate translateX percentage for single-card movement
        // Each card takes (100/totalCards)% of the track width
        const cardWidthInTrack = 100 / totalCards;
        const translateX = -(index * cardWidthInTrack);

        // Apply transform with smooth transition
        track.style.transform = `translateX(${translateX}%)`;

        // Update dots
        updateDots();

        // Update navigation buttons
        const prevBtn = navigation.querySelector('.carousel-prev');
        const nextBtn = navigation.querySelector('.carousel-next');

        if (prevBtn && nextBtn) {
            prevBtn.style.opacity = currentIndex === 0 ? '0.5' : '1';
            prevBtn.style.cursor = currentIndex === 0 ? 'not-allowed' : 'pointer';

            nextBtn.style.opacity = currentIndex === maxIndex ? '0.5' : '1';
            nextBtn.style.cursor = currentIndex === maxIndex ? 'not-allowed' : 'pointer';
        }

        console.log(`🎯 Moved to card ${currentIndex + 1}/${totalCards} (translateX: ${translateX}%, showing ${Math.min(visibleCards, totalCards - currentIndex)} cards)`);
    }

    function updateDots() {
        const dots = navigation.querySelectorAll('.carousel-dot');
        dots.forEach((dot, index) => {
            const isVisible = index >= currentIndex && index < currentIndex + visibleCards;
            const isActive = index === currentIndex;

            dot.classList.toggle('active', isActive);

            // Visual feedback for dot states
            if (isActive) {
                dot.style.background = '#B68C2F';
                dot.style.width = '12px';
                dot.style.height = '12px';
                dot.style.boxShadow = '0 0 0 2px rgba(182, 140, 47, 0.3)';
            } else if (isVisible) {
                dot.style.background = 'rgba(182, 140, 47, 0.6)';
                dot.style.width = '10px';
                dot.style.height = '10px';
                dot.style.boxShadow = 'none';
            } else {
                dot.style.background = 'rgba(182, 140, 47, 0.3)';
                dot.style.width = '8px';
                dot.style.height = '8px';
                dot.style.boxShadow = 'none';
            }
        });
    }

    function nextCard() {
        if (currentIndex < maxIndex) {
            goToCard(currentIndex + 1);
        } else {
            // Loop to beginning
            goToCard(0);
        }
    }

    function prevCard() {
        if (currentIndex > 0) {
            goToCard(currentIndex - 1);
        } else {
            // Loop to end
            goToCard(maxIndex);
        }
    }

    function disableAutoplay() {
        if (!userHasInteracted) {
            userHasInteracted = true;
            if (autoplayInterval) {
                clearInterval(autoplayInterval);
                console.log('⏸️ Autoplay disabled by user interaction');
            }
        }
    }

    // Navigation button handlers
    const prevBtn = navigation.querySelector('.carousel-prev');
    const nextBtn = navigation.querySelector('.carousel-next');

    if (prevBtn) {
        prevBtn.addEventListener('click', () => {
            disableAutoplay();
            prevCard();
        });
    }

    if (nextBtn) {
        nextBtn.addEventListener('click', () => {
            disableAutoplay();
            nextCard();
        });
    }

    // Dot click handlers - direct navigation to specific card
    navigation.querySelectorAll('.carousel-dot').forEach((dot, index) => {
        dot.addEventListener('click', () => {
            disableAutoplay();
            // Navigate to show the clicked card as the first visible card
            // But limit to maxIndex (totalCards - visibleCards)
            const targetIndex = Math.min(index, maxIndex);
            console.log(`🎯 Dot ${index} clicked, navigating to card index ${targetIndex} (maxIndex: ${maxIndex})`);
            goToCard(targetIndex);
        });
    });

    // Touch/swipe support for single-card movement
    let startX = 0;
    let isDragging = false;
    let startTime = 0;
    let currentTransform = 0;
    let initialTransform = 0;

    wrapper.addEventListener('touchstart', (e) => {
        startX = e.touches[0].clientX;
        startTime = Date.now();
        isDragging = true;

        // Get current transform value
        const currentMatrix = window.getComputedStyle(track).transform;
        if (currentMatrix && currentMatrix !== 'none') {
            const values = currentMatrix.split('(')[1].split(')')[0].split(',');
            currentTransform = parseFloat(values[4]) || 0;
        } else {
            currentTransform = 0;
        }
        initialTransform = currentTransform;

        // Temporarily disable transitions for smooth dragging
        track.style.transition = 'none';
    }, { passive: true });

    wrapper.addEventListener('touchmove', (e) => {
        if (!isDragging) return;

        const currentX = e.touches[0].clientX;
        const diffX = currentX - startX;

        // Apply live transform during drag
        const newTransform = initialTransform + diffX;
        track.style.transform = `translateX(${newTransform}px)`;

        // Prevent scrolling during swipe
        e.preventDefault();
    }, { passive: false });

    wrapper.addEventListener('touchend', (e) => {
        if (!isDragging) return;

        // Re-enable transitions
        track.style.transition = 'transform 0.4s cubic-bezier(0.4, 0, 0.2, 1)';

        const endX = e.changedTouches[0].clientX;
        const diffX = startX - endX;
        const diffTime = Date.now() - startTime;
        const velocity = Math.abs(diffX) / diffTime;

        // Require minimum distance and reasonable velocity for card change
        if (Math.abs(diffX) > 50 && velocity > 0.1) {
            disableAutoplay();
            if (diffX > 0) {
                // Swipe left - show next card (move right side card into view)
                nextCard();
            } else {
                // Swipe right - show previous card (move left card into view)
                prevCard();
            }
        } else {
            // Snap back to current position if not enough movement
            goToCard(currentIndex);
        }

        isDragging = false;
    }, { passive: true });

    // Auto-play setup
    if (autoplayEnabled && totalCards > visibleCards) {
        console.log(`▶️ Starting autoplay (${autoplayDelay}ms interval)`);
        autoplayInterval = setInterval(() => {
            if (!userHasInteracted) {
                nextCard();
            }
        }, autoplayDelay);
    }

    // Initialize UI
    goToCard(0);
}

// Public API
window.RimanResponsiveCarousel = {
    reinitialize: initResponsiveCarousels,
    version: '2.0.0'
};

console.log('🎠 RIMAN Responsive Carousel loaded successfully');