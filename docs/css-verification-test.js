/**
 * CSS Verification Test
 * Checks if the CSS contains the correct rules for container width fix
 */

const fs = require('fs').promises;
const path = require('path');

async function verifyCSSRules() {
    console.log('🔍 Verifying Service Cards CSS Rules...\n');

    try {
        // Read the CSS file
        const cssPath = path.join(__dirname, '..', 'wp-content', 'plugins', 'riman-blocks', 'assets', 'service-cards.css');
        const cssContent = await fs.readFile(cssPath, 'utf8');

        console.log('✅ CSS file loaded successfully');

        // Check for the key CSS rule we expect
        const expectedRules = [
            '.riman-service-cards-wrap.sc-container',
            '.riman-service-cards-wrap.sc-container.sc-responsive',
            'max-width: 920px !important',
            'width: 100% !important',
            'margin-left: auto !important',
            'margin-right: auto !important'
        ];

        console.log('🔍 Checking for required CSS rules:\n');

        let allRulesFound = true;
        for (const rule of expectedRules) {
            const found = cssContent.includes(rule);
            console.log(`${found ? '✅' : '❌'} "${rule}": ${found}`);
            if (!found) allRulesFound = false;
        }

        // Extract the specific CSS block for container rules
        const containerRuleMatch = cssContent.match(/\/\* Standard container width from Global Styles.*?\}[\s\S]*?\}/s);
        if (containerRuleMatch) {
            console.log('\n📋 Found container CSS block:');
            console.log('```css');
            console.log(containerRuleMatch[0]);
            console.log('```\n');
        }

        // Check the PHP logic for the :not(.sc-container) fix
        const phpPath = path.join(__dirname, '..', 'wp-content', 'plugins', 'riman-blocks', 'blocks', 'service-cards.php');
        const phpContent = await fs.readFile(phpPath, 'utf8');

        const hasNotContainerLogic = phpContent.includes(':not(.sc-container)');
        console.log(`🔧 PHP contains ":not(.sc-container)" logic: ${hasNotContainerLogic}`);

        // Check for the responsive overrides protection
        const tabletOverrideMatch = phpContent.match(/781px.*?1024px.*?:not\(\.sc-container\)/s);
        const mobileOverrideMatch = phpContent.match(/max-width: 780px.*?:not\(\.sc-container\)/s);

        console.log(`📱 Tablet responsive override protection: ${!!tabletOverrideMatch}`);
        console.log(`📱 Mobile responsive override protection: ${!!mobileOverrideMatch}`);

        console.log('\n🎯 Analysis Results:');
        console.log('===================');

        if (allRulesFound) {
            console.log('✅ ALL REQUIRED CSS RULES FOUND');
            console.log('✅ Container width should be correctly constrained to 920px');
            console.log('✅ Rules have !important priority to override other styles');
        } else {
            console.log('❌ SOME CSS RULES MISSING');
            console.log('⚠️ Container width may not be properly constrained');
        }

        if (hasNotContainerLogic) {
            console.log('✅ PHP properly prevents responsive overrides of sc-container');
        } else {
            console.log('⚠️ PHP may allow responsive overrides of sc-container');
        }

        console.log('\n📝 Summary:');
        console.log(`- CSS rules present: ${allRulesFound ? 'YES' : 'NO'}`);
        console.log(`- Override protection: ${hasNotContainerLogic ? 'YES' : 'NO'}`);
        console.log('- Expected behavior: 920px max-width enforced');

        return {
            cssRulesFound: allRulesFound,
            overrideProtection: hasNotContainerLogic,
            containerRuleExists: !!containerRuleMatch
        };

    } catch (error) {
        console.error('❌ Verification failed:', error.message);
        return { error: error.message };
    }
}

// Run the verification
verifyCSSRules().then(result => {
    console.log('\n🏁 Verification complete!');
    if (result.error) {
        process.exit(1);
    }
});