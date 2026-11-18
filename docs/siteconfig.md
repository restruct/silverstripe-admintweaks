# SiteConfig extension

Extra fields on the site settings (SiteConfig) for contact details, social media, logos, a browser
theme colour, raw HTML in head and body, and a few theme choices. The fields are not rendered
anywhere by themselves: your templates output them.

## Enable the extension

The extension is not applied by default:

```yaml
SilverStripe\SiteConfig\SiteConfig:
  extensions:
    - Restruct\Silverstripe\AdminTweaks\Extensions\SiteConfigExtension
```

Then switch on the parts you need. Every option is off by default, except the container class
dropdown:

| Option | Default | Adds |
|--------|---------|------|
| `enable_contact_social_media_fields` | `false` | A Contact tab with contact details and social media links |
| `enable_logo_upload_fields` | `false` | A Logos tab with five logo uploads |
| `enable_browser_color_theme_field` | `false` | A browser theme colour on the Theme/HTML tab |
| `enable_raw_head_body_fields` | `false` | Four raw HTML fields on the Theme/HTML tab |
| `theme_container_classes` | seven Bootstrap container classes | A container class dropdown on the Theme/HTML tab |
| `theme_subnav_slots` | `false` | A dropdown to choose where a sub menu goes |
| `rearrange_access_fields` | `false` | Moves the Access fields onto the Main tab and renames it "Site" |

## Contact and social media

```yaml
SilverStripe\SiteConfig\SiteConfig:
  enable_contact_social_media_fields: true
```

Fields: `Email`, `Phone`, `Address`, `ContactPage` (a page), `Facebook`, `Instagram`, `Twitter`,
`Linkedin`, `Youtube` and `Whatsapp`.

```html
<% with $SiteConfig %>
  <% if $Email %><a href="mailto:$Email">$Email</a><% end_if %>
  <% if $Phone %><a href="tel:$Phone">$Phone</a><% end_if %>
  <% if $Address %><address>$Address</address><% end_if %>
  <% if $ContactPage %><a href="$ContactPage.Link">Contact</a><% end_if %>
  <% if $Linkedin %><a href="$Linkedin">LinkedIn</a><% end_if %>
<% end_with %>
```

## Logos

```yaml
SilverStripe\SiteConfig\SiteConfig:
  enable_logo_upload_fields: true
```

Five uploads, each a file in the `logos` folder (images and SVG): `Logo`, `ExtraLogo`, `FavIcon`,
`NavLogo` and `FooterLogo`. They are published together with the site settings.

```html
<% if $SiteConfig.Logo %><img src="$SiteConfig.Logo.URL" alt="$SiteConfig.Title"><% end_if %>
```

## Browser theme colour

The colour mobile browsers give their address bar:

```yaml
SilverStripe\SiteConfig\SiteConfig:
  enable_browser_color_theme_field: true
  # or a hex colour, which also switches it on and is shown as the suggested value:
  # enable_browser_color_theme_field: '#336699'
```

```html
<% if $SiteConfig.ThemeBrowserColor %>
  <meta name="theme-color" content="$SiteConfig.ThemeBrowserColor">
<% end_if %>
```

## Raw HTML in head and body

For analytics, tracking pixels or third-party scripts:

```yaml
SilverStripe\SiteConfig\SiteConfig:
  enable_raw_head_body_fields: true
```

Fields: `ExtraHTML_HeadStart` (after `<head>`), `ExtraHTML_HeadEnd` (before `</head>`),
`ExtraHTML_BodyStart` (after `<body>`) and `ExtraHTML_BodyEnd` (before `</body>`).

```html
<head>
  $SiteConfig.ExtraHTML_HeadStart.RAW
  <title>$Title</title>
  $SiteConfig.ExtraHTML_HeadEnd.RAW
</head>
<body>
  $SiteConfig.ExtraHTML_BodyStart.RAW
  ...
  $SiteConfig.ExtraHTML_BodyEnd.RAW
</body>
```

`.RAW` outputs the HTML unescaped, so whoever can edit the site settings can put any script on
every page. Only give that permission to people you trust with that.

## Container class

A dropdown of container classes, stored in `ThemeContainerClass`. `$SiteConfig.ContainerClass`
returns the chosen class, or the first option when none is chosen:

```html
<div class="$SiteConfig.ContainerClass">...</div>
```

The default options are `container-fluid`, `container`, `container-sm`, `container-md`,
`container-lg`, `container-xl` and `container-xxl`. A list you set in YAML is **added** to these
(Silverstripe config appends lists). To replace them, set `false` in one fragment and your list in a
later one:

```yaml
---
Name: app-siteconfig-reset
---
SilverStripe\SiteConfig\SiteConfig:
  theme_container_classes: false
---
Name: app-siteconfig
After: '#app-siteconfig-reset'
---
SilverStripe\SiteConfig\SiteConfig:
  theme_container_classes:
    - container
    - container-fluid
```

To leave the dropdown out altogether, set `theme_container_classes: false`.

## Sub menu slot

A dropdown for where a sub menu is shown, with the slots you name; the stored value is in
`ThemeSubNavSlot`, empty for "no sub menu":

```yaml
SilverStripe\SiteConfig\SiteConfig:
  theme_subnav_slots:
    - before
    - after
```

```html
<% if $SiteConfig.ThemeSubNavSlot == 'before' %><% include SubNav %><% end_if %>
```

The option labels are translated through
`Restruct\Silverstripe\AdminTweaks\Extensions\SiteConfigExtension.ThemeSubNavSlot_<slot>`.

## Full example

```yaml
SilverStripe\SiteConfig\SiteConfig:
  extensions:
    - Restruct\Silverstripe\AdminTweaks\Extensions\SiteConfigExtension
  enable_contact_social_media_fields: true
  enable_logo_upload_fields: true
  enable_browser_color_theme_field: '#336699'
  enable_raw_head_body_fields: true
  theme_subnav_slots: [before, after]
```
