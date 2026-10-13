import { test, expect, openAdmin, type Page } from './support';

// CMS system switcher (admintweaks#71). The fixture YAML (fixtures/_config/systemswitcher.yml) configures
// three systems plus an escaping case and an invalid url, with "site" as the current one; the host runs in
// dev mode, so the environment suffix is " (DEV)" and the dev fragment's docs url applies.
// /admin/atb-noswitcher (AtBNoSwitcherAdmin) is the control with no systems configured.

const header = (page: Page) => page.locator('.cms-menu__header .cms-sitename');
const toggle = (page: Page) => page.locator('.at-system-switcher__toggle');
const list = (page: Page) => page.locator('.at-system-switcher__list');

test('the header names the current system with the environment suffix, and so does the tab title', async ({ page }) => {
    await openAdmin(page, 'plain');
    await expect(toggle(page)).toBeVisible();
    await expect(page.locator('.at-system-switcher__label')).toHaveText('Example SITE (DEV)');
    // The system name, not SiteConfig.Title (a public site's name), which core would show here
    const siteTitle = await page.evaluate(() => document.querySelector('.at-system-switcher__label')?.textContent);
    expect(siteTitle).not.toContain('Your Site Name');
    // <title> = application_name - section, application_name being the switcher's label
    await expect(page).toHaveTitle('Example SITE (DEV) - AdminTweaks plain');
    // core's own header link is replaced, not duplicated
    await expect(header(page).locator('a.cms-sitename__title')).toHaveCount(0);
});

test('the tab title keeps a single suffix across pjax navigation', async ({ page }) => {
    await openAdmin(page, 'plain');
    // The CMS retitles the tab from the X-Title header of each pjax response
    await page.locator('#Menu-Restruct-AtBrowser-AtBTweakedAdmin a').click();
    await expect(page.locator('#Form_EditForm_tweaked')).toBeVisible();
    await expect(page).toHaveTitle('Example SITE (DEV) - AdminTweaks tweaked');
});

test('the dropdown lists the other valid systems, escaped, and opens and closes by keyboard', async ({ page }) => {
    await openAdmin(page, 'plain');
    await expect(toggle(page)).toHaveAttribute('aria-expanded', 'false');
    await expect(list(page)).toBeHidden();

    // Keyboard: focus the button, Enter opens
    await toggle(page).focus();
    await page.keyboard.press('Enter');
    await expect(toggle(page)).toHaveAttribute('aria-expanded', 'true');
    await expect(list(page)).toBeVisible();

    const links = list(page).locator('a.at-system-switcher__link');
    // Not the current system, not the javascript: url; in config order
    await expect(links).toHaveText(['Example DOCS', 'Example LMS', '<img src=x onerror=alert(1)> "Tricky" & co']);
    // The dev fragment overrode docs' url only; display came from the base block
    await expect(links.nth(0)).toHaveAttribute('href', 'https://docs-dev.example.org/admin');
    await expect(links.nth(1)).toHaveAttribute('href', 'https://learn.example.org/admin');
    await expect(links.nth(2)).toHaveAttribute('href', 'https://tricky.example.org/admin?a=1&b="2"');
    // Escaped: the display's markup is text, no element was created from it
    await expect(list(page).locator('img')).toHaveCount(0);
    await expect(list(page).locator('a[href^="javascript"]')).toHaveCount(0);

    // Escape closes and puts focus back on the button
    await page.keyboard.press('Escape');
    await expect(list(page)).toBeHidden();
    await expect(toggle(page)).toHaveAttribute('aria-expanded', 'false');
    await expect(toggle(page)).toBeFocused();
});

test('a click outside closes the dropdown, a link navigates in the same tab', async ({ page }) => {
    await openAdmin(page, 'plain');
    await toggle(page).click();
    await expect(list(page)).toBeVisible();
    await page.locator('.cms-content').first().click({ position: { x: 5, y: 5 } });
    await expect(list(page)).toBeHidden();

    // Answer the other CMS locally, so the check needs no network
    await page.route('https://learn.example.org/**', (route) =>
        route.fulfill({ status: 200, contentType: 'text/html', body: '<title>Other CMS</title>' }),
    );
    await toggle(page).click();
    await list(page).getByText('Example LMS').click();
    await expect(page).toHaveURL('https://learn.example.org/admin');
    await expect(page).toHaveTitle('Other CMS');
});

test('the collapsed menu shows the initials and the dropdown still works', async ({ page }) => {
    await openAdmin(page, 'plain');
    await page.locator('.cms-panel-toggle__button, .cms-panel-toggle a.toggle-collapse').first().click();
    await expect(page.locator('.cms-menu')).toHaveClass(/(^|\s)collapsed(\s|$)/);

    await expect(page.locator('.at-system-switcher__initials')).toBeVisible();
    await expect(page.locator('.at-system-switcher__initials')).toHaveText('ES');
    await expect(page.locator('.at-system-switcher__label')).toBeHidden();
    // The button keeps its accessible name while the label is hidden
    await expect(toggle(page)).toHaveAccessibleName('Example SITE (DEV)');

    await toggle(page).click();
    // Fixed-positioned, so not clipped by the 60px menu: the link is really clickable
    const docs = list(page).getByText('Example DOCS');
    await expect(docs).toBeVisible();
    const box = await docs.boundingBox();
    expect(box && box.width, 'dropdown wider than the collapsed menu').toBeGreaterThan(60);
    await page.route('https://docs-dev.example.org/**', (route) =>
        route.fulfill({ status: 200, contentType: 'text/html', body: '<title>Docs CMS</title>' }),
    );
    await docs.click();
    await expect(page).toHaveURL('https://docs-dev.example.org/admin');
});

test('without systems configured, the core header and title are unchanged', async ({ page }) => {
    await page.goto('/admin/atb-noswitcher');
    await expect(page.locator('#Form_EditForm_noswitcher')).toBeVisible();

    await expect(page.locator('.at-system-switcher')).toHaveCount(0);
    // Core's markup: the logo link and the title link to the site, in a new tab
    await expect(header(page).locator('a.cms-sitename__link')).toHaveCount(1);
    const title = header(page).locator('a.cms-sitename__title');
    await expect(title).toHaveAttribute('target', '_blank');
    await expect(title).toHaveText('Your Site Name');
    // No suffix on the tab title: core's application_name
    await expect(page).toHaveTitle('Silverstripe - AdminTweaks no switcher');
});
