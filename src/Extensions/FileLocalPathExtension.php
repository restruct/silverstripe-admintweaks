<?php

namespace Restruct\Silverstripe\AdminTweaks\Extensions;

use League\Flysystem\Filesystem;
use SilverStripe\Assets\File;
use SilverStripe\Assets\FilenameParsing\FileResolutionStrategy;
use SilverStripe\Assets\FilenameParsing\ParsedFileID;
use SilverStripe\Assets\Flysystem\FlysystemAssetStore;
use SilverStripe\Assets\Flysystem\LocalFilesystemAdapter;
use SilverStripe\Assets\Storage\AssetStore;
use SilverStripe\Core\Injector\Injector;
use SilverStripe\ORM\DataExtension;

/**
 * Adds getLocalPath() to File objects for resolving the actual filesystem path.
 *
 * Uses the framework's own FlysystemAssetStore resolution chain (FileResolutionStrategy
 * + LocalFilesystemAdapter::prefixPath()) to find files on disk. This handles all storage
 * layouts: hash paths, natural paths, and any future FileIDHelper implementations.
 *
 * Primary use case: passing file paths to external CLI tools (cpdf, pdftotext, wkhtmltopdf, etc.)
 * For reading file content only, prefer $file->getString() instead.
 *
 * @extends DataExtension<File>
 */
class FileLocalPathExtension extends DataExtension
{
    /**
     * Get the absolute local filesystem path for this file.
     *
     * Delegates to the framework's resolution strategy which tries all configured
     * FileIDHelpers (Hash, Natural) and performs DB lookups when hashes are missing.
     *
     * Checks protected filesystem first (most common for getLocalPath use cases),
     * then falls back to public filesystem.
     *
     * @return string|null Absolute filesystem path, or null if file not found on disk
     */
    public function getLocalPath(): ?string
    {
        $file = $this->owner;
        if (!$file->exists()) {
            return null;
        }

        $filename = $file->getFilename();
        $hash = $file->getHash();
        if (!$filename) {
            return null;
        }

        $store = Injector::inst()->get(AssetStore::class);
        if (!$store instanceof FlysystemAssetStore) {
            return null;
        }

        $parsedFileID = new ParsedFileID($filename, $hash ?: '');

        // Try protected filesystem first (most common for getLocalPath use cases)
        $result = $this->resolveOnFilesystem(
            $parsedFileID,
            $store->getProtectedFilesystem(),
            $store->getProtectedResolutionStrategy()
        );
        if ($result) {
            return $result;
        }

        // Fall back to public filesystem
        return $this->resolveOnFilesystem(
            $parsedFileID,
            $store->getPublicFilesystem(),
            $store->getPublicResolutionStrategy()
        );
    }

    /**
     * Resolve a ParsedFileID to an absolute filesystem path on the given filesystem.
     */
    private function resolveOnFilesystem(
        ParsedFileID $parsedFileID,
        Filesystem $filesystem,
        FileResolutionStrategy $strategy
    ): ?string {
        $resolved = $strategy->searchForTuple($parsedFileID, $filesystem);
        if (!$resolved) {
            return null;
        }

        $adapter = $filesystem->getAdapter();
        if (!$adapter instanceof LocalFilesystemAdapter) {
            return null;
        }

        $path = $adapter->prefixPath($resolved->getFileID());

        return file_exists($path) ? $path : null;
    }
}
