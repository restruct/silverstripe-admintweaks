<?php

namespace Restruct\Silverstripe\AdminTweaks\Logging;

use Monolog\Handler\SymfonyMailerHandler;
use Monolog\LogRecord;
use Restruct\Silverstripe\AdminTweaks\Helpers\CacheHelpers;
use SilverStripe\Core\Config\Configurable;

/**
 * SymfonyMailerHandler with cache-based deduplication.
 *
 * Extends SymfonyMailerHandler and overrides send() to check a PSR-16 cache
 * before sending. Duplicate errors (same level + first 200 chars of message)
 * are suppressed for a configurable time window.
 *
 * Advantages over Monolog's DeduplicationHandler (BufferHandler):
 * - No buffering — sends immediately, no end-of-request flush timing issues
 * - PSR-16 cache handles TTL expiry (no manual GC, no temp file)
 * - Works with APCu on production, filesystem on dev
 */
class DedupSymfonyMailerHandler extends SymfonyMailerHandler
{
    use Configurable;

    /** @config int seconds to suppress duplicate errors */
    private static $dedup_time = 300;

    /**
     * Check cache before sending — suppress duplicates within the dedup window.
     *
     * @param string $content Formatted email body
     * @param LogRecord[] $records Log records
     */
    protected function send(string $content, array $records): void
    {
        # Build dedup key from level + first 200 chars of message
        $record = reset($records);
        $dedupKey = 'errmail_' . md5($record->level->name . substr($record->message, 0, 200));

        try {
            $cache = CacheHelpers::load_cache();

            if ($cache->has($dedupKey)) {
                return; # suppress duplicate
            }

            # Mark as sent, then send
            $cache->set($dedupKey, true, static::config()->get('dedup_time'));
        } catch (\Throwable $e) {
            # Cache failure should not prevent error emails — send anyway
        }

        parent::send($content, $records);
    }
}
