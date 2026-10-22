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
    // The suffix is its own badge after the name since admintweaks#79 (so the name's ellipsis cannot cut it
    // off); this assertion pinned the earlier single-string label "Example SITE (DEV)"
    await expect(page.locator('.at-system-switcher__label')).toHaveText('Example SITE');
    await expect(page.locator('.at-system-switcher__env')).toHaveText('DEV');
    await expect(toggle(page)).toHaveAccessibleName('Example SITE (DEV)');
    // The system name, not SiteConfig.Title (a public site's name), which core would show here
    const siteTitle = await page.evaluate(() => document.querySelector('.at-system-switcher__label')?.textContent);
    expect(siteTitle).not.toContain('Your Site Name');
    // <title> = application_name - section, application_name being the switcher's label
    await expect(page).toHaveTitle('Example SITE (DEV) - AdminTweaks plain');
    // core's own header link is replaced, not duplicated
    await expect(header(page).locator('a.cms-sitename__title')).toHaveCount(0);
});

// admintweaks#82: the reset of core's `.cms-menu__header span { padding-left: 26px }` also matched the badge and,
// being more specific, took its left padding (computed 0px left against 4px right).
test('the environment badge keeps its padding on both sides, the name has none on the left', async ({ page }) => {
    await openAdmin(page, 'plain');
    await expect(page.locator('.at-system-switcher__env')).toBeVisible();
    const pad = await page.evaluate(() => {
        const env = getComputedStyle(document.querySelector('.at-system-switcher__env') as HTMLElement);
        const label = getComputedStyle(document.querySelector('.at-system-switcher__label') as HTMLElement);
        return { envLeft: parseFloat(env.paddingLeft), envRight: parseFloat(env.paddingRight), labelLeft: parseFloat(label.paddingLeft) };
    });
    expect(pad.envLeft, 'the badge has left padding').toBeGreaterThan(0);
    expect(pad.envLeft, 'the badge padding is symmetric').toBeCloseTo(pad.envRight, 1);
    // core's 26px stays reset on the name
    expect(pad.labelLeft, 'the name has no left padding').toBe(0);
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

// admintweaks#79: the environment suffix was part of the ellipsised header text, so a long name cut off exactly
// the part that tells environments apart. /admin/atb-longname (AtBLongNameAdmin) has a name too long for the
// header and the TEST suffix.
const LONG_NAME = 'Example SITE with a rather long display name that cannot fit the menu header';
const envBadge = (page: Page) => page.locator('.at-system-switcher__env');

/** Element and header rects plus overflow metrics, measured in the page. */
async function measure(page: Page, selector: string) {
    return page.evaluate((sel) => {
        const el = document.querySelector(sel) as HTMLElement;
        const box = document.querySelector('.cms-menu__header .cms-sitename') as HTMLElement;
        const r = el.getBoundingClientRect();
        const c = box.getBoundingClientRect();
        return {
            el: { left: r.left, right: r.right, top: r.top, bottom: r.bottom, width: r.width },
            header: { left: c.left, right: c.right, top: c.top, bottom: c.bottom },
            scrollWidth: el.scrollWidth,
            clientWidth: el.clientWidth,
            scrollHeight: el.scrollHeight,
            clientHeight: el.clientHeight,
            lineHeight: parseFloat(getComputedStyle(el).lineHeight),
        };
    }, selector);
}

function expectInsideHeader(m: Awaited<ReturnType<typeof measure>>, what: string) {
    expect(m.el.width, `${what} has a width`).toBeGreaterThan(0);
    expect(m.el.left, `${what} left edge inside the header`).toBeGreaterThanOrEqual(m.header.left);
    expect(m.el.right, `${what} right edge inside the header`).toBeLessThanOrEqual(m.header.right);
    expect(m.el.top, `${what} top edge inside the header`).toBeGreaterThanOrEqual(m.header.top);
    expect(m.el.bottom, `${what} bottom edge inside the header`).toBeLessThanOrEqual(m.header.bottom);
}

test('a long name keeps the environment badge whole, only the name is truncated', async ({ page }, testInfo) => {
    await page.goto('/admin/atb-longname');
    await expect(page.locator('#Form_EditForm_longname')).toBeVisible();

    await expect(envBadge(page)).toBeVisible();
    await expect(envBadge(page)).toHaveText('TEST');
    await expect(page.locator('.at-system-switcher__label')).toHaveText(LONG_NAME);
    await page.locator('.cms-menu__header').screenshot({ path: testInfo.outputPath('header-expanded-long-name.png') });

    // The badge: inside the header, nothing of it clipped
    const env = await measure(page, '.at-system-switcher__env');
    expectInsideHeader(env, 'the badge');
    expect(env.scrollWidth, 'the badge text is not truncated').toBeLessThanOrEqual(env.clientWidth);

    // The name: inside the header, left of the badge, wrapped to two lines and truncated after them
    const label = await measure(page, '.at-system-switcher__label');
    expectInsideHeader(label, 'the name');
    expect(label.el.right, 'the name ends before the badge').toBeLessThanOrEqual(env.el.left);
    expect(Math.round(label.clientHeight / label.lineHeight), 'the name wraps to two lines').toBe(2);
    expect(label.scrollHeight, 'the name is truncated').toBeGreaterThan(label.clientHeight);

    // The caret still shows, and the full label stays in the accessible name and the tab title
    expectInsideHeader(await measure(page, '.at-system-switcher__caret'), 'the caret');
    await expect(toggle(page)).toHaveAccessibleName(`${LONG_NAME} (TEST)`);
    await expect(toggle(page)).toHaveAttribute('title', `${LONG_NAME} (TEST)`);
    await expect(page).toHaveTitle(`${LONG_NAME} (TEST) - AdminTweaks long name`);
});

test('the collapsed menu shows the environment badge under the initials', async ({ page }, testInfo) => {
    await page.goto('/admin/atb-longname');
    await expect(page.locator('#Form_EditForm_longname')).toBeVisible();
    await page.locator('.cms-panel-toggle__button, .cms-panel-toggle a.toggle-collapse').first().click();
    await expect(page.locator('.cms-menu')).toHaveClass(/(^|\s)collapsed(\s|$)/);

    await expect(page.locator('.at-system-switcher__initials')).toBeVisible();
    await expect(page.locator('.at-system-switcher__label')).toBeHidden();
    await expect(envBadge(page)).toBeVisible();
    await expect(envBadge(page)).toHaveText('TEST');
    // The collapse animates the menu width: measure once it has settled at the collapsed width
    await expect
        .poll(async () => {
            const { header } = await measure(page, '.at-system-switcher__env');
            return header.right - header.left;
        })
        .toBeLessThanOrEqual(60);
    await page.locator('.cms-menu__header').screenshot({ path: testInfo.outputPath('header-collapsed-long-name.png') });

    const env = await measure(page, '.at-system-switcher__env');
    expectInsideHeader(env, 'the badge');
    expect(env.scrollWidth, 'the badge text is not truncated').toBeLessThanOrEqual(env.clientWidth);
    const initials = await measure(page, '.at-system-switcher__initials');
    expectInsideHeader(initials, 'the initials');
    expect(env.el.top, 'the badge sits under the initials').toBeGreaterThanOrEqual(initials.el.bottom);
    await expect(toggle(page)).toHaveAccessibleName(`${LONG_NAME} (TEST)`);
});
