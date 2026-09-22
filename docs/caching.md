# Caching & General Helpers

The module provides utility classes for HTTP request caching, file operations, and general helper methods.

## CacheHelpers

Cached HTTP requests using Guzzle with automatic invalidation on errors.

### cached_http_request()

Make cached HTTP requests with Guzzle.

```php
use Restruct\Silverstripe\AdminTweaks\Helpers\CacheHelpers;

// GET request with 1 hour cache
$response = CacheHelpers::cached_http_request(
    'https://api.example.com/data',
    'GET',
    [],      // Guzzle options
    3600     // Cache TTL in seconds
);

// POST request (not cached by default)
$response = CacheHelpers::cached_http_request(
    'https://api.example.com/submit',
    'POST',
    ['json' => ['key' => 'value']],
    0        // No caching for POST
);
```

### cached_json_request()

Fetch and parse JSON API responses.

```php
// Returns parsed JSON as array/object
$data = CacheHelpers::cached_json_request(
    'https://api.example.com/products',
    'GET',
    [],
    3600
);

foreach ($data['products'] as $product) {
    echo $product['name'];
}
```

### cached_jsonLD_request()

Extract JSON-LD structured data from web pages.

```php
// Extract JSON-LD from a product page
$jsonLd = CacheHelpers::cached_jsonLD_request(
    'https://shop.example.com/product/123',
    3600
);

if ($jsonLd && $jsonLd['@type'] === 'Product') {
    $price = $jsonLd['offers']['price'];
    $name = $jsonLd['name'];
}
```

**Use cases:**
- Extracting product data from e-commerce sites
- Reading article metadata from news sites
- Importing structured data from external sources

### Cache Behavior

- Cache uses SilverStripe's cache system (`appcache` by default)
- Failed requests (errors, non-200 status) are not cached
- Cache is automatically invalidated on errors
- Cache key is generated from URL + method + options hash

---

## GeneralHelpers

Static utility methods for common operations.

### Tab Management

```php
use Restruct\Silverstripe\AdminTweaks\Helpers\GeneralHelpers;

// Move a field to a specific tab
GeneralHelpers::moveFieldToTab($fields, 'MyField', 'Root.Settings');

// Create a new tab if it doesn't exist
GeneralHelpers::ensureTab($fields, 'Root.Advanced', 'Advanced Settings');
```

### Safe Property Access

Access nested object properties without null checks:

```php
// Instead of: $obj?->relation?->subrelation?->property
$value = GeneralHelpers::safelyGetProperty($obj, 'relation.subrelation.property');

// With default value
$value = GeneralHelpers::safelyGetProperty($obj, 'config.setting', 'default');
```

### Option Translation

Translate option arrays for dropdowns:

```php
$options = [
    'draft' => 'Draft',
    'published' => 'Published',
    'archived' => 'Archived',
];

// Translates values using i18n
$translated = GeneralHelpers::translateOptions($options, 'MyApp.Status');
```

### File Import/Download

Download remote files and save to assets:

```php
// Download image and save to assets
$file = GeneralHelpers::download_and_save_asset(
    'https://example.com/image.jpg',
    'imports',           // Folder in assets
    'imported-image.jpg' // Filename (optional, uses remote name if omitted)
);

if ($file) {
    $this->ImageID = $file->ID;
    $this->write();
}
```

**Features:**
- Creates folder if it doesn't exist
- Handles file naming conflicts
- Returns File object on success, null on failure
- Supports all file types

### Import File Asset

Alternative method for importing files:

```php
$file = GeneralHelpers::import_file_asset(
    'https://example.com/document.pdf',
    'assets/documents'
);
```

---

## Default Cache Configuration

The module configures two caches automatically:

```yaml
# Applied via _config/caches.yml
SilverStripe\Core\Injector\Injector:
  # Default cache: 24 hours
  Psr\SimpleCache\CacheInterface.cacheblock:
    factory: SilverStripe\Core\Cache\CacheFactory
    constructor:
      namespace: cacheblock
      defaultLifetime: 86400

  # App cache: 1 hour
  Psr\SimpleCache\CacheInterface.appcache:
    factory: SilverStripe\Core\Cache\CacheFactory
    constructor:
      namespace: appcache
      defaultLifetime: 3600
```

### Using Custom Caches

```php
use SilverStripe\Core\Injector\Injector;
use Psr\SimpleCache\CacheInterface;

// Get app cache
$cache = Injector::inst()->get(CacheInterface::class . '.appcache');

// Store value
$cache->set('my-key', $data, 3600);

// Retrieve value
$data = $cache->get('my-key');

// Delete value
$cache->delete('my-key');
```

---

## ScheduledMethodCall

Schedule asynchronous method calls using QueuedJobs.

### Requirements

```bash
composer require symbiote/silverstripe-queuedjobs
```

### Basic Usage

```php
use Restruct\Silverstripe\AdminTweaks\Jobs\ScheduledMethodCall;

// Schedule a static method call
ScheduledMethodCall::schedule(
    MyClass::class,
    'processData',
    ['arg1', 'arg2'],
    '+5 minutes'
);

// Schedule for specific time
ScheduledMethodCall::schedule(
    EmailService::class,
    'sendNewsletter',
    [$newsletterId],
    '2024-12-25 09:00:00'
);

// Schedule with no delay (ASAP)
ScheduledMethodCall::schedule(
    CacheService::class,
    'warmCache',
    []
);
```

### Scheduling Format

The timing parameter accepts:
- Relative times: `'+1 hour'`, `'+30 minutes'`, `'+1 day'`
- Absolute times: `'2024-12-25 09:00:00'`
- `null` or omitted: Run as soon as possible

### Use Cases

- Sending emails after a delay
- Processing uploads in the background
- Cache warming
- Cleanup tasks
- Any operation that shouldn't block the request

---

## Image Fallback Extensions

Provides fallback methods when optional image manipulation modules are not installed.

### FocusImageFallbackExtension

Fallback methods when FocusPoint is not installed:

```php
// These methods work whether FocusPoint is installed or not
$image->FocusFillFallback(400, 300);
$image->FocusCropWidth(400);
$image->FocusCropHeight(300);
```

### ImageCropperFallbackExtension

Fallback for image cropping methods.

### LegacyManipulationsExtension

Compatibility methods for legacy image manipulations from SilverStripe 3.
