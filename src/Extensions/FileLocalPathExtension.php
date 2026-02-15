<?php

namespace Restruct\Silverstripe\AdminTweaks\Extensions;

use Psr\Log\LoggerInterface;
use SilverStripe\Assets\File;
use SilverStripe\Core\Environment;
use SilverStripe\Core\Injector\Injector;
use SilverStripe\ORM\DataExtension;

/**
 * Adds getLocalPath() to File objects for resolving the actual filesystem path.
 *
 * Handles both public and protected stores, with and without hash-prefixed directories.
 * Detects and corrects relative SS_PROTECTED_ASSETS_PATH values (framework bug:
 * https://github.com/silverstripe/silverstripe-assets/issues/706).
 *
 * Primary use case: passing file paths to external CLI tools (cpdf, pdftotext, wkhtmltopdf, etc.)
 * For reading file content only, prefer $file->getString() instead.
 *
 * @extends DataExtension<File>
 */
class FileLocalPathExtension extends DataExtension
{
    private static bool $has_warned_relative_path = false;

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
     *
     * When a relative path is detected, it is resolved against BASE_PATH and a warning
     * is logged with the correct absolute path. The framework's ProtectedAssetAdapter
     * has a bug where relative paths resolve against PHP's cwd instead of BASE_PATH
     * (see https://github.com/silverstripe/silverstripe-assets/issues/706).
     */
    private static function resolveProtectedAssetsPath(): string
    {
        $protectedRoot = Environment::getEnv('SS_PROTECTED_ASSETS_PATH');

        if ($protectedRoot) {
            // Resolve relative paths against BASE_PATH and warn
            if (!str_starts_with($protectedRoot, '/')) {
                $resolved = realpath(BASE_PATH . '/' . $protectedRoot);
                $absolutePath = $resolved ?: (BASE_PATH . '/' . $protectedRoot);

                if (!self::$has_warned_relative_path) {
                    self::$has_warned_relative_path = true;
                    try {
                        Injector::inst()->get(LoggerInterface::class)->warning(
                            "SS_PROTECTED_ASSETS_PATH is set to a relative path '{$protectedRoot}'. "
                            . 'Relative paths resolve inconsistently between web and CLI contexts '
                            . 'due to a framework bug (https://github.com/silverstripe/silverstripe-assets/issues/706). '
                            . "Use the absolute path instead: SS_PROTECTED_ASSETS_PATH=\"{$absolutePath}\""
                        );
                    } catch (\Throwable $e) {
                        // Injector not ready (early bootstrap) — skip warning
                    }
                }

                return $absolutePath;
            }

            return $protectedRoot;
        }

        // Default: .protected inside assets
        return ASSETS_PATH . '/.protected';
    }
}