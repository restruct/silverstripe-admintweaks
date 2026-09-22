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

### Adding Custom Page Icons

Add rules in your project SCSS for custom page types. Page icons use inline SVG data URIs:

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

Bootstrap Icons are **disabled by default**. Enable them in your project config:

```yaml
# app/_config/admintweaks.yml
SilverStripe\Admin\LeftAndMain:
  include_bootstrap_icons: true
```

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
