# Changelog

## 4.0.0

**First release of the Silverstripe 6 line.** Silverstripe 4 and 5 continue on the 3.x line
(branch `v3`) until Silverstripe 5 reaches end of life in April 2027. Nothing here is backported;
3.x cannot be installed on Silverstripe 6. Upgrade guide: [UPGRADING.md](UPGRADING.md).

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
