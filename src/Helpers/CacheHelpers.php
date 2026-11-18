<?php

namespace Restruct\Silverstripe\AdminTweaks\Helpers;

use Exception;
use GuzzleHttp\Client;
use GuzzleHttp\TransferStats;
use Psr\SimpleCache\CacheInterface;
use SilverStripe\Core\Config\Configurable;
use SilverStripe\Core\Injector\Injector;

class CacheHelpers
{
    use Configurable;

    /**
     * @config
     * Seconds to remember that an upstream FAILED - the "dead upstream penalty window".
     *
     * Only successes used to be cached, so a dead or slow upstream was re-requested on every
     * single call, forever: the cache stopped working exactly when it was needed most
     * (admintweaks#55). Failures are now cached too, on their own shorter TTL, so a repeat call
     * inside the window fails immediately instead of making another network request.
     *
     * Shorter than the success TTL on purpose: this is a circuit breaker, and it must let a
     * recovered upstream back in reasonably quickly.
     */
    private static int $failure_cache_duration = 60;

    const HTTP_REQUEST_EXCEPTION = 1;
    const INVALID_JSON_EXCEPTION = 2;
    const NO_JSONLD_FOUND_EXCEPTION = 3;

    /**
     * @deprecated use load_cache for clarity (difference between loading cache object and getting/setting a value)
     */
    public static function get_cache($cacheNameSpace = 'adminCache')
    {
        return self::load_cache($cacheNameSpace);
    }

    /**
     * 'Load' and return a cache instance
     *
     * @param $cacheNameSpace
     * @return CacheInterface
     * @throws \Psr\Container\NotFoundExceptionInterface
     */
    public static function load_cache($cacheNameSpace = 'adminCache')
    {
        return Injector::inst()->get(CacheInterface::class . '.' . $cacheNameSpace);
    }

    /**
     * 'Load' and return 'appcache' cache instance
     *
     * @return CacheInterface
     * @throws \Psr\Container\NotFoundExceptionInterface
     */
    public static function load_appcache()
    {
        return Injector::inst()->get(CacheInterface::class . '.appcache');
    }

//    public static function curl_get($requestUrl, $timeout=10)
//    {
//        $ch = curl_init();
////        $headers["Content-Length"] = strlen($postString);
//        $headers["User-Agent"] = "Curl/1.0";
//
//        curl_setopt($ch, CURLOPT_URL, $requestUrl);
//        curl_setopt($ch, CURLOPT_HEADER, false);
////        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
//        curl_setopt($ch, CURLOPT_USERAGENT, "Curl/1.0");
//        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
////		curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
////		curl_setopt($ch, CURLOPT_USERPWD, 'admin:');
//        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 10); // connect timeout unlimited
//        curl_setopt($ch, CURLOPT_TIMEOUT, $timeout); //timeout in seconds (for whole execution)
//        $response = curl_exec($ch);
//        curl_close($ch);
//
//        return $response;
//    }

    /**
     * Perform a cached HTTP request via Guzzle and return the response body
     *
     * @param $reqUrl
     * @param string $reqMethod
     * @param array $options
     * @param int $cacheDuration
     * @return array [ 'body'=>..., 'statuscode'=>..., 'requesturl'=>... 'effectiveurl'=>..., ]
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    public static function cached_http_request($reqUrl, $reqMethod = 'GET', array $options = [], $cacheDuration=900)
    {
        // Caching is optional...
        if(!$cacheDuration) {
            return GeneralHelpers::perform_http_request($reqUrl, $reqMethod, $options);
        }

        $cacheKey = md5("cachedResponse_{$reqUrl}");
        $cache = self::load_cache();
        $cachedResponse = $cache->get($cacheKey);

        if ($cachedResponse) {
            // A cached FAILURE is re-thrown without touching the network: the caller sees exactly
            // what it saw the first time, but the dead upstream is not contacted again until the
            // penalty window expires.
            if (isset($cachedResponse['failed'])) {
                throw new Exception($cachedResponse['failed'], self::HTTP_REQUEST_EXCEPTION);
            }

            return $cachedResponse;
        }

        $failureDuration = static::config()->get('failure_cache_duration');

        try {
            $cachedResponse = GeneralHelpers::perform_http_request($reqUrl, $reqMethod, $options);
        } catch (Exception $exception) {
            // perform_http_request() THROWS on a GuzzleException (connection refused, timeout),
            // which is the common real-world failure - so the old success-only cache write below
            // was never even reached for it, and every call re-made the request.
            $cache->set($cacheKey, ['failed' => $exception->getMessage()], $failureDuration);

            throw $exception;
        }

        // write to cache if OK
        if ($cachedResponse['statuscode'] === 200) {
            self::load_cache()->set($cacheKey, $cachedResponse, $cacheDuration);
        } else {
            // A non-200 is cached too, but only for the penalty window: the return value is
            // unchanged (callers still get the response array), while a 500ing upstream stops
            // being hammered on every render.
            $cache->set($cacheKey, $cachedResponse, $failureDuration);
        }

        return $cachedResponse;
    }

    /**
     * Load JSON from a (cached) URL and return if valid
     *
     * @param $reqUrl
     * @param string $reqMethod
     * @param array $options
     * @param int $cacheDuration
     * @return mixed parsed JSON (associative)
     * @throws Exception
     */
    public static function cached_json_request($reqUrl, $reqMethod = 'GET', array $options = [], $cacheDuration=900)
    {
        $jsonData = self::cached_http_request($reqUrl, $reqMethod, $options, $cacheDuration);

        // invalidate cache if invalid JSON
        $jsonDataParsed = json_decode($jsonData['body'], true);
        if(json_last_error() !== JSON_ERROR_NONE) {
            self::load_cache()->delete(md5($reqUrl));

            throw new Exception("EXCEPTION (INVALID JSON RESPONSE) {$reqUrl}", self::INVALID_JSON_EXCEPTION);
        }

        return $jsonDataParsed;
    }

    /**
     * Load and extract+parse json+ld fragments from a (cached) URL
     *
     * @param $reqUrl
     * @param string $reqMethod
     * @param array $options
     * @param int $cacheDuration
     *
     * @return array containing parsed json (associative)
     */
    public static function cached_jsonLD_request($reqUrl, $reqMethod = 'GET', array $options = [], $cacheDuration=900)
    {
        $htmlData = self::cached_http_request($reqUrl, $reqMethod, $options, $cacheDuration);
        $jsonLDstart = '<script type="application/ld+json">';
        if(!strpos($htmlData['body'], $jsonLDstart)){
            $redirectInfo = $reqUrl!=$htmlData['effectiveurl'] ? " → {$htmlData['effectiveurl']}" : '';
            throw new Exception("EXCEPTION: NO JSON+LD found in RESPONSE ({$reqUrl}{$redirectInfo})", self::NO_JSONLD_FOUND_EXCEPTION);
        }
        $jsonLDparts = [];
        $jsonLDhtmlFragments = explode('<script type="application/ld+json">', $htmlData['body']);
        array_shift($jsonLDhtmlFragments); // remove part before json-ld
        foreach ($jsonLDhtmlFragments as $fragment) {
            $fragmentParts = explode('</script>', $fragment);
            $jsonLD = array_shift( $fragmentParts );

            $jsonDataParsed = json_decode($jsonLD, true);
            if(json_last_error() === JSON_ERROR_NONE) {
                $jsonLDparts[] = $jsonDataParsed;
            }
        }

        // invalidate cache if no valid json+ld found
        if(!count($jsonLDparts)){
            self::load_cache()->delete(md5($reqUrl));
            throw new Exception("EXCEPTION (NO JSON+LD found in RESPONSE) {$reqUrl}");
        }

        return $jsonLDparts;
    }
}
