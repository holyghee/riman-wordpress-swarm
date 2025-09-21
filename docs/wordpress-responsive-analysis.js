// WordPress Container and Wide Responsive Behavior Analysis
// This script analyzes how WordPress handles container and alignwide on different viewports

const testViewports = [
  { name: 'Desktop', width: 1920, height: 1080 },
  { name: 'Laptop', width: 1440, height: 900 },
  { name: 'Tablet', width: 768, height: 1024 },
  { name: 'Mobile', width: 375, height: 667 }
];

async function analyzeWordPressResponsiveBehavior() {
  console.log('=== WordPress Container & Wide Responsive Analysis ===\n');

  for (const viewport of testViewports) {
    console.log(`\n${viewport.name} (${viewport.width}px):`);
    console.log('─'.repeat(40));

    // Simulate viewport by calculating CSS values
    const vw = viewport.width / 100;

    // Calculate spacing-50: clamp(30px, 5vw, 50px)
    const spacing50_5vw = 5 * vw;
    const spacing50 = Math.max(30, Math.min(spacing50_5vw, 50));

    // WordPress CSS Variables
    const contentSize = 1140;
    const wideSize = 1170;
    const globalPadding = spacing50;

    // Available content area
    const availableWidth = viewport.width - (globalPadding * 2);

    // Effective widths
    const effectiveContentWidth = Math.min(contentSize, availableWidth);
    const effectiveWideWidth = Math.min(wideSize, availableWidth);

    console.log(`Global Padding: ${globalPadding}px (from clamp(30px, 5vw, 50px))`);
    console.log(`Available Width: ${availableWidth}px`);
    console.log(`Content Size (.is-layout-constrained): ${effectiveContentWidth}px`);
    console.log(`Wide Size (.alignwide): ${effectiveWideWidth}px`);

    // Check if they're the same (constrained by viewport)
    if (effectiveContentWidth === effectiveWideWidth) {
      console.log(`⚠️  Content and Wide are SAME width (viewport constrained)`);
    } else {
      console.log(`✅ Content and Wide have different widths`);
      console.log(`   Difference: ${effectiveWideWidth - effectiveContentWidth}px`);
    }
  }

  console.log('\n=== Key Findings ===');
  console.log('1. WordPress uses clamp(30px, 5vw, 50px) for responsive padding');
  console.log('2. Content size: 1140px, Wide size: 1170px (only 30px difference)');
  console.log('3. On narrow viewports, both content and wide become same width');
  console.log('4. The difference is only visible on larger screens');
}

// CSS Rules from WordPress Twenty Twenty-Five
const wordpressCSSRules = `
/* WordPress Core Responsive Rules */
:root {
  --wp--style--global--content-size: 1140px;
  --wp--style--global--wide-size: 1170px;
  --wp--preset--spacing--50: clamp(30px, 5vw, 50px);
  --wp--style--root--padding-right: var(--wp--preset--spacing--50);
  --wp--style--root--padding-left: var(--wp--preset--spacing--50);
}

.is-layout-constrained > :where(:not(.alignleft):not(.alignright):not(.alignfull)) {
  max-width: var(--wp--style--global--content-size);
  margin-left: auto !important;
  margin-right: auto !important;
}

.is-layout-constrained > .alignwide {
  max-width: var(--wp--style--global--wide-size);
}

.has-global-padding {
  padding-right: var(--wp--style--root--padding-right);
  padding-left: var(--wp--style--root--padding-left);
}
`;

console.log('\n=== WordPress CSS Rules ===');
console.log(wordpressCSSRules);

// Run the analysis
analyzeWordPressResponsiveBehavior();