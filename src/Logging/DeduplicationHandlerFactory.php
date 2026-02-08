<?php

namespace Restruct\Silverstripe\AdminTweaks\Logging;

use Monolog\Handler\DeduplicationHandler;
use Monolog\Handler\HandlerInterface;
use Monolog\Level;
use SilverStripe\Core\Environment;
use SilverStripe\Core\Injector\Factory;
use SilverStripe\Core\TempFolder;

/**
 * Factory for creating a properly configured DeduplicationHandler.
 *
 * This factory resolves the deduplication store path and time window
 * from environment variables or sensible defaults.
 *
 * Environment variables:
 * - APP_LOG_MAIL_DEDUP_TIME: Time in seconds to suppress duplicates (default: 300 = 5 min)
 *
 * The deduplication store file is created in the temp folder.
 */
class DeduplicationHandlerFactory implements Factory
{
    public function create($service, array $params = [])
    {
        // Get the wrapped handler (required)
        $wrappedHandler = $params['handler'] ?? null;
        if (!$wrappedHandler instanceof HandlerInterface) {
            throw new \InvalidArgumentException('DeduplicationHandlerFactory requires a "handler" parameter');
        }

        // Determine deduplication store path (in temp folder)
        $tempPath = TempFolder::getTempFolder(BASE_PATH);
        $dedupStore = $tempPath . '/error-dedup.log';

        // Get deduplication time window from env (default: 300 seconds = 5 minutes)
        $dedupTime = (int) Environment::getEnv('APP_LOG_MAIL_DEDUP_TIME') ?: 300;

        // Minimum level to deduplicate
        $level = Level::Error;

        return new DeduplicationHandler(
            $wrappedHandler,
            $dedupStore,
            $level,
            $dedupTime
        );
    }
}
