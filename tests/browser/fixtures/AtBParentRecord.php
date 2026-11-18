<?php

namespace Restruct\AtBrowser;

use SilverStripe\Forms\CheckboxField;
use SilverStripe\Forms\GridField\GridField;
use SilverStripe\Forms\GridField\GridFieldConfig_RecordEditor;
use SilverStripe\Forms\GridField\GridFieldDataColumns;
use SilverStripe\Forms\TextField;
use SilverStripe\ORM\DataObject;
use Symbiote\GridFieldExtensions\GridFieldEditableColumns;

/**
 * BROWSER-TEST FIXTURE ONLY - a record whose edit form holds an editable GridField of its has_many
 * children (AtBChildRecord), as a project uses GridFieldEditableColumns: the rows are saved with the
 * parent's own Save button. Listed by AtBParentAdmin (/admin/atb-parent), for
 * editable-gridfield-save.spec.ts.
 *
 * The children's checkboxes are what the spec saves unticked: Active a plain checkbox, Featured one
 * drawn as a toggle switch. An unticked checkbox submits nothing, and GridFieldEditableColumns
 * skips a row whose submitted fields did not change, so without admintweaks' zero input
 * (admintweaks.js, `.checkbox_zero_input`) the untick is lost.
 *
 * Needs symbiote/silverstripe-gridfieldextensions in the host (SS<major>_EXTRA_REQUIRE in targets.sh).
 * (See AtBRecord for why this never loads in a real install.)
 *
 * @property string $Title
 * @method \SilverStripe\ORM\HasManyList<AtBChildRecord> Children()
 */
class AtBParentRecord extends DataObject
{
    # Short table name: no namespaced default (MySQL caps table names at 64 characters).
    private static $table_name = 'AtBParentRecord';

    private static $singular_name = 'Browser Parent Record';

    private static $db = [
        'Title' => 'Varchar(255)',
    ];

    private static $has_many = [
        'Children' => AtBChildRecord::class,
    ];

    private static $summary_fields = [
        'Title' => 'Title',
    ];

    # The seeded parent and its children, ONE ticked box per row: Child one has Active ticked, Child
    # two Featured. gridfieldextensions 4 (Silverstripe 5) compares the submitted values with the
    # record's strictly, so a second ticked box in the row ('1' against the stored 1) counts as a
    # change and saves the whole row, which would hide a lost untick.
    public const TITLE = 'Parent with children';

    public const CHILDREN = [
        'Child one' => ['Active' => true, 'Featured' => false],
        'Child two' => ['Active' => false, 'Featured' => true],
    ];

    public function getCMSFields()
    {
        $fields = parent::getCMSFields();

        # The scaffolded has_many GridField (on its own tab) makes way for an editable one on the
        # main tab, so the spec needs no tab switch.
        $fields->removeByName('Children');

        $config = GridFieldConfig_RecordEditor::create();
        $config->removeComponentsByType(GridFieldDataColumns::class);
        $editable = GridFieldEditableColumns::create();
        $editable->setDisplayFields([
            'Title' => ['title' => 'Title', 'field' => TextField::class],
            'Active' => ['title' => 'Active', 'field' => CheckboxField::class],
            # The class goes on in a callback, as in AtBEditableAdmin (docs/form-fields.md)
            'Featured' => [
                'title' => 'Featured',
                'callback' => fn ($record, $col) => CheckboxField::create($col)->addExtraClass('toggle-switch'),
            ],
        ]);
        $config->addComponent($editable);

        $fields->addFieldToTab(
            'Root.Main',
            GridField::create('Children', 'Children', $this->Children(), $config)
        );

        return $fields;
    }

    public function requireDefaultRecords()
    {
        parent::requireDefaultRecords();

        # Wipe and re-seed, so every run starts from the same ticked boxes (a run that saved them
        # unticked must not leave that for the next).
        foreach (AtBChildRecord::get() as $old) {
            $old->delete();
        }
        foreach (static::get() as $old) {
            $old->delete();
        }
        $parent = static::create(['Title' => self::TITLE]);
        $parent->write();
        foreach (self::CHILDREN as $title => $boxes) {
            AtBChildRecord::create(['Title' => $title, 'ParentID' => $parent->ID] + $boxes)->write();
        }
    }
}
