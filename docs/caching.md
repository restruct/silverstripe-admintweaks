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

Request URLs are fetched as given: validate any URL that comes from user input before passing it.

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
same. The fourth argument, `$insertAfter`, is not used yet: the tab is always placed after `Main`.

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

Downloads a file from a URL, or reads a local path (also with a `file:` prefix), and stores it as a
File, or an Image for image extensions:

```php
$file = GeneralHelpers::import_file_asset('https://example.com/document.pdf', 'Imports/document.pdf');
```

`import_file_asset(string $filePathOrUrl, ?string $assetPath = null, bool $publish = true,
string $conflictResolution = AssetStore::CONFLICT_OVERWRITE)`:

- `$assetPath` is `folder/filename` in the asset store.
- The file is published, and an existing file with the same name is overwritten, unless you pass
  other values.
- Only extensions in `File::getAllowedExtensions()` are accepted.
- It throws an `Exception` for a disallowed extension, an empty download or a failed store.
- The download does not use the default timeouts above, and the URL is fetched as given.

### getFileAssetsPath()

`GeneralHelpers::getFileAssetsPath($file)` returns `$file->getLocalPath()`, or `null` for a missing
file; see [Asset path resolution](asset-path-resolution.md).

## Image method fallbacks

Template methods of optional image modules, kept working when the module is not installed, and some
Silverstripe 3 method names:

| Extension | Applied | Methods |
|-----------|---------|---------|
| `FocusImageFallbackExtension` | while [FocusPoint](https://github.com/jonom/silverstripe-focuspoint) is not installed | `FocusFillMax($w, $h)`, `FocusCropWidth($w)`, `FocusCropHeight($h)`, `FocusFillFallback($w, $h)` |
| `ImageCropperFallbackExtension` | while Restruct's image cropper is not installed | `CroppedFocusedImage($w, $h)`, `CroppedImage($w, $h)` |
| `LegacyManipulationsExtension` | always | `PaddedImage` (`Pad`), `SetWidth` (`ScaleWidth`), `SetHeight` (`ScaleHeight`) |

The FocusPoint fallbacks map to core's `Fill`, `FillMax`, `CropWidth` and `CropHeight`, without a
focus point.
`FocusFillMax`, `FocusCropWidth` and `FocusCropHeight` therefore work in a template with or
without FocusPoint. FocusPoint's `FocusFill` has no fallback; `FocusFillFallback` only exists
while FocusPoint is not installed.
