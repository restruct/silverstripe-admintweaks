<?php

namespace Restruct\Silverstripe\AdminTweaks\Logging;

use Monolog\Handler\SymfonyMailerHandler;
use Monolog\LogRecord;
use Restruct\Silverstripe\AdminTweaks\Helpers\CacheHelpers;
use SilverStripe\Core\Config\Configurable;

/**
 * SymfonyMailerHandler with cache-based deduplication AND a global rate cap.
 *
 * Extends SymfonyMailerHandler and overrides send() with two independent guards
 * against error-email floods (inbox spam + mail-provider credit depletion):
 *
 *   1. Content deduplication — the SAME error (same level + first 200 chars of the
 *      message) is emailed at most once per `dedup_time` window.
 *
 *   2. Global rate cap — at most `max_emails_per_window` error emails are sent in any
 *      `rate_window`, REGARDLESS of content. This is the guard the content dedup cannot
 *      provide: a hostile client that triggers errors whose MESSAGE varies per request
 *      (e.g. errors that embed the request URI/host, or a scanner walking many paths)
 *      produces a different dedup key every time and would otherwise send one email per
 *      request. The cap bounds the total no matter how the messages vary. When the cap is
 *      hit, one final "further errors suppressed" notice is sent, then the rest are silent
 *      for the window. Suppressed errors are still written to the normal log (and Sentry,
 *      if configured) — only the EMAIL is capped.
 *
 * Advantages over Monolog's DeduplicationHandler (BufferHandler):
 * - No buffering — sends immediately, no end-of-request flush timing issues
 * - PSR-16 cache handles TTL expiry (no manual GC, no temp file)
 * - Works with APCu on production, filesystem on dev
 */
class DedupSymfonyMailerHandler extends SymfonyMailerHandler
{
    use Configurable;

    /** @config int seconds to suppress duplicate (same-content) errors */
    private static $dedup_time = 300;

    /**
     * @config int maximum error emails sent in any `rate_window`, across ALL content.
     * The hard ceiling on inbox/credit impact. 0 disables the cap (dedup still applies).
     */
    private static $max_emails_per_window = 20;

    /** @config int length in seconds of the global rate-cap window */
    private static $rate_window = 3600;

    /**
     * @param string $content Formatted email body
     * @param LogRecord[] $records Log records
     */
    protected function send(string $content, array $records): void
    {
        $record = reset($records);
        $dedupKey = 'errmail_' . md5($record->level->name . substr($record->message, 0, 200));

        try {
            $cache = CacheHelpers::load_cache();

            # --- Guard 1: content dedup — suppress an identical error within the window ---
            if ($cache->has($dedupKey)) {
                return;
            }

            # --- Guard 2: global rate cap — bound total emails per window, any content ---
            $max = (int) static::config()->get('max_emails_per_window');
            if ($max > 0) {
                $window = max(1, (int) static::config()->get('rate_window'));
                # Fixed window: one counter per wall-clock slot. Cheap and self-expiring.
                $capKey = 'errmail_cap_' . intdiv(time(), $window);
                $sent = (int) $cache->get($capKey);

                if ($sent >= $max) {
                    # Already at (or past) the cap for this window — stay silent.
                    return;
                }

                # Reserve this slot BEFORE sending (TTL = 2x window so the counter always
                # outlives its own window without a manual sweep).
                $cache->set($capKey, $sent + 1, $window * 2);

                if ($sent + 1 === $max) {
                    # This send is the last allowed one — replace its body with a single
                    # "rate limit reached" notice so the recipient knows further errors are
                    # occurring but being withheld. All still land in the log.
                    $content = $this->rateLimitNotice($max, $window) . "\n\n---\n\n" . $content;
                }
            }

            # Mark this content as seen (dedup) once it's cleared both guards.
            $cache->set($dedupKey, true, static::config()->get('dedup_time'));
        } catch (\Throwable $e) {
            # Cache failure must not silently swallow errors — fall through and send. The
            # flood risk of a broken cache is bounded by how often the cache is actually
            # down, which is rare; losing error visibility entirely is the worse failure.
        }

        parent::send($content, $records);
    }

    /**
     * Human-readable banner prepended to the final pre-cap email.
     */
    private function rateLimitNotice(int $max, int $window): string
    {
        $minutes = (int) round($window / 60);

        return sprintf(
            '[admintweaks] Error-email rate limit reached: %d emails in ~%d min. '
            . 'Further error emails are suppressed until the window resets. '
            . 'All errors are still being logged — check the application log / Sentry.',
            $max,
            $minutes
        );
    }
}
