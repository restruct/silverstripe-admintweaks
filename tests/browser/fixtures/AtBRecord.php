<?php

namespace Restruct\AtBrowser;

use Restruct\Silverstripe\AdminTweaks\FormFields\CopyTextField;
use SilverStripe\ORM\DataObject;

/**
 * BROWSER-TEST FIXTURE ONLY - the record both fixture ModelAdmins list, with a CopyTextField on its
 * edit form.
 *
 * Never loaded by a real install: it lives under tests/browser/, which carries a _manifest_exclude
 * marker, and the browser-test runner copies it into a scratch host's app/ before dev/build.
 * Written to load on both Silverstripe 5 and 6 (no class imports that moved between the two).
 *
 * @property string $Title
 */
class AtBRecord extends DataObject
{
    # Short table name: no namespaced default (MySQL caps table names at 64 characters).
    private static $table_name = 'AtBRecord';

    private static $singular_name = 'Browser Record';

    private static $db = [
        'Title' => 'Varchar(255)',
    ];

    private static $default_sort = '"Title" ASC';

    private static $summary_fields = [
        'Title' => 'Title',
    ];

    # The value the CopyTextField shows and the copy spec expects on the clipboard.
    public const COPY_VALUE = 'atb-copy-value-123';

    # Seeded on every dev/build; three rows so the GridField has a footer count to look at.
    private const SEEDS = ['Alpha record', 'Bravo record', 'Charlie record'];

    public function getCMSFields()
    {
        $fields = parent::getCMSFields();
        $fields->addFieldToTab(
            'Root.Main',
            CopyTextField::create('CopyMe', 'Copy me', self::COPY_VALUE)->setButtonLabel('Copy')
        );
        return $fields;
    }

    public function requireDefaultRecords()
    {
        parent::requireDefaultRecords();

        # Wipe and re-seed, so every run starts from the same rows.
        foreach (static::get() as $old) {
            $old->delete();
        }
        foreach (self::SEEDS as $title) {
            static::create(['Title' => $title])->write();
        }
    }
}
