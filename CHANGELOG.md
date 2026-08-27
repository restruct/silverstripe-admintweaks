# Changelog

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
