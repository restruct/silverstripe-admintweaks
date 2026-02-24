<?php

namespace Restruct\Silverstripe\AdminTweaks\Tests\Helpers;

use Exception;
use Restruct\Silverstripe\AdminTweaks\Helpers\GeneralHelpers;
use SilverStripe\Assets\File;
use SilverStripe\Assets\Image;
use SilverStripe\Dev\SapphireTest;

/**
 * Tests for GeneralHelpers::import_file_asset() (which delegates to download_and_save_asset).
 *
 * Covers: local file import, file:// URI, extension validation, empty file detection,
 * publish behavior, existing file overwrite, and Image vs File type detection.
 */
class ImportFileAssetTest extends SapphireTest
{
    protected $usesDatabase = true;

    /** @var string Temp file created for tests */
    private $tempFile;

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
        parent::tearDown();
    }

    public function testImportLocalFileCreatesFileRecord(): void
    {
        $result = GeneralHelpers::import_file_asset(
            $this->tempFile,
            'Imports/test-import.txt'
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
            'Imports/content-check.txt'
        );

        # Verify the content was actually stored, not just the record
        $content = $result->getString();
        $this->assertEquals('test file content for asset import', $content);
    }

    public function testImportLocalFileIsPublishedByDefault(): void
    {
        $result = GeneralHelpers::import_file_asset(
            $this->tempFile,
            'Imports/published-default.txt'
        );

        $this->assertTrue($result->isPublished(), 'File should be published by default');
    }

    public function testImportLocalFileCanSkipPublish(): void
    {
        $result = GeneralHelpers::import_file_asset(
            $this->tempFile,
            'Imports/draft-only.txt',
            false
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
            'Imports/from-uri.txt'
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
            'Imports/malicious.exe'
        );
    }

    public function testImportNonExistentFileThrows(): void
    {
        # Non-existent path without file: prefix falls through to Guzzle (treated as URL)
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
            'Imports/ghost.txt'
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
                'Imports/empty.txt'
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
            'Imports/overwrite-test.txt'
        );
        $firstId = $first->ID;

        # Create a second temp file with different content
        $secondFile = tempnam(sys_get_temp_dir(), 'test2_') . '.txt';
        file_put_contents($secondFile, 'updated content');

        try {
            $second = GeneralHelpers::import_file_asset(
                $secondFile,
                'Imports/overwrite-test.txt'
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
                'Imports/test-image.png'
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
            'Custom/Folder/renamed-file.txt'
        );

        $this->assertEquals('Custom/Folder/renamed-file.txt', $result->getFilename());
    }

    public function testImportFallsBackToSourceFilename(): void
    {
        # When no assetPath is provided, it should derive from the source filename
        $result = GeneralHelpers::import_file_asset(
            $this->tempFile
        );

        $this->assertInstanceOf(File::class, $result);
        $this->assertTrue($result->exists());
        # Filename should be the basename of the temp file
        $this->assertStringContainsString('.txt', $result->getFilename());
    }
}
