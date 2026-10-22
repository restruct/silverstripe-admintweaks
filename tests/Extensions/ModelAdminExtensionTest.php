<?php

namespace Restruct\Silverstripe\AdminTweaks\Tests\Extensions;

use Restruct\Silverstripe\AdminTweaks\Tests\Stub\ImportOffAdmin;
use Restruct\Silverstripe\AdminTweaks\Tests\Stub\ImportOffRecord;
use Restruct\Silverstripe\AdminTweaks\Tests\Stub\TestUploadValidator;
use SilverStripe\Assets\Upload_Validator;
use SilverStripe\Admin\ModelAdmin;
use SilverStripe\Core\Config\Config;
use SilverStripe\Core\Injector\Injector;
use SilverStripe\Dev\FunctionalTest;

/**
 * hide_scaffolded_csv_buttons must switch the CSV import OFF, not only hide its button: ModelAdmin
 * keeps routing ImportForm and only checks $showImportForm, so before the fix a hand-built POST
 * still imported with the button gone.
 *
 * The tests post the import form the way the Import modal does (the CSV under _CsvFile, the
 * import action), against a stub admin with core's defaults (generic CsvBulkLoader per model),
 * and assert on the observable result: whether a record was created.
 *
 * Compatibility: must run on PHPUnit 9 (SS5) and 11 (SS6). No doc-comment metadata.
 */
class ModelAdminExtensionTest extends FunctionalTest
{
    protected $usesDatabase = true;

    protected static $extra_dataobjects = [
        ImportOffRecord::class,
    ];

    protected static $extra_controllers = [
        ImportOffAdmin::class,
    ];

    private ?string $csvFile = null;

    protected function setUp(): void
    {
        parent::setUp();
        $this->logInWithPermission('ADMIN');
        $this->autoFollowRedirection = false;
        # A test-written file is never is_uploaded_file(); SapphireTest restores the Injector
        Injector::inst()->load([Upload_Validator::class => ['class' => TestUploadValidator::class]]);
    }

    protected function tearDown(): void
    {
        if ($this->csvFile && file_exists($this->csvFile)) {
            unlink($this->csvFile);
        }
        unset($_FILES['_CsvFile']);
        parent::tearDown();
    }

    /**
     * POST a one-row CSV to the stub admin's import form, as the Import modal does.
     */
    private function postCsvImport(): void
    {
        $this->csvFile = tempnam(sys_get_temp_dir(), 'atcsv');
        file_put_contents($this->csvFile, "Title\nImported row\n");
        $file = [
            'name' => 'import.csv',
            'type' => 'text/csv',
            'tmp_name' => $this->csvFile,
            'error' => UPLOAD_ERR_OK,
            'size' => filesize($this->csvFile),
        ];
        # ModelAdmin::import() reads $_FILES directly; the request builder merges it into the POST
        $_FILES['_CsvFile'] = $file;

        $tab = str_replace('\\', '-', ImportOffRecord::class);
        $this->post("admin/admintweaks-import-off-test/{$tab}/ImportForm", [
            '_CsvFile' => $file,
            'action_import' => 'Import from CSV',
        ]);
    }

    public function testImportStillWorksWhenTheSettingIsOff()
    {
        # Control: without the setting core's import works, so the test below proves something
        Config::modify()->set(ModelAdmin::class, 'hide_scaffolded_csv_buttons', false);
        Config::modify()->set(ImportOffAdmin::class, 'hide_scaffolded_csv_buttons', false);

        $this->postCsvImport();

        $this->assertSame(
            1,
            ImportOffRecord::get()->filter('Title', 'Imported row')->count(),
            'With hide_scaffolded_csv_buttons off, the stub admin must import the CSV'
        );
    }

    public function testImportIsRefusedWhenTheSettingIsOn()
    {
        Config::modify()->set(ImportOffAdmin::class, 'hide_scaffolded_csv_buttons', true);

        $this->postCsvImport();

        $this->assertSame(
            0,
            ImportOffRecord::get()->count(),
            'With hide_scaffolded_csv_buttons on, a hand-built import POST must not create records'
        );
    }

    public function testTheSettingSwitchesShowImportFormOff()
    {
        Config::modify()->set(ImportOffAdmin::class, 'hide_scaffolded_csv_buttons', true);

        $admin = ImportOffAdmin::create();
        $admin->extend('onBeforeInit');

        $this->assertFalse($admin->showImportForm, 'The setting must switch showImportForm off');
    }
}
