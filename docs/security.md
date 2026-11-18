# Security: password policy, breached passwords and permissions

What the module changes about passwords and access, and how to adjust it. Everything on this page
applies on install except `expire_on_login` and the `EnforceCMSPermission` trait, which a project
switches on itself.

## Password policy

`_config/passwords.yml` points the `PasswordValidator` service at core's `RulesPasswordValidator`
(Silverstripe 6 made the entropy-based `EntropyPasswordValidator` the default, which has none of
the settings below):

| Environment | `MinLength` | `HistoricCount` | `MinTestScore` |
|-------------|-------------|-----------------|----------------|
| live | 12 | 6 | 3 |
| dev and test | 5 | 0 | 3 |

The relaxed policy outside live lets a local `admin:admin` work. The same live values are set as
`min_length`, `historic_count` and `min_test_score` config on `RulesPasswordValidator`, for code
that does `new RulesPasswordValidator()` instead of asking the Injector.

To change them, set the properties on the service in a fragment that loads after this module's:

```yaml
---
Name: app-password-policy
After: '#admintweaks-password-policy'
---
SilverStripe\Core\Injector\Injector:
  SilverStripe\Security\Validation\PasswordValidator:
    properties:
      MinLength: 14
```

The dev/test fragment is named `admintweaks-dev-password-policy`; add it to `After:` as well if
your change must apply outside live too.

## Breached passwords (Have I Been Pwned)

A new password that occurs in the [Have I Been Pwned](https://haveibeenpwned.com/Passwords) breach
data is refused, wherever a password is set: the member form in the Security admin, the
change-password form and the reset form. The member sees "This password appears in a list of
passwords exposed in data breaches. Please choose a different password."

How the lookup works:

- **k-anonymity**: only the first 5 characters of the password's SHA-1 hash are sent to the
  [range API](https://haveibeenpwned.com/API/v3#PwnedPasswords). The API answers with every hash
  suffix that shares that prefix and the match is made on your server. Neither the password nor
  its full hash leaves the server. The request asks for padded responses, so the response size
  says nothing about the prefix.
- **Fails open**: when the API cannot be reached in time, the password is accepted and a warning
  is logged. An outage at HIBP never stops anyone from setting a password or logging in.
- **Works with any validator**: the check hangs on the `updateValidatePassword` hook of the
  abstract `PasswordValidator`, so it also runs when a project configures the entropy validator.
- **Off outside live**: `enabled` is `false` on dev and test, so a dev `admin:admin` is not
  refused and no request goes out from a developer machine.

### Settings

On `Restruct\Silverstripe\AdminTweaks\Security\BreachedPasswords`:

| Setting | Default | What it does |
|---------|---------|--------------|
| `enabled` | `true` (`false` outside live) | Master switch for both checks |
| `min_breach_count` | `1` | A password counts as breached from this many occurrences in the data |
| `expire_on_login` | `false` | Also check the password a member logs in with (below) |
| `api_url` | `https://api.pwnedpasswords.com/range/` | The range API endpoint |
| `http_connect_timeout` | `2` | Seconds; kept short because the lookup runs inside a form submission |
| `http_timeout` | `4` | Seconds |

```yaml
Restruct\Silverstripe\AdminTweaks\Security\BreachedPasswords:
  min_breach_count: 10
  expire_on_login: true
```

To switch the check off on live as well:

```yaml
Restruct\Silverstripe\AdminTweaks\Security\BreachedPasswords:
  enabled: false
```

### Expiring a breached password at login

New passwords are refused when they are set, but a password set before the check existed stays
valid. With `expire_on_login: true`, the password a member logs in with is looked up as well. When
it is breached, its expiry date is set to today, so core sends the member to the change-password
form straight after this login (also behind MFA), where another breached password is refused.
A notice naming the member ID is logged.

This adds an outbound request to every successful login, which is why it is opt-in. Only a form
login posts a password; other authenticators (single sign-on, for example) are skipped.

## Secure session cookies

`Session.cookie_secure` is set to `true`, so the session cookie is only sent over HTTPS. A site
that must run over plain HTTP sets it back to `false`.

## EnforceCMSPermission

A trait that ties a DataObject's `canView`, `canEdit`, `canDelete` and `canCreate` to **Pages
section access**: each returns `Permission::check('CMS_ACCESS_CMSMain')` for the **current user**.

```php
use Restruct\Silverstripe\AdminTweaks\Traits\EnforceCMSPermission;

class MyDataObject extends DataObject
{
    use EnforceCMSPermission;
}
```

The `$member` argument is ignored, so `canEdit($someOtherMember)` still answers for whoever is
logged in. Holders of `CMS_ACCESS_LeftAndMain` (all CMS sections) and `ADMIN` pass as well, as
`Permission::check()` treats those as implying `CMS_ACCESS_CMSMain`.

## Permission codes in the Security admin

On a group's Permissions tab, every permission checkbox shows its permission code as a badge, so
similarly named permissions can be told apart. Nothing to configure; see the
[README](../README.md#permission-badges) for a screenshot.
