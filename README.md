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
- **Bootstrap Icon Classes** - Standard `bi bi-*` classes mirroring `.font-icon-*` pattern (CDN-compatible)
- **Menu Grouping** - Groups admin sections under "Advanced" (requires `symbiote/silverstripe-grouped-cms-menu`)
- **Permission Badges** - Shows permission codes in Security admin
- **Checkbox Fixes** - Proper handling of unchecked checkboxes in editable GridFields

```php
// Add Bootstrap icon to a button
FormAction::create('add', 'Add Item')->addExtraClass('bi bi-plus-circle');
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

#### Cleanup Broken Jobs Task

Quickly delete all broken queued jobs:

```bash
vendor/bin/sake dev/tasks/cleanup-broken-jobs
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
| `symbiote/silverstripe-queuedjobs` | Enables ScheduledMethodCall |
| `wilr/silverstripe-googlesitemaps` | Auto-activates sitemap generation |
| `silverstripe/mimevalidator` | Auto-activates MIME upload validation |
| `wedevelopnl/silverstripe-webp-images` | Activates WEBP format support |
| `restruct/silverstripe-shortcodable` | Registers CurrentYear/FeaturedImage shortcodes |

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

## SS3/SS4 → SS5 migration repair tasks

Three `BuildTask`s that fix asset artifacts left behind by a migration to SS5. All are **dry-run by
default** — pass `apply=1` to write. Run them in this order:

| # | Task | Fixes |
|---|------|-------|
| 1 | `dev/tasks/fix-folder-filefilename` | `/admin/assets` dies with **`HashFileIDHelper::buildFileID requires an $hash value`**. Folder rows must have an EMPTY `FileFilename` (a folder derives its path from the parent chain); a populated value makes the folder's `visibility` lookup build a file ID with no hash. Also reports empty-`FileHash` File rows, which trip the same exception. |
| 2 | `dev/tasks/fix-misclassified-images` | Image files carried over with `ClassName = File` instead of `Image`. Blank tiles in the asset-admin grid — but more importantly, a `has_one` **Image** relation pointing at such a record renders **nothing on the front end**, because `Fill()`/`FitMax()`/`ScaleWidth()` don't exist on `File`. Resolves the target class via `File::get_class_for_file_extension()`, so `.svg` correctly becomes your registered SVG image class rather than `Image`. |
| 3 | `dev/tasks/generate-cms-thumbnails` | Blank tiles in the asset-admin file grid. The grid is served by GraphQL and asset-admin deliberately injects a **non-generating** thumbnail generator (`ThumbnailGenerator.graphql` → `Generates: false`), so it emits the `__FitMax[...]` URL but never creates the variant. Normal uploads generate variants on save; migrated files never went through SS5, so their variants don't exist and the `<img>` 404s. SS4 ran `ImageThumbnailHelper` inside `MigrateFileTask` — **SS5 removed the task but kept the helper**. This runs it. |

Order matters: (2) before (3), because the thumbnail helper skips anything whose `getIsImage()` is
false — a file still stuck on `ClassName = File` would be passed over.

Prerequisite for (3): the files must actually be migrated (a populated `FileHash`). On a database
where `FileHash` is empty there are no stored bytes to make a thumbnail from, and the task will
correctly report that it generated nothing.

Task (3) requires `silverstripe/asset-admin`; it hides itself if that isn't installed.

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
