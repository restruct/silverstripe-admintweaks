# Template Helpers

The module provides extensive template helper methods through extensions and global providers.

## PageHelpersExtension

Adds 30+ helper methods to Page (and by extension, all page types).

### Enable

Applied automatically to `Page` class. No configuration needed.

### Environment Methods

```html
<% if $IsDev %>
  <div class="debug-bar">Development Mode</div>
<% end_if %>

<% if $IsLive %>
  <!-- Production analytics -->
<% end_if %>

<% if $IsTest %>
  <div class="test-banner">Test Environment</div>
<% end_if %>
```

### Breadcrumb Helpers

```html
<!-- Get breadcrumb pages (excludes current and home) -->
<% loop $BreadcrumbPagesExceptHome %>
  <a href="$Link">$MenuTitle</a> /
<% end_loop %>

<!-- Check if breadcrumbs exist -->
<% if $HasBreadcrumbs %>
  <nav aria-label="breadcrumb">...</nav>
<% end_if %>
```

### Descendant Iteration

```html
<!-- All descendants (including unpublished in preview) -->
<% loop $AllDescendants %>
  $Title
<% end_loop %>

<!-- Children of a specific page -->
<% loop $ChildrenOf('about-us') %>
  <a href="$Link">$Title</a>
<% end_loop %>
```

### Cache Busting

```html
<!-- Auto cache key based on file modification time -->
<link rel="stylesheet" href="$ThemeDir/css/style.css?v=$CacheKey('css/style.css')">
```

### Current URL Checks

```html
<% if $IsCurrentSection %>
  <li class="active">$Title</li>
<% end_if %>

<% if $IsCurrent %>
  <span>$Title</span>
<% else %>
  <a href="$Link">$Title</a>
<% end_if %>
```

### Combined Requirements

```html
<!-- Combine multiple CSS files -->
$CombinedCSS('combined.css', 'css/base.css,css/layout.css,css/theme.css')

<!-- Combine multiple JS files -->
$CombinedJS('combined.js', 'js/lib.js,js/app.js')
```

### Miscellaneous

```html
<!-- Random number (useful for cache busting or unique IDs) -->
<div id="section-$RandomNumber">

<!-- Current locale info -->
<html lang="$CurrentLocale">

<!-- Last modified date -->
<p>Last updated: $LastEdited.Nice</p>
```

---

## TemplateHelpers (Global Provider)

Global template methods available on all pages.

### themeDirResourceURL()

Get the exposed resource URL for a theme directory:

```html
<!-- In templates -->
<video src="{$themeDirResourceURL('my-theme')}/videos/intro.mp4"></video>

<!-- With path -->
<img src="{$themeDirResourceURL('my-theme')}/images/logo.png">
```

Replaces the legacy `$ThemeDir` variable with proper resource URL handling.

### ImagePlaceholder()

Generate SVG placeholder images:

```html
<!-- As include (inline SVG) -->
<% include ImagePlaceholder W=300, H=200, Label='Product Image' %>

<!-- As include with class -->
<% include ImagePlaceholder W=180, H=50, Label='logo', AddClass='rounded' %>

<!-- As data URI (for img src) -->
<img src="$ImagePlaceholder(300, 200, 'Placeholder', true)" alt="Placeholder">

<!-- In PHP -->
TemplateHelpers::ImagePlaceholder(300, 200, 'My Label', false, 'custom-class');
```

**Parameters:**
1. `W` - Width in pixels
2. `H` - Height in pixels
3. `Label` - Text label (optional)
4. `AsDataUri` - Return as base64 data URI (default: false)
5. `AddClass` - Additional CSS classes

### CurrentLocale()

```html
<html lang="$CurrentLocale">
<!-- Output: <html lang="en_US"> or <html lang="nl_NL"> -->
```

---

## SSViewer_ExtraIterators

Additional iterator methods for loops, useful for creating grid layouts.

### Enable

Applied automatically. No configuration needed.

### Available Methods

```html
<% loop $Items %>
  <!-- Group size (for CSS grid classes) -->
  <div class="col-$GroupSize-of-4">

  <!-- Position within current group -->
  Position in group: $PosInGroup

  <!-- Check if first/last in group -->
  <% if $FirstOfGroup %>class="first"<% end_if %>
  <% if $LastOfGroup %>class="last"<% end_if %>

  <!-- Combined first/last check -->
  <% if $FirstLastOfGroup %>class="single"<% end_if %>
<% end_loop %>
```

### Grid Layout Example

```html
<div class="row">
  <% loop $Products %>
    <div class="col-md-{$GroupSize}">
      <div class="product-card">
        $Title
      </div>
    </div>
    <% if $LastOfGroup %></div><div class="row"><% end_if %>
  <% end_loop %>
</div>
```

### GroupOfGroups

Nest loops for complex grid structures:

```html
<% loop $Items.GroupOfGroups(4) %>
  <div class="row">
    <% loop $Items %>
      <div class="col-3">$Title</div>
    <% end_loop %>
  </div>
<% end_loop %>
```

---

## DBDatetimeExtension

Restores SilverStripe 3 / PHP datetime formatting (SS4+ uses CLDR format).

### Enable

Applied automatically to `DBDatetime` fields.

### Usage

```html
<!-- Legacy PHP format (SS3 style) -->
$Created.LegacyFormat('d-m-Y H:i')
<!-- Output: 25-12-2024 14:30 -->

<!-- ISO 8601 format -->
$LastEdited.Iso8601
<!-- Output: 2024-12-25T14:30:00+00:00 -->
```

### Format Reference

| SS3/PHP Format | CLDR Equivalent |
|----------------|-----------------|
| `d-m-Y` | `dd-MM-yyyy` |
| `Y-m-d` | `yyyy-MM-dd` |
| `d/m/Y H:i` | `dd/MM/yyyy HH:mm` |
| `F j, Y` | `MMMM d, yyyy` |

Use `LegacyFormat()` when migrating SS3 templates or when PHP format strings are more convenient.

---

## SelectiveLumberjack

Enhanced Lumberjack extension that respects `hide_from_cms_tree` configuration.

### Problem Solved

Standard Lumberjack hides all child pages in the tree. SelectiveLumberjack:
- Respects `hide_from_cms_tree: true` config on page types
- Does NOT filter the listview (shows all children in grid)
- Works with existing Lumberjack configuration

### Usage

```yaml
# Instead of Lumberjack
MyNamespace\Pages\BlogHolder:
  extensions:
    - Restruct\Silverstripe\AdminTweaks\Extensions\SelectiveLumberjack
```

```php
// On child pages you want hidden from tree
class BlogPost extends Page
{
    private static $hide_from_cms_tree = true;
}
```
