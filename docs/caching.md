# Caching and general helpers

Static helpers for cached HTTP requests, CMS fields and file imports, the cache namespaces the
module sets up, and the image method fallbacks.

## Cache namespaces

`_config/caches.yml` sets up three PSR-16 caches:

| Namespace | Lifetime | Used for |
|-----------|----------|----------|
| `cacheblock` | 24 hours (core: 10 minutes) | Template `<% cached %>` blocks |
| `appcache` | 1 hour | Free for project code |
| `adminCache` | 1 hour | `CacheHelpers` requests, error-mail deduplication |

```php
use Restruct\Silverstripe\AdminTweaks\Helpers\CacheHelpers;

$cache = CacheHelpers::load_appcache();         // the appcache namespace
$cache = CacheHelpers::load_cache('adminCache'); // any namespace by name
$cache->set('my-key', $value, 600);
```

Your own namespace:

```yaml
SilverStripe\Core\Injector\Injector:
  Psr\SimpleCache\CacheInterface.myCache:
    factory: SilverStripe\Core\Cache\CacheFactory
    constructor:
      namespace: "myCache"
      defaultLifetime: 7200
```

## CacheHelpers: cached HTTP requests

All three methods take `($reqUrl, $reqMethod = 'GET', array $options = [], $cacheDuration = 900)`.
`$options` are Guzzle request options. Responses are cached in `adminCache` for `$cacheDuration`
seconds; `0` skips the cache.

### cached_http_request()

```php
$response = CacheHelpers::cached_http_request('https://api.example.com/data');
// ['body' => '...', 'statuscode' => 200, 'requesturl' => '...', 'effectiveurl' => '...']
```

### cached_json_request()

The decoded JSON as an associative array; throws an `Exception` with code
`CacheHelpers::INVALID_JSON_EXCEPTION` when the body is not valid JSON.

```php
$data = CacheHelpers::cached_json_request('https://api.example.com/data', 'GET', [], 3600);
```

### cached_jsonLD_request()

Every `<script type="application/ld+json">` block on a web page, decoded, as a list. Throws an
`Exception` (code `CacheHelpers::NO_JSONLD_FOUND_EXCEPTION`) when the page has none.

```php
try {
    $blocks = CacheHelpers::cached_jsonLD_request('https://example.com/product', 'GET', [], 3600);
    $product = $blocks[0];
} catch (Exception $e) {
    // no JSON-LD on the page, or the request failed
}
```

### Cache behaviour

- The cache key is the URL only: the method and options are not part of it, so a GET and a POST
  to the same URL share one cache entry.
- A request that fails (an exception) or answers with a status other than 200 is cached as well,
  for `failure_cache_duration` seconds (default 60): a repeat call in that window throws the same
  error, or returns the same response, without another request. A dead upstream is then not
  hammered on every page view.
- Requests have a connect timeout of 5 seconds and a total timeout of 10 seconds (below), unless
  `$options` sets its own.

```yaml
Restruct\Silverstripe\AdminTweaks\Helpers\CacheHelpers:
  failure_cache_duration: 120
```

### Outbound URL checks

`perform_http_request()`, and with it all the `cached_*_request()` methods, and the download in
`import_file_asset()` send their request through `OutboundUrlGuard`:

- only `http` and `https` URLs;
- `localhost`, `*.localhost` and any host that resolves to a loopback, private, carrier-grade NAT,
  link-local, multicast, documentation, benchmarking, reserved or unspecified address (IPv4 and
  IPv6) are refused with an `OutboundUrlRejectedException` before anything is sent;
- the connection is pinned to the address that was checked (cURL `CURLOPT_RESOLVE`), so a second
  DNS answer cannot swap in another one;
- redirects are followed one hop at a time, at most `max_redirects` (default 5), and every hop is
  checked the same way. An `allow_redirects` in `$options` is ignored.

Pinning needs Guzzle's cURL handler, which it uses whenever `ext-curl` is loaded.

A project that fetches from an internal host on purpose lists it, by name, address or CIDR range:

```yaml
Restruct\Silverstripe\AdminTweaks\Helpers\OutboundUrlGuard:
  allowed_hosts:
    - intranet.example.lan
    - 10.20.0.0/16
```

The list is empty by default. It widens the destinations only: other schemes stay refused.
`OutboundUrlGuard::validate($url)` and `OutboundUrlGuard::send($method, $url, $options)` can be used
directly for your own requests.

## GeneralHelpers

### perform_http_request()

The uncached request the methods above use: `perform_http_request($reqUrl, $reqMethod = 'GET',
array $options = [])` returns the same array as `cached_http_request()` and throws an `Exception`
when the request fails. Default timeouts:

```yaml
Restruct\Silverstripe\AdminTweaks\Helpers\GeneralHelpers:
  http_connect_timeout: 5   # seconds to connect
  http_timeout: 10          # seconds for the whole request
```

### add_tab_if_not_exists()

```php
use Restruct\Silverstripe\AdminTweaks\Helpers\GeneralHelpers;

GeneralHelpers::add_tab_if_not_exists($fields, 'Advanced', 'Advanced settings');
```

Adds a tab to a CMS FieldList unless it is there already. `Advanced` and `Root.Advanced` are the
same. A fourth argument names the tab to insert after, in the same tab set
(`GeneralHelpers::add_tab_if_not_exists($fields, 'Advanced', 'Advanced settings', 'Main')`); without
it, or when that tab does not exist, the new tab goes at the end.

### get_options_translations()

Turns a list of values into a value => translated label map, for a dropdown:

```php
$source = GeneralHelpers::get_options_translations(['draft', 'published'], MyClass::class . '.Status_');
// ['draft' => _t('MyClass.Status_draft', 'draft'), 'published' => ...]
```

### safelyGetProperty()

Follows a chain of plain PHP object properties and returns `null` when any step is missing:

```php
$value = GeneralHelpers::safelyGetProperty($apiResponse, 'data->items->first');
```

It uses `property_exists()`, so it does not see a DataObject's fields or relations.

### import_file_asset()

Downloads a file from a URL, or reads a local path (also with a `file:` prefix) when you opt in,
and stores it as a File, or an Image for image extensions:

```php
$file = GeneralHelpers::import_file_asset('https://example.com/document.pdf', 'Imports/document.pdf');
```

`import_file_asset(string $filePathOrUrl, ?string $assetPath = null, bool $publish = true,
string $conflictResolution = AssetStore::CONFLICT_OVERWRITE, ?string $localBaseDir = null)`:

- `$assetPath` is `folder/filename` in the asset store.
- The file is published, and an existing file with the same name is overwritten, unless you pass
  other values.
- Only extensions in `File::getAllowedExtensions()` are accepted.
- It throws an `Exception` for a disallowed extension, an empty download or a failed store.
- A URL download uses the default timeouts above and the [outbound URL checks](#outbound-url-checks).
- A string with a scheme other than `file:` is a URL; anything else is a local path.

#### Local files are opt-in

A local path or a `file:` URL is refused with a `LocalImportRefusedException` (an `Exception`)
unless `$localBaseDir` names the directory the file must be in. Pass the narrowest directory you
can, ideally one your own code just created, such as the directory an uploaded zip was extracted
to:

```php
use SilverStripe\Assets\Storage\AssetStore;

$file = GeneralHelpers::import_file_asset(
    $extractDir . '/images/photo.jpg',
    'Imports/photo.jpg',
    false,
    AssetStore::CONFLICT_RENAME,
    $extractDir
);
// or, on PHP 8, with named arguments:
$file = GeneralHelpers::import_file_asset($path, 'Imports/photo.jpg', localBaseDir: $extractDir);
```

- Both the path and `$localBaseDir` are resolved with `realpath()` before the check, so `..`
  segments and symlinks (to a file or a directory) cannot lead outside the base directory.
- A file outside the base is refused, also when it does not exist; a missing file inside it throws
  the usual "missing or empty" `Exception`.
- A base directory that does not exist is refused.
- The opt-in is per call on purpose: there is no project-wide switch, so a call site that passes
  user input does not inherit a permission granted for another one. A broad base directory such
  as `/`, the project root or the system temp directory gives that protection away.
- `$localBaseDir` has no effect on URLs, which always go through the
  [outbound URL checks](#outbound-url-checks).

### getFileAssetsPath()

`GeneralHelpers::getFileAssetsPath($file)` returns `$file->getLocalPath()`, or `null` for a missing
file; see [Asset path resolution](asset-path-resolution.md) (Silverstripe 5 only).

## Image method fallbacks

Template methods of optional image modules, kept working when the module is not installed, and some
Silverstripe 3 method names:

| Extension | Applied | Methods |
|-----------|---------|---------|
| `FocusImageFallbackExtension` | while [FocusPoint](https://github.com/jonom/silverstripe-focuspoint) is not installed | `FocusFill($w, $h)`, `FocusFillMax($w, $h)`, `FocusCropWidth($w)`, `FocusCropHeight($h)`, and the deprecated `FocusFillFallback($w, $h)` |
| `ImageCropperFallbackExtension` | while Restruct's image cropper is not installed | `CroppedFocusedImage($w, $h)`, `CroppedImage($w, $h)` |
| `LegacyManipulationsExtension` | always | `PaddedImage` (`Pad`), `SetWidth` (`ScaleWidth`), `SetHeight` (`ScaleHeight`) |

The FocusPoint fallbacks map to core's `Fill`, `FillMax`, `CropWidth` and `CropHeight`, without a
focus point.
`FocusFill`, `FocusFillMax`, `FocusCropWidth` and `FocusCropHeight` therefore work in a template
with or without FocusPoint. `FocusFillFallback` is the old name of the `FocusFill` fallback; it only
exists while FocusPoint is not installed, so use `FocusFill`.
