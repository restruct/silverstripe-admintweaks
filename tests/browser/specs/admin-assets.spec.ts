import { test, expect, ADMINS, openAdmin, openRecordFromList, watchDocumentNavigations } from './support';

// The admin-wide parts of the module: its stylesheet and script are required on every LeftAndMain
// screen (_config/admin.yml extra_requirements_*), the script tags the body with the framework
// version, and the script must not break the CMS's own pjax navigation.

test('the module stylesheet and script load on a CMS screen', async ({ page }) => {
    const assets: Record<string, number> = {};
    page.on('response', (r) => {
        const m = r.url().match(/silverstripe-admintweaks\/client\/dist\/(css\/admintweaks\.css|js\/admintweaks\.js)/);
        if (m) {
            assets[m[1]] = r.status();
        }
    });
    await openAdmin(page, 'plain');

    // Served from the exposed _resources path, with a 200 (the console guard catches a 404 too,
    // but this names the file).
    expect(assets, 'admintweaks.css and admintweaks.js requested and served').toEqual({
        'css/admintweaks.css': 200,
        'js/admintweaks.js': 200,
    });
});

test('the body carries the framework-version classes', async ({ page }) => {
    await openAdmin(page, 'plain');
    // admintweaks.js reads the version badge in the CMS help menu ("6.2") and adds fw_v6 and
    // fw_v6-2 to the body, for version-specific CSS in the module and in projects.
    const version = ((await page.locator('.cms-sitename__version').textContent()) ?? '').trim();
    expect(version, 'the CMS shows its version').toMatch(/^\d+\.\d+/);
    const [major] = version.split('.');
    await expect(page.locator('body')).toHaveClass(new RegExp(`(^|\\s)fw_v${major}(\\s|$)`));
    await expect(page.locator('body')).toHaveClass(new RegExp(`(^|\\s)fw_v${version.replace('.', '-')}(\\s|$)`));
});

test('opening a record and going back through pjax logs no console errors', async ({ page }) => {
    // A pjax round trip with the module's script loaded: every entwine block in admintweaks.js
    // runs on the inserted forms, and any error it throws fails the spec (console guard). The forms
    // must arrive through pjax, not a page load, or the check would prove nothing.
    // NOT a regression check for the entwine comment-node shim at the bottom of admintweaks.js:
    // with the shim disabled on a throwaway copy, neither a stock SS5 nor a stock SS6 host logged
    // the "el.getAttribute is not a function" error it guards against (2026-10-02), on this path
    // or on a Pages tree navigation, so there is nothing for a spec to see here.
    const grid = await openAdmin(page, 'plain');
    const navigations = watchDocumentNavigations(page);
    const form = await openRecordFromList(page, grid, 'Bravo record');
    await expect(form.locator('input[name="Title"]')).toHaveValue('Bravo record');
    expect(navigations(), 'document navigations while opening the record').toEqual([]);

    // And back to the list, the second pjax load of the round trip.
    await page.locator('.breadcrumbs-wrapper a.crumb').first().click();
    await expect(page.locator(`#Form_EditForm_${ADMINS.plain.grid} tr.ss-gridfield-item`).first()).toBeVisible();
    expect(navigations(), 'document navigations on the way back').toEqual([]);
});
