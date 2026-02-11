# Icons & Bootstrap Icon Classes

The module provides two icon systems for admin UI enhancement:

1. **Page Icons** - Icons for page types in the CMS tree
2. **Bootstrap Icon Utility Classes** - `.bs-icon-*` classes for buttons and UI elements

## Page Icons

Stylish icons appear next to page types in the CMS page tree. **Bootstrap Icons** are used by default (smaller file size, consistent style). FontAwesome 7 is available as a fallback.

### Supported Page Types

The following page types have icons out of the box:

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

### Switching to FontAwesome

The icon provider is set at SCSS compile time. To use FontAwesome instead:

**Option 1: Override in custom SCSS**
```scss
$icon-provider: 'fontawesome';
@import 'restruct/silverstripe-admintweaks/client/src/scss/admintweaks';
```

**Option 2: Edit source directly**
Change line 213 in `client/src/scss/admintweaks.scss`:
```scss
$icon-provider: 'fontawesome' !default;
```

Then rebuild: `npm run production`

### Adding Custom Page Icons

Add rules in your project SCSS for custom page types:

```scss
// Using Bootstrap Icons (default)
li.jstree-leaf > a .jstree-pageicon, .page-icon {
  &[class*="MyCustomPage"]:before {
    font-family: 'bootstrap-icons' !important;
    font-weight: normal !important;
    content: '\f3a5'; // bi-file-earmark-code
    width: 1.2rem;
    font-size: 1.2rem;
    text-align: center;
    display: block;
  }
}
```

Find icon codes at [Bootstrap Icons](https://icons.getbootstrap.com/).

---

## Bootstrap Icon Utility Classes

The `.bs-icon-*` classes mirror SilverStripe's `.font-icon-*` pattern but use Bootstrap Icons. Use them on buttons, links, and other admin UI elements.

### Usage in PHP

```php
use SilverStripe\Forms\FormAction;
use SilverStripe\Forms\GridField\GridFieldAddNewButton;

// FormAction with icon
$button = FormAction::create('doAdd', 'Add Item')
    ->addExtraClass('bs-icon-plus-circle');

// GridField add button
$addButton = GridFieldAddNewButton::create('buttons-before-left')
    ->setButtonName('Add Record');
$addButton->addExtraClass('bs-icon-plus-circle-fill');

// Any element
$field->addExtraClass('bs-icon-pencil');
```

### Usage in Templates

```html
<!-- Button with icon -->
<a href="$Link" class="btn btn-primary bs-icon-plus-circle">Add Item</a>

<!-- Danger button -->
<button type="button" class="btn btn-danger bs-icon-trash">Delete</button>

<!-- Icon-only button (no text) -->
<button class="btn btn-outline-secondary bs-icon-gear bs-icon-only" title="Settings"></button>

<!-- Link with icon -->
<a href="$EditLink" class="action bs-icon-pencil">Edit</a>
```

### Available Icon Classes

#### Actions
| Class | Icon |
|-------|------|
| `bs-icon-plus` | Plus |
| `bs-icon-plus-lg` | Plus (large) |
| `bs-icon-plus-circle` | Plus in circle |
| `bs-icon-plus-circle-fill` | Plus in filled circle |
| `bs-icon-plus-square` | Plus in square |
| `bs-icon-dash` | Minus/dash |
| `bs-icon-dash-circle` | Minus in circle |
| `bs-icon-x` | X/close |
| `bs-icon-x-lg` | X (large) |
| `bs-icon-x-circle` | X in circle |
| `bs-icon-x-circle-fill` | X in filled circle |
| `bs-icon-check` | Checkmark |
| `bs-icon-check-lg` | Checkmark (large) |
| `bs-icon-check-circle` | Check in circle |
| `bs-icon-check-circle-fill` | Check in filled circle |

#### Edit/CRUD
| Class | Icon |
|-------|------|
| `bs-icon-pencil` | Pencil |
| `bs-icon-pencil-fill` | Pencil (filled) |
| `bs-icon-pencil-square` | Pencil in square |
| `bs-icon-trash` | Trash can |
| `bs-icon-trash-fill` | Trash (filled) |
| `bs-icon-trash3` | Trash (alternate) |
| `bs-icon-copy` | Copy/clipboard |
| `bs-icon-clipboard` | Clipboard |
| `bs-icon-clipboard-check` | Clipboard with check |

#### Navigation
| Class | Icon |
|-------|------|
| `bs-icon-arrow-left` | Left arrow |
| `bs-icon-arrow-right` | Right arrow |
| `bs-icon-arrow-up` | Up arrow |
| `bs-icon-arrow-down` | Down arrow |
| `bs-icon-chevron-left` | Left chevron |
| `bs-icon-chevron-right` | Right chevron |
| `bs-icon-chevron-up` | Up chevron |
| `bs-icon-chevron-down` | Down chevron |
| `bs-icon-caret-left` | Left caret |
| `bs-icon-caret-right` | Right caret |
| `bs-icon-caret-up` | Up caret |
| `bs-icon-caret-down` | Down caret |

#### Files & Folders
| Class | Icon |
|-------|------|
| `bs-icon-file` | File |
| `bs-icon-file-earmark` | File with corner |
| `bs-icon-file-text` | Text file |
| `bs-icon-file-earmark-text` | Text file with corner |
| `bs-icon-file-pdf` | PDF file |
| `bs-icon-file-code` | Code file |
| `bs-icon-folder` | Folder |
| `bs-icon-folder-fill` | Folder (filled) |
| `bs-icon-folder-plus` | Folder with plus |

#### Media
| Class | Icon |
|-------|------|
| `bs-icon-image` | Image |
| `bs-icon-images` | Multiple images |
| `bs-icon-camera` | Camera |
| `bs-icon-film` | Film strip |
| `bs-icon-play` | Play button |
| `bs-icon-play-fill` | Play (filled) |
| `bs-icon-play-circle` | Play in circle |

#### UI Elements
| Class | Icon |
|-------|------|
| `bs-icon-search` | Magnifying glass |
| `bs-icon-filter` | Filter |
| `bs-icon-sort-down` | Sort descending |
| `bs-icon-sort-up` | Sort ascending |
| `bs-icon-list` | List |
| `bs-icon-list-ul` | Bulleted list |
| `bs-icon-grid` | Grid |
| `bs-icon-grid-3x3` | 3x3 grid |
| `bs-icon-three-dots` | Horizontal dots |
| `bs-icon-three-dots-vertical` | Vertical dots |

#### Status & Alerts
| Class | Icon |
|-------|------|
| `bs-icon-info` | Info |
| `bs-icon-info-circle` | Info in circle |
| `bs-icon-info-circle-fill` | Info in filled circle |
| `bs-icon-question` | Question mark |
| `bs-icon-question-circle` | Question in circle |
| `bs-icon-exclamation` | Exclamation |
| `bs-icon-exclamation-circle` | Exclamation in circle |
| `bs-icon-exclamation-triangle` | Warning triangle |
| `bs-icon-bell` | Bell |
| `bs-icon-bell-fill` | Bell (filled) |

#### People
| Class | Icon |
|-------|------|
| `bs-icon-person` | Person |
| `bs-icon-person-fill` | Person (filled) |
| `bs-icon-person-plus` | Add person |
| `bs-icon-person-dash` | Remove person |
| `bs-icon-people` | People |
| `bs-icon-people-fill` | People (filled) |

#### Settings & Tools
| Class | Icon |
|-------|------|
| `bs-icon-gear` | Gear |
| `bs-icon-gear-fill` | Gear (filled) |
| `bs-icon-sliders` | Sliders |
| `bs-icon-wrench` | Wrench |
| `bs-icon-tools` | Tools |

#### Communication
| Class | Icon |
|-------|------|
| `bs-icon-envelope` | Envelope |
| `bs-icon-envelope-fill` | Envelope (filled) |
| `bs-icon-chat` | Chat bubble |
| `bs-icon-chat-dots` | Chat with dots |
| `bs-icon-send` | Send/paper plane |
| `bs-icon-telephone` | Telephone |

#### Links & External
| Class | Icon |
|-------|------|
| `bs-icon-link` | Link |
| `bs-icon-link-45deg` | Angled link |
| `bs-icon-box-arrow-up-right` | External link |
| `bs-icon-share` | Share |
| `bs-icon-share-fill` | Share (filled) |

#### Data & Sync
| Class | Icon |
|-------|------|
| `bs-icon-download` | Download |
| `bs-icon-upload` | Upload |
| `bs-icon-cloud-download` | Cloud download |
| `bs-icon-cloud-upload` | Cloud upload |
| `bs-icon-database` | Database |
| `bs-icon-database-check` | Database with check (convert/save) |
| `bs-icon-arrow-repeat` | Sync/refresh |
| `bs-icon-arrow-clockwise` | Refresh clockwise |

#### Security
| Class | Icon |
|-------|------|
| `bs-icon-lock` | Lock |
| `bs-icon-lock-fill` | Lock (filled) |
| `bs-icon-unlock` | Unlock |
| `bs-icon-unlock-fill` | Unlock (filled) |
| `bs-icon-shield` | Shield |
| `bs-icon-shield-check` | Shield with check |
| `bs-icon-key` | Key |

#### Time & Calendar
| Class | Icon |
|-------|------|
| `bs-icon-calendar` | Calendar |
| `bs-icon-calendar-plus` | Calendar with plus |
| `bs-icon-calendar-check` | Calendar with check |
| `bs-icon-clock` | Clock |
| `bs-icon-clock-fill` | Clock (filled) |
| `bs-icon-stopwatch` | Stopwatch |

#### Miscellaneous
| Class | Icon |
|-------|------|
| `bs-icon-star` | Star |
| `bs-icon-star-fill` | Star (filled) |
| `bs-icon-stars` | Stars/sparkles (AI) |
| `bs-icon-heart` | Heart |
| `bs-icon-heart-fill` | Heart (filled) |
| `bs-icon-bookmark` | Bookmark |
| `bs-icon-bookmark-fill` | Bookmark (filled) |
| `bs-icon-tag` | Tag |
| `bs-icon-tags` | Tags |
| `bs-icon-eye` | Eye |
| `bs-icon-eye-slash` | Eye with slash |
| `bs-icon-printer` | Printer |
| `bs-icon-house` | House |
| `bs-icon-globe` | Globe |
| `bs-icon-box` | Box |
| `bs-icon-archive` | Archive |

### Icon-Only Buttons

For buttons with no text (just an icon), add `bs-icon-only` to remove the right margin:

```html
<button class="btn btn-sm bs-icon-gear bs-icon-only" title="Settings"></button>
```

### Custom Icon Class

To use an icon not in the predefined list, add custom CSS:

```scss
.bs-icon-custom:before {
  font-family: 'bootstrap-icons' !important;
  content: '\f123'; // Unicode from Bootstrap Icons website
}
```

---

## Icon Font Files

The module includes both icon fonts:

| Font | Location | Size |
|------|----------|------|
| Bootstrap Icons | `client/fonts/bootstrap-icons/` | ~130 KB (woff2) |
| FontAwesome 7 Free | `client/fonts/fontawesome-free-7.1.0-web/` | ~113 KB (woff2) |

Both are loaded by default to support fallback scenarios, but only one is used based on configuration.
