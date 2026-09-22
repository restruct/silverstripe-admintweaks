# Changelog

## 3.20.7

### Fixed

- **Two classes extending optional dependencies could fatal the entire application.**
  `MultivalueSortField` (`symbiote/silverstripe-multivaluefield`) and
  `GridFieldConfig_VersionedOrderable` (`symbiote/silverstripe-gridfieldextensions`) now guard on
  their parent class existing, as `SelectiveLumberjack` and `GridFieldSiteTreeAddNewButton`
  already did.

  This was not a dormant error. `silverstripe/config`'s `PrivateStaticTransformer` calls
  `class_exists()` on every class in the manifest during bootstrap
  (`PrivateStaticTransformer.php:43`) to read its config statics, and `class_exists()` autoloads -
  so merely having the file on disk was enough. On any install without those optional modules the
  result was a bootstrap fatal taking down CMS, front end and CLI alike, reported as
  `Class "Symbiote\...\X" not found` from a file nothing had knowingly used.

  Backported from the 4.x line, where it was found while porting to Silverstripe 6.

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
