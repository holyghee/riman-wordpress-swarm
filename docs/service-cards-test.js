const { chromium } = require('playwright');

async function testServiceCards() {
    const browser = await chromium.launch();
    const context = await browser.newContext();

    const testResults = {
        startseite: {},
        template: {},
        summary: []
    };

    const urls = [
        { name: 'startseite', url: 'http://127.0.0.1:8801/?p=5593' },
        { name: 'template', url: 'http://127.0.0.1:8801/?p=10496' }
    ];

    const viewports = [
        { name: 'desktop', width: 1440, height: 900 },
        { name: 'tablet', width: 900, height: 800 },
        { name: 'mobile', width: 375, height: 667 }
    ];

    for (const { name: urlName, url } of urls) {
        testResults[urlName] = {};

        for (const { name: viewportName, width, height } of viewports) {
            const page = await context.newPage();
            await page.setViewportSize({ width, height });

            console.log(`\n=== Testing ${urlName} on ${viewportName} (${width}x${height}) ===`);

            try {
                await page.goto(url, { waitUntil: 'networkidle' });

                // Wait for Service Cards to load
                await page.waitForSelector('.riman-service-cards-wrap', { timeout: 10000 });

                // Get Service Cards container measurements
                const measurements = await page.evaluate(() => {
                    const container = document.querySelector('.riman-service-cards-wrap');
                    if (!container) return null;

                    const rect = container.getBoundingClientRect();
                    const computedStyle = window.getComputedStyle(container);

                    return {
                        width: rect.width,
                        height: rect.height,
                        left: rect.left,
                        right: rect.right,
                        marginLeft: computedStyle.marginLeft,
                        marginRight: computedStyle.marginRight,
                        maxWidth: computedStyle.maxWidth,
                        className: container.className,
                        offsetWidth: container.offsetWidth,
                        scrollWidth: container.scrollWidth,
                        viewportWidth: window.innerWidth,
                        viewportHeight: window.innerHeight
                    };
                });

                // Take screenshot
                const screenshotPath = `/Users/holgerbrandt/dev/claude-code/projects/riman-wordpress-swarm/docs/${urlName}-${viewportName}-${width}x${height}.png`;
                await page.screenshot({
                    path: screenshotPath,
                    fullPage: false
                });

                if (measurements) {
                    testResults[urlName][viewportName] = measurements;

                    console.log(`✅ Service Cards found:`);
                    console.log(`   Container Width: ${measurements.width}px`);
                    console.log(`   Viewport Width: ${measurements.viewportWidth}px`);
                    console.log(`   Width Percentage: ${((measurements.width / measurements.viewportWidth) * 100).toFixed(1)}%`);
                    console.log(`   Margin Left: ${measurements.marginLeft}`);
                    console.log(`   Margin Right: ${measurements.marginRight}`);
                    console.log(`   Max Width: ${measurements.maxWidth}`);
                    console.log(`   CSS Classes: ${measurements.className}`);
                    console.log(`   Screenshot: ${screenshotPath}`);

                    // Check alignment expectations
                    let status = '✅ Correct';
                    let expectedWidth = null;

                    if (measurements.className.includes('sc-container')) {
                        expectedWidth = 920;
                        if (Math.abs(measurements.width - 920) > 10) {
                            status = '❌ Wrong width (expected ~920px)';
                        }
                    } else if (measurements.className.includes('alignwide')) {
                        expectedWidth = 1440;
                        if (Math.abs(measurements.width - 1440) > 50) {
                            status = '❌ Wrong width (expected ~1440px)';
                        }
                    } else if (measurements.className.includes('alignfull')) {
                        expectedWidth = measurements.viewportWidth;
                        if (Math.abs(measurements.width - measurements.viewportWidth) > 20) {
                            status = '❌ Wrong width (expected viewport width)';
                        }
                    }

                    testResults.summary.push({
                        page: urlName,
                        viewport: viewportName,
                        status,
                        actualWidth: measurements.width,
                        expectedWidth,
                        classes: measurements.className
                    });

                    console.log(`   Status: ${status}`);

                } else {
                    console.log(`❌ Service Cards container not found`);
                    testResults.summary.push({
                        page: urlName,
                        viewport: viewportName,
                        status: '❌ Service Cards not found',
                        actualWidth: null,
                        expectedWidth: null,
                        classes: null
                    });
                }

            } catch (error) {
                console.log(`❌ Error testing ${urlName} on ${viewportName}: ${error.message}`);
                testResults.summary.push({
                    page: urlName,
                    viewport: viewportName,
                    status: `❌ Error: ${error.message}`,
                    actualWidth: null,
                    expectedWidth: null,
                    classes: null
                });
            } finally {
                await page.close();
            }
        }
    }

    await browser.close();

    // Generate detailed report
    console.log('\n\n=== DETAILED TEST REPORT ===\n');

    console.log('📊 SUMMARY TABLE:');
    console.log('Page\t\tViewport\t\tActual Width\tExpected Width\tStatus');
    console.log('─'.repeat(80));

    testResults.summary.forEach(result => {
        const actualW = result.actualWidth ? `${result.actualWidth}px` : 'N/A';
        const expectedW = result.expectedWidth ? `${result.expectedWidth}px` : 'N/A';
        console.log(`${result.page.padEnd(12)}\t${result.viewport.padEnd(8)}\t\t${actualW.padEnd(12)}\t${expectedW.padEnd(12)}\t${result.status}`);
    });

    console.log('\n📋 EXPECTED BEHAVIOR:');
    console.log('• .sc-container: Width = 920px, Centered with auto margins');
    console.log('• .alignwide: Width = 1440px');
    console.log('• .alignfull: Width = Viewport width');

    console.log('\n🔍 ISSUES FOUND:');
    const issues = testResults.summary.filter(r => r.status.startsWith('❌'));
    if (issues.length === 0) {
        console.log('✅ No issues found! All Service Cards are working correctly.');
    } else {
        issues.forEach(issue => {
            console.log(`❌ ${issue.page} (${issue.viewport}): ${issue.status}`);
        });
    }

    // Save full results to JSON
    const fs = require('fs');
    fs.writeFileSync(
        '/Users/holgerbrandt/dev/claude-code/projects/riman-wordpress-swarm/docs/service-cards-test-results.json',
        JSON.stringify(testResults, null, 2)
    );

    console.log('\n📁 Files Generated:');
    console.log('• Screenshots: /docs/*-*-*x*.png');
    console.log('• Test Results: /docs/service-cards-test-results.json');

    return testResults;
}

testServiceCards().catch(console.error);