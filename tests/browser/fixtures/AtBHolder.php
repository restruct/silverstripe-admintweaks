<?php

namespace Restruct\AtBrowser;

use Restruct\Silverstripe\AdminTweaks\Extensions\SelectiveLumberjack;
use SilverStripe\CMS\Model\SiteTree;

/**
 * BROWSER-TEST FIXTURE ONLY - a holder page with admintweaks' SelectiveLumberjack applied
 * (admintweaks#62, #105). Its children are managed from the Lumberjack GridField and must stay out
 * of the CMS site tree, on the Pages screen as well as on the holder's own edit screen.
 * (See AtBRecord for why this never loads in a real install.)
 *
 * The two child types cover the two ways a child is hidden:
 * - AtBTreeHiddenPage only through this holder's hide_from_cms_tree, the list SelectiveLumberjack
 *   adds to Lumberjack's exclusions;
 * - AtBLumberjackedPage through its own show_in_sitetree: false, Lumberjack's standard mechanism.
 *
 * Needs silverstripe/lumberjack in the host (SS<n>_EXTRA_REQUIRE in targets.sh).
 */
class AtBHolder extends SiteTree
{
    # Short table name: no namespaced default (MySQL caps table names at 64 characters).
    private static $table_name = 'AtBHolder';

    private static $singular_name = 'Browser Holder';

    private static $extensions = [
        SelectiveLumberjack::class,
    ];

    private static $allowed_children = [
        AtBTreeHiddenPage::class,
        AtBLumberjackedPage::class,
    ];

    private static $hide_from_cms_tree = [
        AtBTreeHiddenPage::class,
    ];

    # What the spec looks for; also the seeded titles.
    public const HOLDER_TITLE = 'Browser Holder';
    public const TREE_HIDDEN_TITLE = 'Child hidden by hide_from_cms_tree';
    public const LUMBERJACKED_TITLE = 'Child hidden by show_in_sitetree';

    public function requireDefaultRecords()
    {
        parent::requireDefaultRecords();

        # requireDefaultRecords() runs once per class; seed from this class only.
        if (static::class !== self::class) {
            return;
        }

        # Wipe and re-seed (stage and live), so every run starts from the same pages.
        foreach ([self::class, AtBTreeHiddenPage::class, AtBLumberjackedPage::class] as $class) {
            foreach ($class::get() as $old) {
                $old->doUnpublish();
                $old->delete();
            }
        }

        $holder = static::create(['Title' => self::HOLDER_TITLE, 'URLSegment' => 'atb-holder']);
        $holder->write();
        $holder->publishRecursive();

        foreach ([
            AtBTreeHiddenPage::class => self::TREE_HIDDEN_TITLE,
            AtBLumberjackedPage::class => self::LUMBERJACKED_TITLE,
        ] as $class => $title) {
            $child = $class::create(['Title' => $title, 'ParentID' => $holder->ID]);
            $child->write();
            $child->publishRecursive();
        }
    }
}
