import { test, expect, openAdmin, type AdminName } from './support';
import type { Locator } from '@playwright/test';

// The GridField title row (GridFieldToolbarHeader) collapses when it holds nothing. Its only
// content in these grids was the "View x-y of z" count, which GridFieldConfigExtension removes
// from every GridFieldConfig, so without the rule in admintweaks.scss an empty ~24px band sat
// between the "Add new" button row and the column headers. The rule must only hit an EMPTY row.

/** Vertical distance between the bottom of the button row and the top of the column headers. */
async function gapBelowButtonRow(grid: Locator): Promise<number> {
    const row = (await grid.locator('.ss-gridfield-buttonrow-before').boundingBox())!;
    const headers = (await grid.locator('tr.sortable-header').boundingBox())!;
    return headers.y - (row.y + row.height);
}

for (const admin of ['tweaked', 'plain'] as AdminName[]) {
    test(`${admin} admin: the empty title row is collapsed, the column headers follow the button row`, async ({ page }) => {
        const grid = await openAdmin(page, admin);
        const titleRow = grid.locator('tr.grid-field__title-row');
        // Still rendered (core markup is untouched), but holding nothing and not displayed.
        await expect(titleRow).toHaveCount(1);
        await expect(titleRow.locator('.pull-xs-right')).toBeEmpty();
        await expect(titleRow.locator('.pull-xs-left')).toBeEmpty();
        await expect(titleRow).toBeHidden();
        expect(Math.abs(await gapBelowButtonRow(grid))).toBeLessThan(1);
    });
}

test('the button row itself is unchanged: with its CSV buttons (plain) as without (tweaked)', async ({ page }) => {
    const plain = await openAdmin(page, 'plain');
    const plainRow = plain.locator('.ss-gridfield-buttonrow-before');
    await expect(plainRow.locator('.new-link')).toBeVisible();
    await expect(plainRow.locator('.action_export')).toBeVisible();
    await expect(plainRow.locator('.grid-print-button')).toBeVisible();
    const plainHeight = (await plainRow.boundingBox())!.height;

    const tweaked = await openAdmin(page, 'tweaked');
    const tweakedRow = tweaked.locator('.ss-gridfield-buttonrow-before');
    await expect(tweakedRow.locator('.new-link')).toBeVisible();
    // Same height: the rule touches the title row only, never the button row's padding.
    expect(Math.abs((await tweakedRow.boundingBox())!.height - plainHeight)).toBeLessThan(1);
});

test('a title row with content keeps showing: fragment text, a component element, or a title', async ({ page }) => {
    const grid = await openAdmin(page, 'tweaked');
    const titleRow = grid.locator('tr.grid-field__title-row');
    await expect(titleRow).toBeHidden();

    // What a component writing into a header fragment, or a GridField with a title, renders.
    const fills = ['text in toolbar-header-right', 'an element in toolbar-header-left', 'the GridField title'];
    for (const what of fills) {
        await titleRow.evaluate((tr, fill) => {
            const th = tr.querySelector('th')!;
            // Reset to core's empty markup before each fill.
            th.innerHTML = '<div class="pull-xs-right"></div><div class="pull-xs-left"></div>';
            if (fill === 'text in toolbar-header-right') {
                th.querySelector('.pull-xs-right')!.textContent = 'View 1-3 of 3';
            } else if (fill === 'an element in toolbar-header-left') {
                th.querySelector('.pull-xs-left')!.innerHTML = '<span class="pagination-records-number">x</span>';
            } else {
                th.insertAdjacentHTML('afterbegin', '<h2 class="grid-field__title title">Records</h2>');
            }
        }, what);
        await expect(titleRow, what).toBeVisible();
        expect((await titleRow.boundingBox())!.height, what).toBeGreaterThan(10);
    }
});
