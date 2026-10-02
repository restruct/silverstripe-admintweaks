import { test as base, expect, type Locator, type Page } from '@playwright/test';

// Shared fixtures and helpers for the admintweaks specs.
//
// The CMS screens are the fixture ModelAdmins in tests/browser/fixtures/ (copied into the scratch
// host by the runner): /admin/atb-tweaked (both ModelAdminExtension opt-ins on) and /admin/atb-plain
// (the control, defaults), both listing AtBRecord, whose edit form carries a CopyTextField.

/** The fixture ModelAdmins: URL segment and GridField name (the managed_models key). */
export const ADMINS = {
    tweaked: { url: '/admin/atb-tweaked', grid: 'tweaked' },
    plain: { url: '/admin/atb-plain', grid: 'plain' },
} as const;
export type AdminName = keyof typeof ADMINS;

/**
 * test, extended with an automatic console guard: every spec fails if the page logs a console
 * error or throws an uncaught exception at any point, page load included. "Failed to load
 * resource" (any 4xx/5xx asset or request) arrives as a console error too, so a module stylesheet
 * or script that 404s is caught here as well. Warnings (the admin's own deprecation notices) do
 * not count.
 */
export const test = base.extend<{ consoleGuard: void }>({
    consoleGuard: [
        async ({ page }, use, testInfo) => {
            const errors: string[] = [];
            page.on('console', (msg) => {
                if (msg.type() === 'error') {
                    errors.push(`console.error: ${msg.text()} (${msg.location().url})`);
                }
            });
            page.on('pageerror', (err) => errors.push(`uncaught: ${err.message}`));

            await use();

            if (errors.length) {
                await testInfo.attach('console-errors', { body: errors.join('\n'), contentType: 'text/plain' });
            }
            expect(errors, 'no console errors or uncaught exceptions').toEqual([]);
        },
        { auto: true },
    ],
});

export { expect };

/** The Silverstripe major under test, from the Playwright project name ("ss5", "ss6"). */
export function majorOf(projectName: string): number {
    return Number(projectName.replace(/^ss/, ''));
}

/** The GridField of a fixture ModelAdmin. */
export function listGrid(page: Page, admin: AdminName): Locator {
    return page.locator(`#Form_EditForm_${ADMINS[admin].grid}`);
}

/** Open a fixture ModelAdmin with a full page load and wait until its GridField has rows. */
export async function openAdmin(page: Page, admin: AdminName): Promise<Locator> {
    await page.goto(ADMINS[admin].url);
    const grid = listGrid(page, admin);
    await expect(grid.locator('tr.ss-gridfield-item').first()).toBeVisible();
    return grid;
}

/** The GridField's search box input (rendered by the admin's React search form). */
export function searchBox(grid: Locator): Locator {
    return grid.locator('input.search-box__content-field');
}

/**
 * Record every DOCUMENT request of the main frame from now on. Opening a record from the list must
 * go through the CMS's own pjax/XHR requests, never by replacing the page (which would also hide
 * the entwine errors the comment-node shim exists for). Returns a getter for the URLs seen.
 */
export function watchDocumentNavigations(page: Page): () => string[] {
    const seen: string[] = [];
    page.on('request', (r) => {
        if (r.isNavigationRequest() && r.frame() === page.mainFrame()) {
            seen.push(`${r.method()} ${r.url()}`);
        }
    });
    return () => [...seen];
}

/**
 * Click a GridField row and wait for the record's edit form, loaded by the CMS through pjax.
 * Returns the form.
 */
export async function openRecordFromList(page: Page, grid: Locator, title: string): Promise<Locator> {
    await grid.locator('tr.ss-gridfield-item', { hasText: title }).click();
    const form = page.locator('#Form_ItemEditForm');
    await expect(form).toBeVisible();
    return form;
}

/**
 * Security admin sections that hold members or groups are read-only until the user re-enters
 * their password ("sudo mode": on SS6, and on SS5 from framework 5.3). Activate it through the
 * real notice when it is shown; a no-op on a version without it. Sudo mode lives in the session,
 * so it holds for the rest of this spec.
 */
export async function ensureSudoMode(page: Page): Promise<void> {
    await page.goto('/admin/security/groups');
    await expect(page.locator('#Form_EditForm_groups')).toBeVisible();
    const notice = page.locator('.sudo-mode-password-field__notice-button');
    if ((await notice.count()) === 0) {
        return;
    }
    await notice.click();
    await page.locator('input#SudoModePassword').fill('admin');
    await page.locator('.sudo-mode-password-field__verify-button').click();
    await expect(page.locator('.sudo-mode-password-field')).toHaveCount(0);
}
