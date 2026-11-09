<?php

namespace Restruct\AtBrowser;

use SilverStripe\CMS\Model\SiteTree;

/**
 * BROWSER-TEST FIXTURE ONLY - a page type that picks its own CMS icon through core's icon class
 * config, as a Bootstrap Icons class (admintweaks#108). page-icons.spec.ts checks the glyph shows in
 * the site tree and in the "Add page" type picker, with no class-name SVG drawn behind it.
 * (See AtBRecord for why this never loads in a real install.)
 *
 * The class name contains "Page" on purpose: admintweaks' [class*="Page"] rule would otherwise also
 * draw its default page SVG here, so this covers "an explicit icon class beats the name match".
 */
class AtBIconPage extends SiteTree
{
    # Short table name: no namespaced default (MySQL caps table names at 64 characters).
    private static $table_name = 'AtBIconPage';

    private static $singular_name = 'Browser Icon Page';

    # Silverstripe 6 name of the setting (5 called it icon_class)
    private static $cms_icon_class = 'bi-briefcase';

    # What the spec looks for; also the seeded title.
    public const TITLE = 'Browser Icon Page';

    public function requireDefaultRecords()
    {
        parent::requireDefaultRecords();

        # requireDefaultRecords() runs once per class; seed from this class only.
        if (static::class !== self::class) {
            return;
        }

        # Wipe and re-seed (stage and live), so every run starts from the same page.
        foreach (self::get() as $old) {
            $old->doUnpublish();
            $old->delete();
        }

        $page = static::create(['Title' => self::TITLE, 'URLSegment' => 'atb-icon-page']);
        $page->write();
        $page->publishRecursive();
    }
}
