# Template helpers

Methods and variables this module adds to templates. All of them are available on install; nothing
needs to be switched on.

## Global variables (TemplateHelpers)

Available in every template.

### $themeDirResourceURL($themeDir)

The URL of a theme's directory, for resources that are not CSS or JS:

```html
<video src="{$themeDirResourceURL('my-theme')}/video.mp4"></video>
```

### $ImagePlaceholder($W, $H, $Label = '', $AddClass = '', $DataUriBase64 = false)

A grey SVG placeholder of the given size, with an optional label and CSS class. Inline SVG by
default; with `$DataUriBase64` set, a base64 `data:` URI for use in a `src` attribute.

```html
$ImagePlaceholder(300, 200, 'Photo')
<img src="$ImagePlaceholder(300, 200, 'Photo', '', true)" alt="">
```

The same SVG as an include:

```html
<% include ImagePlaceholder W=180, H=50, Label='logo' %>
```

From PHP:

```php
use Restruct\Silverstripe\AdminTweaks\Helpers\TemplateHelpers;

$svg = TemplateHelpers::ImagePlaceholder(300, 200, 'My label', 'custom-class');
$uri = TemplateHelpers::ImagePlaceholder(300, 200, 'My label', '', true);
```

### $CurrentLocale

The current locale in RFC 1766 form (`en-US`, `nl-NL`), for `lang` attributes; also in projects
without silverstripe/cms, which provides `$ContentLocale`.

```html
<html lang="$CurrentLocale">
```

## Page helpers (PageHelpersExtension)

Applied to `SiteTree`, so available on every page and in its templates.

### Environment

```html
<% if $IsDev %>...<% end_if %>
<% if $IsTest %>...<% end_if %>
<% if $IsLive %>...<% end_if %>
```

### Request and page

| Method | Returns |
|--------|---------|
| `$BreadCrumbPath` | The menu titles from the top-level page down to this one, as text: `About > Team > Jane` |
| `$PageID_MD5` | An MD5 of the page ID, for a unique ID that does not show the database ID |
| `$RequestStatusCode` | The HTTP status code of the current response |
| `$RequestedURL` | The requested URL (path and query), stripped to safe characters |
| `$StaleCacheKey($delayInSeconds = 300)` | A number that changes every `$delayInSeconds`, for a partial-cache key that expires |
| `$RandomNumber($max = 4)` | A random integer from 1 to `$max`, for example to pick a banner variant |

```html
<% cached 'news', $StaleCacheKey(600) %>...<% end_cached %>
```

### Finding pages

| Method | Returns |
|--------|---------|
| `$HomePage` | The home page |
| `$PageById($id = 1)` | The page with this ID |
| `$PageByClass($ClassName = 'Page')` | The first record of this class |
| `$allDescendants` | All descendants of this page, in the current reading mode (`$allDescendants($parent, $childRelation)` for another parent or relation) |
| `$allStageDescendants` | All descendants including unpublished ones (read from the draft stage) |

### A to Z lists

`$TitleFirstChar` is the first letter or digit of the title, in lower case, and `$AlphabetChars` is
a list of `a` to `z` (each as `$Char`), for grouped lists:

```html
<% loop $AlphabetChars %>
  <a href="#letter-$Char">$Char</a>
<% end_loop %>
```

### Requirements from a template

| Method | Does |
|--------|------|
| `$RequireCustomCSS($css)` | Adds a block of CSS to the page head |
| `$RequireCustomTemplatedCSS($template)` | The same, after rendering `$template` as a template on this page |
| `$RequireCustomJS($script)` | Adds a script (`<script>` tags are removed) |
| `$RequireCustomTemplatedJS($template)` | The same, after rendering it as a template |
| `$RequireCombinedThemeRelativeCSS($path, $makeFirstItem = false)` | Adds a theme file to a combined `themes-styles.css` |
| `$RequireCombinedThemeRelativeJS($path, $makeFirstItem = false)` | Adds a theme file to a combined `themes-scripts.js` |

## Extra loop variables (SSViewer_ExtraIterators)

Inside any `<% loop %>`, for splitting a list into a number of groups (columns, for example). The
argument is the **number of groups**:

| Variable | Returns |
|----------|---------|
| `$GroupSize(n)` | Items per group: the item count divided by `n`, rounded up (the item count without `n`) |
| `$PosInGroup(n)` | The item's position within its group, from 1 |
| `$FirstOfGroup(n)` | Whether the item is the first of its group |
| `$LastOfGroup(n)` | Whether the item is the last of its group (`n` is required) |
| `$FirstLastOfGroup(n)` | The class `first-of-group`, `last-of-group`, both, or nothing (`n` is required) |
| `$GroupOfGroups(n)` | Which group the item is in, from 1 |

Three columns from one list:

```html
<% loop $Items %>
  <% if $FirstOfGroup(3) %><div class="column column-$GroupOfGroups(3)"><% end_if %>
    <div class="$FirstLastOfGroup(3)">$Title</div>
  <% if $LastOfGroup(3) %></div><% end_if %>
<% end_loop %>
```

## Date and time fields

### DBDatetime

```html
<!-- PHP date format, as Silverstripe 3 used (core's Format() takes CLDR) -->
$Created.LegacyFormat('d-m-Y H:i')

<!-- ISO 8601 -->
$LastEdited.Iso8601
```

| PHP format | CLDR equivalent |
|------------|-----------------|
| `d-m-Y` | `dd-MM-yyyy` |
| `Y-m-d` | `yyyy-MM-dd` |
| `d/m/Y H:i` | `dd/MM/yyyy HH:mm` |
| `F j, Y` | `MMMM d, yyyy` |

### DBTime

| Method | Returns |
|--------|---------|
| `LegacyFormat($format)` | The time in a PHP date format |
| `Time12($includeSeconds = false)` | `1:32 pm` |
| `Time24($includeSeconds = false)` | `13:32` |
| `AsSeconds` | Seconds since the start of the day |
| `FormatSecondsAsTime($seconds, $format = 'H:mm')` | A number of seconds formatted as a time (CLDR format) |
| `Ago($includeSeconds = true, $significance = 2)` | "2 hours ago" or "in 2 hours" |
| `TimeDiff($includeSeconds = true, $significance = 2)` | "2 hours" |
| `TimeDiffIn($format)` | The difference in one unit: `seconds`, `minutes`, `hours`, `days`, `months` or `years` |

## String fields

`contains($text)` on any string field (`DBString`): whether the raw value contains `$text`.

```html
<% if $Content.contains('youtube.com') %>...<% end_if %>
```

## Shortcodes

With [restruct/silverstripe-shortcodable](https://github.com/restruct/silverstripe-shortcodable)
installed, two shortcodes are registered for the HTML editor:

- `[currentyear]`: the current year, for copyright lines (the bare year, no wrapper element).
- `[featuredimage]`: the current page's `FeaturedImage`, medium (640 by 480) or full width (1000 by
  600). Only for pages whose controller has a `FeaturedImage()`; its editor labels are Dutch.

## See also

`SelectiveLumberjack`, which used to be described here, is in [GridField](gridfield.md#selectivelumberjack).
