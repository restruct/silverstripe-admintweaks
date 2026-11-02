import { test, expect } from './support';
import type { Locator, Page } from '@playwright/test';

// SelectiveLumberjack keeps a holder's Lumberjack children out of the CMS site tree (#62, #105).
// The fixture holder (tests/browser/fixtures/AtBHolder.php) has two published children, one hidden
// through the holder's hide_from_cms_tree, one through its own show_in_sitetree: false.
//
// #105: the tree was only filtered while it was loaded from the Pages screen (admin/pages/treeview,
// served by CMSPagesController). Opening a page in the editor loads the tree again from
// admin/pages/edit/treeview, served by CMSPageEditController, and on Silverstripe 5 that tree
// listed the child hidden by show_in_sitetree (core's own hide_from_cms_tree handling still hid
// the other one). The edit screen is checked by opening the holder's edit URL directly, as an editor
// following a link would.

const HOLDER = 'Browser Holder';
const CHILDREN = ['Child hidden by hide_from_cms_tree', 'Child hidden by show_in_sitetree'];

/** The CMS site tree, once it has rendered the fixture holder. */
async function loadedTree(page: Page): Promise<Locator> {
    const tree = page.locator('.cms-tree');
    await expect(tree.locator('li', { hasText: HOLDER }).first()).toBeVisible();
    return tree;
}

/** The fixture holder's page ID, read off its site-tree node. */
async function holderId(page: Page): Promise<string> {
    await page.goto('/admin/pages');
    const tree = await loadedTree(page);
    // From the node's own edit link: an li[data-id] match by text would also hit the root node,
    // which contains every other node.
    const href = await tree.getByRole('link', { name: HOLDER, exact: true }).getAttribute('href');
    const id = /edit\/show\/(\d+)/.exec(href ?? '')?.[1];
    expect(id, `the holder node links to its edit screen (${href})`).toBeTruthy();
    return id!;
}

async function expectNoChildrenIn(tree: Locator): Promise<void> {
    for (const title of CHILDREN) {
        await expect(tree.getByText(title, { exact: true }), `"${title}" is not in the tree`).toHaveCount(0);
    }
}

test('the Pages screen tree leaves the holder children out', async ({ page }) => {
    await page.goto('/admin/pages');
    await expectNoChildrenIn(await loadedTree(page));
});

test("the holder's edit screen tree leaves its children out (#105)", async ({ page }) => {
    const id = await holderId(page);
    await page.goto(`/admin/pages/edit/show/${id}`);
    const tree = await loadedTree(page);
    // The editor did open the holder, so this is the edit screen's own tree.
    await expect(page.locator('#Form_EditForm')).toBeVisible();
    await expectNoChildrenIn(tree);
});

test("the edit controller's tree and subtree responses leave the children out (#105)", async ({ page }) => {
    const id = await holderId(page);
    // The two requests the edit screen's tree is built from, checked as returned by the server
    // (the tree only fetches a subtree once a node is expanded beyond the preloaded depth).
    for (const url of ['/admin/pages/edit/treeview', `/admin/pages/edit/getsubtree?ID=${id}`]) {
        const res = await page.request.get(url, { headers: { 'X-Requested-With': 'XMLHttpRequest' } });
        expect(res.ok(), `${url} answers`).toBeTruthy();
        const body = await res.text();
        for (const title of CHILDREN) {
            expect(body, `${url} does not list "${title}"`).not.toContain(title);
        }
    }
});

test("the children are still there: the holder's Lumberjack GridField lists both", async ({ page }) => {
    // Must-match control for the absence checks above: the children exist and are published, so
    // an empty tree cannot pass for a filtered one.
    const id = await holderId(page);
    await page.goto(`/admin/pages/edit/show/${id}`);
    await page.locator('#Form_EditForm').getByRole('tab', { name: 'Child Pages' }).click();
    const grid = page.locator('#Form_EditForm_ChildPages');
    for (const title of CHILDREN) {
        await expect(grid.locator('tr.ss-gridfield-item', { hasText: title })).toBeVisible();
    }
});
