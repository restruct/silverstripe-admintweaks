<?php

namespace Restruct\Silverstripe\AdminTweaks\Helpers;

use Exception;
use GuzzleHttp\Client;
use GuzzleHttp\TransferStats;
use Psr\SimpleCache\CacheInterface;
use SilverStripe\Core\Injector\Injector;

class CacheHelpers
{
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
        $cachedResponse = self::load_cache()->get($cacheKey);

        if (!$cachedResponse) {
            $cachedResponse = GeneralHelpers::perform_http_request($reqUrl, $reqMethod, $options);
            // write to cache if OK
            if ($cachedResponse['statuscode'] === 200) {
                self::load_cache()->set($cacheKey, $cachedResponse, $cacheDuration);
            }
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
