# Restruct SilverStripe Admin Tweaks

A portable toolkit of admin UI enhancements, form field utilities, template helpers, and development conveniences for SilverStripe 4/5 projects.

**Namespace:** `Restruct\Silverstripe\AdminTweaks`
**Compatibility:** SilverStripe 4.13+ and 5.x

## Installation

```bash
composer require restruct/silverstripe-admintweaks
```

## Quick Start

Most features are opt-in via configuration. The module provides sensible defaults for:
- Secure session cookies
- GDPR-compliant UserDefinedForm (no server storage)
- Higher image quality defaults
- 24-hour default cache, 1-hour app cache

## Features Overview

| Category | Features | Documentation |
|----------|----------|---------------|
| **Admin UI** | Page icons, Bootstrap Icon classes, menu grouping, permission badges | [Icons](docs/icons.md) |
| **Form Fields** | CopyTextField, MultivalueSortField, Bootstrap styling | [Form Fields](docs/form-fields.md) |
| **GridField** | Editable+orderable configs, versioned ordering | [GridField](docs/gridfield.md) |
| **Templates** | 30+ helper methods, iterators, image placeholders | [Template Helpers](docs/template-helpers.md) |
| **SiteConfig** | Contact info, social media, theme settings, raw HTML | [SiteConfig](docs/siteconfig.md) |
| **Caching** | HTTP request caching, JSON/JSON-LD parsing | [Caching & Helpers](docs/caching.md) |
| **Email & Logging** | SMTP config, error email reports | [Email & Logging](docs/email-logging.md) |
| **QueuedJobs** | Throttled broken job notifications, scheduled method calls | [QueuedJobs](#queuedjobs-enhancements) |

## Feature Highlights

### Admin UI Enhancements

- **Page Icons** - Stylish icons for common page types using Bootstrap Icons (FA optional)
- **`.bs-icon-*` Classes** - Bootstrap Icon utility classes mirroring `.font-icon-*` pattern
- **Menu Grouping** - Groups admin sections under "Advanced" (requires `symbiote/silverstripe-grouped-cms-menu`)
- **Permission Badges** - Shows permission codes in Security admin
- **Checkbox Fixes** - Proper handling of unchecked checkboxes in editable GridFields

```php
// Add Bootstrap icon to a button
FormAction::create('add', 'Add Item')->addExtraClass('bs-icon-plus-circle');
```

### Form Fields

- **CopyTextField** - Read-only field with copy-to-clipboard button
- **MultivalueSortField** - Sortable multi-value field
- **Bootstrap Styling** - Auto-adds Bootstrap classes to form fields (opt-in)

```php
CopyTextField::create('ApiKey', 'API Key', $apiKey)
    ->setButtonLabel('Copy')
    ->setShowAlert(true);
```

### GridField Configurations

```php
// Inline editing with drag-drop ordering
$config = GridFieldConfigs::editable_orderable();

// Filterable, orderable with record editor
$config = GridFieldConfigs::filterable_orderable_recordeditor();
```

### Template Helpers

```html
<!-- Environment checks -->
<% if $IsDev %>Debug mode<% end_if %>

<!-- Image placeholder SVG -->
<% include ImagePlaceholder W=180, H=50, Label='logo' %>

<!-- Theme resource URL -->
<video src="{$themeDirResourceURL('my-theme')}/video.mp4"></video>

<!-- Extra iterators -->
<% loop $Items %>
  <div class="col-{$GroupSize}-of-4">$Title</div>
<% end_loop %>
```

### Cached HTTP Requests

```php
use Restruct\Silverstripe\AdminTweaks\Helpers\CacheHelpers;

// Cached JSON API request (1 hour TTL)
$data = CacheHelpers::cached_json_request('https://api.example.com/data', 'GET', [], 3600);

// Extract JSON-LD from a webpage
$jsonLd = CacheHelpers::cached_jsonLD_request('https://example.com/product');
```

### QueuedJobs Enhancements

#### Throttled Broken Job Notifications

By default, `QueuedJobService` logs "Broken jobs found" on every cron run (every minute) until broken jobs are manually fixed, possibly causing notification/logging spam. `ThrottledQueuedJobService` only notifies once per hour (configurable).

```yaml
# Enable throttled notifications
SilverStripe\Core\Injector\Injector:
  Symbiote\QueuedJobs\Services\QueuedJobService:
    class: Restruct\Silverstripe\AdminTweaks\Services\ThrottledQueuedJobService

# Optional: adjust interval (default 3600 = 1 hour)
Restruct\Silverstripe\AdminTweaks\Services\ThrottledQueuedJobService:
  broken_job_notify_interval: 7200  # 2 hours

# Required: cache backend for throttling
Psr\SimpleCache\CacheInterface.queuedjobs:
  factory: SilverStripe\Core\Cache\CacheFactory
  constructor:
    namespace: 'queuedjobs'
```

**How it works:**
- Notifications are cached per unique set of broken job IDs
- When a new job breaks, you get notified immediately (cache key changes)
- Same broken jobs sitting there → only notified once per interval

See: [symbiote/silverstripe-queuedjobs#299](https://github.com/symbiote/silverstripe-queuedjobs/issues/299)

#### Scheduled Method Calls

```php
use Restruct\Silverstripe\AdminTweaks\Jobs\ScheduledMethodCall;

// Schedule a method call
ScheduledMethodCall::schedule(
    MyClass::class,
    'myMethod',
    ['arg1', 'arg2'],
    '+1 hour'
);
```

## Configuration

### Email & SMTP (via .env)

```ini
APP_SYSTEM_EMAIL_SENDER="My App"
APP_SYSTEM_EMAIL_ADDRESS="noreply@example.com"

APP_SMTP_HOST="smtp.mailgun.org"
APP_SMTP_PORT="587"
APP_SMTP_ENCRYPTION="tls"
APP_SMTP_USERNAME="postmaster@mg.example.com"
APP_SMTP_PASSWORD="secret"

# Error email logging (omit on dev/test to disable)
APP_LOG_MAIL_RECIPIENT="admin@example.com"
APP_LOG_MAIL_SUBJECT="Error on MyApp"
APP_LOG_MAIL_SENDER="noreply@example.com"
APP_LOG_MAIL_LEVEL="error"
```

### SiteConfig Extension (opt-in)

```yaml
SilverStripe\SiteConfig\SiteConfig:
  extensions:
    - Restruct\Silverstripe\AdminTweaks\Extensions\SiteConfigExtension
  enable_contact_social_media_fields: true
  enable_raw_head_body_fields: true
  enable_browser_color_theme_field: true
```

### Bootstrap Form Classes (opt-in)

```yaml
SilverStripe\Forms\FormField:
  extensions:
    - Restruct\Silverstripe\AdminTweaks\Extensions\FormFieldBootstrapExtension
```

## Optional Module Integration

The module enhances functionality when these modules are installed:

| Module | Enhancement |
|--------|-------------|
| `symbiote/silverstripe-grouped-cms-menu` | Groups admin sections under "Advanced" |
| `symbiote/silverstripe-queuedjobs` | Enables ScheduledMethodCall, ThrottledQueuedJobService |
| `wilr/silverstripe-googlesitemaps` | Auto-activates sitemap generation |
| `silverstripe/mimevalidator` | Auto-activates MIME upload validation |
| `wedevelopnl/silverstripe-webp-images` | Activates WEBP format support |
| `sheadawson/silverstripe-shortcodable` | Registers CurrentYear/FeaturedImage shortcodes |

## Traits

### EnforceCMSPermission

Require CMS access for DataObject CRUD operations:

```php
use Restruct\Silverstripe\AdminTweaks\Traits\EnforceCMSPermission;

class MyDataObject extends DataObject
{
    use EnforceCMSPermission;
}
```

## Default Behaviors

These are applied automatically:

- `Session.cookie_secure: true` - Secure session cookies
- UserDefinedForm submissions disabled by default (GDPR)
- Higher image quality (90% JPEG, 8 PNG compression)
- URL segment character replacements (umlauts, special chars)
- Hides CampaignAdmin and ReportAdmin from navigation

## Building Assets

```bash
cd _dev/admintweaks
npm install
npm run dev        # Development build
npm run production # Production build
npm run watch      # Watch mode
```

## Detailed Documentation

- [Icons & Bootstrap Icon Classes](docs/icons.md)
- [Form Fields](docs/form-fields.md)
- [GridField Configurations](docs/gridfield.md)
- [Template Helpers](docs/template-helpers.md)
- [SiteConfig Extension](docs/siteconfig.md)
- [Caching & General Helpers](docs/caching.md)
- [Email & Logging Configuration](docs/email-logging.md)

## License

BSD-3-Clause
