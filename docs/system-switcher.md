# CMS system switcher

For organisations running several Silverstripe CMSes side by side (for example a public site, a
document system and an e-learning platform): the admin menu header names the CMS you are in, with
the environment, and opens a dropdown with links to the other CMSes.

## Configuration

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

| Setting | Default | What it does |
|---------|---------|--------------|
| `systems` | `[]` | The CMSes, keyed; each takes `display`, `url` and optionally `initials`. The switcher is off while this is empty |
| `current` | `null` | The key of the CMS this config is loaded in |
| `environment_suffixes` | `dev: ' (DEV)'`, `test: ' (TEST)'`, `live: ''` | Suffix after the name, per environment type |

## What it shows

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
  badge under them; set `initials` on a system to choose them yourself, for example
  `site: { display: 'Example SITE', initials: 'SI', url: ... }`.

## Other suffixes

The suffix strings are configurable per environment type (the admin title uses them as written,
the header badge as described above):

```yaml
Restruct\Silverstripe\AdminTweaks\SystemSwitcher:
  environment_suffixes:
    test: ' [acceptance]'
```

## Notes

- **With [restruct/silverstripe-login-branding](https://github.com/restruct/silverstripe-login-branding)**
  and its `application_name_overrides_title`: works the same, the switcher shows its own label in
  the header either way. To keep one place for the name there, set `LeftAndMain.application_name`
  and leave `display` out for the current system (it defaults to `application_name`).
- Each system keeps its own login; the switcher only links.
- The header template is an override of core's
  `SilverStripe/Admin/Includes/LeftAndMain_MenuLogo.ss`; a theme or project that overrides that
  include as well takes precedence, and the switcher then does not show.
