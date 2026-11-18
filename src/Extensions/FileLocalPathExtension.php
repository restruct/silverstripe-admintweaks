<?php

namespace Restruct\Silverstripe\AdminTweaks\Extensions;

use League\Flysystem\Adapter\Local; # Flysystem 1 (Silverstripe 4); absent on SS5, only used in instanceof
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
 * + LocalFilesystemAdapter::prefixPath(), or Flysystem 1's Local::applyPathPrefix() on
 * Silverstripe 4) to find files on disk. This handles all storage
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
//        if (!$adapter instanceof LocalFilesystemAdapter) {
//            return null;
//        }
//
//        $path = $adapter->prefixPath($resolved->getFileID());
        if ($adapter instanceof LocalFilesystemAdapter) {
            # Silverstripe 5: silverstripe/assets 2.x on Flysystem 3
            $path = $adapter->prefixPath($resolved->getFileID());
        } elseif ($adapter instanceof Local) {
            # Silverstripe 4: silverstripe/assets 1.x has no LocalFilesystemAdapter; its Public/
            # ProtectedAssetAdapter extend Flysystem 1's Local adapter, which builds the path with
            # applyPathPrefix(). Without this branch every SS4 file resolved to null (admintweaks#141).
            $path = $adapter->applyPathPrefix($resolved->getFileID());
        } else {
            # Not on local disk (S3 or another remote adapter): there is no local path
            return null;
        }

        return file_exists($path) ? $path : null;
    }
}
