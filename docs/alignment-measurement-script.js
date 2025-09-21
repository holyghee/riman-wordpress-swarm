/**
 * RIMAN Service Cards Alignment Visual Test Script
 *
 * This script demonstrates the visual differences between Container/Wide/Full alignments
 * by manipulating the CSS classes live and measuring the actual spacing differences.
 */

class AlignmentTester {
    constructor() {
        this.container = null;
        this.originalClasses = '';
        this.measurements = [];
        this.init();
    }

    init() {
        // Find the service cards container
        this.container = document.querySelector('.riman-service-cards-wrap');

        if (!this.container) {
            console.error('❌ Service Cards Container nicht gefunden!');
            return;
        }

        // Store original classes
        this.originalClasses = this.container.className;

        console.log('🎯 RIMAN Alignment Tester initialisiert');
        console.log('📦 Original Classes:', this.originalClasses);

        // Add visual indicators
        this.addVisualIndicators();

        // Start the demonstration
        this.runAlignmentDemo();
    }

    addVisualIndicators() {
        // Add measurement styles
        const style = document.createElement('style');
        style.id = 'alignment-test-styles';
        style.innerHTML = `
            .alignment-test-boundary {
                border: 3px solid red !important;
                position: relative !important;
                background: rgba(255, 0, 0, 0.05) !important;
            }

            .alignment-test-boundary::before {
                content: attr(data-alignment-type) !important;
                position: absolute !important;
                top: -30px !important;
                left: 0 !important;
                background: red !important;
                color: white !important;
                padding: 6px 12px !important;
                font-size: 14px !important;
                font-weight: bold !important;
                z-index: 1000 !important;
                border-radius: 4px !important;
            }

            .alignment-test-grid {
                border: 2px dashed blue !important;
                background: rgba(0, 0, 255, 0.05) !important;
                position: relative !important;
            }

            .alignment-test-grid::before {
                content: 'Grid Container - Width: ' attr(data-grid-width) !important;
                position: absolute !important;
                top: -25px !important;
                right: 0 !important;
                background: blue !important;
                color: white !important;
                padding: 4px 8px !important;
                font-size: 12px !important;
                z-index: 1000 !important;
                border-radius: 3px !important;
            }

            .alignment-measurement-overlay {
                position: fixed;
                top: 10px;
                right: 10px;
                background: rgba(0, 0, 0, 0.9);
                color: white;
                padding: 20px;
                border-radius: 8px;
                font-family: 'Courier New', monospace;
                font-size: 14px;
                z-index: 10000;
                max-width: 400px;
                line-height: 1.6;
            }
        `;
        document.head.appendChild(style);
    }

    measureContainer(alignmentType) {
        if (!this.container) return null;

        const rect = this.container.getBoundingClientRect();
        const viewportWidth = window.innerWidth;
        const grid = this.container.querySelector('.riman-service-grid');
        const gridRect = grid ? grid.getBoundingClientRect() : null;

        const measurement = {
            alignmentType: alignmentType,
            timestamp: new Date().toLocaleTimeString(),
            viewport: {
                width: viewportWidth,
                height: window.innerHeight
            },
            container: {
                width: Math.round(rect.width),
                height: Math.round(rect.height),
                left: Math.round(rect.left),
                right: Math.round(rect.right),
                top: Math.round(rect.top),
                bottom: Math.round(rect.bottom)
            },
            spacing: {
                leftOffset: Math.round(rect.left),
                rightOffset: Math.round(viewportWidth - rect.right),
                totalPadding: Math.round((viewportWidth - rect.width))
            },
            grid: gridRect ? {
                width: Math.round(gridRect.width),
                height: Math.round(gridRect.height),
                left: Math.round(gridRect.left),
                right: Math.round(gridRect.right)
            } : null,
            classes: this.container.className
        };

        this.measurements.push(measurement);
        return measurement;
    }

    setAlignment(alignmentType) {
        if (!this.container) return;

        // Remove all alignment classes
        this.container.classList.remove('sc-container', 'alignwide', 'alignfull', 'sc-responsive');

        // Add new alignment class
        switch(alignmentType) {
            case 'container':
                this.container.classList.add('sc-container', 'sc-responsive');
                break;
            case 'wide':
                this.container.classList.add('alignwide', 'sc-responsive');
                break;
            case 'full':
                this.container.classList.add('alignfull', 'sc-responsive');
                break;
        }

        // Add visual indicators
        this.container.classList.add('alignment-test-boundary');
        this.container.setAttribute('data-alignment-type',
            `${alignmentType.toUpperCase()} Alignment`);

        const grid = this.container.querySelector('.riman-service-grid');
        if (grid) {
            grid.classList.add('alignment-test-grid');
            grid.setAttribute('data-grid-width', Math.round(grid.getBoundingClientRect().width) + 'px');
        }

        // Wait for CSS to apply, then measure
        setTimeout(() => {
            const measurement = this.measureContainer(alignmentType);
            this.displayMeasurement(measurement);
            this.logMeasurement(measurement);
        }, 100);
    }

    displayMeasurement(measurement) {
        // Remove existing overlay
        const existing = document.querySelector('.alignment-measurement-overlay');
        if (existing) existing.remove();

        // Create new overlay
        const overlay = document.createElement('div');
        overlay.className = 'alignment-measurement-overlay';
        overlay.innerHTML = `
            <h3>📏 ${measurement.alignmentType.toUpperCase()} Alignment</h3>
            <div><strong>Viewport:</strong> ${measurement.viewport.width}px × ${measurement.viewport.height}px</div>
            <div><strong>Container Width:</strong> ${measurement.container.width}px</div>
            <div><strong>Left Offset:</strong> ${measurement.spacing.leftOffset}px</div>
            <div><strong>Right Offset:</strong> ${measurement.spacing.rightOffset}px</div>
            <div><strong>Total Padding:</strong> ${measurement.spacing.totalPadding}px</div>
            ${measurement.grid ? `<div><strong>Grid Width:</strong> ${measurement.grid.width}px</div>` : ''}
            <div><strong>Classes:</strong> ${measurement.classes.split(' ').slice(0, 3).join(' ')}</div>
            <div style="margin-top: 10px; font-size: 12px; opacity: 0.8;">
                Updated: ${measurement.timestamp}
            </div>
        `;

        document.body.appendChild(overlay);

        // Auto-remove after 10 seconds
        setTimeout(() => {
            if (overlay.parentNode) overlay.remove();
        }, 10000);
    }

    logMeasurement(measurement) {
        console.log(`\n🎯 ${measurement.alignmentType.toUpperCase()} ALIGNMENT MEASUREMENT`);
        console.log('=' .repeat(50));
        console.log('📐 Container Dimensions:');
        console.log(`   Width: ${measurement.container.width}px`);
        console.log(`   Height: ${measurement.container.height}px`);
        console.log(`   Position: ${measurement.container.left}px → ${measurement.container.right}px`);

        console.log('\n📏 Spacing Analysis:');
        console.log(`   Left Offset: ${measurement.spacing.leftOffset}px`);
        console.log(`   Right Offset: ${measurement.spacing.rightOffset}px`);
        console.log(`   Total Horizontal Padding: ${measurement.spacing.totalPadding}px`);

        if (measurement.grid) {
            console.log('\n🗂️ Grid Container:');
            console.log(`   Width: ${measurement.grid.width}px`);
            console.log(`   Position: ${measurement.grid.left}px → ${measurement.grid.right}px`);
        }

        console.log('\n🎨 CSS Classes:');
        console.log(`   ${measurement.classes}`);

        console.log('\n🖥️ Viewport:');
        console.log(`   ${measurement.viewport.width}px × ${measurement.viewport.height}px`);
        console.log('=' .repeat(50));
    }

    async runAlignmentDemo() {
        console.log('\n🚀 Starting Alignment Demonstration...');
        console.log('📝 This will cycle through Container → Wide → Full alignments');
        console.log('⏱️ Each alignment will be shown for 5 seconds');

        const alignments = ['container', 'wide', 'full'];

        for (let i = 0; i < alignments.length; i++) {
            const alignment = alignments[i];

            console.log(`\n⏳ Showing ${alignment.toUpperCase()} alignment...`);
            this.setAlignment(alignment);

            // Wait 5 seconds before next alignment
            if (i < alignments.length - 1) {
                await new Promise(resolve => setTimeout(resolve, 5000));
            }
        }

        console.log('\n✅ Alignment demonstration complete!');
        console.log('📊 All measurements have been logged above');
        this.showComparisonTable();
    }

    showComparisonTable() {
        if (this.measurements.length === 0) return;

        console.log('\n📊 ALIGNMENT COMPARISON TABLE');
        console.log('=' .repeat(80));

        const table = this.measurements.map(m => ({
            'Alignment': m.alignmentType.toUpperCase(),
            'Container Width': m.container.width + 'px',
            'Left Offset': m.spacing.leftOffset + 'px',
            'Right Offset': m.spacing.rightOffset + 'px',
            'Total Padding': m.spacing.totalPadding + 'px',
            'Classes': m.classes.split(' ').slice(0, 2).join(' ')
        }));

        console.table(table);

        // Analyze differences
        const containerMeasurement = this.measurements.find(m => m.alignmentType === 'container');
        const wideMeasurement = this.measurements.find(m => m.alignmentType === 'wide');
        const fullMeasurement = this.measurements.find(m => m.alignmentType === 'full');

        if (containerMeasurement && wideMeasurement && fullMeasurement) {
            console.log('\n🔍 VISUAL DIFFERENCES ANALYSIS:');
            console.log(`Container vs Wide: ${Math.abs(containerMeasurement.container.width - wideMeasurement.container.width)}px width difference`);
            console.log(`Wide vs Full: ${Math.abs(wideMeasurement.container.width - fullMeasurement.container.width)}px width difference`);
            console.log(`Container vs Full: ${Math.abs(containerMeasurement.container.width - fullMeasurement.container.width)}px width difference`);
        }
    }

    restoreOriginal() {
        if (!this.container) return;

        this.container.className = this.originalClasses;
        this.container.removeAttribute('data-alignment-type');

        const grid = this.container.querySelector('.riman-service-grid');
        if (grid) {
            grid.classList.remove('alignment-test-grid');
            grid.removeAttribute('data-grid-width');
        }

        // Remove measurement overlay
        const overlay = document.querySelector('.alignment-measurement-overlay');
        if (overlay) overlay.remove();

        console.log('🔄 Original alignment restored');
    }

    // Manual methods for testing
    testContainer() { this.setAlignment('container'); }
    testWide() { this.setAlignment('wide'); }
    testFull() { this.setAlignment('full'); }
}

// Auto-start when DOM is ready
if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', () => {
        window.alignmentTester = new AlignmentTester();
    });
} else {
    window.alignmentTester = new AlignmentTester();
}

// Console helper functions
console.log('🎯 RIMAN Alignment Tester loaded!');
console.log('📝 Available commands:');
console.log('   alignmentTester.testContainer() - Test Container alignment');
console.log('   alignmentTester.testWide() - Test Wide alignment');
console.log('   alignmentTester.testFull() - Test Full alignment');
console.log('   alignmentTester.restoreOriginal() - Restore original');
console.log('   alignmentTester.showComparisonTable() - Show comparison');