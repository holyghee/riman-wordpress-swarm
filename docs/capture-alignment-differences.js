/**
 * Script to capture and demonstrate Service Cards alignment differences
 * Run this in the browser console on: http://127.0.0.1:8801/riman-seiten/sicherheits-koordination_und_mediation/
 */

async function captureAlignmentDifferences() {
    console.log('🎯 RIMAN Service Cards Alignment Capture');
    console.log('=' .repeat(60));

    // Find the service cards container
    const container = document.querySelector('.riman-service-cards-wrap');
    if (!container) {
        console.error('❌ Service Cards Container nicht gefunden!');
        return;
    }

    // Store original state
    const originalClasses = container.className;
    console.log('📦 Original Classes:', originalClasses);

    // CSS to highlight boundaries
    const style = document.createElement('style');
    style.id = 'alignment-test-styles';
    style.innerHTML = `
        .alignment-boundary {
            border: 4px solid red !important;
            background: rgba(255, 0, 0, 0.1) !important;
            position: relative !important;
        }
        .alignment-boundary::before {
            content: attr(data-test-label) !important;
            position: absolute !important;
            top: -35px !important;
            left: 0 !important;
            background: red !important;
            color: white !important;
            padding: 8px 16px !important;
            font-size: 16px !important;
            font-weight: bold !important;
            z-index: 1000 !important;
            border-radius: 4px !important;
            white-space: nowrap !important;
        }
        .grid-boundary {
            border: 3px dashed blue !important;
            background: rgba(0, 0, 255, 0.05) !important;
        }
    `;
    document.head.appendChild(style);

    const measurements = [];

    // Function to measure current state
    function measureCurrent(alignmentType) {
        const rect = container.getBoundingClientRect();
        const viewportWidth = window.innerWidth;
        const grid = container.querySelector('.riman-service-grid');
        const gridRect = grid ? grid.getBoundingClientRect() : null;

        const measurement = {
            type: alignmentType,
            viewport: viewportWidth,
            container: {
                width: Math.round(rect.width),
                left: Math.round(rect.left),
                right: Math.round(rect.right)
            },
            spacing: {
                leftOffset: Math.round(rect.left),
                rightOffset: Math.round(viewportWidth - rect.right),
                utilization: Math.round((rect.width / viewportWidth) * 100)
            },
            grid: gridRect ? {
                width: Math.round(gridRect.width),
                left: Math.round(gridRect.left),
                right: Math.round(gridRect.right)
            } : null,
            classes: container.className
        };

        return measurement;
    }

    // Function to apply alignment and measure
    async function testAlignment(type, classes) {
        console.log(`\n🔄 Testing ${type.toUpperCase()} alignment...`);

        // Reset classes
        container.className = 'riman-service-cards-wrap';

        // Apply new classes
        classes.forEach(cls => container.classList.add(cls));

        // Add visual indicators
        container.classList.add('alignment-boundary');
        container.setAttribute('data-test-label', `${type.toUpperCase()} - ${container.offsetWidth}px wide`);

        const grid = container.querySelector('.riman-service-grid');
        if (grid) {
            grid.classList.add('grid-boundary');
        }

        // Wait for CSS to apply
        await new Promise(resolve => setTimeout(resolve, 300));

        // Measure
        const measurement = measureCurrent(type);
        measurements.push(measurement);

        // Log results
        console.log(`📐 Container: ${measurement.container.width}px wide`);
        console.log(`📏 Offsets: ${measurement.spacing.leftOffset}px left, ${measurement.spacing.rightOffset}px right`);
        console.log(`🎯 Viewport utilization: ${measurement.spacing.utilization}%`);
        console.log(`🎨 Classes: ${measurement.classes}`);

        return measurement;
    }

    // Test all three alignments
    const alignments = [
        { type: 'container', classes: ['sc-container', 'sc-responsive'] },
        { type: 'wide', classes: ['alignwide', 'sc-responsive'] },
        { type: 'full', classes: ['alignfull', 'sc-responsive'] }
    ];

    for (const alignment of alignments) {
        await testAlignment(alignment.type, alignment.classes);
        // Wait 2 seconds between tests for visual confirmation
        await new Promise(resolve => setTimeout(resolve, 2000));
    }

    // Show comparison table
    console.log('\n📊 VISUAL DIFFERENCES COMPARISON');
    console.log('=' .repeat(60));

    const table = measurements.map(m => ({
        'Alignment': m.type.toUpperCase(),
        'Width': m.container.width + 'px',
        'Left Margin': m.spacing.leftOffset + 'px',
        'Right Margin': m.spacing.rightOffset + 'px',
        'Viewport %': m.spacing.utilization + '%'
    }));

    console.table(table);

    // Calculate differences
    console.log('\n🔍 WIDTH DIFFERENCES:');
    for (let i = 0; i < measurements.length - 1; i++) {
        const current = measurements[i];
        const next = measurements[i + 1];
        const diff = Math.abs(current.container.width - next.container.width);
        console.log(`${current.type.toUpperCase()} vs ${next.type.toUpperCase()}: ${diff}px difference`);
    }

    // Check if differences are actually visible
    const containerWidth = measurements.find(m => m.type === 'container')?.container.width;
    const wideWidth = measurements.find(m => m.type === 'wide')?.container.width;
    const fullWidth = measurements.find(m => m.type === 'full')?.container.width;

    console.log('\n🚨 PROBLEM ANALYSIS:');
    if (containerWidth === wideWidth && wideWidth === fullWidth) {
        console.log('❌ ALL ALIGNMENTS HAVE IDENTICAL WIDTHS!');
        console.log('💡 The CSS rules are not working correctly.');
        console.log('🔧 Need to check CSS specificity and rule conflicts.');
    } else {
        console.log('✅ Width differences detected:');
        console.log(`   Container: ${containerWidth}px`);
        console.log(`   Wide: ${wideWidth}px`);
        console.log(`   Full: ${fullWidth}px`);
    }

    // Restore original state
    setTimeout(() => {
        container.className = originalClasses;
        container.removeAttribute('data-test-label');
        const grid = container.querySelector('.riman-service-grid');
        if (grid) {
            grid.classList.remove('grid-boundary');
        }
        style.remove();
        console.log('\n🔄 Original state restored');
    }, 5000);

    return measurements;
}

// Auto-run
captureAlignmentDifferences();