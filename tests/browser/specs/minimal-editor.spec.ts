import type { Page } from '@playwright/test';
import { test, expect, openAdmin, openRecordFromList } from './support';

// The `minimal` HTML editor config (#116): a copy of `cms` made on first request, with one
// toolbar line. The fixture (fixtures/AtBEditorRecord.php, /admin/atb-editor) has Intro on
// `minimal` and Body on the default `cms` config, the control.
//
// What the unit test cannot show: that TinyMCE in the browser renders the copy as one line with
// exactly the configured buttons, and that the copied sslink plugins work end to end - the link
// menu opens, its dialog opens, and inserting a link changes the content.

const INTRO = 'Form_ItemEditForm_Intro';

// The word the fixture's Intro contains for the link test (AtBEditorRecord::LINK_WORD).
const LINK_WORD = 'linkme';
const LINK_URL = 'https://example.com/atb-116';

// The toolbar of the issue (bold italic | bullist numlist | sslink unlink | removeformat), as
// TinyMCE labels the buttons it renders. `unlink` renders nothing: in TinyMCE 6 it belongs to the
// core `link` plugin, which the `cms` config (and so its copy) does not enable - core's own cms
// toolbar lists unlink too and drops it the same way. The link label carries the platform's
// shortcut ("Insert link [Ctrl+K]" / "[⌘+K]"), hence the pattern.
const MINIMAL_BUTTONS = [/^Bold$/, /^Italic$/, /^Bullet list$/, /^Numbered list$/, /^Insert link/, /^Clear formatting$/];

/** Open the fixture record and wait until both editors have rendered their toolbars. */
async function openEditors(page: Page) {
    const grid = await openAdmin(page, 'editor');
    const form = await openRecordFromList(page, grid, 'Editor record');
    const intro = form.locator(`#${INTRO}_Holder .tox-toolbar-overlord`);
    const body = form.locator('#Form_ItemEditForm_Body_Holder .tox-toolbar-overlord');
    await expect(intro).toBeVisible();
    await expect(body).toBeVisible();
    return { intro, body };
}

test('a field on the minimal config shows one toolbar line with the configured buttons', async ({ page }) => {
    const { intro, body } = await openEditors(page);

    // One row per toolbar line: a single line renders one toolbar__primary, several lines one
    // toolbar each. The cms control proves the count can be more than one.
    await expect(intro.locator(':scope > div')).toHaveCount(1);
    expect(await body.locator(':scope > div').count(), 'control: the cms editor has more than one line').toBeGreaterThan(1);

    const labels = await intro.locator('button').evaluateAll((els) => els.map((e) => e.getAttribute('aria-label') ?? ''));
    expect(labels).toHaveLength(MINIMAL_BUTTONS.length);
    MINIMAL_BUTTONS.forEach((pattern, i) => expect(labels[i], `button ${i + 1}`).toMatch(pattern));
});

test('the link button of the minimal editor opens the link menu and its dialog inserts a link', async ({ page }) => {
    const { intro } = await openEditors(page);

    // Select the fixture's link word through TinyMCE's own API (a click in the iframe would select
    // whatever word sits under the pointer).
    await page.evaluate(
        ([id, word]) => {
            const editor = (window as any).tinymce.get(id);
            editor.focus();
            const text = editor.getBody().querySelector('p').firstChild;
            const start = text.data.indexOf(word);
            const range = editor.dom.createRng();
            range.setStart(text, start);
            range.setEnd(text, start + word.length);
            editor.selection.setRng(range);
        },
        [INTRO, LINK_WORD],
    );

    await intro.getByRole('button', { name: /^Insert link/ }).click();

    // The sslink menu: its items come from the sslink* plugins `cms` registered (sslinkinternal is
    // silverstripe/cms's), so they show the copy kept them.
    const menu = page.locator('.tox-menu');
    await expect(menu).toBeVisible();
    for (const item of ['Page on this site', 'Link to external URL', 'Link to email address']) {
        await expect(menu.getByRole('menuitem', { name: item })).toBeVisible();
    }

    await menu.getByRole('menuitem', { name: 'Link to external URL' }).click();
    const dialog = page.locator('.modal.show');
    await expect(dialog).toContainText('Insert external link');
    await dialog.locator('input[name="Link"]').fill(LINK_URL);
    await dialog.locator('button[name="action_insert"]').click();
    await expect(dialog).toBeHidden();

    const html = await page.evaluate((id) => (window as any).tinymce.get(id).getContent(), INTRO);
    expect(html).toMatch(new RegExp(`<a [^>]*href="${LINK_URL}"[^>]*>${LINK_WORD}</a>`));
});
