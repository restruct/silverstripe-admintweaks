<?php

namespace Restruct\Silverstripe\AdminTweaks\Tests\Stub;

use SilverStripe\Dev\TestOnly;
use SilverStripe\ORM\DataObject;

/**
 * Test-only record managed by ImportOffAdmin, for the CSV import tests of ModelAdminExtension.
 */
class ImportOffRecord extends DataObject implements TestOnly
{
    private static $table_name = 'AdminTweaksTest_ImportOffRecord';

    private static $db = [
        'Title' => 'Varchar',
    ];
}
