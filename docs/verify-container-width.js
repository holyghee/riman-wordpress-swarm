/**
 * Container Width Verification Script
 * Tests if the service cards container width correction is working
 * Expected: 920px max-width for .sc-container class
 */

const TEST_URL = 'http://127.0.0.1:8801/riman-seiten/sicherheits-koordination_und_mediation/';

async function verifyContainerWidth() {
    console.log('🔍 Testing Service Cards Container Width Correction...\n');

    try {
        // Fetch the page HTML
        const response = await fetch(TEST_URL);
        if (!response.ok) {
            throw new Error(`HTTP ${response.status}: ${response.statusText}`);
        }

        const html = await response.text();
        console.log('✅ Page fetched successfully');

        // Check if service cards wrapper exists in HTML
        const hasServiceCards = html.includes('riman-service-cards-wrap');
        console.log(`📦 Service Cards found in HTML: ${hasServiceCards}`);

        if (!hasServiceCards) {
            console.log('⚠️ No service cards found on this page');
            return;
        }

        // Check for key CSS classes in HTML
        const hasScContainer = html.includes('sc-container');
        const hasScResponsive = html.includes('sc-responsive');

        console.log(`🏷️ Contains 'sc-container' class: ${hasScContainer}`);
        console.log(`🏷️ Contains 'sc-responsive' class: ${hasScResponsive}`);

        // Extract inline styles if present
        const inlineStyleMatch = html.match(/riman-service-cards-wrap[^>]*style="([^"]*)/);
        if (inlineStyleMatch) {
            console.log(`🎨 Inline styles found: ${inlineStyleMatch[1]}`);
        }

        // Check if CSS file is enqueued
        const hasCssEnqueued = html.includes('service-cards.css');
        console.log(`📄 CSS file enqueued: ${hasCssEnqueued}`);

        // Look for the specific CSS rules we expect
        console.log('\n📝 CSS Analysis:');
        console.log('Expected CSS rules for .sc-container:');
        console.log('- width: 100% !important');
        console.log('- max-width: 920px !important');
        console.log('- margin-left: auto !important');
        console.log('- margin-right: auto !important');

        // Test what we can verify from the HTML structure
        console.log('\n🧪 HTML Structure Verification:');

        // Find service cards wrapper classes
        const wrapperMatch = html.match(/class="([^"]*riman-service-cards-wrap[^"]*)"/);
        if (wrapperMatch) {
            const classes = wrapperMatch[1].split(' ');
            console.log(`📋 Wrapper classes: ${classes.join(', ')}`);

            const hasContainer = classes.includes('sc-container');
            const hasResponsive = classes.includes('sc-responsive');

            console.log(`✅ Has 'sc-container': ${hasContainer}`);
            console.log(`✅ Has 'sc-responsive': ${hasResponsive}`);

            if (hasContainer && hasResponsive) {
                console.log('\n🎯 SUCCESS: Container has both required classes!');
                console.log('Expected behavior:');
                console.log('- CSS rule .sc-container.sc-responsive should apply');
                console.log('- Container width should be 920px max-width');
                console.log('- This should override any responsive settings');
            } else if (hasContainer) {
                console.log('\n✅ GOOD: Container has sc-container class');
                console.log('- Standard 920px max-width should apply');
            } else {
                console.log('\n⚠️ WARNING: Container missing sc-container class');
                console.log('- May not have consistent width constraints');
            }
        }

        console.log('\n📊 Test Summary:');
        console.log('================');
        console.log(`✓ Page accessible: ${response.ok}`);
        console.log(`✓ Service cards present: ${hasServiceCards}`);
        console.log(`✓ sc-container class: ${hasScContainer}`);
        console.log(`✓ CSS file loaded: ${hasCssEnqueued}`);

        console.log('\n🔗 To verify actual rendered width, check in browser:');
        console.log(`1. Open: ${TEST_URL}`);
        console.log('2. Inspect .riman-service-cards-wrap element');
        console.log('3. Check computed styles for max-width: 920px');

    } catch (error) {
        console.error('❌ Test failed:', error.message);
    }
}

// Run the verification
verifyContainerWidth();