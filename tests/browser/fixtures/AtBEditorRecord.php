<?php

namespace Restruct\AtBrowser;

use Restruct\Silverstripe\AdminTweaks\Forms\MinimalEditorConfig;
use SilverStripe\Forms\HTMLEditor\HTMLEditorField;
use SilverStripe\ORM\DataObject;

/**
 * BROWSER-TEST FIXTURE ONLY - a record with two HTML editors, listed in AtBEditorAdmin
 * (/admin/atb-editor), for minimal-editor.spec.ts (admintweaks#116):
 * - Intro uses the `minimal` editor config,
 * - Body uses the default (`cms`) config, the control the spec compares the toolbar against.
 *
 * Uses framework's TinyMCE (Silverstripe 4 and 5). On 6 TinyMCE is silverstripe/htmleditor-tinymce,
 * which the main line's targets.sh adds to its host.
 * (See AtBRecord for why this never loads in a real install.)
 *
 * @property string $Title
 * @property string $Intro
 * @property string $Body
 */
class AtBEditorRecord extends DataObject
{
    # Short table name: no namespaced default (MySQL caps table names at 64 characters).
    private static $table_name = 'AtBEditorRecord';

    private static $singular_name = 'Browser Editor Record';

    private static $db = [
        'Title' => 'Varchar(255)',
        'Intro' => 'HTMLText',
        'Body' => 'HTMLText',
    ];

    private static $summary_fields = [
        'Title' => 'Title',
    ];

    # The seeded record, and the word in Intro the spec selects and turns into a link.
    public const TITLE = 'Editor record';

    public const LINK_WORD = 'linkme';

    public function getCMSFields()
    {
        $fields = parent::getCMSFields();
        # The scaffolded HTMLEditorFields use the active (cms) config; Intro is switched to
        # `minimal` through the module's getter, as the docs tell a project to.
        $fields->replaceField(
            'Intro',
            HTMLEditorField::create('Intro', 'Intro')->setEditorConfig(MinimalEditorConfig::get())->setRows(4)
        );
        return $fields;
    }

    public function requireDefaultRecords()
    {
        parent::requireDefaultRecords();

        # Wipe and re-seed, so every run starts from the same content (a run that saved a link
        # into Intro must not leave it for the next).
        foreach (static::get() as $old) {
            $old->delete();
        }
        static::create([
            'Title' => self::TITLE,
            'Intro' => '<p>Short intro text, ' . self::LINK_WORD . ' here.</p>',
            'Body' => '<p>Body text with the full toolbar.</p>',
        ])->write();
    }
}
