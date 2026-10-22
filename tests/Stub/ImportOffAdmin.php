<?php

namespace Restruct\Silverstripe\AdminTweaks\Tests\Stub;

use SilverStripe\Admin\ModelAdmin;
use SilverStripe\Dev\TestOnly;

/**
 * Test-only ModelAdmin with core's defaults: model_importers unset (so core registers its generic
 * CsvBulkLoader) and $showImportForm left true. The tests switch hide_scaffolded_csv_buttons.
 */
class ImportOffAdmin extends ModelAdmin implements TestOnly
{
    private static $url_segment = 'admintweaks-import-off-test';

    private static $menu_title = 'Import off test';

    private static $managed_models = [
        ImportOffRecord::class,
    ];
}
