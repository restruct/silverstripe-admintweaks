<?php

namespace Restruct\Silverstripe\AdminTweaks\Extensions;

use SilverStripe\Assets\File;
use SilverStripe\Core\Environment;
use SilverStripe\ORM\DataExtension;

/**
 * Adds getLocalPath() to File objects for resolving the actual filesystem path.
 *
 * Handles both public and protected stores, with and without hash-prefixed directories.
 * Works correctly with custom SS_PROTECTED_ASSETS_PATH (e.g. "../restricted_assets").
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
     * Checks candidate paths in order:
     * 1. Protected store with hash prefix
     * 2. Protected store without hash prefix (natural path)
     * 3. Public store with hash prefix
     * 4. Public store without hash prefix (legacy/natural path)
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

        $hashDir = $hash ? substr($hash, 0, 10) : '';
        $dir = dirname($filename);
        $basename = basename($filename);

        // Resolve protected assets root
        $protectedRoot = self::resolveProtectedAssetsPath();

        // Build candidate paths in order of likelihood
        $candidates = [];

        // 1. Protected store with hash prefix
        if ($hashDir) {
            $candidates[] = $protectedRoot . '/' . $dir . '/' . $hashDir . '/' . $basename;
        }

        // 2. Protected store without hash prefix (natural path)
        $candidates[] = $protectedRoot . '/' . $filename;

        // 3. Public store with hash prefix
        if ($hashDir) {
            $candidates[] = ASSETS_PATH . '/' . $dir . '/' . $hashDir . '/' . $basename;
        }

        // 4. Public store without hash prefix (legacy/natural path)
        $candidates[] = ASSETS_PATH . '/' . $filename;

        foreach ($candidates as $path) {
            if (file_exists($path)) {
                return $path;
            }
        }

        return null;
    }

    /**
     * Resolve the absolute path to the protected assets root directory.
     *
     * Handles SS_PROTECTED_ASSETS_PATH being relative (e.g. "../restricted_assets")
     * or absolute. Falls back to ASSETS_PATH/.protected if not configured.
     */
    private static function resolveProtectedAssetsPath(): string
    {
        $protectedRoot = Environment::getEnv('SS_PROTECTED_ASSETS_PATH');

        if ($protectedRoot) {
            // Resolve relative paths against BASE_PATH
            if (!str_starts_with($protectedRoot, '/')) {
                $resolved = realpath(BASE_PATH . '/' . $protectedRoot);
                return $resolved ?: (BASE_PATH . '/' . $protectedRoot);
            }
            return $protectedRoot;
        }

        // Default: .protected inside assets
        return ASSETS_PATH . '/.protected';
    }
}