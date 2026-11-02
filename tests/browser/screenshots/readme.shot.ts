import { test, expect, type Locator, type Page } from '@playwright/test';
import * as path from 'path';
// The same sudo-mode activation the permission-badges spec uses (read-only groups otherwise).
import { ensureSudoMode } from '../specs/support';

// Generates the README screenshots in docs/images/. Not a test suite: run it with
// playwright.screenshots.config.ts (see README.md next to this file). The content comes from the
// browser fixtures in tests/browser/fixtures/: the system switcher config
// (fixtures/_config/systemswitcher.yml), the fixture ModelAdmins and AtBRecord with its
// CopyTextField, plus the pages and groups a fresh recipe-cms install creates.

const IMAGES = path.join(__dirname, '..', '..', '..', 'docs', 'images');

/**
 * Hide what only the test host has: the fixture ModelAdmins' menu entries (except the ones a shot
 * opens) and the switcher's escaping test entry ("Tricky"). Presentation only; nothing about the
 * module's own UI is changed.
 */
async function hideFixtureChrome(page: Page, keepMenu: string[] = []): Promise<void> {
    const keep = keepMenu.map((seg) => `:not(:has(> a[href*="admin/${seg}"]))`).join('');
    await page.addStyleTag({
        content:
            `.cms-menu li:has(> a[href*="admin/atb-"])${keep} { display: none !important; }` +
            '.at-system-switcher__list li:has(> a[data-system="tricky"]) { display: none !important; }',
    });
}

/** No hover state, no focus ring: move the mouse to an empty spot and blur the active element. */
async function settle(page: Page): Promise<void> {
    await page.mouse.move(1270, 790);
    await page.evaluate(() => (document.activeElement as HTMLElement | null)?.blur());
    await page.waitForTimeout(500);
}

/** A clip rectangle around an element, with some margin, kept inside the viewport width. */
async function around(el: Locator, margin = 16): Promise<{ x: number; y: number; width: number; height: number }> {
    const box = (await el.boundingBox())!;
    const x = Math.max(0, Math.floor(box.x - margin));
    const y = Math.max(0, Math.floor(box.y - margin));
    return { x, y, width: Math.min(1280 - x, Math.ceil(box.width + 2 * margin)), height: Math.ceil(box.height + 2 * margin) };
}

test('System switcher: the CMS name with its environment badge, dropdown open', async ({ page }) => {
    await page.goto('/admin/pages');
    await expect(page.locator('.cms-tree li').first()).toBeVisible();
    await hideFixtureChrome(page);
    await page.locator('.at-system-switcher__toggle').click();
    await expect(page.locator('.at-system-switcher__list')).toBeVisible();
    await page.mouse.move(1270, 790);
    await page.waitForTimeout(500);

    // The menu, the page tree with its page-type icons, and the start of the edit area.
    await page.screenshot({ path: path.join(IMAGES, 'system-switcher.png'), clip: { x: 0, y: 0, width: 1280, height: 440 } });
});

test('ModelAdmin: search bar open on load, no scaffolded CSV buttons', async ({ page }) => {
    await page.goto('/admin/atb-tweaked');
    const grid = page.locator('#Form_EditForm_tweaked');
    await expect(grid.locator('tr.ss-gridfield-item').first()).toBeVisible();
    await expect(grid.locator('input.search-box__content-field')).toBeVisible();
    await hideFixtureChrome(page, ['atb-tweaked']);
    await settle(page);

    const box = (await grid.boundingBox())!;
    await page.screenshot({
        path: path.join(IMAGES, 'modeladmin-tweaks.png'),
        clip: { x: 0, y: 0, width: 1280, height: Math.ceil(box.y + box.height + 16) },
    });
});

test('CopyTextField: the copy button confirms with a check', async ({ page, context }) => {
    await context.grantPermissions(['clipboard-read', 'clipboard-write']);
    await page.goto('/admin/atb-plain');
    const grid = page.locator('#Form_EditForm_plain');
    await grid.locator('tr.ss-gridfield-item', { hasText: 'Alpha record' }).click();
    const form = page.locator('#Form_ItemEditForm');
    await expect(form).toBeVisible();
    const holder = form.locator('#Form_ItemEditForm_CopyMe_Holder');
    await expect(holder).toBeVisible();

    // Clicked: the button shows the green check for two seconds; the shot is taken inside that.
    await holder.locator('.copy-text-field__btn').click();
    await expect(holder.locator('.copy-text-field__icon-check')).toBeVisible();
    await page.mouse.move(1270, 790);

    // The Title field above it as well, for context.
    const title = form.locator('#Form_ItemEditForm_Title_Holder');
    const top = (await title.boundingBox())!;
    const bottom = (await holder.boundingBox())!;
    // The field rows span the edit area; the clip starts right of the CMS menu.
    const x = Math.floor(top.x);
    await page.screenshot({
        path: path.join(IMAGES, 'copytextfield.png'),
        clip: { x, y: Math.floor(top.y - 16), width: Math.min(Math.ceil(top.width), 1280 - x), height: Math.ceil(bottom.y + bottom.height - top.y + 32) },
    });
});

test('Security admin: permission codes as badges', async ({ page }) => {
    // Content Authors (ID 1), a default group. Without sudo mode the group form is read-only and
    // the permissions render without their checkboxes, so there is nothing to badge.
    await ensureSudoMode(page);
    await page.goto('/admin/security/groups/EditForm/field/groups/item/1/edit');
    await page.getByRole('tab', { name: 'Permissions', exact: true }).click();
    const set = page.locator('.permissioncheckboxset').first();
    await expect(set.locator('label .badge').first()).toBeVisible();
    await hideFixtureChrome(page);
    // The fixture ModelAdmins add their own CMS_ACCESS_ permissions; hide those rows too.
    await page.addStyleTag({ content: '.permissioncheckboxset li:has(input[value*="AtBrowser" i]) { display: none !important; }' });
    await settle(page);

    // The first block of permissions is enough to show the badges.
    const clip = await around(set);
    // Start right of the CMS menu, which the margin would otherwise catch.
    const menu = (await page.locator('.cms-menu').boundingBox())!;
    const menuRight = Math.ceil(menu.x + menu.width);
    if (clip.x < menuRight) {
        clip.width -= menuRight - clip.x;
        clip.x = menuRight;
    }
    // The labels and badges take the left part of the form; the rest of the row is empty.
    clip.width = Math.min(clip.width, 640);
    clip.height = Math.min(clip.height, 520);
    await page.screenshot({ path: path.join(IMAGES, 'permission-badges.png'), clip });
});
