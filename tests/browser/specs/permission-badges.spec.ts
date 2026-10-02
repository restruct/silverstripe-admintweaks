import { test, expect, ensureSudoMode } from './support';

// Security admin: admintweaks.js appends each permission's code as a badge to its checkbox label
// on a group's Permissions tab, so admins can tell similarly named permissions apart.

test('every permission checkbox on a group shows its code as a badge', async ({ page }) => {
    await ensureSudoMode(page);

    // Content Authors (ID 1), a default group. A full load of the edit URL, then the tab.
    await page.goto('/admin/security/groups/EditForm/field/groups/item/1/edit');
    await page.getByRole('tab', { name: 'Permissions', exact: true }).click();

    const items = page.locator('.permissioncheckboxset li').filter({ has: page.locator('input[type="checkbox"]') });
    await expect(items.first()).toBeVisible();
    const count = await items.count();
    expect(count, 'permission checkboxes listed').toBeGreaterThan(5);

    // One badge per label, and its text is that checkbox's own value (the permission code).
    const pairs = await items.evaluateAll((lis) =>
        lis.map((li) => ({
            code: li.querySelector('input[type="checkbox"]')?.getAttribute('value') ?? '',
            badges: [...li.querySelectorAll('label .badge')].map((b) => b.textContent ?? ''),
        })),
    );
    for (const { code, badges } of pairs) {
        expect(badges, `badge for ${code}`).toEqual([code]);
    }
    // A known one, by name, so an empty code list cannot pass.
    await expect(
        items.filter({ has: page.locator('input[value="CMS_ACCESS_CMSMain"]') }).locator('label .badge'),
    ).toHaveText('CMS_ACCESS_CMSMain');
});
