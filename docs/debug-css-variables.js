const { chromium } = require('playwright');

async function debugCSSVariables() {
    const browser = await chromium.launch();
    const context = await browser.newContext();
    const page = await context.newPage();
    await page.setViewportSize({ width: 1440, height: 900 });

    const urls = [
        { name: 'startseite', url: 'http://127.0.0.1:8801/?p=5593' },
        { name: 'template', url: 'http://127.0.0.1:8801/?p=10496' }
    ];

    for (const { name, url } of urls) {
        console.log(`\n=== Debugging CSS Variables on ${name} ===`);

        await page.goto(url, { waitUntil: 'networkidle' });
        await page.waitForSelector('.riman-service-cards-wrap', { timeout: 10000 });

        const cssDebug = await page.evaluate(() => {
            const container = document.querySelector('.riman-service-cards-wrap');
            if (!container) return null;

            const computedStyle = window.getComputedStyle(container);
            const rootStyle = window.getComputedStyle(document.documentElement);

            // Get all CSS custom properties
            const getAllCSSVariables = (element) => {
                const variables = {};
                const style = window.getComputedStyle(element);
                for (let i = 0; i < style.length; i++) {
                    const prop = style[i];
                    if (prop.startsWith('--')) {
                        variables[prop] = style.getPropertyValue(prop);
                    }
                }
                return variables;
            };

            return {
                containerClasses: container.className,
                computedWidth: computedStyle.width,
                computedMaxWidth: computedStyle.maxWidth,
                computedMarginLeft: computedStyle.marginLeft,
                computedMarginRight: computedStyle.marginRight,
                rootVariables: getAllCSSVariables(document.documentElement),
                containerVariables: getAllCSSVariables(container),
                wpStyleGlobalContentSize: rootStyle.getPropertyValue('--wp--style--global--content-size'),
                wpStyleGlobalWideSize: rootStyle.getPropertyValue('--wp--style--global--wide-size'),
                localContentSize: computedStyle.getPropertyValue('--local-content-size'),
                localWideSize: computedStyle.getPropertyValue('--local-wide-size'),
                bodyClass: document.body.className,
                htmlClass: document.documentElement.className
            };
        });

        console.log('Container Classes:', cssDebug.containerClasses);
        console.log('Body Classes:', cssDebug.bodyClass);
        console.log('HTML Classes:', cssDebug.htmlClass);
        console.log('\nCSS Variables:');
        console.log('  --wp--style--global--content-size:', cssDebug.wpStyleGlobalContentSize);
        console.log('  --wp--style--global--wide-size:', cssDebug.wpStyleGlobalWideSize);
        console.log('  --local-content-size:', cssDebug.localContentSize);
        console.log('  --local-wide-size:', cssDebug.localWideSize);
        console.log('\nComputed Styles:');
        console.log('  width:', cssDebug.computedWidth);
        console.log('  max-width:', cssDebug.computedMaxWidth);
        console.log('  margin-left:', cssDebug.computedMarginLeft);
        console.log('  margin-right:', cssDebug.computedMarginRight);

        // Check what CSS rules are applied
        const appliedRules = await page.evaluate(() => {
            const container = document.querySelector('.riman-service-cards-wrap');
            if (!container) return [];

            const rules = [];
            const sheets = Array.from(document.styleSheets);

            for (const sheet of sheets) {
                try {
                    const cssRules = Array.from(sheet.cssRules || sheet.rules || []);
                    for (const rule of cssRules) {
                        if (rule.selectorText && container.matches(rule.selectorText)) {
                            rules.push({
                                selector: rule.selectorText,
                                width: rule.style.width,
                                maxWidth: rule.style.maxWidth,
                                marginLeft: rule.style.marginLeft,
                                marginRight: rule.style.marginRight,
                                href: sheet.href || 'inline'
                            });
                        }
                    }
                } catch (e) {
                    // Cross-origin stylesheets may not be accessible
                }
            }
            return rules;
        });

        console.log('\nApplied CSS Rules:');
        appliedRules.forEach((rule, i) => {
            console.log(`  ${i + 1}. ${rule.selector} (from ${rule.href})`);
            if (rule.width) console.log(`     width: ${rule.width}`);
            if (rule.maxWidth) console.log(`     max-width: ${rule.maxWidth}`);
            if (rule.marginLeft) console.log(`     margin-left: ${rule.marginLeft}`);
            if (rule.marginRight) console.log(`     margin-right: ${rule.marginRight}`);
        });
    }

    await browser.close();
}

debugCSSVariables().catch(console.error);