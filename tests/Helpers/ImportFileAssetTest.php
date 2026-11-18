<?php

namespace Restruct\Silverstripe\AdminTweaks\Tests\Helpers;

use Exception;
use Restruct\Silverstripe\AdminTweaks\Helpers\GeneralHelpers;
use Restruct\Silverstripe\AdminTweaks\Helpers\LocalImportRefusedException;
use Restruct\Silverstripe\AdminTweaks\Helpers\OutboundUrlRejectedException;
use SilverStripe\Assets\File;
use SilverStripe\Assets\Image;
use SilverStripe\Dev\SapphireTest;

/**
 * Tests for GeneralHelpers::import_file_asset() (which delegates to download_and_save_asset).
 *
 * Covers: local file import, file:// URI, extension validation, empty file detection,
 * publish behavior, existing file overwrite, and Image vs File type detection.
 * Local sources are opt-in (admintweaks#151): refused by default, read only from inside the
 * $localBaseDir the caller names, with `..` and symlink escapes refused.
 */
class ImportFileAssetTest extends SapphireTest
{
    protected $usesDatabase = true;

    /** @var string Temp file created for tests */
    private $tempFile;

    /** @var string|null Scratch directory for the opt-in tests, removed in tearDown */
    private $scratchDir;

    protected function setUp(): void
    {
        parent::setUp();

        # Create a temp file with some content for local file tests
        $this->tempFile = tempnam(sys_get_temp_dir(), 'test_asset_');
        file_put_contents($this->tempFile, 'test file content for asset import');
        # Rename to .txt so it passes the allowed extensions check
        $txtFile = $this->tempFile . '.txt';
        rename($this->tempFile, $txtFile);
        $this->tempFile = $txtFile;
    }

    protected function tearDown(): void
    {
        if ($this->tempFile && file_exists($this->tempFile)) {
            @unlink($this->tempFile);
        }
        if ($this->scratchDir) {
            $this->removeTree($this->scratchDir);
        }
        parent::tearDown();
    }

    /**
     * A fresh directory layout for the opt-in tests:
     *   <scratch>/base/inside.txt         the file a caller is allowed to import
     *   <scratch>/outside.txt             a file next to, not in, the base directory
     *   <scratch>/base-other/sibling.txt  a directory sharing the base's name as a prefix
     *
     * @return string the scratch directory (not yet resolved, may sit behind a symlink on macOS)
     */
    private function makeScratch(): string
    {
        $this->scratchDir = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'at_import_' . uniqid();
        mkdir($this->scratchDir . '/base', 0700, true);
        mkdir($this->scratchDir . '/base-other', 0700, true);
        file_put_contents($this->scratchDir . '/base/inside.txt', 'inside content');
        file_put_contents($this->scratchDir . '/outside.txt', 'outside content');
        file_put_contents($this->scratchDir . '/base-other/sibling.txt', 'sibling content');

        return $this->scratchDir;
    }

    private function removeTree(string $dir): void
    {
        # lstat-based: a symlink is unlinked, never followed into its target
        foreach (scandir($dir) ?: [] as $entry) {
            if ($entry === '.' || $entry === '..') {
                continue;
            }
            $path = $dir . DIRECTORY_SEPARATOR . $entry;
            if (is_dir($path) && !is_link($path)) {
                $this->removeTree($path);
            } else {
                @unlink($path);
            }
        }
        @rmdir($dir);
    }

    public function testImportLocalFileCreatesFileRecord(): void
    {
        $result = GeneralHelpers::import_file_asset(
            $this->tempFile,
            'Imports/test-import.txt',
            true,
            \SilverStripe\Assets\Storage\AssetStore::CONFLICT_OVERWRITE,
            dirname($this->tempFile)
        );

        $this->assertInstanceOf(File::class, $result);
        $this->assertTrue($result->exists(), 'File record should exist in database');
        $this->assertNotEmpty($result->getHash(), 'File should have a content hash');
        $this->assertEquals('Imports/test-import.txt', $result->getFilename());
    }

    public function testImportLocalFileContentIsAccessible(): void
    {
        $result = GeneralHelpers::import_file_asset(
            $this->tempFile,
            'Imports/content-check.txt',
            true,
            \SilverStripe\Assets\Storage\AssetStore::CONFLICT_OVERWRITE,
            dirname($this->tempFile)
        );

        # Verify the content was actually stored, not just the record
        $content = $result->getString();
        $this->assertEquals('test file content for asset import', $content);
    }

    public function testImportLocalFileIsPublishedByDefault(): void
    {
        $result = GeneralHelpers::import_file_asset(
            $this->tempFile,
            'Imports/published-default.txt',
            true,
            \SilverStripe\Assets\Storage\AssetStore::CONFLICT_OVERWRITE,
            dirname($this->tempFile)
        );

        $this->assertTrue($result->isPublished(), 'File should be published by default');
    }

    public function testImportLocalFileCanSkipPublish(): void
    {
        $result = GeneralHelpers::import_file_asset(
            $this->tempFile,
            'Imports/draft-only.txt',
            false,
            \SilverStripe\Assets\Storage\AssetStore::CONFLICT_OVERWRITE,
            dirname($this->tempFile)
        );

        $this->assertTrue($result->exists(), 'File record should exist');
        # Note: with "no staging" file versioning (Versioned.versioned), write() alone
        # makes files published — so isPublished() may be true even with publish=false.
        # We only verify the record was created and has content.
        $this->assertNotEmpty($result->getHash(), 'File should have content stored');
    }

    public function testImportFileUriCreatesFileRecord(): void
    {
        $fileUri = 'file://' . $this->tempFile;

        $result = GeneralHelpers::import_file_asset(
            $fileUri,
            'Imports/from-uri.txt',
            true,
            \SilverStripe\Assets\Storage\AssetStore::CONFLICT_OVERWRITE,
            dirname($this->tempFile)
        );

        $this->assertInstanceOf(File::class, $result);
        $this->assertTrue($result->exists());
        $this->assertEquals('test file content for asset import', $result->getString());
    }

    public function testImportDisallowedExtensionThrows(): void
    {
        $this->expectException(Exception::class);
        $this->expectExceptionMessage('FILE EXTENSION NOT ALLOWED');

        # .exe is not in allowed extensions
        GeneralHelpers::import_file_asset(
            $this->tempFile,
            'Imports/malicious.exe',
            true,
            \SilverStripe\Assets\Storage\AssetStore::CONFLICT_OVERWRITE,
            dirname($this->tempFile)
        );
    }

    public function testImportNonExistentFileThrows(): void
    {
        # Non-existent path without file: prefix falls through to Guzzle (treated as URL)
        # Since #151 it is refused as a local source instead, before Guzzle; still an Exception
        $this->expectException(Exception::class);

        GeneralHelpers::import_file_asset(
            '/tmp/this-file-does-not-exist-at-all.txt',
            'Imports/ghost.txt'
        );
    }

    public function testImportNonExistentFileUriThrows(): void
    {
        # file:// URI to non-existent path should throw our "missing or empty" error
        $this->expectException(Exception::class);
        $this->expectExceptionMessage('Source file is missing or empty');

        GeneralHelpers::import_file_asset(
            'file:///tmp/this-file-does-not-exist-at-all.txt',
            'Imports/ghost.txt',
            true,
            \SilverStripe\Assets\Storage\AssetStore::CONFLICT_OVERWRITE,
            '/tmp'
        );
    }

    public function testImportEmptyFileThrows(): void
    {
        # Create an empty file
        $emptyFile = tempnam(sys_get_temp_dir(), 'empty_') . '.txt';
        file_put_contents($emptyFile, '');

        try {
            $this->expectException(Exception::class);
            $this->expectExceptionMessage('Source file is missing or empty');

            GeneralHelpers::import_file_asset(
                $emptyFile,
                'Imports/empty.txt',
                true,
                \SilverStripe\Assets\Storage\AssetStore::CONFLICT_OVERWRITE,
                dirname($emptyFile)
            );
        } finally {
            @unlink($emptyFile);
        }
    }

    public function testImportExistingFileOverwritesContent(): void
    {
        # Import first version
        $first = GeneralHelpers::import_file_asset(
            $this->tempFile,
            'Imports/overwrite-test.txt',
            true,
            \SilverStripe\Assets\Storage\AssetStore::CONFLICT_OVERWRITE,
            dirname($this->tempFile)
        );
        $firstId = $first->ID;

        # Create a second temp file with different content
        $secondFile = tempnam(sys_get_temp_dir(), 'test2_') . '.txt';
        file_put_contents($secondFile, 'updated content');

        try {
            $second = GeneralHelpers::import_file_asset(
                $secondFile,
                'Imports/overwrite-test.txt',
                true,
                \SilverStripe\Assets\Storage\AssetStore::CONFLICT_OVERWRITE,
                dirname($secondFile)
            );

            # Should reuse the existing File record (same ID)
            $this->assertEquals($firstId, $second->ID, 'Should reuse existing File record');
            $this->assertEquals('updated content', $second->getString());
        } finally {
            @unlink($secondFile);
        }
    }

    public function testImportImageFileCreatesImageRecord(): void
    {
        # Create a minimal 1x1 PNG
        $pngFile = tempnam(sys_get_temp_dir(), 'img_') . '.png';
        $img = imagecreatetruecolor(1, 1);
        imagepng($img, $pngFile);
        imagedestroy($img);

        try {
            $result = GeneralHelpers::import_file_asset(
                $pngFile,
                'Imports/test-image.png',
                true,
                \SilverStripe\Assets\Storage\AssetStore::CONFLICT_OVERWRITE,
                dirname($pngFile)
            );

            $this->assertInstanceOf(Image::class, $result, 'PNG should create an Image, not a File');
            $this->assertTrue($result->exists());
        } finally {
            @unlink($pngFile);
        }
    }

    public function testImportUsesFilenameFromAssetPathNotSource(): void
    {
        $result = GeneralHelpers::import_file_asset(
            $this->tempFile,
            'Custom/Folder/renamed-file.txt',
            true,
            \SilverStripe\Assets\Storage\AssetStore::CONFLICT_OVERWRITE,
            dirname($this->tempFile)
        );

        $this->assertEquals('Custom/Folder/renamed-file.txt', $result->getFilename());
    }

    public function testImportFallsBackToSourceFilename(): void
    {
        # When no assetPath is provided, it should derive from the source filename
        $result = GeneralHelpers::import_file_asset(
            $this->tempFile,
            null,
            true,
            \SilverStripe\Assets\Storage\AssetStore::CONFLICT_OVERWRITE,
            dirname($this->tempFile)
        );

        $this->assertInstanceOf(File::class, $result);
        $this->assertTrue($result->exists());
        # Filename should be the basename of the temp file
        $this->assertStringContainsString('.txt', $result->getFilename());
    }

    public function testImportRejectsAPrivateDestination(): void
    {
        # A URL to an internal address is refused before any request (admintweaks#139)
        $this->expectException(\Restruct\Silverstripe\AdminTweaks\Helpers\OutboundUrlRejectedException::class);

        GeneralHelpers::import_file_asset('http://192.168.1.1/config.txt', 'Imports/config.txt');
    }

    public function testLocalPathIsRefusedByDefault(): void
    {
        $scratch = $this->makeScratch();
        $before = File::get()->count();

        try {
            GeneralHelpers::import_file_asset($scratch . '/base/inside.txt', 'Imports/refused.txt');
            $this->fail('A local path without $localBaseDir must be refused');
        } catch (LocalImportRefusedException $e) {
            $this->assertStringContainsString('refused', $e->getMessage());
        }

        # Refused before any record is created
        $this->assertSame($before, File::get()->count());
    }

    public function testFileUrlIsRefusedByDefault(): void
    {
        $scratch = $this->makeScratch();

        $this->expectException(LocalImportRefusedException::class);
        GeneralHelpers::import_file_asset('file://' . $scratch . '/base/inside.txt', 'Imports/refused.txt');
    }

    public function testEmptyBaseDirIsNoOptIn(): void
    {
        # A caller still passing the old five arguments ends up with `false` (cast to '') here
        $scratch = $this->makeScratch();

        $this->expectException(LocalImportRefusedException::class);
        GeneralHelpers::import_file_asset(
            $scratch . '/base/inside.txt',
            'Imports/refused.txt',
            true,
            \SilverStripe\Assets\Storage\AssetStore::CONFLICT_OVERWRITE,
            ''
        );
    }

    public function testLocalPathInsideBaseDirIsImported(): void
    {
        $scratch = $this->makeScratch();

        $result = GeneralHelpers::import_file_asset(
            $scratch . '/base/inside.txt',
            'Imports/allowed.txt',
            true,
            \SilverStripe\Assets\Storage\AssetStore::CONFLICT_OVERWRITE,
            $scratch . '/base'
        );

        $this->assertInstanceOf(File::class, $result);
        $this->assertSame('inside content', $result->getString());
    }

    public function testFileUrlInsideBaseDirIsImported(): void
    {
        $scratch = $this->makeScratch();

        $result = GeneralHelpers::import_file_asset(
            'file://' . $scratch . '/base/inside.txt',
            'Imports/allowed-uri.txt',
            true,
            \SilverStripe\Assets\Storage\AssetStore::CONFLICT_OVERWRITE,
            $scratch . '/base'
        );

        $this->assertSame('inside content', $result->getString());
    }

    public function testDotDotTraversalOutOfBaseDirIsRefused(): void
    {
        $scratch = $this->makeScratch();

        $this->expectException(LocalImportRefusedException::class);
        GeneralHelpers::import_file_asset(
            $scratch . '/base/../outside.txt',
            'Imports/escape.txt',
            true,
            \SilverStripe\Assets\Storage\AssetStore::CONFLICT_OVERWRITE,
            $scratch . '/base'
        );
    }

    public function testSymlinkOutOfBaseDirIsRefused(): void
    {
        $scratch = $this->makeScratch();
        # A link inside the base that points at a file outside it
        if (!@symlink($scratch . '/outside.txt', $scratch . '/base/link.txt')) {
            $this->markTestSkipped('symlink() is not available here');
        }

        $this->expectException(LocalImportRefusedException::class);
        GeneralHelpers::import_file_asset(
            $scratch . '/base/link.txt',
            'Imports/escape.txt',
            true,
            \SilverStripe\Assets\Storage\AssetStore::CONFLICT_OVERWRITE,
            $scratch . '/base'
        );
    }

    public function testSymlinkedDirectoryOutOfBaseDirIsRefused(): void
    {
        $scratch = $this->makeScratch();
        # A directory link inside the base that points outside it
        if (!@symlink($scratch . '/base-other', $scratch . '/base/linked-dir')) {
            $this->markTestSkipped('symlink() is not available here');
        }

        $this->expectException(LocalImportRefusedException::class);
        GeneralHelpers::import_file_asset(
            'file://' . $scratch . '/base/linked-dir/sibling.txt',
            'Imports/escape.txt',
            true,
            \SilverStripe\Assets\Storage\AssetStore::CONFLICT_OVERWRITE,
            $scratch . '/base'
        );
    }

    public function testSiblingDirectorySharingThePrefixIsRefused(): void
    {
        # /x/base must not admit /x/base-other
        $scratch = $this->makeScratch();

        $this->expectException(LocalImportRefusedException::class);
        GeneralHelpers::import_file_asset(
            $scratch . '/base-other/sibling.txt',
            'Imports/escape.txt',
            true,
            \SilverStripe\Assets\Storage\AssetStore::CONFLICT_OVERWRITE,
            $scratch . '/base'
        );
    }

    public function testMissingBaseDirIsRefused(): void
    {
        $scratch = $this->makeScratch();

        $this->expectException(LocalImportRefusedException::class);
        $this->expectExceptionMessage('base directory does not exist');
        GeneralHelpers::import_file_asset(
            $scratch . '/base/inside.txt',
            'Imports/refused.txt',
            true,
            \SilverStripe\Assets\Storage\AssetStore::CONFLICT_OVERWRITE,
            $scratch . '/no-such-dir'
        );
    }

    public function testMissingFileOutsideBaseDirIsRefusedNotReportedMissing(): void
    {
        # The error must not tell a caller whether a file exists outside the base
        $scratch = $this->makeScratch();

        $this->expectException(LocalImportRefusedException::class);
        GeneralHelpers::import_file_asset(
            $scratch . '/no-such-file.txt',
            'Imports/refused.txt',
            true,
            \SilverStripe\Assets\Storage\AssetStore::CONFLICT_OVERWRITE,
            $scratch . '/base'
        );
    }

    public function testMissingFileInsideBaseDirIsReportedMissing(): void
    {
        $scratch = $this->makeScratch();

        $this->expectException(Exception::class);
        $this->expectExceptionMessage('Source file is missing or empty');
        GeneralHelpers::import_file_asset(
            $scratch . '/base/no-such-file.txt',
            'Imports/ghost.txt',
            true,
            \SilverStripe\Assets\Storage\AssetStore::CONFLICT_OVERWRITE,
            $scratch . '/base'
        );
    }

    public function testRemoteUrlStillGoesThroughTheGuardWithTheOptIn(): void
    {
        # The local opt-in widens local reads only; a URL is still checked by OutboundUrlGuard
        $scratch = $this->makeScratch();

        $this->expectException(OutboundUrlRejectedException::class);
        GeneralHelpers::import_file_asset(
            'http://127.0.0.1/config.txt',
            'Imports/config.txt',
            true,
            \SilverStripe\Assets\Storage\AssetStore::CONFLICT_OVERWRITE,
            $scratch . '/base'
        );
    }

    public function testOtherSchemesGoToTheGuard(): void
    {
        # Not a local path: OutboundUrlGuard refuses any scheme but http(s)
        $this->expectException(OutboundUrlRejectedException::class);
        GeneralHelpers::import_file_asset('ftp://example.com/config.txt', 'Imports/config.txt');
    }
}
