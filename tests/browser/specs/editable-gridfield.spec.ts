import { test, expect, openAdmin, watchDocumentNavigations } from './support';
import type { Locator, Page } from '@playwright/test';

// Editable GridFields (gridfieldextensions' GridFieldEditableColumns, #114). The fixture
// (AtBEditableAdmin, /admin/atb-editable) has a text input, a select, a checkbox, a checkbox set
// and a read-only two-line column per row.
//
// - CSS: the editable fields no longer make rows tall (Bootstrap form spacing on selects,
//   checkboxes and option sets), checkboxes stay inside their cell, and every cell's first line
//   sits at the same height.
// - JS: gridfieldextensions stops the click on every row that holds an editable field, so a click
//   on the row did nothing. A click outside the fields now opens the detail form; a click on a
//   field keeps its own behaviour; a row without an edit link does nothing.
//
// Measured on SS5 without the fix (2026-11-17): rows 119.5px, the first-line centres spread over
// about 10px, the Active checkbox 4px outside its cell. With it: 86px, spread 0px.

const EDITABLE = { url: '/admin/atb-editable/editable', grid: '#Form_EditForm_editable' };
const NOLINK = { url: '/admin/atb-editable/nolink', grid: '#Form_EditForm_nolink' };

async function openGrid(page: Page, which: typeof EDITABLE): Promise<Locator> {
    await page.goto(which.url);
    const grid = page.locator(which.grid);
    await expect(grid.locator('tr.ss-gridfield-item').first()).toBeVisible();
    return grid;
}

function row(grid: Locator, title: string): Locator {
    // The title is an input value in an editable row, so match on the input, not on text.
    return grid.locator('tr.ss-gridfield-item').filter({
        has: grid.page().locator(`input[value="${title}"]`),
    });
}

/**
 * Vertical centre of each cell's first line, relative to the row: the first visible control
 * (input or select) if the cell starts with one, else the first line of text. A checkbox set's
 * first item counts with its box.
 */
async function firstLineCentres(r: Locator): Promise<Record<string, number>> {
    return r.evaluate((tr) => {
        const rowTop = tr.getBoundingClientRect().top;
        const out: Record<string, number> = {};
        for (const td of Array.from(tr.children) as HTMLElement[]) {
            // Data columns only (col-Title), not the action column (grid-field__col-compact).
            const col = (td.className.match(/(?:^|\s)col-(\w+)/) || [])[1];
            if (!col) {
                continue;
            }
            const ctrl = td.querySelector('input:not([type=hidden]), select');
            let rect: DOMRect | undefined;
            if (ctrl) {
                rect = ctrl.getBoundingClientRect();
            } else {
                const walker = document.createTreeWalker(td, NodeFilter.SHOW_TEXT, {
                    acceptNode: (n) => (n.textContent!.trim() ? NodeFilter.FILTER_ACCEPT : NodeFilter.FILTER_REJECT),
                });
                const text = walker.nextNode();
                if (text) {
                    const range = document.createRange();
                    range.selectNodeContents(text);
                    rect = range.getClientRects()[0];
                }
            }
            if (rect) {
                out[col] = rect.top + rect.height / 2 - rowTop;
            }
        }
        return out;
    });
}

/** Click a point of the row's Summary cell to the right of its text: row space, no field. */
async function clickRowSpace(r: Locator, modifiers?: Array<'Meta' | 'Control' | 'ControlOrMeta'>): Promise<void> {
    const cell = r.locator('td.col-Summary');
    const box = (await cell.boundingBox())!;
    const textRight = await cell.evaluate((td) => {
        const range = document.createRange();
        range.selectNodeContents(td);
        return Math.max(...Array.from(range.getClientRects()).map((x) => x.right)) - td.getBoundingClientRect().left;
    });
    // Guard: the point must be empty cell space, or the test would click text instead.
    expect(box.width - textRight, 'free space right of the summary text').toBeGreaterThan(12);
    await r.locator('td.col-Summary').click({ position: { x: box.width - 6, y: box.height / 2 }, modifiers });
}

test('rows stay compact, checkboxes stay in their cell, first lines line up', async ({ page }) => {
    const grid = await openGrid(page, EDITABLE);
    const r = row(grid, 'Alpha editable');

    // A row holds a 3-item checkbox set (three lines of ~20px); without the fix it was 119.5px.
    expect((await r.boundingBox())!.height).toBeLessThan(95);

    const centres = await firstLineCentres(r);
    expect(Object.keys(centres).sort()).toEqual(['Active', 'Category', 'Summary', 'Tags', 'Title']);
    const values = Object.values(centres);
    expect(Math.max(...values) - Math.min(...values), JSON.stringify(centres)).toBeLessThan(3);

    // Bootstrap's negative margin-left on .form-check-input pushed the single checkbox out of its cell.
    for (const box of await r.locator('input[type=checkbox]').all()) {
        const cellLeft = (await box.evaluate((el) => el.closest('td')!.getBoundingClientRect().left));
        expect((await box.boundingBox())!.x).toBeGreaterThanOrEqual(cellLeft);
    }
});

test('a click on a checkbox toggles it and does not open the record', async ({ page }) => {
    const grid = await openGrid(page, EDITABLE);
    const navigations = watchDocumentNavigations(page);
    const r = row(grid, 'Alpha editable');

    const active = r.locator('td.col-Active input[type=checkbox]');
    await expect(active).not.toBeChecked();
    await active.click();
    await expect(active).toBeChecked();

    // A checkbox set item, by its box and by its label text.
    const green = r.locator('td.col-Tags input[value=green]');
    await green.click();
    await expect(green).toBeChecked();
    const blue = r.locator('td.col-Tags input[value=blue]');
    await r.locator('td.col-Tags label', { hasText: 'Blue' }).click();
    await expect(blue).toBeChecked();

    // Still the list: no edit form, no page load, the URL unchanged.
    await page.waitForTimeout(500);
    await expect(page.locator('#Form_ItemEditForm')).toHaveCount(0);
    expect(page.url()).toContain(EDITABLE.url);
    expect(navigations()).toEqual([]);
});

test('a click on a widget that replaces a field in its cell does not open the record', async ({ page }) => {
    const grid = await openGrid(page, EDITABLE);
    const navigations = watchDocumentNavigations(page);
    const r = row(grid, 'Alpha editable');

    // Mimic a toggle-switch widget (e.g. Switchery): it hides the checkbox and draws its own
    // markup next to it in the same cell, so the click lands on the widget, not on the field.
    const cell = r.locator('td.col-Active');
    await cell.evaluate((td) => {
        const input = td.querySelector('input.editable-column-field') as HTMLElement;
        input.style.display = 'none';
        const widget = document.createElement('span');
        widget.className = 'test-widget';
        widget.innerHTML = '<small>toggle</small>';
        widget.style.display = 'inline-block';
        widget.style.padding = '4px 12px';
        input.insertAdjacentElement('afterend', widget);
    });
    await cell.locator('.test-widget small').click();

    await page.waitForTimeout(750);
    await expect(page.locator('#Form_ItemEditForm')).toHaveCount(0);
    expect(page.url()).toContain(EDITABLE.url);
    expect(navigations()).toEqual([]);
});

test('a click on the select and the text input focuses them and does not open the record', async ({ page }) => {
    const grid = await openGrid(page, EDITABLE);
    const r = row(grid, 'Alpha editable');

    // A native select's popup is not part of the DOM; a click that reached it focuses it, and the
    // value can be changed.
    const select = r.locator('td.col-Category select');
    await select.click();
    await expect(select).toBeFocused();
    await select.selectOption('three');
    await expect(select).toHaveValue('three');

    const title = r.locator('td.col-Title input');
    await title.click();
    await expect(title).toBeFocused();

    await page.waitForTimeout(500);
    await expect(page.locator('#Form_ItemEditForm')).toHaveCount(0);
});

test('a click on empty row space opens the detail form', async ({ page }) => {
    const grid = await openGrid(page, EDITABLE);
    const navigations = watchDocumentNavigations(page);
    const r = row(grid, 'Bravo editable');
    const href = await r.locator('.edit-link').first().getAttribute('href');
    expect(href).toBeTruthy();

    await clickRowSpace(r);
    const form = page.locator('#Form_ItemEditForm');
    await expect(form).toBeVisible();
    await expect(form.locator('input[name=Title]')).toHaveValue('Bravo editable');
    // Loaded in the CMS panel (pjax), not by replacing the page.
    expect(navigations()).toEqual([]);
    expect(page.url()).toContain(href!.split('?')[0]);
});

test('a Cmd/Ctrl-click on empty row space opens the record in a new tab, as core does', async ({ page }) => {
    const grid = await openGrid(page, EDITABLE);
    const r = row(grid, 'Bravo editable');
    const href = await r.locator('.edit-link').first().getAttribute('href');
    expect(href).toBeTruthy();

    const popup = page.context().waitForEvent('page');
    await clickRowSpace(r, ['ControlOrMeta']);
    const tab = await popup;
    expect(tab.url()).toContain(href!.split('?')[0]);
    // The grid itself stays where it was
    await expect(page.locator('#Form_ItemEditForm')).toHaveCount(0);
    await tab.close();
});

test('a row without an edit link does nothing on a click', async ({ page }) => {
    const grid = await openGrid(page, NOLINK);
    const navigations = watchDocumentNavigations(page);
    const r = row(grid, 'Alpha editable');
    await expect(r.locator('.edit-link')).toHaveCount(0);

    await clickRowSpace(r);
    await page.waitForTimeout(750);
    await expect(page.locator('#Form_ItemEditForm')).toHaveCount(0);
    expect(page.url()).toContain(NOLINK.url);
    expect(navigations()).toEqual([]);
});

test('a non-editable GridField is unchanged: a row click opens the record', async ({ page }) => {
    const grid = await openAdmin(page, 'plain');
    await expect(grid).not.toHaveClass(/ss-gridfield-editable/);
    const navigations = watchDocumentNavigations(page);
    await grid.locator('tr.ss-gridfield-item', { hasText: 'Bravo record' }).click();
    const form = page.locator('#Form_ItemEditForm');
    await expect(form).toBeVisible();
    await expect(form.locator('input[name=Title]')).toHaveValue('Bravo record');
    expect(navigations()).toEqual([]);
});
