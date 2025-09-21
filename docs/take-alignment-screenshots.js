#!/usr/bin/env node

/**
 * Script to capture visual evidence of Service Cards alignment differences
 * This script will take actual screenshots showing Container vs Wide vs Full
 */

const { chromium } = require('playwright');
const fs = require('fs').promises;
const path = require('path');

async function captureAlignmentScreenshots() {
    const screenshotDir = path.join(__dirname, 'alignment-evidence');

    // Create directory
    try {
        await fs.mkdir(screenshotDir, { recursive: true });
    } catch (e) {}

    console.log('🎯 Starting Alignment Screenshot Capture');
    console.log('📁 Screenshots will be saved to:', screenshotDir);

    // Launch browser
    const browser = await chromium.launch({
        headless: false,
        slowMo: 500
    });

    const page = await browser.newPage();
    await page.setViewportSize({ width: 1440, height: 900 });

    const url = 'http://127.0.0.1:8801/riman-seiten/sicherheits-koordination_und_mediation/';

    try {
        // Navigate to page
        console.log('📍 Navigating to:', url);
        await page.goto(url, { waitUntil: 'networkidle', timeout: 30000 });
        await page.waitForSelector('.riman-service-cards-wrap', { timeout: 10000 });

        // Add visual enhancement styles
        await page.addStyleTag({
            content: `
                .alignment-highlight {
                    border: 5px solid red !important;
                    background: rgba(255, 0, 0, 0.1) !important;
                    position: relative !important;
                }

                .alignment-highlight::before {
                    content: attr(data-alignment-type) !important;
                    position: absolute !important;
                    top: -50px !important;
                    left: 50% !important;
                    transform: translateX(-50%) !important;
                    background: red !important;
                    color: white !important;
                    padding: 15px 30px !important;
                    font-size: 24px !important;
                    font-weight: bold !important;
                    z-index: 1000 !important;
                    border-radius: 8px !important;
                    white-space: nowrap !important;
                    font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif !important;
                    text-align: center !important;
                }

                .measurement-ruler {
                    position: fixed !important;
                    top: 0 !important;
                    bottom: 0 !important;
                    width: 2px !important;
                    background: yellow !important;
                    z-index: 10000 !important;
                    opacity: 0.8 !important;
                }

                .measurement-ruler.left {
                    left: var(--ruler-left, 0) !important;
                }

                .measurement-ruler.right {
                    right: var(--ruler-right, 0) !important;
                }

                .measurement-label {
                    position: fixed !important;
                    background: yellow !important;
                    color: black !important;
                    padding: 5px 10px !important;
                    font-size: 14px !important;
                    font-weight: bold !important;
                    z-index: 10001 !important;
                    border-radius: 4px !important;
                    font-family: 'Courier New', monospace !important;
                }

                .info-panel {
                    position: fixed !important;
                    top: 20px !important;
                    right: 20px !important;
                    background: rgba(0, 0, 0, 0.9) !important;
                    color: white !important;
                    padding: 20px !important;
                    border-radius: 8px !important;
                    font-family: 'Courier New', monospace !important;
                    font-size: 16px !important;
                    line-height: 1.6 !important;
                    z-index: 10000 !important;
                    min-width: 300px !important;
                }
            `
        });

        const measurements = [];

        // Test each alignment
        const alignments = [
            { type: 'container', classes: ['sc-container', 'sc-responsive'], color: '#28a745' },
            { type: 'wide', classes: ['alignwide', 'sc-responsive'], color: '#007bff' },
            { type: 'full', classes: ['alignfull', 'sc-responsive'], color: '#dc3545' }
        ];

        for (const alignment of alignments) {
            console.log(`\n📐 Testing ${alignment.type.toUpperCase()} alignment...`);

            // Reset and apply alignment
            await page.evaluate((data) => {
                const container = document.querySelector('.riman-service-cards-wrap');
                if (!container) return;

                // Reset classes
                container.className = 'riman-service-cards-wrap';

                // Apply new classes
                data.classes.forEach(cls => container.classList.add(cls));

                // Add highlight
                container.classList.add('alignment-highlight');
                container.setAttribute('data-alignment-type', data.type.toUpperCase() + ' ALIGNMENT');
                container.style.setProperty('--highlight-color', data.color);

            }, alignment);

            await page.waitForTimeout(1000);

            // Measure container
            const measurement = await page.evaluate(() => {
                const container = document.querySelector('.riman-service-cards-wrap');
                if (!container) return null;

                const rect = container.getBoundingClientRect();
                const viewportWidth = window.innerWidth;

                return {
                    width: Math.round(rect.width),
                    height: Math.round(rect.height),
                    left: Math.round(rect.left),
                    right: Math.round(rect.right),
                    top: Math.round(rect.top),
                    bottom: Math.round(rect.bottom),
                    leftMargin: Math.round(rect.left),
                    rightMargin: Math.round(viewportWidth - rect.right),
                    utilization: Math.round((rect.width / viewportWidth) * 100),
                    viewport: viewportWidth,
                    classes: container.className
                };
            });

            if (measurement) {
                measurement.type = alignment.type;
                measurements.push(measurement);

                // Add measurement rulers and info panel
                await page.evaluate((m) => {
                    // Remove existing rulers and info
                    document.querySelectorAll('.measurement-ruler, .measurement-label, .info-panel').forEach(el => el.remove());

                    // Add left ruler
                    const leftRuler = document.createElement('div');
                    leftRuler.className = 'measurement-ruler left';
                    leftRuler.style.left = m.left + 'px';
                    document.body.appendChild(leftRuler);

                    // Add right ruler
                    const rightRuler = document.createElement('div');
                    rightRuler.className = 'measurement-ruler right';
                    rightRuler.style.right = m.rightMargin + 'px';
                    document.body.appendChild(rightRuler);

                    // Add left label
                    const leftLabel = document.createElement('div');
                    leftLabel.className = 'measurement-label';
                    leftLabel.style.left = '10px';
                    leftLabel.style.top = '100px';
                    leftLabel.textContent = `Left: ${m.leftMargin}px`;
                    document.body.appendChild(leftLabel);

                    // Add right label
                    const rightLabel = document.createElement('div');
                    rightLabel.className = 'measurement-label';
                    rightLabel.style.right = '10px';
                    rightLabel.style.top = '100px';
                    rightLabel.textContent = `Right: ${m.rightMargin}px`;
                    document.body.appendChild(rightLabel);

                    // Add info panel
                    const infoPanel = document.createElement('div');
                    infoPanel.className = 'info-panel';
                    infoPanel.innerHTML = `
                        <div style="font-size: 20px; font-weight: bold; margin-bottom: 15px;">
                            📏 ${m.type.toUpperCase()} ALIGNMENT
                        </div>
                        <div>Container Width: ${m.width}px</div>
                        <div>Viewport Width: ${m.viewport}px</div>
                        <div>Left Margin: ${m.leftMargin}px</div>
                        <div>Right Margin: ${m.rightMargin}px</div>
                        <div>Utilization: ${m.utilization}%</div>
                        <div style="margin-top: 10px; font-size: 12px; opacity: 0.8;">
                            ${m.classes.split(' ').slice(0, 3).join(' ')}
                        </div>
                    `;
                    document.body.appendChild(infoPanel);

                }, measurement);

                await page.waitForTimeout(1000);

                // Take screenshot
                const screenshotPath = path.join(screenshotDir, `${alignment.type}-alignment-with-measurements.png`);
                await page.screenshot({
                    path: screenshotPath,
                    fullPage: false
                });

                console.log(`📷 Screenshot saved: ${screenshotPath}`);
                console.log(`📐 Width: ${measurement.width}px | Margins: ${measurement.leftMargin}px / ${measurement.rightMargin}px | Utilization: ${measurement.utilization}%`);
            }

            await page.waitForTimeout(1500);
        }

        // Take a final comparison screenshot
        console.log('\n📊 Creating comparison image...');

        // Remove highlights for clean comparison
        await page.evaluate(() => {
            document.querySelectorAll('.measurement-ruler, .measurement-label, .info-panel').forEach(el => el.remove());
            const container = document.querySelector('.riman-service-cards-wrap');
            if (container) {
                container.classList.remove('alignment-highlight');
                container.removeAttribute('data-alignment-type');
            }
        });

        // Reset to original state
        await page.evaluate(() => {
            const container = document.querySelector('.riman-service-cards-wrap');
            if (container) {
                // Get original classes from page source
                container.className = 'riman-service-cards-wrap riman-sc-68ce662a9c034 alignfull sc-responsive';
            }
        });

        await page.waitForTimeout(500);

        const originalPath = path.join(screenshotDir, 'original-alignment.png');
        await page.screenshot({
            path: originalPath,
            fullPage: false
        });

        console.log(`📷 Original screenshot saved: ${originalPath}`);

        // Generate report
        console.log('\n📊 MEASUREMENT REPORT');
        console.log('=' .repeat(70));

        const table = measurements.map(m => ({
            'Alignment': m.type.toUpperCase(),
            'Width': m.width + 'px',
            'Left': m.leftMargin + 'px',
            'Right': m.rightMargin + 'px',
            'Viewport %': m.utilization + '%'
        }));

        console.table(table);

        // Analysis
        const container = measurements.find(m => m.type === 'container');
        const wide = measurements.find(m => m.type === 'wide');
        const full = measurements.find(m => m.type === 'full');

        if (container && wide && full) {
            console.log('\n🔍 DIFFERENCE ANALYSIS:');
            const containerVsWide = Math.abs(container.width - wide.width);
            const wideVsFull = Math.abs(wide.width - full.width);
            const containerVsFull = Math.abs(container.width - full.width);

            console.log(`📏 Container vs Wide: ${containerVsWide}px`);
            console.log(`📏 Wide vs Full: ${wideVsFull}px`);
            console.log(`📏 Container vs Full: ${containerVsFull}px`);

            if (containerVsWide === 0 && wideVsFull === 0) {
                console.log('\n🚨 PROBLEM CONFIRMED:');
                console.log('❌ All alignments have identical widths!');
                console.log('💡 CSS rules are not working correctly.');
            } else {
                console.log('\n✅ DIFFERENCES DETECTED:');
                console.log(`📦 Container: ${container.width}px (${container.utilization}%)`);
                console.log(`📐 Wide: ${wide.width}px (${wide.utilization}%)`);
                console.log(`🎯 Full: ${full.width}px (${full.utilization}%)`);
            }
        }

        console.log('\n📁 All screenshots saved in:', screenshotDir);

        // Write measurements to JSON
        const measurementsPath = path.join(screenshotDir, 'measurements.json');
        await fs.writeFile(measurementsPath, JSON.stringify(measurements, null, 2));
        console.log('💾 Measurements saved to:', measurementsPath);

    } catch (error) {
        console.error('❌ Error during capture:', error);
    } finally {
        await browser.close();
    }
}

// Run the script
captureAlignmentScreenshots().catch(console.error);