# Changelog

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
