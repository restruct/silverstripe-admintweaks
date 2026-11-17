<?php

namespace Restruct\AtBrowser;

use SilverStripe\ORM\DataObject;

/**
 * BROWSER-TEST FIXTURE ONLY - the record AtBEditableAdmin (/admin/atb-editable) lists in a GridField
 * with GridFieldEditableColumns, for editable-gridfield.spec.ts (admintweaks#114): a text input,
 * a select, a checkbox, a checkbox set and a read-only two-line column per row.
 *
 * Needs symbiote/silverstripe-gridfieldextensions in the host (SS6_EXTRA_REQUIRE in targets.sh).
 * (See AtBRecord for why this never loads in a real install.)
 *
 * @property string $Title
 * @property string $Category
 * @property bool $Active
 * @property string $Tags
 * @property bool $Locked
 */
class AtBEditableRecord extends DataObject
{
    # Short table name: no namespaced default (MySQL caps table names at 64 characters).
    private static $table_name = 'AtBEditableRecord';

    private static $singular_name = 'Browser Editable Record';

    private static $db = [
        'Title' => 'Varchar(255)',
        'Category' => 'Varchar(50)',
        'Active' => 'Boolean',
        'Tags' => 'Varchar(255)',
        # A locked record cannot be edited: its row shows read-only values and a view link only.
        'Locked' => 'Boolean',
    ];

    private static $default_sort = '"Title" ASC';

    # The select's and the checkbox set's options.
    public const CATEGORIES = ['one' => 'Category one', 'two' => 'Category two', 'three' => 'Category three'];

    public const TAGS = ['red' => 'Red', 'green' => 'Green', 'blue' => 'Blue'];

    # Seeded rows: two editable ones and one locked.
    public const EDITABLE_TITLES = ['Alpha editable', 'Bravo editable'];

    public const LOCKED_TITLE = 'Charlie locked';

    /**
     * The read-only two-line column. Plain text on purpose (escaped by the column's cast), the
     * line break comes from the column's formatting in AtBEditableAdmin.
     */
    public function getSummary(): string
    {
        return 'First line of ' . $this->Title;
    }

    public function canEdit($member = null)
    {
        return !$this->Locked && parent::canEdit($member);
    }

    public function requireDefaultRecords()
    {
        parent::requireDefaultRecords();

        # Wipe and re-seed, so every run starts from the same rows (a run that ticked a box must
        # not leave it for the next).
        foreach (static::get() as $old) {
            $old->delete();
        }
        foreach (self::EDITABLE_TITLES as $title) {
            static::create(['Title' => $title, 'Category' => 'two', 'Active' => false, 'Tags' => '["red"]'])->write();
        }
        static::create(['Title' => self::LOCKED_TITLE, 'Category' => 'one', 'Locked' => true])->write();
    }
}
