# Restruct SilverStripe Admin Tweaks

A portable toolkit of admin UI enhancements, form field utilities, template helpers, and development conveniences for SilverStripe projects.

**Namespace:** `Restruct\Silverstripe\AdminTweaks`

| Module line | Silverstripe | PHP | Status |
|---|---|---|---|
| `^4` (branch `main`) | 6.x | `^8.3` | active development |
| `^3` (branch `v3`) | 4.13+, 5.x | `^7.4 \| ^8` | security and bug fixes until Silverstripe 5 EOL (April 2027) |

## Installation

```bash
composer require restruct/silverstripe-admintweaks:^4   # Silverstripe 6
composer require restruct/silverstripe-admintweaks:^3   # Silverstripe 4 / 5
```

Upgrading from 3.x? See [UPGRADING.md](UPGRADING.md) - the CMS menu behaviour and the task
invocations both changed.

## Quick Start

Most features are opt-in via configuration. The module provides sensible defaults for:
- Secure session cookies
- GDPR-compliant UserDefinedForm (no server storage)
- Higher image quality defaults
- 24-hour default cache, 1-hour app cache

## Features Overview

| Category | Features | Documentation |
|----------|----------|---------------|
| **Admin UI** | Page icons, Bootstrap Icon classes, menu grouping, permission badges, [CMS system switcher](#cms-system-switcher-opt-in-via-config) | [Icons](docs/icons.md) |
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
- **Search-popover fixes** - GridField 'Search options' popover: checkboxset/optionset holders no
  longer collapse/overlap when a `form-control` class leaks onto the field holder (eg from
  project code that decorates scaffolded search fields for front-end reuse), and long option
  lists scroll within the popover (automatic, CSS-only)

### Per-ModelAdmin Tweaks (opt-in via config)

`ModelAdminExtension` is registered on `ModelAdmin`; enable per subclass (or project-wide via
yml on `SilverStripe\Admin\ModelAdmin`, with per-subclass overrides):

```php
class ProductAdmin extends ModelAdmin
{
    // Open the GridField search bar on load instead of hiding it behind the magnifier icon
    private static bool $auto_expand_gridfield_search = true;

    // Hide the framework-scaffolded Export to CSV / Print / Import CSV buttons
    // (the scaffolded export just dumps summary_fields; the default CsvBulkLoader
    // import is a data-integrity risk on synced/managed models)
    private static bool $hide_scaffolded_csv_buttons = true;
}
```

```php
// Add Bootstrap icon to a button
FormAction::create('add', 'Add Item')->addExtraClass('bi bi-plus-circle');
```

### CMS System Switcher (opt-in via config)

For organisations running several Silverstripe CMSes side by side (eg a public site, a document
system and an e-learning platform): the admin menu header names the CMS you are in, with the
environment, and opens a dropdown with links to the other CMSes.

```yaml
---
Name: app-systemswitcher
---
Restruct\Silverstripe\AdminTweaks\SystemSwitcher:
  current: site            # this CMS's key below; differs per project
  systems:                 # the same block can be shared by every CMS
    site: { display: 'Example SITE', url: 'https://www.example.org/admin' }
    docs: { display: 'Example DOCS', url: 'https://docs.example.org/admin' }
    lms:  { display: 'Example LMS',  url: 'https://learn.example.org/admin' }
---
Name: app-systemswitcher-test
After: '#app-systemswitcher'  # so this fragment merges over the one above
Only:
  environment: test
---
Restruct\Silverstripe\AdminTweaks\SystemSwitcher:
  systems:                 # only the urls differ per environment; display is merged from above
    site: { url: 'https://site-test.example.org/admin' }
    docs: { url: 'https://docs-test.example.org/admin' }
    lms:  { url: 'https://learn-test.example.org/admin' }
```

- **Off until `systems` is configured**: the core header (`$SiteConfig.Title`, linking to the site)
  and the admin `<title>` stay exactly as Silverstripe renders them.
- **Header** = the current system's `display` plus an environment suffix: ` (TEST)` on `test`,
  ` (DEV)` on `dev`, nothing on `live`. Without a `display` for `current` (or without `current`)
  the name is `LeftAndMain.application_name`. It replaces `$SiteConfig.Title` in the header, so a
  public site's name does not show there. The suffix shows as a small badge after the name, without
  its surrounding spaces and one enclosing pair of brackets (` (TEST)` -> `TEST`, ` [acceptance]` ->
  `acceptance`); a name too long for the header wraps to two lines and is then cut off with an
  ellipsis, the badge never is. No suffix (live): no badge. The button's tooltip and accessible name
  keep the full label (`Example SITE (TEST)`).
- **Admin title**: while the switcher is on, that same label becomes `LeftAndMain.application_name`
  for the request, so the browser tab reads `Example SITE (TEST) - Pages` (also after pjax
  navigation). The suffix is never doubled, also not when `application_name` already ends with it.
- **Dropdown** lists the *other* systems, links in the same tab (switching moves you to the other
  CMS; ctrl/cmd-click opens a new tab as usual). Entries without a `display`, or with a `url` that
  is not an absolute `http(s)://` URL, are left out. Keyboard: the header is a button
  (`aria-expanded`), Escape closes. All configured strings are HTML-escaped.
- **Collapsed menu** shows the initials of the name ("Example SITE" -> `ES`), with the environment
  badge under them; set `initials` on a system to choose them yourself, eg
  `site: { display: 'Example SITE', initials: 'SI', url: ... }`.
- **Suffix strings** are configurable per environment type (the admin title uses them as written,
  the header badge as described above):

```yaml
Restruct\Silverstripe\AdminTweaks\SystemSwitcher:
  environment_suffixes:
    test: ' [acceptance]'
```

- **With [restruct/silverstripe-login-branding](https://github.com/restruct/silverstripe-login-branding)**
  and its `application_name_overrides_title`: works the same, the switcher shows its own label in
  the header either way. To keep one place for the name there, set `LeftAndMain.application_name`
  and leave `display` out for the current system (it defaults to `application_name`).
- Each system keeps its own login. The header template is an override of core's
  `SilverStripe/Admin/Includes/LeftAndMain_MenuLogo.ss`; a theme or project that overrides that
  include as well takes precedence, and the switcher then does not show.

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
vendor/bin/sake tasks:cleanup-broken-jobs
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

Hiding the Reports and Campaigns CMS sections is **opt-in** since 4.1 ([#54]), and applied at
runtime so no `After:` ordering is needed to override it:

```yaml
SilverStripe\Admin\LeftAndMain:
  hide_rarely_used_menu_sections: true
```

[#54]: https://github.com/restruct/silverstripe-admintweaks/issues/54

## SS3/SS4 → SS5 migration repair tasks

Three `BuildTask`s that fix asset artifacts left behind by a migration to SS5. All are **dry-run by
default** — pass `--apply` to write. Run them in this order:

| # | Task | Fixes |
|---|------|-------|
| 1 | `sake tasks:fix-folder-filefilename` | `/admin/assets` dies with **`HashFileIDHelper::buildFileID requires an $hash value`**. Folder rows must have an EMPTY `FileFilename` (a folder derives its path from the parent chain); a populated value makes the folder's `visibility` lookup build a file ID with no hash. Also reports empty-`FileHash` File rows, which trip the same exception. |
| 2 | `sake tasks:fix-misclassified-images` | Image files carried over with `ClassName = File` instead of `Image`. Blank tiles in the asset-admin grid — but more importantly, a `has_one` **Image** relation pointing at such a record renders **nothing on the front end**, because `Fill()`/`FitMax()`/`ScaleWidth()` don't exist on `File`. Resolves the target class via `File::get_class_for_file_extension()`, so `.svg` correctly becomes your registered SVG image class rather than `Image`. |
| 3 | `sake tasks:generate-cms-thumbnails` | Blank tiles in the asset-admin file grid. The grid is served by GraphQL and asset-admin deliberately injects a **non-generating** thumbnail generator (`ThumbnailGenerator.graphql` → `Generates: false`), so it emits the `__FitMax[...]` URL but never creates the variant. Normal uploads generate variants on save; migrated files never went through SS5, so their variants don't exist and the `<img>` 404s. SS4 ran `ImageThumbnailHelper` inside `MigrateFileTask` — **SS5 removed the task but kept the helper**. This runs it. |

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
