# Icons

The module provides two icon systems for admin UI enhancement:

1. **Page Icons** - SVG icons for page types in the CMS tree (self-contained, no external dependencies)
2. **Bootstrap Icon Classes** - Standard `.bi bi-*` classes for buttons and UI elements (full CDN, opt-in)

---

## Page Icons

Stylish SVG icons appear next to page types in the CMS page tree. These are self-contained inline SVGs, requiring no external font files.

### Supported Page Types

| Page Type | Icon |
|-----------|------|
| `Page`, `SiteTree` | File |
| `HomePage` | House |
| `RedirectorPage` | Signpost |
| `VirtualPage`, `SubsitesVirtualPage` | Files (clone) |
| `UserDefinedForm` | Checklist |
| `ErrorPage` | Exclamation circle |
| `ProfilePage` | Person card |
| `CartPage` | Shopping cart |
| `AlbumPage` | Images |
| `CatalogPage` | Book |
| `CatalogItemPage` | File text |
| `PersonPage`, `SpecialistPage` | Person |
| `PersonCatalog`, `SpecialistCatalog` | People rolodex |
| `SitemapPage` | Diagram |
| `SimpleCalendar` | Calendar |
| `ArchivePage` | Archive box |
| `ContactPage` | Telephone |
| `InfoPage` | Info circle |
| `BlocksPage`, `BlockPage` | Grid |
| `VideoPage` | Play file |
| `DepartmentPage` | Building |
| `DepartmentHolder` | Signpost split |
| `ReservationPage` | Calendar check |
| `IFramePage` | Window |
| `NewsGridHolder` | Globe |
| `NewsGridPage` | Newspaper |
| `ProtectedPage` | Lock |
| `ImportedContentPage` | Download |
| `SettingsPage` | Gear |
| `DialogPage` | Chat |
| `HelpPage` | Life preserver |

Every other page type gets the default page icon (the one `Page` has), so a type such as
`FooHolder`, with neither "Page" nor "SiteTree" in its class name, no longer renders without one.

### Switching the page icons off

The page icons are on by default. To keep core's own page icons instead:

```yaml
# app/_config/admintweaks.yml
SilverStripe\Admin\LeftAndMain:
  page_icons: false
```

This leaves out admintweaks' page icon stylesheet (`client/dist/css/admintweaks-page-icons.css`)
altogether, so everything in the list above goes: the icon per page type, the default page icon for
every other type, and the replacement of core's `font-icon-*` icons. Core's icons, and any `icon`
image a page type sets, then show exactly as core renders them.

A page type's own Bootstrap Icons class ([below](#choosing-an-icon-per-page-type)) keeps working with
`page_icons: false`: that is the page type's choice, not admintweaks' icon styling, and its sizing
lives in `admintweaks.css`, which always loads.

### Choosing an icon per page type

A page type can pick its own icon with core's icon class config, using any
[Bootstrap Icon](https://icons.getbootstrap.com/) by its `bi-*` class:

```yaml
App\Pages\FooPortal:
  icon_class: 'bi-briefcase'
```

or in the class itself: `private static $icon_class = 'bi-briefcase';`

- The glyph shows in the site tree and in the "Add page" type picker, in the same size and colour
  as the SVG icons.
- It beats the class-name list above: a `QuizPage` with a `bi-*` icon class shows only that glyph,
  not the default page icon behind it.
- The Bootstrap Icons stylesheet loads automatically once any page type uses a `bi-*` icon class
  (see [Configuration](#configuration) below). With `include_bootstrap_icons: false` it does not,
  and such a page type shows no icon unless the site loads the font itself.
- Silverstripe 6 renamed the setting to `cms_icon_class` (admintweaks 4.x reads that name).
- Other icon classes, such as core's `font-icon-*`, are still replaced by the SVG icons (unless
  [`page_icons: false`](#switching-the-page-icons-off)).
- A `bi-*` icon class shows its glyph with `page_icons: false` too.
- A module page type that declares an `$icon` image (core turns it into a `background: url(...)`
  rule) shows that image instead of the SVG icon. Set `icon: ''` on the type in YAML to get the
  admintweaks icon back.

### Adding Custom Page Icons

To draw your own SVG rather than a Bootstrap Icon, add rules in your project SCSS for custom page
types. Page icons use inline SVG data URIs:

```scss
// Import the page icons partial
@import "restruct/silverstripe-admintweaks/client/src/scss/page-icons-svg";

// Use existing icon for a custom page type
li.jstree-leaf > a .jstree-pageicon, .page-icon {
  &[class*="MyCustomPage"] {
    @include page-icon-svg('file-code');
  }
}
```

Or add a completely custom SVG:

```scss
li.jstree-leaf > a .jstree-pageicon, .page-icon {
  &[class*="MyCustomPage"] {
    background-image: url("data:image/svg+xml,...your-escaped-svg...");
  }
}
```

---

## Bootstrap Icon Classes

Uses standard Bootstrap Icons class convention (`bi bi-{name}`). When enabled, the full Bootstrap Icons CSS is loaded from CDN (jsDelivr), giving you access to all 2000+ icons.

### Configuration

Bootstrap Icons are **not loaded by default**, unless a page type uses a `bi-*` icon class (see
[Choosing an icon per page type](#choosing-an-icon-per-page-type)). Enable them in your project
config:

```yaml
# app/_config/admintweaks.yml
SilverStripe\Admin\LeftAndMain:
  include_bootstrap_icons: true
```

`include_bootstrap_icons` has three states: unset (the default) loads the stylesheet only when a
page type needs it, `true` always loads it, `false` never does.

To use a different version or CDN URL:

```yaml
SilverStripe\Admin\LeftAndMain:
  include_bootstrap_icons: true
  bootstrap_icons_cdn_url: 'https://cdn.jsdelivr.net/npm/bootstrap-icons@1.13.1/font/bootstrap-icons.min.css'
```

### Quick Start

```php
// In PHP (GridField actions, FormActions, etc.)
$button = FormAction::create('doSave', 'Save')
    ->addExtraClass('bi bi-check-circle');

// Icon-only button (no text label)
$button = FormAction::create('doDelete')
    ->addExtraClass('bi bi-trash bi-icon-only');
```

```html
<!-- In templates -->
<button class="btn bi bi-plus-circle">Add Item</button>
<a href="#" class="action bi bi-pencil">Edit</a>
<span class="bi bi-star-fill"></span>
```

### Icon-Only Buttons

For buttons with no text (just an icon), add `bi-icon-only` to remove the right margin:

```html
<button class="btn btn-sm bi bi-gear bi-icon-only" title="Settings"></button>
```

---

## Resources

- [Bootstrap Icons](https://icons.getbootstrap.com/) - Full searchable icon browser
- [Bootstrap Icons GitHub](https://github.com/twbs/icons) - Source repository
- [jsDelivr CDN](https://www.jsdelivr.com/package/npm/bootstrap-icons) - CDN hosting
