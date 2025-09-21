#!/usr/bin/env node

/**
 * Automated Alignment Capture Script
 *
 * This script uses Playwright to:
 * 1. Open the RIMAN page
 * 2. Test all three alignments (Container/Wide/Full)
 * 3. Capture screenshots of each
 * 4. Measure actual differences
 * 5. Generate a visual report
 */

const { chromium } = require('playwright');
const fs = require('fs').promises;
const path = require('path');

class AlignmentCapture {
    constructor() {
        this.browser = null;
        this.page = null;
        this.measurements = [];
        this.screenshotDir = path.join(__dirname, 'alignment-screenshots');
    }

    async init() {
        // Create screenshots directory
        try {
            await fs.mkdir(this.screenshotDir, { recursive: true });
        } catch (error) {
            // Directory already exists
        }

        // Launch browser
        this.browser = await chromium.launch({
            headless: false, // Show browser for debugging
            slowMo: 1000 // Slow down for visual confirmation
        });

        this.page = await this.browser.newPage();

        // Set viewport to desktop size
        await this.page.setViewportSize({ width: 1440, height: 900 });

        console.log('🚀 Browser launched, navigating to page...');
    }

    async navigateToPage() {
        const url = 'http://127.0.0.1:8801/riman-seiten/sicherheits-koordination_und_mediation/';

        try {
            await this.page.goto(url, {
                waitUntil: 'networkidle',
                timeout: 30000
            });

            // Wait for service cards to load
            await this.page.waitForSelector('.riman-service-cards-wrap', { timeout: 10000 });
            console.log('✅ Page loaded successfully');

        } catch (error) {
            console.error('❌ Failed to load page:', error.message);
            throw error;
        }
    }

    async addVisualStyles() {
        // Add CSS for visual boundaries and measurements
        await this.page.addStyleTag({
            content: `
                .alignment-test-boundary {
                    border: 4px solid red !important;
                    background: rgba(255, 0, 0, 0.1) !important;
                    position: relative !important;
                }

                .alignment-test-boundary::before {
                    content: attr(data-alignment-label) !important;
                    position: absolute !important;
                    top: -40px !important;
                    left: 0 !important;
                    background: red !important;
                    color: white !important;
                    padding: 10px 20px !important;
                    font-size: 18px !important;
                    font-weight: bold !important;
                    z-index: 1000 !important;
                    border-radius: 6px !important;
                    white-space: nowrap !important;
                    font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif !important;
                }

                .grid-test-boundary {
                    border: 3px dashed blue !important;
                    background: rgba(0, 0, 255, 0.05) !important;
                    position: relative !important;
                }

                .grid-test-boundary::after {
                    content: 'Grid: ' attr(data-grid-width) 'px wide' !important;
                    position: absolute !important;
                    top: -30px !important;
                    right: 0 !important;
                    background: blue !important;
                    color: white !important;
                    padding: 6px 12px !important;
                    font-size: 14px !important;
                    z-index: 1000 !important;
                    border-radius: 4px !important;
                    font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif !important;
                }

                .measurement-overlay {
                    position: fixed !important;
                    top: 20px !important;
                    right: 20px !important;
                    background: rgba(0, 0, 0, 0.9) !important;
                    color: white !important;
                    padding: 20px !important;
                    border-radius: 8px !important;
                    font-family: 'Courier New', monospace !important;
                    font-size: 16px !important;
                    z-index: 10000 !important;
                    line-height: 1.6 !important;
                    min-width: 300px !important;
                }
            `
        });
    }

    async measureContainer() {
        return await this.page.evaluate(() => {
            const container = document.querySelector('.riman-service-cards-wrap');
            const grid = document.querySelector('.riman-service-grid');

            if (!container) return null;

            const containerRect = container.getBoundingClientRect();
            const gridRect = grid ? grid.getBoundingClientRect() : null;
            const viewportWidth = window.innerWidth;

            return {
                viewport: {
                    width: viewportWidth,
                    height: window.innerHeight
                },
                container: {
                    width: Math.round(containerRect.width),
                    height: Math.round(containerRect.height),
                    left: Math.round(containerRect.left),
                    right: Math.round(containerRect.right),
                    top: Math.round(containerRect.top),
                    bottom: Math.round(containerRect.bottom)
                },
                grid: gridRect ? {
                    width: Math.round(gridRect.width),
                    height: Math.round(gridRect.height),
                    left: Math.round(gridRect.left),
                    right: Math.round(gridRect.right)
                } : null,
                spacing: {
                    leftOffset: Math.round(containerRect.left),
                    rightOffset: Math.round(viewportWidth - containerRect.right),
                    utilization: Math.round((containerRect.width / viewportWidth) * 100),
                    totalPadding: Math.round(viewportWidth - containerRect.width)
                },
                classes: container.className
            };
        });
    }

    async testAlignment(alignmentType, classes) {
        console.log(`\n🔄 Testing ${alignmentType.toUpperCase()} alignment...`);

        // Reset and apply alignment classes
        await this.page.evaluate(({ alignmentType, classes }) => {
            const container = document.querySelector('.riman-service-cards-wrap');
            const grid = document.querySelector('.riman-service-grid');

            if (!container) return;

            // Reset classes
            container.className = 'riman-service-cards-wrap';

            // Apply new classes
            classes.forEach(cls => container.classList.add(cls));

            // Add visual indicators
            container.classList.add('alignment-test-boundary');
            container.setAttribute('data-alignment-label',
                `${alignmentType.toUpperCase()} ALIGNMENT`);

            if (grid) {
                grid.classList.add('grid-test-boundary');
                // Will be updated after measurement
            }

        }, { alignmentType, classes });

        // Wait for CSS to apply
        await this.page.waitForTimeout(500);

        // Measure
        const measurement = await this.measureContainer();
        if (!measurement) {
            console.error('❌ Failed to measure container');
            return null;
        }

        // Update grid label with actual measurement
        await this.page.evaluate((gridWidth) => {
            const grid = document.querySelector('.riman-service-grid');
            if (grid) {
                grid.setAttribute('data-grid-width', gridWidth);
            }
        }, measurement.grid?.width || measurement.container.width);

        // Add measurement overlay
        await this.page.evaluate((measurement) => {
            // Remove existing overlay
            const existing = document.querySelector('.measurement-overlay');
            if (existing) existing.remove();

            // Create new overlay
            const overlay = document.createElement('div');
            overlay.className = 'measurement-overlay';
            overlay.innerHTML = `
                <div style="font-size: 20px; font-weight: bold; margin-bottom: 15px;">
                    📏 ${measurement.classes.includes('sc-container') ? 'CONTAINER' :
                           measurement.classes.includes('alignwide') ? 'WIDE' : 'FULL'} ALIGNMENT
                </div>
                <div><strong>Viewport:</strong> ${measurement.viewport.width}px × ${measurement.viewport.height}px</div>
                <div><strong>Container:</strong> ${measurement.container.width}px wide</div>
                <div><strong>Left Margin:</strong> ${measurement.spacing.leftOffset}px</div>
                <div><strong>Right Margin:</strong> ${measurement.spacing.rightOffset}px</div>
                <div><strong>Utilization:</strong> ${measurement.spacing.utilization}%</div>
                ${measurement.grid ? `<div><strong>Grid:</strong> ${measurement.grid.width}px</div>` : ''}
                <div style="margin-top: 10px; font-size: 14px; opacity: 0.8;">
                    Classes: ${measurement.classes.split(' ').slice(0, 3).join(' ')}
                </div>
            `;

            document.body.appendChild(overlay);
        }, measurement);

        // Wait for visual confirmation
        await this.page.waitForTimeout(1000);

        // Take screenshot
        const screenshotPath = path.join(this.screenshotDir, `${alignmentType}-alignment-1440px.png`);
        await this.page.screenshot({
            path: screenshotPath,
            fullPage: false
        });

        // Store measurement
        measurement.alignmentType = alignmentType;
        measurement.screenshotPath = screenshotPath;
        this.measurements.push(measurement);

        console.log(`📐 ${alignmentType.toUpperCase()}: ${measurement.container.width}px wide (${measurement.spacing.utilization}% of viewport)`);
        console.log(`📷 Screenshot saved: ${screenshotPath}`);

        return measurement;
    }

    async testAllAlignments() {
        await this.addVisualStyles();

        // Define alignments to test
        const alignments = [
            { type: 'container', classes: ['sc-container', 'sc-responsive'] },
            { type: 'wide', classes: ['alignwide', 'sc-responsive'] },
            { type: 'full', classes: ['alignfull', 'sc-responsive'] }
        ];

        // Test each alignment
        for (const alignment of alignments) {
            await this.testAlignment(alignment.type, alignment.classes);
            await this.page.waitForTimeout(2000); // Pause between tests
        }
    }

    async testResponsiveBreakpoints() {
        const viewports = [
            { name: 'desktop', width: 1440, height: 900 },
            { name: 'tablet', width: 768, height: 1024 },
            { name: 'mobile', width: 375, height: 667 }
        ];

        console.log('\n📱 Testing responsive breakpoints...');

        for (const viewport of viewports) {
            console.log(`\n🖥️ Testing ${viewport.name} (${viewport.width}px)...`);

            await this.page.setViewportSize({
                width: viewport.width,
                height: viewport.height
            });

            await this.page.waitForTimeout(1000);

            // Test container alignment at this viewport
            await this.page.evaluate(() => {
                const container = document.querySelector('.riman-service-cards-wrap');
                if (container) {
                    container.className = 'riman-service-cards-wrap sc-container sc-responsive';
                }
            });

            await this.page.waitForTimeout(500);

            const screenshotPath = path.join(this.screenshotDir, `container-${viewport.name}-${viewport.width}px.png`);
            await this.page.screenshot({
                path: screenshotPath,
                fullPage: false
            });

            console.log(`📷 ${viewport.name} screenshot saved: ${screenshotPath}`);
        }
    }

    generateReport() {
        console.log('\n📊 ALIGNMENT DIFFERENCES REPORT');
        console.log('=' .repeat(80));

        if (this.measurements.length === 0) {
            console.log('❌ No measurements captured');
            return;
        }

        // Create comparison table
        const table = this.measurements.map(m => ({
            'Alignment': m.alignmentType.toUpperCase(),
            'Container Width': m.container.width + 'px',
            'Left Margin': m.spacing.leftOffset + 'px',
            'Right Margin': m.spacing.rightOffset + 'px',
            'Viewport %': m.spacing.utilization + '%',
            'Screenshot': path.basename(m.screenshotPath)
        }));

        console.table(table);

        // Analyze differences
        console.log('\n🔍 DIFFERENCE ANALYSIS:');

        const container = this.measurements.find(m => m.alignmentType === 'container');
        const wide = this.measurements.find(m => m.alignmentType === 'wide');
        const full = this.measurements.find(m => m.alignmentType === 'full');

        if (container && wide && full) {
            const containerVsWide = Math.abs(container.container.width - wide.container.width);
            const wideVsFull = Math.abs(wide.container.width - full.container.width);
            const containerVsFull = Math.abs(container.container.width - full.container.width);

            console.log(`📏 Container vs Wide: ${containerVsWide}px difference`);
            console.log(`📏 Wide vs Full: ${wideVsFull}px difference`);
            console.log(`📏 Container vs Full: ${containerVsFull}px difference`);

            // Check if there are meaningful differences
            if (containerVsWide === 0 && wideVsFull === 0) {
                console.log('\n🚨 PROBLEM DETECTED:');
                console.log('❌ All alignments have identical widths!');
                console.log('💡 This indicates the CSS rules are not working correctly.');
                console.log('🔧 Possible causes:');
                console.log('   - CSS specificity conflicts');
                console.log('   - Missing or incorrect CSS classes');
                console.log('   - CSS not loaded properly');
                console.log('   - Override by theme or other plugins');
            } else {
                console.log('\n✅ VISUAL DIFFERENCES CONFIRMED:');
                console.log(`📦 Container: ${container.container.width}px (${container.spacing.utilization}% of viewport)`);
                console.log(`📐 Wide: ${wide.container.width}px (${wide.spacing.utilization}% of viewport)`);
                console.log(`🎯 Full: ${full.container.width}px (${full.spacing.utilization}% of viewport)`);
            }
        }

        console.log('\n📁 Screenshots saved in:', this.screenshotDir);
    }

    async cleanup() {
        if (this.browser) {
            await this.browser.close();
        }
    }

    async run() {
        try {
            await this.init();
            await this.navigateToPage();
            await this.testAllAlignments();
            await this.testResponsiveBreakpoints();
            this.generateReport();

        } catch (error) {
            console.error('❌ Error during capture:', error);

        } finally {
            await this.cleanup();
        }
    }
}

// Run if this file is executed directly
if (require.main === module) {
    const capture = new AlignmentCapture();
    capture.run().catch(console.error);
}

module.exports = AlignmentCapture;