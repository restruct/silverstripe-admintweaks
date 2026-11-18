<?php

namespace Restruct\AtBrowser;

use SilverStripe\ORM\DataObject;

/**
 * BROWSER-TEST FIXTURE ONLY - a has_many child of AtBParentRecord, edited in the parent's editable
 * GridField (editable-gridfield-save.spec.ts). Seeded by AtBParentRecord::requireDefaultRecords().
 * (See AtBRecord for why this never loads in a real install.)
 *
 * @property string $Title
 * @property bool $Active
 * @property bool $Featured
 * @property int $ParentID
 * @method AtBParentRecord Parent()
 */
class AtBChildRecord extends DataObject
{
    # Short table name: no namespaced default (MySQL caps table names at 64 characters).
    private static $table_name = 'AtBChildRecord';

    private static $singular_name = 'Browser Child Record';

    private static $db = [
        'Title' => 'Varchar(255)',
        'Active' => 'Boolean',
        'Featured' => 'Boolean',
    ];

    private static $has_one = [
        'Parent' => AtBParentRecord::class,
    ];

    private static $default_sort = '"Title" ASC';
}
