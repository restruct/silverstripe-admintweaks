# Email and error mails

The system sender and the queued-job report recipient from environment variables, and error mails
with request details and deduplication.

## Email Configuration

Configure email settings via environment variables in `.env`.

### System Email Settings

```ini
# Sender name and address for system emails
APP_SYSTEM_EMAIL_SENDER="My Application"
APP_SYSTEM_EMAIL_ADDRESS="noreply@example.com"
```

Together they fill `Email.admin_email`, the default sender of outgoing email, but only when the
project has not set `admin_email` itself (an explicit config value always wins):

```php
// Emails will use these as From address
$email = Email::create()
    ->setTo('user@example.com')
    ->setSubject('Welcome')
    ->send();
```

### SMTP Configuration

**Silverstripe 4 only.** The `APP_SMTP_*` variables configure SwiftMailer's `Swift_SmtpTransport`
(the fragment in `_config/email.yml` is gated on that class), so they do nothing on Silverstripe 5.
On Silverstripe 5, Symfony Mailer reads `MAILER_DSN` instead, e.g.
`MAILER_DSN="smtp://user:pass@smtp.example.com:587"`.

```ini
# SMTP server settings (Silverstripe 4 only)
APP_SMTP_HOST="smtp.mailgun.org"
APP_SMTP_PORT="587"
APP_SMTP_ENCRYPTION="tls"
APP_SMTP_USERNAME="postmaster@mg.example.com"
APP_SMTP_PASSWORD="your-secret-password"
```

**Encryption options:**
- `tls` - STARTTLS (port 587, recommended)
- `ssl` - SMTP over SSL/TLS (port 465)
- Empty - No encryption (port 25, not recommended)

**Common SMTP providers:**

| Provider | Host | Port | Encryption |
|----------|------|------|------------|
| Mailgun | smtp.mailgun.org | 587 | tls |
| SendGrid | smtp.sendgrid.net | 587 | tls |
| Amazon SES | email-smtp.{region}.amazonaws.com | 587 | tls |
| Gmail | smtp.gmail.com | 587 | tls |
| Office 365 | smtp.office365.com | 587 | tls |

---

## Error Email Logging

Send error logs via email to administrators. Only activates when ALL four variables are set, and
only on Silverstripe 5: the handler is built on Monolog 3 and Symfony Mailer, which Silverstripe 4
does not have, so there it stays off even with the variables set.

```ini
# Error email configuration (set on LIVE only)
APP_LOG_MAIL_RECIPIENT="admin@example.com"
APP_LOG_MAIL_SUBJECT="Error on MyApp LIVE"
APP_LOG_MAIL_SENDER="errors@example.com"
APP_LOG_MAIL_LEVEL="error"
```

### Log Levels

| Level | Description |
|-------|-------------|
| `debug` | Detailed debug information (not recommended for email) |
| `info` | Interesting events |
| `notice` | Normal but significant events |
| `warning` | Exceptional occurrences that are not errors |
| `error` | Runtime errors (recommended for email) |
| `critical` | Critical conditions |
| `alert` | Action must be taken immediately |
| `emergency` | System is unusable |

### Disabling on Dev/Test

Simply omit the `APP_LOG_MAIL_*` variables on non-production environments:

```ini
# .env.dev - No error emails
APP_SYSTEM_EMAIL_SENDER="Dev App"
APP_SYSTEM_EMAIL_ADDRESS="dev@example.com"
# APP_LOG_MAIL_* variables not set = no error emails
```

### Queued-job reports

With [symbiote/silverstripe-queuedjobs](https://github.com/symbiote/silverstripe-queuedjobs)
installed, `Email.queued_job_admin_email` (the recipient of its broken, stalled and missing-job
reports) is filled from `APP_LOG_MAIL_RECIPIENT`, again only when the project has not set it. Set
it to `false` to receive no such reports.

While a job runs, the job and its memory use are recorded in `$_SERVER` (`QUEUED_JOB`,
`QUEUED_JOB_MEMORY`), so an error mail from the queue runner names the job and step instead of only
the runner task. This `QueuedJobContextExtension` is deprecated here:
[queuedjobs-enhancements](https://github.com/restruct/silverstripe-queuedjobs-enhancements) 1.7.0
and later register their own copy, and this one then stays off.

---

## EnhancedErrorFormatter

Extends `DetailedErrorFormatter` - inherits record parsing and context extraction,
overrides `output()` to produce email-friendly HTML.

Solves: `DetailedErrorFormatter::output()` calls `Debug::create_debug_view()` which
falls back to `CliDebugView` (plain text) when there's no HTTP request context during
error handling - producing unreadable "wall of text" emails.

### Features

- Inline-styled HTML (no external CSS links that get stripped in email)
- Source code context around the error
- Full stack trace
- A table of request and server details from `$_SERVER` (like SS3 had), including the queued job
  that was running, when the error came from the queue runner
- Configurable: `format_mode` = `html` (default) or `plaintext` (monospace)

```yaml
Restruct\Silverstripe\AdminTweaks\Logging\EnhancedErrorFormatter:
  # Switch to monospace plaintext mode if preferred
  format_mode: plaintext
  # More $_SERVER keys in the details table (added to the default list)
  server_vars:
    - HTTP_X_FORWARDED_FOR
```

The default `server_vars` list is in `src/Logging/EnhancedErrorFormatter.php`.

---

## DedupSymfonyMailerHandler

Extends `SymfonyMailerHandler` with PSR-16 cache-based deduplication.
Prevents email bombing when the same error repeats (e.g. DB down).

- No buffering - sends immediately (unlike Monolog's DeduplicationHandler/BufferHandler)
- PSR-16 cache handles TTL expiry (uses the `adminCache` pool)
- The same error (same level and message start) is mailed once per `dedup_time`
- A hard cap of `max_emails_per_window` mails per `rate_window` across all errors, so even an
  outage that throws many different errors cannot flood the inbox

| Setting | Default | What it does |
|---------|---------|--------------|
| `dedup_time` | `300` | Seconds a repeat of the same error is not mailed again |
| `max_emails_per_window` | `20` | Most error mails in one window; `0` switches the cap off (deduplication still applies) |
| `rate_window` | `3600` | Length of that window in seconds |

```yaml
Restruct\Silverstripe\AdminTweaks\Logging\DedupSymfonyMailerHandler:
  dedup_time: 600  # 10 minutes
```

---

## Complete .env Example

```ini
# ===========================================
# Email Configuration
# ===========================================

# System email identity
APP_SYSTEM_EMAIL_SENDER="My Application"
APP_SYSTEM_EMAIL_ADDRESS="noreply@myapp.com"

# SMTP server (Mailgun example), Silverstripe 4 only; on Silverstripe 5 use MAILER_DSN
APP_SMTP_HOST="smtp.mailgun.org"
APP_SMTP_PORT="587"
APP_SMTP_ENCRYPTION="tls"
APP_SMTP_USERNAME="postmaster@mg.myapp.com"
APP_SMTP_PASSWORD="secret-api-key"

# ===========================================
# Error Logging (LIVE only)
# ===========================================

APP_LOG_MAIL_RECIPIENT="devteam@myapp.com"
APP_LOG_MAIL_SUBJECT="[LIVE] Error on MyApp"
APP_LOG_MAIL_SENDER="errors@myapp.com"
APP_LOG_MAIL_LEVEL="error"
```

---

## Troubleshooting

### Emails not sending

1. Check SMTP credentials are correct (`MAILER_DSN` on Silverstripe 5, `APP_SMTP_*` on Silverstripe 4)
2. Verify port is not blocked by firewall
3. Check `APP_SYSTEM_EMAIL_ADDRESS` is set
4. Review SilverStripe logs for mailer errors

### Error emails not received

1. Ensure ALL four `APP_LOG_MAIL_*` variables are set
2. Check spam folder
3. Verify sender address is allowed by SMTP provider
4. Test with lower log level (`warning` instead of `error`)

### Too many error emails

1. Raise `APP_LOG_MAIL_LEVEL` to `critical`
2. Lower `max_emails_per_window` or raise `dedup_time` (above)
3. Consider using external error tracking (Sentry, Bugsnag); the mail handler lets records bubble
   on to other handlers, so both can run

### Disabling the error email handler from a project

If you need to disable the error email handler without removing the env vars, override the
handler definition in your project config:

```yaml
---
Name: project-disable-error-email-handler
After:
  - '#admintweaks-error-email-logger'
Only:
  envvarset:
    - APP_LOG_MAIL_RECIPIENT
---
SilverStripe\Core\Injector\Injector:
  MailHandler:
    class: Monolog\Handler\NullHandler
```

