const { test, expect } = require('@playwright/test');

const BASE_URL = process.env.PLAYWRIGHT_BASE_URL || 'http://127.0.0.1:8801';

/**
 * Utility to hover the first mega-menu and collect alignment data
 */
async function collectAlignmentData(page) {
  await page.goto(BASE_URL);
  const megaTrigger = page.locator('.auto-populated-dd').first();
  await megaTrigger.hover();
  await page.waitForSelector('.auto-populated-dragdrop', { state: 'visible', timeout: 5000 });
  await page.waitForTimeout(300); // allow layout settle

  return await page.evaluate(() => {
    const columns = Array.from(document.querySelectorAll('.auto-populated-dragdrop > .wp-block-navigation-item'));

    return columns.map((column, index) => {
      const columnRect = column.getBoundingClientRect();
      const header = column.querySelector('.wp-block-navigation-item__content');
      const firstLink = column.querySelector('.subcategory-link a, .subcategory-posts a, .post-item a');
      const headerRect = header ? header.getBoundingClientRect() : null;
      const firstLinkRect = firstLink ? firstLink.getBoundingClientRect() : null;
      const headerAlign = header ? window.getComputedStyle(header).textAlign : null;
      const firstLinkAlign = firstLink ? window.getComputedStyle(firstLink).textAlign : null;

      return {
        columnIndex: index,
        headerText: header?.textContent?.trim() || '(no header)',
        headerDiff: headerRect ? Math.abs(headerRect.left - columnRect.left) : null,
        firstLinkText: firstLink?.textContent?.trim() || '(no link)',
        firstLinkDiff: firstLinkRect ? Math.abs(firstLinkRect.left - columnRect.left) : null,
        headerAlign,
        firstLinkAlign
      };
    });
  });
}

const MAX_OFFSET_PX = 2; // allow tiny rounding differences

test.describe('Mega menu alignment', () => {
  test('columns and nested links stay left-aligned inside mega menu', async ({ page }) => {
    const alignmentData = await collectAlignmentData(page);

    expect(alignmentData.length, 'Should have at least one mega-menu column').toBeGreaterThan(0);

    alignmentData.forEach((column) => {
      if (column.headerDiff !== null) {
        expect(column.headerDiff, `Header "${column.headerText}" should start near column left`).toBeLessThanOrEqual(MAX_OFFSET_PX);
      }

      if (column.firstLinkDiff !== null) {
        expect(column.firstLinkDiff, `First link "${column.firstLinkText}" should align with column left`).toBeLessThanOrEqual(MAX_OFFSET_PX);
      }

      if (column.headerAlign) {
        expect(column.headerAlign, `Header text-align for "${column.headerText}"`).toBe('left');
      }

      if (column.firstLinkAlign) {
        expect(column.firstLinkAlign, `Link text-align for "${column.firstLinkText}"`).toBe('left');
      }
    });
  });
});
