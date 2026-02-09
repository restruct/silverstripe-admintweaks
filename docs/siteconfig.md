# SiteConfig Extension

The SiteConfigExtension adds optional fields for common site-wide settings. All features are opt-in via configuration.

## Enable the Extension

```yaml
SilverStripe\SiteConfig\SiteConfig:
  extensions:
    - Restruct\Silverstripe\AdminTweaks\Extensions\SiteConfigExtension
```

## Available Features

All features are enabled by default when the extension is active. Disable individually:

```yaml
SilverStripe\SiteConfig\SiteConfig:
  extensions:
    - Restruct\Silverstripe\AdminTweaks\Extensions\SiteConfigExtension

  # Disable specific features
  enable_contact_social_media_fields: false
  enable_raw_head_body_fields: false
  enable_browser_color_theme_field: false
  enable_subnav_activation_field: false
  rearrange_access_fields: false
  theme_container_classes: false
```

---

## Contact & Social Media Fields

Enable contact information and social media links in SiteConfig.

```yaml
SilverStripe\SiteConfig\SiteConfig:
  enable_contact_social_media_fields: true
```

### Available Fields

**Contact Information:**
- `ContactEmail` - Email address
- `ContactPhone` - Phone number
- `ContactAddress` - Physical address

**Social Media:**
- `FacebookURL` - Facebook page URL
- `TwitterURL` - Twitter/X profile URL
- `InstagramURL` - Instagram profile URL
- `LinkedInURL` - LinkedIn page URL
- `YouTubeURL` - YouTube channel URL

### Template Usage

```html
<footer>
  <% with $SiteConfig %>
    <% if $ContactEmail %>
      <a href="mailto:$ContactEmail">$ContactEmail</a>
    <% end_if %>

    <% if $ContactPhone %>
      <a href="tel:$ContactPhone">$ContactPhone</a>
    <% end_if %>

    <div class="social-links">
      <% if $FacebookURL %><a href="$FacebookURL">Facebook</a><% end_if %>
      <% if $TwitterURL %><a href="$TwitterURL">Twitter</a><% end_if %>
      <% if $InstagramURL %><a href="$InstagramURL">Instagram</a><% end_if %>
    </div>
  <% end_with %>
</footer>
```

---

## Raw HTML Fields

Inject custom HTML into page head/body. Useful for analytics, tracking pixels, or third-party scripts.

```yaml
SilverStripe\SiteConfig\SiteConfig:
  enable_raw_head_body_fields: true
```

### Available Fields

- `ExtraHTML_HeadStart` - After opening `<head>` tag
- `ExtraHTML_HeadEnd` - Before closing `</head>` tag
- `ExtraHTML_BodyStart` - After opening `<body>` tag
- `ExtraHTML_BodyEnd` - Before closing `</body>` tag

### Template Usage

```html
<!DOCTYPE html>
<html>
<head>
  $SiteConfig.ExtraHTML_HeadStart.RAW

  <title>$Title</title>
  <!-- ... other head content ... -->

  $SiteConfig.ExtraHTML_HeadEnd.RAW
</head>
<body>
  $SiteConfig.ExtraHTML_BodyStart.RAW

  <!-- ... page content ... -->

  $SiteConfig.ExtraHTML_BodyEnd.RAW
</body>
</html>
```

> **Security Note:** Use `.RAW` to output unescaped HTML. Only trusted administrators should have access to edit these fields.

---

## Browser Theme Color

Set the browser chrome color for mobile browsers.

```yaml
SilverStripe\SiteConfig\SiteConfig:
  enable_browser_color_theme_field: true
```

### Template Usage

```html
<head>
  <% if $SiteConfig.BrowserThemeColor %>
    <meta name="theme-color" content="$SiteConfig.BrowserThemeColor">
  <% end_if %>
</head>
```

---

## Subnav Activation Field

Control sub-navigation display per-site.

```yaml
SilverStripe\SiteConfig\SiteConfig:
  enable_subnav_activation_field: true
```

### Template Usage

```html
<% if $SiteConfig.ShowSubnav %>
  <nav class="subnav">
    <% loop $Children %>
      <a href="$Link">$MenuTitle</a>
    <% end_loop %>
  </nav>
<% end_if %>
```

---

## Access Fields Rearrangement

Move the "Access" tab fields to the main tab for easier access.

```yaml
SilverStripe\SiteConfig\SiteConfig:
  rearrange_access_fields: true  # default
```

Set to `false` to keep the default SilverStripe tab structure.

---

## Theme Container Classes

Configure Bootstrap container classes for use in templates.

```yaml
SilverStripe\SiteConfig\SiteConfig:
  theme_container_classes:
    - container
    - container-fluid
    - container-lg
```

Or disable entirely:

```yaml
SilverStripe\SiteConfig\SiteConfig:
  theme_container_classes: false
```

### Template Usage

```html
<div class="$SiteConfig.ContainerClass">
  $Content
</div>
```

---

## Logo Fields

The extension may include logo upload fields (check current implementation):

```html
<% if $SiteConfig.Logo %>
  <img src="$SiteConfig.Logo.URL" alt="$SiteConfig.Title">
<% end_if %>

<% if $SiteConfig.LogoInverted %>
  <!-- For dark backgrounds -->
  <img src="$SiteConfig.LogoInverted.URL" alt="$SiteConfig.Title">
<% end_if %>
```

---

## Full Configuration Example

```yaml
SilverStripe\SiteConfig\SiteConfig:
  extensions:
    - Restruct\Silverstripe\AdminTweaks\Extensions\SiteConfigExtension

  # Enable all features
  enable_contact_social_media_fields: true
  enable_raw_head_body_fields: true
  enable_browser_color_theme_field: true
  enable_subnav_activation_field: true
  rearrange_access_fields: true

  # Bootstrap containers
  theme_container_classes:
    - container
    - container-fluid
```
