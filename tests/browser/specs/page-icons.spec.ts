import { test, expect } from './support';
import type { Locator, Page } from '@playwright/test';

// Page-type icons in the CMS (#108).
//
// admintweaks draws page icons as SVG backgrounds picked by a substring of the page's class name,
// and blanked every icon's :before. So a page type that chose its own icon through core's icon
// class config (Silverstripe 6: cms_icon_class, 5: icon_class) as a Bootstrap Icons class showed
// nothing, and a type whose class name contains neither "Page" nor "SiteTree" (AtBHolder) got no
// icon at all.
//
// Fixtures: AtBIconPage (tests/browser/fixtures/) sets its icon class to 'bi-briefcase'; nothing
// in the fixture config switches include_bootstrap_icons on, so the stylesheet must load by itself.
// AtBHolder (the SelectiveLumberjack fixture) is the type with no "Page" in its name, and the
// default install's "Home" is a plain Page.

const ICON_PAGE = 'Browser Icon Page';
const ICON_PAGE_CSS_CLASS = 'class-Restruct_AtBrowser_AtBIconPage';
const HOLDER = 'Browser Holder';
const PLAIN_PAGE = 'About Us';

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

/** The icon span of a site-tree node, found by the node's own link. */
async function treeIcon(page: Page, title: string): Promise<Locator> {
    const tree = page.locator('.cms-tree');
    const link = tree.getByRole('link', { name: title, exact: true });
    await expect(link).toBeVisible();
    return link.locator(ICON).first();
}

/** Bootstrap Icons' font face has actually loaded, so the glyph is drawn rather than a blank box. */
async function bootstrapIconsFontLoaded(page: Page): Promise<boolean> {
    return page.evaluate(async () => {
        await document.fonts.ready;
        // The stylesheet declares the face; a :before using it makes the browser fetch it.
        return Array.from(document.fonts).some(
            (f) => f.family.replace(/["']/g, '') === 'bootstrap-icons' && f.status === 'loaded',
        );
    });
}

function expectBootstrapGlyph(style: IconStyle, where: string): void {
    expect(style.classes, `${where}: core put the configured icon class on the span`).toContain('bi-briefcase');
    expect(style.before, `${where}: the glyph's :before is not blanked`).not.toBe('none');
    expect(style.before, `${where}: the glyph's :before has content`).not.toBe('normal');
    expect(style.beforeFont, `${where}: drawn in the Bootstrap Icons font`).toContain('bootstrap-icons');
    expect(style.background, `${where}: no class-name SVG behind the glyph`).toBe('none');
}

function expectSvgIcon(style: IconStyle, where: string): void {
    expect(style.background, `${where}: an admintweaks SVG icon`).toContain('data:image/svg+xml');
    expect(style.before, `${where}: no glyph on top of the SVG`).toBe('none');
}

test('a page type with a bi-* icon class shows that glyph in the site tree (#108)', async ({ page }) => {
    await page.goto('/admin/pages');
    const icon = await treeIcon(page, ICON_PAGE);
    expectBootstrapGlyph(await iconStyle(icon), 'site tree');
    expect(await bootstrapIconsFontLoaded(page), 'the Bootstrap Icons font loaded without config').toBe(true);
});

test('a plain Page keeps the default SVG page icon', async ({ page }) => {
    // Control for the test above: the glyph exemption must not reach page types without a bi-* class.
    await page.goto('/admin/pages');
    expectSvgIcon(await iconStyle(await treeIcon(page, PLAIN_PAGE)), 'plain Page');
});

test('a page type without "Page" in its class name gets the default icon (#108)', async ({ page }) => {
    await page.goto('/admin/pages');
    const style = await iconStyle(await treeIcon(page, HOLDER));
    expect(style.classes, 'the fixture really has no "Page" in its class').not.toMatch(/class-\S*Page/);
    expectSvgIcon(style, 'AtBHolder');
});

test('the "Add page" type picker shows the bi-* glyph too (#108)', async ({ page }) => {
    await page.goto('/admin/pages/add');
    const icon = page.locator(`#Form_AddForm .${ICON_PAGE_CSS_CLASS}`).first();
    await expect(icon).toBeAttached();
    expectBootstrapGlyph(await iconStyle(icon), 'Add page picker');
    // And the same control there: the plain Page option keeps its SVG.
    const plain = page.locator('#Form_AddForm .class-Page').first();
    expectSvgIcon(await iconStyle(plain), 'Add page picker, Page');
});
