import { test, expect, watchDocumentNavigations } from './support';
import type { Locator, Page } from '@playwright/test';

// The opt-in toggle switch: a checkbox with the `toggle-switch` class (on the input, or on an
// element around it) is drawn as a switch by CSS alone. The fixture record (AtBEditableRecord) has
// Featured as a switch in the "toggle" tab of /admin/atb-editable (a GridFieldEditableColumns
// column, the class added in the column's callback), and in its edit form Featured
// (->setToggleSwitch()), Tags (a CheckboxSetField, ->setToggleSwitch()) and Archived (disabled).
//
// Must-fail control: without the toggle-switch rules in admintweaks.scss, the "renders as a switch"
// assertions fail (the checkbox keeps its square box: appearance stays native on SS5, the box is 1em
// wide, and there is no knob that moves).

const TOGGLE = { url: '/admin/atb-editable/toggle', grid: '#Form_EditForm_toggle' };

async function openGrid(page: Page): Promise<Locator> {
    await page.goto(TOGGLE.url);
    const grid = page.locator(TOGGLE.grid);
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
 * Open a record's edit form at its edit link's URL (a full page load; the link itself sits in the
 * row's action menu) and return the form.
 */
async function openForm(page: Page, title: string): Promise<Locator> {
    const grid = await openGrid(page);
    const href = await row(grid, title).locator('.edit-link').first().getAttribute('href');
    expect(href).toBeTruthy();
    await page.goto(href!);
    const form = page.locator('#Form_ItemEditForm');
    await expect(form).toBeVisible();
    await expect(form.locator('input[name=Title]')).toHaveValue(title);
    return form;
}

/** What makes a checkbox look like a switch, read from its computed style. */
async function switchStyle(input: Locator) {
    return input.evaluate((el) => {
        const cs = getComputedStyle(el);
        const knob = getComputedStyle(el, '::before');
        const box = el.getBoundingClientRect();
        return {
            appearance: cs.appearance,
            // The knob is positioned against the track, so the track must be a positioned box
            position: cs.position,
            width: box.width,
            height: box.height,
            radius: parseFloat(cs.borderTopLeftRadius),
            knobContent: knob.content,
            knobWidth: parseFloat(knob.width),
            // The knob's horizontal shift (0 when off)
            knobX: knob.transform === 'none' ? 0 : new DOMMatrix(knob.transform).m41,
            background: cs.backgroundColor,
        };
    });
}

/**
 * Assert the checkbox is drawn as a switch: no native box, wider than high with round ends, a knob
 * that sits left when off and moves right (and the track changes colour) when checked.
 */
async function expectSwitch(input: Locator): Promise<void> {
    const wasChecked = await input.isChecked();
    if (wasChecked) {
        await input.uncheck();
    }
    // The knob slides with a short transition: wait until it is back at the left
    await expect.poll(async () => (await switchStyle(input)).knobX).toBe(0);
    const off = await switchStyle(input);
    expect(off.appearance, 'appearance').toBe('none');
    expect(off.position, 'the track holds the knob').not.toBe('static');
    expect(off.width / off.height, `width/height ${off.width}x${off.height}`).toBeGreaterThan(1.5);
    expect(off.radius, 'round ends').toBeGreaterThanOrEqual(off.height / 2 - 1);
    expect(off.knobContent, 'knob pseudo-element').not.toBe('none');
    expect(off.knobWidth).toBeGreaterThan(0);
    expect(off.knobX).toBe(0);

    await input.check();
    // Wait for the knob to arrive, a track width minus its height
    await expect.poll(async () => (await switchStyle(input)).knobX).toBeCloseTo(off.width - off.height, 0);
    const on = await switchStyle(input);
    expect(on.background, 'track colour when checked').not.toBe(off.background);

    if (!wasChecked) {
        await input.uncheck();
    }
}

/** A visible focus indicator: an outline, or a box-shadow ring. */
async function focusIndicator(input: Locator) {
    return input.evaluate((el) => {
        const cs = getComputedStyle(el);
        return {
            focusVisible: el.matches(':focus-visible'),
            outline: cs.outlineStyle !== 'none' && parseFloat(cs.outlineWidth) > 0 ? `${cs.outlineWidth} ${cs.outlineStyle} ${cs.outlineColor}` : '',
            ring: cs.boxShadow !== 'none' ? cs.boxShadow : '',
        };
    });
}

test.describe('in an editable GridField column', () => {
    test('renders as a switch', async ({ page }) => {
        const grid = await openGrid(page);
        const r = row(grid, 'Alpha editable');
        const featured = r.locator('td.col-Featured input[type=checkbox]');
        await expectSwitch(featured);
        // On the first line of its row, level with the plain checkbox in the Active column
        const centre = async (l: Locator) => {
            const b = (await l.boundingBox())!;
            return b.y + b.height / 2;
        };
        const plain = await centre(r.locator('td.col-Active input[type=checkbox]'));
        expect(Math.abs((await centre(featured)) - plain), 'switch and checkbox centres').toBeLessThan(3);
    });

    test('a click toggles it and does not open the row; unchecking keeps the zero input', async ({ page }) => {
        const grid = await openGrid(page);
        const navigations = watchDocumentNavigations(page);
        const r = row(grid, 'Alpha editable');
        const featured = r.locator('td.col-Featured input[type=checkbox]');

        await expect(featured).not.toBeChecked();
        await featured.click();
        await expect(featured).toBeChecked();
        await featured.click();
        await expect(featured).not.toBeChecked();

        // admintweaks.js: an unchecked checkbox in a GridField row submits '0' through a hidden
        // input with its name, inserted before it; the switch is still that checkbox.
        const name = await featured.getAttribute('name');
        const zero = r.locator('td.col-Featured input.checkbox_zero_input[type=hidden]');
        await expect(zero).toHaveCount(1);
        await expect(zero).toHaveAttribute('name', name!);

        await page.waitForTimeout(500);
        await expect(page.locator('#Form_ItemEditForm')).toHaveCount(0);
        expect(page.url()).toContain(TOGGLE.url);
        expect(navigations()).toEqual([]);
    });

    test('the space key toggles it, with a visible focus ring, and does not open the row', async ({ page }) => {
        const grid = await openGrid(page);
        const navigations = watchDocumentNavigations(page);
        const r = row(grid, 'Bravo editable');
        const featured = r.locator('td.col-Featured input[type=checkbox]');

        // Reach it by keyboard: focus the field before it (the read-only Summary column has none,
        // so the last field before it is in Tags) and Tab, as a keyboard user would.
        await r.locator('td.col-Tags input[type=checkbox]').last().focus();
        await page.keyboard.press('Tab');
        await expect(featured).toBeFocused();
        const focus = await focusIndicator(featured);
        expect(focus.outline || focus.ring, JSON.stringify(focus)).not.toBe('');

        await page.keyboard.press('Space');
        await expect(featured).toBeChecked();

        await page.waitForTimeout(500);
        await expect(page.locator('#Form_ItemEditForm')).toHaveCount(0);
        expect(navigations()).toEqual([]);
    });
});

test.describe('in an edit form', () => {
    test('CheckboxField and CheckboxSetField options render as switches', async ({ page }) => {
        const form = await openForm(page, 'Alpha editable');
        await expectSwitch(form.locator('#Form_ItemEditForm_Featured'));
        // Every option of the checkbox set: the class is on their wrapper, not on the inputs
        const tags = form.locator('#Form_ItemEditForm_Tags input[type=checkbox]');
        await expect(tags).toHaveCount(3);
        for (const tag of await tags.all()) {
            await expectSwitch(tag);
        }
        // A plain checkbox next to them is left alone
        const locked = await switchStyle(form.locator('#Form_ItemEditForm_Locked'));
        expect(locked.width / locked.height).toBeLessThan(1.5);
    });

    test('the switch lines up with the plain checkboxes, and its label stays on its line', async ({ page }) => {
        const form = await openForm(page, 'Alpha editable');
        const holder = form.locator('#Form_ItemEditForm_Featured_Holder');
        const input = holder.locator('input[type=checkbox]');
        const label = holder.locator('label.form-check-label');
        const inputBox = (await input.boundingBox())!;
        const labelBox = (await label.boundingBox())!;
        // Left edges: the CheckboxField switch, a checkbox-set switch and a plain checkbox (Locked)
        const plainX = (await form.locator('#Form_ItemEditForm_Locked').boundingBox())!.x;
        const tagX = (await form.locator('#Form_ItemEditForm_Tags input[type=checkbox]').first().boundingBox())!.x;
        expect(Math.abs(inputBox.x - plainX), `switch ${inputBox.x}, plain checkbox ${plainX}`).toBeLessThan(2);
        expect(Math.abs(tagX - plainX), `set switch ${tagX}, plain checkbox ${plainX}`).toBeLessThan(2);
        // The label is one line high: the switch did not push its text onto a line of its own
        expect(labelBox.height).toBeLessThan(inputBox.height * 2 + 4);
        // and the text starts after the switch, not under it
        const textX = await label.evaluate((el) => {
            // The label's own text nodes only (a range over the whole label includes the input)
            const xs: number[] = [];
            for (const node of Array.from(el.childNodes)) {
                if (node.nodeType === Node.TEXT_NODE && node.textContent!.trim()) {
                    const range = document.createRange();
                    range.selectNodeContents(node);
                    xs.push(...Array.from(range.getClientRects()).map((r) => r.x));
                }
            }
            return Math.min(...xs);
        });
        expect(textX).toBeGreaterThanOrEqual(inputBox.x + inputBox.width);
    });

    test('a click and the space key toggle it, with a visible focus ring', async ({ page }) => {
        const form = await openForm(page, 'Alpha editable');
        const featured = form.locator('#Form_ItemEditForm_Featured');

        await expect(featured).not.toBeChecked();
        await featured.click();
        await expect(featured).toBeChecked();

        // By keyboard: Tab from the field before it (Locked), then Space
        await form.locator('#Form_ItemEditForm_Locked').focus();
        await page.keyboard.press('Tab');
        await expect(featured).toBeFocused();
        const focus = await focusIndicator(featured);
        expect(focus.focusVisible, 'focus from the keyboard is :focus-visible').toBe(true);
        expect(focus.outline || focus.ring, JSON.stringify(focus)).not.toBe('');
        await page.keyboard.press('Space');
        await expect(featured).not.toBeChecked();
    });

    test('a disabled switch cannot be toggled', async ({ page }) => {
        const form = await openForm(page, 'Alpha editable');
        const archived = form.locator('#Form_ItemEditForm_Archived');
        await expect(archived).toBeDisabled();
        await expect(archived).not.toBeChecked();
        // Still drawn as a switch, faded
        const style = await switchStyle(archived);
        expect(style.appearance).toBe('none');
        expect(Number(await archived.evaluate((el) => getComputedStyle(el).opacity))).toBeLessThan(1);

        // A click on it (forced past Playwright's own disabled check) and on its label change nothing
        await archived.click({ force: true });
        await form.locator('#Form_ItemEditForm_Archived_Holder label').click({ force: true });
        await expect(archived).not.toBeChecked();
    });

    test('the value is saved, on and off', async ({ page }) => {
        // Bravo: editable-gridfield.spec.ts toggles Alpha's tags and expects them unticked first
        let form = await openForm(page, 'Bravo editable');
        const url = page.url();
        await form.locator('#Form_ItemEditForm_Featured').click();
        await form.locator('#Form_ItemEditForm_Tags input[value=blue]').click();
        await form.locator('#Form_ItemEditForm_Tags input[value=red]').click();
        await form.locator('button[name=action_doSave]').click();
        await expect(page.locator('.message.good, .alert-success, .toast--success').first()).toBeVisible();

        // A full reload: the values come from the database, not from the page
        await page.goto(url);
        form = page.locator('#Form_ItemEditForm');
        await expect(form.locator('#Form_ItemEditForm_Featured')).toBeChecked();
        await expect(form.locator('#Form_ItemEditForm_Tags input[value=blue]')).toBeChecked();
        await expect(form.locator('#Form_ItemEditForm_Tags input[value=red]')).not.toBeChecked();

        // And off again: an unchecked switch saves as off
        await form.locator('#Form_ItemEditForm_Featured').click();
        await form.locator('button[name=action_doSave]').click();
        await expect(page.locator('.message.good, .alert-success, .toast--success').first()).toBeVisible();
        await page.goto(url);
        await expect(page.locator('#Form_ItemEditForm_Featured')).not.toBeChecked();
    });
});
