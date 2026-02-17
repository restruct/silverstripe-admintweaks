# Email & Logging Configuration

The module provides environment-based email configuration and enhanced error logging.

## Email Configuration

Configure email settings via environment variables in `.env`.

### System Email Settings

```ini
# Sender name and address for system emails
APP_SYSTEM_EMAIL_SENDER="My Application"
APP_SYSTEM_EMAIL_ADDRESS="noreply@example.com"
```

These are used as defaults for all outgoing emails:

```php
// Emails will use these as From address
$email = Email::create()
    ->setTo('user@example.com')
    ->setSubject('Welcome')
    ->send();
```

### SMTP Configuration

```ini
# SMTP server settings
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

Send error logs via email to administrators. Only activates when ALL four variables are set.

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

---

## EnhancedErrorFormatter

Custom Monolog formatter for more informative error emails.

### Features

- Source code context around the error
- Full stack trace
- Request information (URL, method, headers)
- Server environment details
- Session data (sanitized)
- POST data (sanitized)

### Email Format

Error emails include:

```
Error: Division by zero

File: /var/www/app/src/Calculator.php
Line: 42

Source Context:
  40:     public function divide($a, $b)
  41:     {
> 42:         return $a / $b;
  43:     }

Stack Trace:
#0 /var/www/app/src/Controller.php(15): Calculator->divide(10, 0)
#1 /var/www/vendor/silverstripe/framework/...

Request:
URL: https://example.com/calculate
Method: POST
IP: 192.168.1.1

Environment:
PHP: 8.3.0
SS: 5.2.0
Server: Apache/2.4
```

---

## Logging Handler Factories

### DeduplicationHandlerFactory

Prevents duplicate log entries within a time window:

```yaml
SilverStripe\Core\Injector\Injector:
  DeduplicationHandler:
    factory: Restruct\Silverstripe\AdminTweaks\Logging\DeduplicationHandlerFactory
```

### SymfonyMailerHandlerFactory

Monolog handler using Symfony Mailer for sending log emails:

```yaml
SilverStripe\Core\Injector\Injector:
  MailerHandler:
    factory: Restruct\Silverstripe\AdminTweaks\Logging\SymfonyMailerHandlerFactory
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

# SMTP server (Mailgun example)
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

1. Check SMTP credentials are correct
2. Verify port is not blocked by firewall
3. Check `APP_SYSTEM_EMAIL_ADDRESS` is set
4. Review SilverStripe logs for mailer errors

### Error emails not received

1. Ensure ALL four `APP_LOG_MAIL_*` variables are set
2. Check spam folder
3. Verify sender address is allowed by SMTP provider
4. Test with lower log level (`warning` instead of `error`)

### Too many error emails

1. Increase log level to `critical`
2. Use DeduplicationHandler to prevent duplicates
3. Consider using external error tracking (Sentry, Bugsnag)

### Disabling the error email handler from a project

If you need to disable the error email handler without removing the env vars, override the
handler definitions in your project config:

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
  DeduplicatingMailHandler:
    class: Monolog\Handler\NullHandler
  MailHandler:
    class: Monolog\Handler\NullHandler
```

---

## Changelog

### 3.7.1

- **Fixed:** SymfonyMailerHandler now uses `bubble: false` to prevent the EnhancedErrorFormatter
  HTML from leaking into web responses via SilverStripe's HTTPOutputHandler. Previously, the
  formatted email HTML was set on `$record->formatted` and propagated to downstream handlers,
  corrupting AJAX/JSON responses with HTML error email content.
- **Removed:** ThrottledQueuedJobService — the parent QueuedJobService now handles broken job
  notification dedup natively via the `NotifiedBroken` DB flag (queuedjobs 4.x+). The reimplemented
  `checkJobHealth()` had also become stale, missing upstream fixes.
