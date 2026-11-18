<?php

namespace Restruct\Silverstripe\AdminTweaks\Tests\Extensions;

use SilverStripe\Assets\Dev\TestAssetStore;
use SilverStripe\Assets\File;
use SilverStripe\Dev\SapphireTest;

/**
 * getLocalPath() must find a stored file on disk on every framework this line supports
 * (admintweaks#141): Silverstripe 4 (silverstripe/assets 1.x, Flysystem 1, adapters extending
 * League\Flysystem\Adapter\Local) as well as Silverstripe 5 (assets 2.x, Flysystem 3,
 * SilverStripe\Assets\Flysystem\LocalFilesystemAdapter).
 */
class FileLocalPathExtensionTest extends SapphireTest
{
    protected $usesDatabase = true;

    protected function setUp(): void
    {
        parent::setUp();
        # A throwaway asset store with the framework's own local adapters, in a temp folder
        TestAssetStore::activate('FileLocalPathExtensionTest');
    }

    protected function tearDown(): void
    {
        TestAssetStore::reset();
        parent::tearDown();
    }

    private function storedFile(): File
    {
        $file = File::create();
        $file->setFromString('local path probe', 'LocalPath/probe.txt');
        $file->write();

        return $file;
    }

    public function testAProtectedFileResolvesToItsContentOnDisk(): void
    {
        $file = $this->storedFile();

        $path = $file->getLocalPath();
        $this->assertNotNull($path, 'getLocalPath() must find a draft (protected) file');
        $this->assertSame('local path probe', file_get_contents($path));
    }

    public function testAPublishedFileResolvesToItsContentOnDisk(): void
    {
        $file = $this->storedFile();
        $file->publishSingle();

        $path = $file->getLocalPath();
        $this->assertNotNull($path, 'getLocalPath() must find a published (public) file');
        $this->assertSame('local path probe', file_get_contents($path));
    }

    public function testAFileWithoutContentHasNoPath(): void
    {
        $this->assertNull(File::create()->getLocalPath());
    }
}
