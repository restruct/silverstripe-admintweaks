import { test, expect, openAdmin, openRecordFromList } from './support';

// CopyTextField: a read-only input with a button that copies its value to the clipboard and
// flips to a green check for two seconds (client JS in admintweaks.js, template in
// templates/Restruct/Silverstripe/AdminTweaks/FormFields/CopyTextField.ss).

// The fixture's value, see fixtures/AtBRecord.php COPY_VALUE.
const VALUE = 'atb-copy-value-123';

// Clipboard access for the page (127.0.0.1 counts as a secure context, so navigator.clipboard exists).
test.use({ permissions: ['clipboard-read', 'clipboard-write'] });

test('the copy button puts the value on the clipboard and confirms it', async ({ page }) => {
    const grid = await openAdmin(page, 'plain');
    const form = await openRecordFromList(page, grid, 'Alpha record');

    const field = form.locator('.copy-text-field');
    const input = field.locator('input');
    const button = field.locator('.copy-text-field__btn');
    await expect(input).toHaveValue(VALUE);
    await expect(input).toHaveAttribute('readonly', '');
    await expect(button).toContainText('Copy');

    // Start from a different clipboard, so the check below cannot pass on a stale value.
    await page.evaluate(() => navigator.clipboard.writeText('not-copied-yet'));

    await button.click();
    await expect.poll(() => page.evaluate(() => navigator.clipboard.readText()), { message: 'clipboard' }).toBe(VALUE);

    // Success state: green outline and the check icon instead of the copy icon.
    await expect(button).toHaveClass(/btn-outline-success/);
    await expect(button).not.toHaveClass(/btn-outline-secondary/);
    await expect(button.locator('.copy-text-field__icon-check')).toBeVisible();
    await expect(button.locator('.copy-text-field__icon-copy')).toBeHidden();

    // And back after two seconds.
    await expect(button).toHaveClass(/btn-outline-secondary/, { timeout: 5_000 });
    await expect(button.locator('.copy-text-field__icon-copy')).toBeVisible();
    await expect(button.locator('.copy-text-field__icon-check')).toBeHidden();

    // A button click inside a CMS form must not submit or navigate it.
    await expect(page.locator('#Form_ItemEditForm input[name="Title"]')).toHaveValue('Alpha record');
});
