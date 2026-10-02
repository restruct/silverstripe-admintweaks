import { test, expect, openAdmin, searchBox } from './support';

// ModelAdminExtension's two opt-ins, each against the control admin with the defaults, plus
// GridFieldConfigExtension, which drops the duplicate "View x-y of z" count from the header.

test.describe('hide_scaffolded_csv_buttons', () => {
    test('the scaffolded Export, Print and Import buttons are gone when it is on', async ({ page }) => {
        const grid = await openAdmin(page, 'tweaked');
        // Add stays: only the scaffolded CSV/print components are removed.
        await expect(grid.locator('.new-link')).toHaveCount(1);
        await expect(grid.locator('.action_export')).toHaveCount(0);
        await expect(grid.locator('.grid-print-button')).toHaveCount(0);
        await expect(grid.getByRole('button', { name: /Import CSV/ })).toHaveCount(0);
    });

    test('the control admin (default off) still has them', async ({ page }) => {
        const grid = await openAdmin(page, 'plain');
        await expect(grid.locator('.action_export')).toHaveCount(1);
        await expect(grid.locator('.grid-print-button')).toHaveCount(1);
        await expect(grid.getByRole('button', { name: /Import CSV/ })).toHaveCount(1);
    });
});

test.describe('auto_expand_gridfield_search', () => {
    test('the search bar opens on load when it is on', async ({ page }) => {
        // Covers both toggle markups: SS5 renders the "Open search and filter" toggle with the
        // grid-field__filter-open class, framework 6 as a plain button[name=showFilter]
        // (admintweaks#65, where this test was fixme on SS6 until admintweaks.js matched both).
        const grid = await openAdmin(page, 'tweaked');
        // The marker class ModelAdminExtension adds is what the script keys on.
        await expect(grid).toHaveClass(/(^|\s)at-auto-expand-search(\s|$)/);
        await expect(searchBox(grid)).toBeVisible();
    });

    test('the control admin (default off) keeps it closed behind the toggle', async ({ page }) => {
        const grid = await openAdmin(page, 'plain');
        await expect(grid).not.toHaveClass(/at-auto-expand-search/);
        await expect(grid.locator('button[name="showFilter"]')).toBeVisible();
        // Give a deferred auto-open (the module clicks after a 0 ms timeout) the time to happen.
        await page.waitForTimeout(500);
        await expect(searchBox(grid)).toBeHidden();
    });
});

test('the record count shows once, in the footer, not also in the header', async ({ page }) => {
    const grid = await openAdmin(page, 'plain');
    const counts = grid.locator('.pagination-records-number');
    await expect(counts).toHaveCount(1);
    await expect(grid.locator('tfoot .pagination-records-number')).toHaveText(/View\s+1\D+3\s+of\s+3/);
});
