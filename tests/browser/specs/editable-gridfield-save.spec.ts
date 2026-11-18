import { test, expect } from './support';
import type { Locator, Page } from '@playwright/test';

// Saving an unticked checkbox through an editable GridField that sits in a record's edit form, as
// in real use: AtBParentRecord (/admin/atb-parent) has a has_many GridField of AtBChildRecord with
// GridFieldEditableColumns, saved with the parent's own Save button. Child one starts with Active
// (a plain checkbox) ticked, Child two with Featured (a toggle switch) ticked; one ticked box per
// row, because on Silverstripe 5 a second ticked box would save the row anyway (AtBParentRecord).
//
// An unticked checkbox submits nothing, so GridFieldEditableColumns never sees the change and the
// box comes back ticked after the save. admintweaks.js adds a hidden `.checkbox_zero_input` with the
// checkbox's name when it is unticked, which submits an empty value instead.
//
// Must-fail control: with the `.ss-gridfield-item input[type="checkbox"]` entwine rule in
// admintweaks.js disabled, both checks below fail: after the reload the boxes are ticked again.

async function openParentForm(page: Page): Promise<{ form: Locator; url: string }> {
    await page.goto('/admin/atb-parent/parent');
    const list = page.locator('#Form_EditForm_parent');
    await list.locator('tr.ss-gridfield-item', { hasText: 'Parent with children' }).click();
    const form = page.locator('#Form_ItemEditForm');
    await expect(form.locator('input[name=Title]')).toHaveValue('Parent with children');
    return { form, url: page.url() };
}

/** The children GridField's checkbox of one column in the row of the child with this title. */
function childCheckbox(form: Locator, title: string, column: 'Active' | 'Featured'): Locator {
    const grid = form.locator('#Form_ItemEditForm_Children');
    // The title is an input value in an editable row, so match on the input, not on text.
    const row = grid.locator('tr.ss-gridfield-item').filter({
        has: form.page().locator(`input[value="${title}"]`),
    });
    return row.locator(`td.col-${column} input[type=checkbox]`);
}

async function saveAndReload(page: Page, form: Locator, url: string): Promise<Locator> {
    await form.locator('button[name=action_doSave]').click();
    await expect(page.locator('.message.good, .alert-success, .toast--success').first()).toBeVisible();
    // A full reload: the values come from the database, not from the page
    await page.goto(url);
    const reloaded = page.locator('#Form_ItemEditForm');
    await expect(reloaded.locator('input[name=Title]')).toHaveValue('Parent with children');
    return reloaded;
}

test('an unticked checkbox in an editable GridField is saved unticked with the parent record', async ({ page }) => {
    let { form, url } = await openParentForm(page);
    const active = childCheckbox(form, 'Child one', 'Active');
    // Guard: the seed ticked it, so an unticked box after the save can only come from the save
    await expect(active).toBeChecked();
    await active.click();
    await expect(active).not.toBeChecked();

    form = await saveAndReload(page, form, url);
    await expect(childCheckbox(form, 'Child one', 'Active')).not.toBeChecked();
    // The other child was not touched
    await expect(childCheckbox(form, 'Child two', 'Featured')).toBeChecked();
});

test('an unticked toggle switch in an editable GridField is saved unticked with the parent record', async ({ page }) => {
    let { form, url } = await openParentForm(page);
    // Child two: the test above unticks Child one's Active box
    const featured = childCheckbox(form, 'Child two', 'Featured');
    await expect(featured).toHaveClass(/toggle-switch/);
    await expect(featured).toBeChecked();
    await featured.click();
    await expect(featured).not.toBeChecked();

    form = await saveAndReload(page, form, url);
    await expect(childCheckbox(form, 'Child two', 'Featured')).not.toBeChecked();
    // Child two's Active box was never ticked and stays so
    await expect(childCheckbox(form, 'Child two', 'Active')).not.toBeChecked();
});
