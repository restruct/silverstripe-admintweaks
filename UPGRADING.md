# Upgrading

## 3.x -> 5.0

**5.0 is the Silverstripe 6 line.** Silverstripe 4 and 5 stay on the 3.x line, which continues to
get security and bug fixes until Silverstripe 5 reaches end of life (April 2027). Nothing in 5.0
is backported, and 3.x is not installable on Silverstripe 6.

```
composer require restruct/silverstripe-admintweaks:^5   # Silverstripe 6
composer require restruct/silverstripe-admintweaks:^3   # Silverstripe 4 / 5
```

Requirements changed with the line: PHP `^8.3` (was `^7.4 | ^8`), `silverstripe/framework ^6`.

### Coming from 4.0.x instead?

A `4.0.x` exists on Packagist from an earlier, abandoned Silverstripe 6 attempt. It shipped without
tests, with one of the six tasks, and with `symbiote/silverstripe-gridfieldextensions` as a hard
requirement. `5.0` restores the full module, and that dependency is **optional** again - if your
project used it only because admintweaks pulled it in, require it explicitly:

```
composer require symbiote/silverstripe-gridfieldextensions
```

Everything else below applies to you too, except the menu-hiding change: `4.0.x` did not hide those
sections either, so `5.0` behaves the same way for you.

---

### 1. Reports and Campaigns are no longer hidden from the CMS menu

**This is the one change that alters what your editors see, so it is first.**

Up to 3.x this module hid the **Reports** and **Campaigns** sections by shipping
`ignore_menuitem: true` for their controllers. That is now **opt-in** ([#54]).

If you want them hidden, say so once:

```yaml
SilverStripe\Admin\LeftAndMain:
  hide_rarely_used_menu_sections: true
```

If you are happy with them visible, set it to `false` explicitly. Either value silences the
one-off dev-mode notice that 5.0 logs for projects that have not decided:

```yaml
SilverStripe\Admin\LeftAndMain:
  hide_rarely_used_menu_sections: false
```

**If your project already carries a workaround, remove it.** Under 3.x the documented override
(`ignore_menuitem: false`) only worked if the project's own config fragment also carried
`After: '#admintweaks-leftandmain'` - same-key scalar config is decided by fragment *order*, not by
project-beats-vendor, so without it the override silently lost. That whole dance is now
unnecessary: 5.0 applies the setting at runtime, so a project that sets `ignore_menuitem` itself
always wins.

```yaml
# 3.x workaround - no longer needed in 4.0, delete it
---
Name: myproject-reports-menu
After:
  - '#admintweaks-leftandmain'
---
SilverStripe\Reports\ReportAdmin:
  ignore_menuitem: false
```

---

### 2. Tasks are console commands now, so their invocation changed

Silverstripe 6 rebuilt `BuildTask` on `symfony/console`, so every task in this module takes
**declared options** instead of query/request variables. The legacy `dev/tasks/...` URL form is
deprecated framework-wide; these are the spellings to use.

| 3.x | 5.0 |
|---|---|
| `sake dev/tasks/fix-folder-filefilename apply=1` | `sake tasks:fix-folder-filefilename --apply` |
| `sake dev/tasks/fix-misclassified-images apply=1` | `sake tasks:fix-misclassified-images --apply` |
| `sake dev/tasks/generate-cms-thumbnails apply=1` | `sake tasks:generate-cms-thumbnails --apply` |
| `sake dev/tasks/qjob-profile class=My\Jobs\SyncJob maxcalls=50` | `sake tasks:qjob-profile --class='My\Jobs\SyncJob' --maxcalls=50` |
| `sake dev/tasks/FocusPointInvertYaxisTask` | `sake tasks:focuspoint-invert-yaxis` |
| `sake dev/tasks/orm-query class=Member limit=5` | `sake tasks:orm-query --class=Member --limit=5` |

Two `orm-query` options changed shape rather than just spelling:

| 3.x | 5.0 | why |
|---|---|---|
| `"filter[Email:PartialMatch]=example"` | `--filter='Email:PartialMatch=example'` | symfony/console has no bracket notation; the option is repeatable instead |
| `groupBy=Type` | `--group-by=Type` | console options are kebab-case |

**Check your crons and deployment scripts.** A cron still calling the old form will fail rather
than silently do the wrong thing, but it will fail on the schedule, not when you upgrade.

---

### 3. SMTP configuration

The `APP_SMTP_HOST` / `APP_SMTP_PORT` / `APP_SMTP_ENCRYPTION` / `APP_SMTP_USERNAME` /
`APP_SMTP_PASSWORD` variables did nothing from Silverstripe 5 onwards (SwiftMailer was replaced by
Symfony Mailer), and the fragment that read them is removed on this line. Use `MAILER_DSN`:

```
MAILER_DSN="smtp://user:pass@smtp.example.com:587"
```

---

### 4. Password policy config moved with the framework

Nothing to do unless your project overrides this module's password settings. Silverstripe 6 moved
`PasswordValidator` into `SilverStripe\Security\Validation` and made
`EntropyPasswordValidator` the default - which has no `MinLength` / `HistoricCount` /
`MinTestScore`. This module keeps its rule-based policy by pointing the service at
`RulesPasswordValidator`. A project override keyed to the old
`SilverStripe\Security\PasswordValidator` will configure nothing, with no error; run
`sake config:audit` to find any.

---

### 5. Removed on this line

| Removed | Why / what to use |
|---|---|
| `Email\CliSafeMailerSubscriber` and `_config/mailer.yml` | It backported a Silverstripe 6 fix. The `$CurrentPageURL` rewrite it patched does not exist in Silverstripe 6 at all. |
| The SwiftMailer SMTP config fragment | See section 3. |

---

### 6. Optional-dependency safety (also worth knowing on 3.x)

`MultivalueSortField` and `GridFieldConfig_VersionedOrderable` extend classes from modules this
module only *suggests*. They now guard on the parent existing, as `SelectiveLumberjack` and
`GridFieldSiteTreeAddNewButton` already did.

This was not a dormant problem: `silverstripe/config`'s `PrivateStaticTransformer` calls
`class_exists()` on every class in the manifest during bootstrap, which autoloads the file and
fatals the **entire application** - CMS, front end and CLI - on any install without those optional
modules. If you are staying on 3.x and do not have `symbiote/silverstripe-multivaluefield` and
`symbiote/silverstripe-gridfieldextensions` installed, this is worth backporting.

[#54]: https://github.com/restruct/silverstripe-admintweaks/issues/54
