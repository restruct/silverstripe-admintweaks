<?php

namespace Restruct\Silverstripe\AdminTweaks\Tests\Helpers;

use Exception;
use Restruct\Silverstripe\AdminTweaks\Helpers\CacheHelpers;
use Restruct\Silverstripe\AdminTweaks\Helpers\GeneralHelpers;
use SilverStripe\Dev\SapphireTest;

/**
 * Tests for the cached HTTP helper's failure handling (admintweaks#55).
 *
 * The bug: only a 200 was ever written to cache, and perform_http_request() THROWS on a
 * connection failure - so a dead upstream was re-requested on every single call, forever. The
 * cache stopped working exactly when it was needed most, and on a render path that pins one
 * PHP-FPM worker per request until the (then unbounded) timeout.
 *
 * These drive a real URL that cannot connect, and count the attempts, rather than asserting on
 * the cache internals - the behaviour that matters is "the network is not touched again".
 */
class CacheHelpersTest extends SapphireTest
{
    protected $usesDatabase = false;

    /**
     * A port nothing is listening on, on a reserved-for-documentation address that will not
     * resolve out to anything real. Connection is refused (or times out) fast and locally.
     */
    private const DEAD_UPSTREAM = 'http://127.0.0.1:9/admintweaks-test-dead-upstream';

    private function uniqueDeadUrl(): string
    {
        // A fresh URL per test: the cache key is derived from the URL, and these tests are about
        // what happens on the SECOND call for the same one.
        return self::DEAD_UPSTREAM . '?' . uniqid('', true);
    }

    private function callAndCountFailures(string $url, int $times, int $cacheDuration = 900): int
    {
        $failures = 0;
        for ($i = 0; $i < $times; $i++) {
            try {
                CacheHelpers::cached_http_request($url, 'GET', [], $cacheDuration);
            } catch (Exception $e) {
                $failures++;
            }
        }

        return $failures;
    }

    public function testADeadUpstreamStillThrowsToTheCaller()
    {
        // The contract must not change: callers still see the failure.
        $this->assertSame(1, $this->callAndCountFailures($this->uniqueDeadUrl(), 1));
    }

    public function testARepeatedCallToADeadUpstreamStillThrowsFromCache()
    {
        // Every call fails, as before - but the second one must fail from cache, not from the wire.
        // That is asserted separately below by timing; this pins the caller-visible contract.
        $this->assertSame(3, $this->callAndCountFailures($this->uniqueDeadUrl(), 3));
    }

    public function testAFailureIsWrittenToTheCache()
    {
        // The discriminator for #55. Before the fix the cache was only ever written on a 200, and
        // perform_http_request() throws before reaching that line on a connection failure - so
        // nothing was stored and the next call re-made the request.
        //
        // Asserting on the cache rather than on timing: a refused connection returns in
        // microseconds either way, so a timing assertion passes against the unfixed code too
        // (checked - it did).
        $url = $this->uniqueDeadUrl();
        $cacheKey = md5("cachedResponse_{$url}");

        $this->assertFalse(
            (bool) CacheHelpers::load_cache()->get($cacheKey),
            'Nothing may be cached for this URL before the first call'
        );

        $this->callAndCountFailures($url, 1);

        $cached = CacheHelpers::load_cache()->get($cacheKey);
        $this->assertIsArray($cached, 'The failure must be cached so the next call does not re-request');
        $this->assertArrayHasKey('failed', $cached, 'The cached entry must be marked as a failure');
    }

    public function testTheCachedFailureIsWhatTheSecondCallThrows()
    {
        // Proves the second call is served FROM the cache: the cached marker is replaced with a
        // recognisable sentinel, and the next call must throw that rather than a fresh
        // connection error.
        $url = $this->uniqueDeadUrl();
        $cacheKey = md5("cachedResponse_{$url}");

        $this->callAndCountFailures($url, 1);
        CacheHelpers::load_cache()->set($cacheKey, ['failed' => 'SENTINEL-FROM-CACHE'], 900);

        // fail() throws a PHPUnit exception, so it must NOT sit inside a catch (Exception) block
        // - the catch would swallow it and report something misleading instead. Record what
        // happened, then assert outside.
        $thrownMessage = null;
        $returned = null;
        try {
            $returned = CacheHelpers::cached_http_request($url);
        } catch (Exception $e) {
            $thrownMessage = $e->getMessage();
        }

        $this->assertNotNull(
            $thrownMessage,
            'A cached failure must still throw, not return the cache entry to the caller (got: '
                . var_export($returned, true) . ')'
        );
        $this->assertSame(
            'SENTINEL-FROM-CACHE',
            $thrownMessage,
            'The second call must come from the failure cache, not from a new request'
        );
    }

    public function testCachingCanStillBeTurnedOffEntirely()
    {
        // cacheDuration of 0 bypasses the cache completely, including the failure cache - the
        // pre-existing escape hatch must keep working.
        $url = $this->uniqueDeadUrl();
        $this->assertSame(2, $this->callAndCountFailures($url, 2, 0));
    }

    // ------------------------------------------------------- bounded requests

    public function testTheHttpHelperDeclaresBoundedTimeouts()
    {
        // Guzzle itself defaults both to 0 (unlimited): configureDefaults() sets neither. An
        // unbounded outbound call on a render path is an availability bug, so this module must
        // carry its own non-zero defaults.
        $this->assertGreaterThan(0, GeneralHelpers::config()->get('http_connect_timeout'));
        $this->assertGreaterThan(0, GeneralHelpers::config()->get('http_timeout'));
    }
}
