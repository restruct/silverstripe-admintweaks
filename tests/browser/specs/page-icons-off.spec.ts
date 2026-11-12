import { test, expect } from './support';
import type { BrowserContext, Locator, Page, TestInfo } from '@playwright/test';

// The page_icons opt-out: `SilverStripe\Admin\LeftAndMain.page_icons: false` keeps core's page icons
// as core renders them, and leaves out admintweaks' SVG icons (admintweaks-page-icons.css). A page
// type's own Bootstrap Icons class (cms_icon_class: 'bi-briefcase') still shows its glyph.
//
// The setting is switched off per request by the fixture AtBPageIconsSwitch (cookie
// atb_page_icons=off), so this spec looks at the real Pages section while every other spec keeps
// the default. page-icons.spec.ts covers the default (on).

const ICON_PAGE = 'Browser Icon Page';
const PLAIN_PAGE = 'About Us';
const PAGE_ICONS_CSS = 'admintweaks-page-icons.css';

// Core's icon markup: SS6 renders .record-icon/.jstree-recordicon, SS4/5 .page-icon/.jstree-pageicon.
const ICON = '.record-icon, .page-icon';

type IconStyle = { classes: string; background: string; before: string; beforeFont: string };

async function iconStyle(icon: Locator): Promise<IconStyle> {
    return icon.evaluate((el) => {
        const own = getComputedStyle(el);
        const before = getComputedStyle(el, '::before');
        return {
            classes: el.className,
            background: own.backgroundImage,
            before: before.content,
            beforeFont: before.fontFamily,
        };
    });
}

/** The icon span of a site-tree node, by the link's text content (see page-icons.spec.ts). */
async function treeIcon(page: Page, title: string): Promise<Locator> {
    const tree = page.locator('.cms-tree');
    const link = tree.locator('a').filter({ hasText: new RegExp(`^\\s*${title}\\s*$`) });
    await expect(link).toBeVisible();
    return link.locator(ICON).first();
}

/** The stylesheets the page links, by href. */
async function stylesheets(page: Page): Promise<string[]> {
    return page.locator('link[rel="stylesheet"]').evaluateAll((links) =>
        links.map((l) => (l as HTMLLinkElement).href),
    );
}

/** Switch page_icons off for this test's requests (AtBPageIconsSwitch). */
async function pageIconsOff(context: BrowserContext, testInfo: TestInfo): Promise<void> {
    const baseURL = testInfo.project.use.baseURL;
    if (!baseURL) {
        throw new Error('no baseURL for this project');
    }
    await context.addCookies([{ name: 'atb_page_icons', value: 'off', url: baseURL }]);
}

test('control: with the default, the SVG page icon stylesheet is linked', async ({ page }) => {
    // Must-match control for the absence check below: same page, same lookup, no cookie.
    await page.goto('/admin/pages');
    await expect(page.locator('.cms-tree')).toBeVisible();
    expect((await stylesheets(page)).some((h) => h.includes(PAGE_ICONS_CSS))).toBe(true);
});

test.describe('page_icons: false', () => {
    test.beforeEach(async ({ context }, testInfo) => {
        await pageIconsOff(context, testInfo);
    });

    test('leaves the SVG page icon stylesheet out, keeps admintweaks.css', async ({ page }) => {
        await page.goto('/admin/pages');
        await expect(page.locator('.cms-tree')).toBeVisible();
        const hrefs = await stylesheets(page);
        expect(hrefs.some((h) => h.includes('/css/admintweaks.css')), 'admintweaks.css still loads').toBe(true);
        expect(hrefs.filter((h) => h.includes(PAGE_ICONS_CSS)), 'no admintweaks-page-icons.css').toEqual([]);
    });

    test("a plain Page shows core's own icon, no admintweaks SVG", async ({ page }) => {
        await page.goto('/admin/pages');
        const style = await iconStyle(await treeIcon(page, PLAIN_PAGE));
        expect(style.classes, "core's font icon class on the span").toContain('font-icon-page');
        expect(style.background, 'no admintweaks SVG background').not.toContain('data:image/svg+xml');
        expect(style.before, "core's glyph is not blanked").not.toBe('none');
        expect(style.before, "core's glyph has content").not.toBe('normal');
        expect(style.beforeFont, "drawn in core's icon font").toContain('silverstripe');
    });

    test('a page type with a bi-* icon class still shows its glyph', async ({ page }) => {
        await page.goto('/admin/pages');
        const style = await iconStyle(await treeIcon(page, ICON_PAGE));
        expect(style.classes, 'core put the configured icon class on the span').toContain('bi-briefcase');
        expect(style.before, "the glyph's :before is not blanked").not.toBe('none');
        expect(style.before, "the glyph's :before has content").not.toBe('normal');
        expect(style.beforeFont, 'drawn in the Bootstrap Icons font').toContain('bootstrap-icons');
        expect(style.background, 'no SVG behind the glyph').toBe('none');
    });
});
