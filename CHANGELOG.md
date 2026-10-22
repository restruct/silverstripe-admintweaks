# Changelog

## 4.4.0 (2026-10-22)

### Security

- **`hide_scaffolded_csv_buttons` now switches the CSV import off, not only its button.** ModelAdmin
  keeps its `ImportForm` / `import` actions routable and only checks the public `$showImportForm`
  property; with `model_importers` unset core registers a generic `CsvBulkLoader` per model. So
  with the setting on, a hand-built POST to `<admin>/<Model>/ImportForm` still imported (the
  bulk loader does check per-record permissions). The extension now also sets
  `$showImportForm = false` before any action runs.

### Changed

- With the setting on project-wide (YAML on `SilverStripe\Admin\ModelAdmin`), SecurityAdmin, which
  is a ModelAdmin too, loses its member/group CSV import as well. Keep it with
  `hide_scaffolded_csv_buttons: false` on `SilverStripe\Admin\SecurityAdmin` (README).

### Fixed

- **`FormFieldTweaksExtension` fataled on Silverstripe 6** for any field carrying a message: it
  imported `SilverStripe\ORM\ValidationResult`, which SS6 moved to
  `SilverStripe\Core\Validation\ValidationResult` (#90). New `FormFieldTweaksExtensionTest` covers
  the is-valid / is-invalid class. The docblock-only `SS_List` import in
  `GridFieldConfig_VersionedOrderable` also points at its SS6 path (`SilverStripe\Model\List`) now.

## 4.3.2 (2026-10-22)

### Security

- Build toolchain (dev dependencies only, nothing shipped changes): lockfile refreshed to
  launch-editor 2.14.2 (from 2.14.1; GHSA-587h-7wmj-h5pm, GHSA-r825-w8mm-5pxw, GHSA-698v-4r3x-3vcv), which pulls shell-quote ^1.10.0 (already 1.12.0).
  `client/dist` rebuilt: the JS and CSS are byte-identical.

## 4.3.1 (2026-10-22)

### Security

- Build toolchain (dev dependencies only, nothing shipped changes): lockfile refreshed to
  follow-redirects 1.16.1 (GHSA-q3jh-wrc6-jgq8, GHSA-8r9p-f939-6h3c). `client/dist` rebuilt: the
  JS and CSS are byte-identical. The 3.x line locks 1.15.11, outside the affected range, and needs
  no release.

## 4.3.0 (2026-10-21)

### Deprecated

- **`QueuedJobContextExtension` and `QueuedJobProfileTask` moved to
  restruct/silverstripe-queuedjobs-enhancements 1.7.0** (`Restruct\QueuedJobsEnhancements\Extensions\
  QueuedJobContextExtension`, `Restruct\QueuedJobsEnhancements\Tasks\QueuedJobProfileTask`). When
  those classes exist, this module no longer registers its extension on `QueuedJobDescriptor`
  (`Except: classexists` in `_config/queuedjobs.yml`) and its task disables itself (`isEnabled()`),
  so the job context is recorded once and `sake tasks:qjob-profile` resolves to the queuedjobs-enhancements
  task. Without queuedjobs-enhancements, or with one older than 1.7.0, both keep working as before.
  `EnhancedErrorFormatter` is unchanged: both extensions set the same `QUEUED_JOB` /
  `QUEUED_JOB_MEMORY` keys in the same format. The copies here will be removed in a future major.

## 4.2.1 (2026-10-14)

### Fixed

- **System switcher: the environment suffix is no longer cut off** (admintweaks#79). With a long
  system name, core's single-line ellipsis on the menu header hid exactly the ` (TEST)` part. The
  suffix is now its own badge after the name (`TEST`: the configured suffix without its spaces and
  one enclosing pair of brackets), which never shrinks; only the name truncates, after wrapping to
  two lines. The collapsed menu shows the badge under the initials. No badge on live (no suffix).
  The admin `<title>`/X-Title, the button's tooltip and its accessible name keep the full label.

### Security

- Build toolchain (dev dependencies only, nothing shipped changes): lockfile refreshed to
  shell-quote 1.12.0, source-map-js 1.2.2 and postcss-selector-parser 7.1.6. The last one has no
  patched 6.x release, so a `resolutions` entry lifts cssnano's `^6` users to 7.1.6. `client/dist`
  rebuilt: byte-identical.

## 4.2.0 (2026-10-14)

### Added

- **CMS system switcher** (admintweaks#71). For organisations running several CMSes side by side:
  configure `Restruct\Silverstripe\AdminTweaks\SystemSwitcher.systems` (key => `display`, `url`)
  and `current`, and the admin menu header names the CMS you are in, with an environment suffix
  (` (TEST)` / ` (DEV)`, configurable, nothing on live), and opens a dropdown with links to the
  other CMSes. Per-environment URLs go in an `Only: environment:` fragment that overrides only
  `url`. The same label becomes the admin `<title>` (`Example SITE (TEST) - Pages`). Keyboard
  accessible, collapsed menu shows initials, configured strings escaped, non-http(s) URLs left
  out. See the README. Same feature as 3.21.0 on the 3.x line.

  Nothing changes until `systems` is configured: the module now overrides core's
  `LeftAndMain_MenuLogo.ss` include, but with the switcher off it renders core's own template
  file, so the header stays exactly what the installed admin ships (covered by a unit test that
  compares the two, and a browser spec).

  The title: core `LeftAndMain::getApplicationName()` has no extension hook, so
  `SystemSwitcherExtension::onBeforeInit()` sets `application_name` for the handling
  controller's class for that request. That reaches the `<title>`, the `X-Title` header the CMS
  retitles the tab from after pjax navigation, and `$ApplicationName` in the menu, without a
  LeftAndMain subclass or a copy of `LeftAndMain.ss`. It is computed from the configured name
  each time, so the suffix is never doubled. `LeftAndMain`'s own config is left alone.

### Changed

- `client/dist` rebuilt with the current lockfile's toolchain: besides the switcher, the minified
  JS differs only in minifier output (eg method shorthand), no behaviour change.

## 4.1.3 (2026-10-12)

- Added `restruct/silverstripe-favicons` to `suggest`: it generates the favicon, touch icons and web
  manifest from one PNG and adds the tags to the login pages, the CMS and the site.

## 4.1.2 (2026-10-08)

### Fixed

- **`GridFieldSiteTreeAddNewButton` fatalled on Silverstripe 6** (admintweaks#61). It called
  `SiteTree::page_type_classes()`, which cms 6 removed, so any Lumberjack GridField using the button
  (including the one `SelectiveLumberjack` builds) errored as soon as a parent could add children.
  It now uses `ClassInfo::getValidSubClasses()` plus the `updateAllowedSubClasses()` hook, invoked
  with extensions as `CMSMain` does, so `hide_pagetypes` and project extensions that drop page types
  both apply. A parent without
  `hide_from_cms_tree` no longer raises a `foreach()` warning, and the method signature now declares
  `?SiteTree` explicitly (PHP 8.4 deprecation).
- **`SelectiveLumberjack` never filtered the site tree on Silverstripe 6** (admintweaks#62). It only
  filtered on `CMSPagesController`, which cms 6 removed; `CMSMain` serves `admin/pages` there. The
  filter now matches whichever of the two serves the Pages section, still for `treeview` and
  `getsubtree` only. **Visible on upgrade:** Lumberjack children hidden from the Pages section's
  tree on 3.x are now hidden there on 4.x too. The tree beside a page being edited
  (`admin/pages/edit`, `CMSPageEditController`) stays unfiltered, as it was on 3.x.

### Tests

- Regression test pinning the queued-job report recipient to `APP_LOG_MAIL_RECIPIENT`
  (admintweaks#57; the fix itself shipped in 3.20.4 and has been on this line since 4.1.0).
- `GridFieldSiteTreeAddNewButton` tests cover the `canCreate()` guard and extension-dropped page
  types, through a test-only SiteTree extension (no test DataObjects).
- The Lumberjack tests skip when `silverstripe/lumberjack` is not installed.

## 4.1.1 - 2026-10-02

### Fixed

- **`auto_expand_gridfield_search` did nothing on Silverstripe 6** (admintweaks#65). The GridField
  got its `at-auto-expand-search` marker class, but the search bar stayed closed: the script waited
  for the `grid-field__filter-open` toggle, and framework 6 renders that toggle as a plain
  `button[name=showFilter]` without the class. The script now matches both markups. The browser
  spec for this opt-in, previously marked fixme on SS6, now runs and passes there. The 3.x line
  (Silverstripe 5) was not affected.

## 4.1.0

**The Silverstripe 6 line, rebuilt.** Silverstripe 4 and 5 continue on the 3.x line (branch `v3`)
until Silverstripe 5 reaches end of life in April 2027. Nothing here is backported; 3.x cannot be
installed on Silverstripe 6. Upgrade guide: [UPGRADING.md](UPGRADING.md).

`4.0.0`-`4.0.2` were tagged from an earlier Silverstripe 6 attempt that shipped without tests, with
one of the six tasks, and with `symbiote/silverstripe-gridfieldextensions` as a hard requirement.
This release restores the full module on top of the maintained line.

**One thing to check if you are on `4.0.x`:** `symbiote/silverstripe-gridfieldextensions` is optional
again. If your project used it only because admintweaks pulled it in, require it explicitly -
`composer require symbiote/silverstripe-gridfieldextensions` - before upgrading.

Requires PHP `^8.3` and `silverstripe/framework ^6`.

### Changed

- **Hiding the Reports and Campaigns CMS sections is now OPT-IN** (admintweaks#54). Up to 3.x the
  module shipped `ignore_menuitem: true` for both controllers. Two problems, both silent: a QoL
  module removing whole core CMS sections is surprising (the section still answers at its URL, so
  a project adding its own `Report` subclass reads it as "the report did not register"); and the
  documented override did not work as documented, because same-key scalar config is decided by
  config *fragment order*, not project-beats-vendor, so `ignore_menuitem: false` silently lost
  unless the project fragment also carried `After: '#admintweaks-leftandmain'`.

  Set it once, no `After:` needed:

  ```yaml
  SilverStripe\Admin\LeftAndMain:
    hide_rarely_used_menu_sections: true
  ```

  The setting is deliberately nullable. `null` (not decided) shows the sections and logs a one-off
  **dev-only** notice, since that is exactly the population that may have relied on the 3.x
  default; setting it to either `true` or `false` acknowledges the change and silences it.
  Applied at runtime, so a project setting `ignore_menuitem` itself always wins.

- **Tasks are `symfony/console` commands.** Silverstripe 6 rebuilt `BuildTask` on `PolyCommand`,
  so all six tasks take declared options rather than request variables: `--apply` instead of
  `apply=1`, `--filter='Field:Op=Value'` instead of `filter[Field:Op]=Value`, `--group-by`
  instead of `groupBy`. `FocusPointInvertYaxisTask` is reachable as `tasks:focuspoint-invert-yaxis`
  rather than by class name. Full table in UPGRADING.md - **check your crons**.

- Password policy config follows the framework into `SilverStripe\Security\Validation`. The
  service is pointed at `RulesPasswordValidator`, because Silverstripe 6 defaults to
  `EntropyPasswordValidator`, which has no `MinLength`/`HistoricCount`/`MinTestScore` - leaving
  the old key in place would have dropped this module's password policy with no error.

### Fixed

- **`FocusPointInvertYaxisTask` zeroed every focus point instead of inverting it.** The update ran
  `assignSQL('FocusPointY', "'FocusPointY' * -1")`. The framework connects with `sql_mode=ANSI`
  (`MySQLDatabase::$sql_mode`), under which `"FocusPointY"` is an identifier - but `'FocusPointY'`
  is a string literal in *every* MySQL mode, and `'FocusPointY' * -1` evaluates to `0`. Verified
  directly against MySQL with a control. Now double-quoted.
- **`FocusPointInvertYaxisTask` died with a `DatabaseException` when FocusPoint was not installed**,
  instead of its intended no-op: it called `DB::field_list()` on the `Image` table without checking
  the table exists. `Image` carries no fields of its own, so there is no such table unless
  something (normally FocusPoint itself) added one.
- **A failing upstream is no longer re-requested on every call** (admintweaks#55).
  `CacheHelpers::cached_http_request()` only ever wrote to cache on a 200, and the underlying
  `GeneralHelpers::perform_http_request()` *throws* on a connection failure - so the common
  real-world failure never even reached the cache write, and a dead upstream was contacted again
  on every single call. The cache stopped working exactly when it was needed most. Failures are
  now cached on their own short TTL (`CacheHelpers.failure_cache_duration`, default 60s), and a
  repeat call inside that window fails immediately without touching the network. The
  caller-visible contract is unchanged: failures still throw, non-200s are still returned.
- **Outbound HTTP requests are bounded** (admintweaks#55). Guzzle ships no default `timeout` or
  `connect_timeout` (`configureDefaults()` sets neither, so both are 0 = unlimited), so an
  outbound call on a render path against a vanished upstream blocked indefinitely and pinned a
  PHP-FPM worker - one crawler burst on such a page then saturates the pool and 504s the whole
  site, which is what took a production site down on 2026-08-17. Defaults are now 5s connect /
  10s total, configurable via `GeneralHelpers.http_connect_timeout` and
  `GeneralHelpers.http_timeout`, and a caller passing its own `$options` still wins.
- **Two classes extending optional dependencies could fatal the entire application.**
  `MultivalueSortField` (`symbiote/silverstripe-multivaluefield`) and
  `GridFieldConfig_VersionedOrderable` (`symbiote/silverstripe-gridfieldextensions`) now guard on
  the parent existing, as `SelectiveLumberjack` and `GridFieldSiteTreeAddNewButton` already did.
  This was not dormant: `silverstripe/config`'s `PrivateStaticTransformer` calls `class_exists()`
  on every manifest class during bootstrap, which autoloads the file and fatals CMS, front end and
  CLI alike on any install without those optional modules. **Affects the 3.x line identically.**

### Removed

- `Email\CliSafeMailerSubscriber` and `_config/mailer.yml`. They backported the Silverstripe 6 fix
  for emails fataling with no current controller (3.20.6, below). The `$CurrentPageURL` rewrite
  that caused it does not exist anywhere in Silverstripe 6 (0 occurrences, against 1 for
  `urlRewriter` and 3 for `absoluteURLs` as controls), so there is nothing left to patch.
- The SwiftMailer SMTP config fragment. It could not activate from Silverstripe 5 onwards; use
  `MAILER_DSN`.

- **Two `--filter` arguments now both apply** (admintweaks#60). On the 3.x line two
  `filter[...]` CLI arguments silently collapsed to the last one: framework's
  `CLIRequestBuilder` parses each argument in isolation and `array_merge()`s the results, which
  overwrites string keys. The query still reported itself as filtered, so a
  narrower-than-intended result read as a reassuring small number.

  This line cannot reproduce it - tasks are symfony/console commands and `--filter` is declared
  `VALUE_IS_ARRAY`, so repeats collect rather than overwrite. Verified against a real dataset:
  two filters AND correctly and give the same answer in either order, where "last filter wins"
  would have returned a different count per order. Pinned by tests so the option declaration
  cannot quietly regress.

### Added

- **Continuous integration** (`.github/workflows/ci.yml`): the suite on Silverstripe 6 against PHP
  8.3 and 8.4 with MariaDB, plus a real `db:build` and `sake config:audit` on a booted host app -
  the previous Silverstripe 6 attempt failed on a bootstrap fatal, which no unit test catches.
- Tests for the opt-in menu behaviour, including an end-to-end check that drives the real CMS
  controller and reads the rendered menu (51 tests total, up from 46).
- Rendering tests for `CopyTextField`. The suite asserted `getTemplates()` contained the template
  path but never rendered it, so a removed template accessor would have passed unnoticed.

## 3.20.6

### Fixed

- **Emails now send when no controller is current** (admintweaks#59). This is a backport of the SS6
  framework fix: [silverstripe/framework#11678](https://github.com/silverstripe/silverstripe-framework/issues/11678),
  fixed by [PR #11690](https://github.com/silverstripe/silverstripe-framework/pull/11690), merged 2025-04-14 into 6.0 only.
  - In SS5, `MailerSubscriber::updateUrls()` runs `HTTP::absoluteURLs()` on every email body, and
    that method first does `str_replace('$CurrentPageURL', Controller::curr()->getRequest()->getURL(), ...)`
    (framework 5.4.26 `HTTP.php:68`). With no controller on the stack (bare CLI scripts, shutdown
    functions, buffered log flushes, anything after `popCurrent()`) every email fataled with
    `Call to a member function getRequest() on null`.
  - That covered admintweaks' error-log mails AND plain `Email::send()`, including
    `symbiote/silverstripe-queuedjobs` `QueuedJobService::checkJobHealth()`'s broken/stalled-job report.
  - For log mails, Monolog rethrew the error at the `$logger->error()` call site: a queued job that
    merely logged an error was marked Broken, and handlers below the mail handler never received the record.
  - New `Email\CliSafeMailerSubscriber` (wired in `_config/mailer.yml` by Injector-replacing
    `SilverStripe\Control\Email\MailerSubscriber`) uses the SS6 `absoluteURLs()`: the same URL rewriting
    minus the unused `$CurrentPageURL` step. Relative URLs are still made absolute.
  - Behaviour change: a literal `$CurrentPageURL` placeholder in an email body is no longer
    substituted, same as SS6. Nothing in framework/CMS used it.
  - **Maintenance cost:** the framework's `onMessage()` helpers are private, so `applyConfig()`,
    `setTo()`, `setFrom()` and `updateUrls()` are verbatim copies from framework 5.4.26 (~60 lines).
    Re-diff them against `vendor/silverstripe/framework/src/Control/Email/MailerSubscriber.php` on
    framework minor bumps. `updateOnMessage` extensions keep working.
  - **Drift guard:**
    - Framework `MailerSubscriber.php` is byte-identical in every stable 5.x tag (5.0.0-5.4.30,
      verified 2026-09-14), so the copies are exact for the whole `^5` range this line allows.
    - A test pins a hash of each copied parent method and fails if a framework release changes one.
    - The override is `Only: classexists`, so it stays inert on SS4 (which has no `MailerSubscriber`).
    - SS6 cannot install this line.
  - **SS6 removal note:** drop `src/Email/CliSafeMailerSubscriber.php` and `_config/mailer.yml` on the
    SS6 line (main / 4.x); the framework already contains the fix there.
- **A failing error-email send no longer throws into the code that logged** (secondary safety net).
  - `DedupSymfonyMailerHandler::send()` now wraps `parent::send()`. On any remaining send failure
    (transport down, rejected credentials, invalid address) the reason plus the original record go
    to PHP's `error_log()`, not the logger, which would recurse.
  - The log call no longer throws, and handlers below the mail handler still receive the record.

## 3.20.5

### Fixed

- **Env-based email config no longer clobbers an explicitly-set project value** (admintweaks#58).
  `_config.php` set `admin_email` (from `APP_SYSTEM_EMAIL_ADDRESS`/`_SENDER`) and
  `queued_job_admin_email` (from `APP_LOG_MAIL_RECIPIENT`) **unconditionally**, overwriting any value
  the project had explicitly assigned in YAML — the opposite of the intended "sane default, explicit
  wins" behaviour (e.g. a project's `queued_job_admin_email: admin@example.com` was silently replaced
  by the env value). Both are now **fallback-only**: env fills them only when the project hasn't set
  them. An explicit value always wins; `false` remains the opt-out for `queued_job_admin_email`.

## 3.20.3

Error-mail handler: fix a fatal on malformed request hosts, and cap the flood risk.

### Fixed

- **Error-notification email no longer fatals when the request Host is malformed.**
  `ErrorNotificationEmail` set its `from` via `calls: setFrom`, but `Email::__construct()`
  resolves `setFrom($from ?: getDefaultFrom())` itself — so the `calls` override arrived
  too late and `getDefaultFrom()` had already run. `getDefaultFrom()` falls back to
  `no-reply@<Director::host()>`; on a request with a trailing-dot FQDN Host (scanner
  traffic, e.g. `www.goflex.nl.`) while `admin_email` is momentarily empty (it can be,
  during early-bootstrap error logging) that yields an RFC-2822-invalid address and a
  **fatal** — turning any logged error into an HTTP 500. `from` is now passed as the
  first **constructor** argument, so `getDefaultFrom()` is never reached. (admintweaks#56)

### Added

- **Global rate cap on error emails** (`DedupSymfonyMailerHandler`), on top of the
  existing same-content deduplication. `max_emails_per_window` (default 20) bounds the
  total error emails sent in any `rate_window` (default 3600s) **regardless of message
  content** — closing the flood vector where an attacker triggers errors whose message
  varies per request (embedding the URL/host, or walking many paths), defeating the
  content dedup and sending one email per request. At the cap, one "further errors
  suppressed" notice is sent, then the rest are silent for the window; all errors are
  still written to the log / Sentry. Set `max_emails_per_window: 0` to disable the cap
  (content dedup still applies).
