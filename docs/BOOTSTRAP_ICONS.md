# Bootstrap Icons for SilverStripe Admin

This module provides a curated selection of [Bootstrap Icons](https://icons.getbootstrap.com/) for use in the SilverStripe CMS admin interface.

## Overview

- **~550 hand-picked icons** for CMS/admin use (vs ~2000+ in full set)
- **Font loaded from CDN** (jsDelivr) for browser caching
- **CSS classes**: `.bs-icon-{name}` (mirrors SilverStripe's `.font-icon-*` pattern)
- **Configurable**: Enable/disable via YAML config

## Quick Start

Use Bootstrap Icons on buttons, links, and other elements:

```php
// In PHP (GridField actions, FormActions, etc.)
$button = FormAction::create('doSave', 'Save')
    ->addExtraClass('bs-icon-check-circle');

// Icon-only button (no text label)
$button = FormAction::create('doDelete')
    ->addExtraClass('bs-icon-trash bs-icon-only');
```

```html
<!-- In templates -->
<button class="btn bs-icon-plus-circle">Add Item</button>
<a href="#" class="action bs-icon-pencil">Edit</a>
<span class="bs-icon-star-fill"></span>
```

## Configuration

### Disable Bootstrap Icons

If you don't use `.bs-icon-*` classes, disable loading to save bandwidth:

```yaml
# app/_config/admintweaks.yml
SilverStripe\Admin\LeftAndMain:
  include_bs_icons: false
```

### Use Full CDN Set Instead

If you need access to ALL Bootstrap Icons (~2000+), disable the curated set and load the full CDN version:

```yaml
# app/_config/admintweaks.yml
SilverStripe\Admin\LeftAndMain:
  include_bs_icons: false
  extra_requirements_css:
    - 'https://cdn.jsdelivr.net/npm/bootstrap-icons@1.13.1/font/bootstrap-icons.min.css'
```

Note: Full set is ~90KB vs ~50KB for curated set.

## Adding Custom Icons

If you need an icon not in the curated set, add it in your app's SCSS:

```scss
// app/client/src/scss/app.scss

// Option 1: Import the map and use the mixin (recommended)
@import "~bootstrap-icons/font/bootstrap-icons-map";

@mixin bs-icon($name) {
  .bs-icon-#{$name}:before {
    content: map-get($bootstrap-icons-map, $name);
  }
}

@include bs-icon('rocket');
@include bs-icon('airplane');
@include bs-icon('emoji-sunglasses');

// Option 2: Manual unicode (find codes at icons.getbootstrap.com)
.bs-icon-rocket:before {
  content: "\f511";
}
```

## Icon Reference

All icons below are available as `.bs-icon-{name}` classes.

### Actions - Add/Create/Remove
| Icon Name | Icon Name | Icon Name | Icon Name |
|-----------|-----------|-----------|-----------|
| plus | plus-lg | plus-circle | plus-circle-fill |
| plus-square | plus-square-fill | dash | dash-lg |
| dash-circle | dash-circle-fill | dash-square | x |
| x-lg | x-circle | x-circle-fill | x-square |
| x-square-fill | | | |

### Actions - Confirm/Success/Check
| Icon Name | Icon Name | Icon Name | Icon Name |
|-----------|-----------|-----------|-----------|
| check | check-lg | check-circle | check-circle-fill |
| check-square | check-square-fill | check-all | check2 |
| check2-all | check2-circle | check2-square | patch-check |
| patch-check-fill | | | |

### Edit/CRUD
| Icon Name | Icon Name | Icon Name | Icon Name |
|-----------|-----------|-----------|-----------|
| pencil | pencil-fill | pencil-square | pen |
| pen-fill | vector-pen | brush | brush-fill |
| eraser | eraser-fill | trash | trash-fill |
| trash2 | trash2-fill | trash3 | trash3-fill |

### Clipboard/Copy
| Icon Name | Icon Name | Icon Name | Icon Name |
|-----------|-----------|-----------|-----------|
| copy | clipboard | clipboard-fill | clipboard-check |
| clipboard-check-fill | clipboard-data | clipboard-data-fill | clipboard-plus |
| clipboard-plus-fill | clipboard-minus | clipboard-minus-fill | clipboard-x |
| clipboard-x-fill | clipboard2 | clipboard2-check | clipboard2-check-fill |
| clipboard2-data | clipboard2-data-fill | clipboard-pulse | clipboard-heart |
| clipboard-heart-fill | | | |

### Navigation - Arrows
| Icon Name | Icon Name | Icon Name | Icon Name |
|-----------|-----------|-----------|-----------|
| arrow-left | arrow-right | arrow-up | arrow-down |
| arrow-left-circle | arrow-right-circle | arrow-up-circle | arrow-down-circle |
| arrow-left-square | arrow-right-square | arrow-up-square | arrow-down-square |
| arrow-bar-left | arrow-bar-right | arrow-bar-up | arrow-bar-down |
| arrow-return-left | arrow-return-right | arrow-repeat | arrow-clockwise |
| arrow-counterclockwise | arrows-move | arrows-expand | arrows-collapse |
| arrows-fullscreen | arrows-angle-expand | arrows-angle-contract | arrow-left-right |
| arrow-down-up | arrows | | |

### Navigation - Chevrons/Carets
| Icon Name | Icon Name | Icon Name | Icon Name |
|-----------|-----------|-----------|-----------|
| chevron-left | chevron-right | chevron-up | chevron-down |
| chevron-double-left | chevron-double-right | chevron-double-up | chevron-double-down |
| chevron-expand | chevron-contract | chevron-bar-left | chevron-bar-right |
| chevron-bar-up | chevron-bar-down | caret-left | caret-left-fill |
| caret-right | caret-right-fill | caret-up | caret-up-fill |
| caret-down | caret-down-fill | | |

### Files - Generic
| Icon Name | Icon Name | Icon Name | Icon Name |
|-----------|-----------|-----------|-----------|
| file | file-fill | file-earmark | file-earmark-fill |
| file-text | file-text-fill | file-earmark-text | file-earmark-text-fill |
| file-richtext | file-richtext-fill | file-earmark-richtext | file-earmark-richtext-fill |
| file-plus | file-plus-fill | file-earmark-plus | file-earmark-plus-fill |
| file-minus | file-minus-fill | file-earmark-minus | file-earmark-minus-fill |
| file-check | file-check-fill | file-earmark-check | file-earmark-check-fill |
| file-x | file-x-fill | file-earmark-x | file-earmark-x-fill |
| file-diff | file-diff-fill | file-earmark-diff | file-earmark-diff-fill |
| files | files-alt | | |

### Files - Specific Types
| Icon Name | Icon Name | Icon Name | Icon Name |
|-----------|-----------|-----------|-----------|
| file-pdf | file-pdf-fill | file-earmark-pdf | file-earmark-pdf-fill |
| file-image | file-image-fill | file-earmark-image | file-earmark-image-fill |
| file-code | file-code-fill | file-earmark-code | file-earmark-code-fill |
| file-music | file-music-fill | file-earmark-music | file-earmark-music-fill |
| file-play | file-play-fill | file-earmark-play | file-earmark-play-fill |
| file-zip | file-zip-fill | file-earmark-zip | file-earmark-zip-fill |
| file-word | file-word-fill | file-earmark-word | file-earmark-word-fill |
| file-excel | file-excel-fill | file-earmark-excel | file-earmark-excel-fill |
| file-ppt | file-ppt-fill | file-earmark-ppt | file-earmark-ppt-fill |
| file-spreadsheet | file-spreadsheet-fill | file-earmark-spreadsheet | file-earmark-spreadsheet-fill |
| file-binary | file-binary-fill | file-earmark-binary | file-earmark-binary-fill |
| file-font | file-font-fill | file-earmark-font | file-earmark-font-fill |

### Folders
| Icon Name | Icon Name | Icon Name | Icon Name |
|-----------|-----------|-----------|-----------|
| folder | folder-fill | folder2 | folder2-open |
| folder-plus | folder-minus | folder-check | folder-x |
| folder-symlink | folder-symlink-fill | | |

### Media - Images
| Icon Name | Icon Name | Icon Name | Icon Name |
|-----------|-----------|-----------|-----------|
| image | image-fill | image-alt | images |
| card-image | camera | camera-fill | camera2 |
| camera-video | camera-video-fill | | |

### Media - Video/Audio
| Icon Name | Icon Name | Icon Name | Icon Name |
|-----------|-----------|-----------|-----------|
| film | play | play-fill | play-btn |
| play-btn-fill | play-circle | play-circle-fill | pause |
| pause-fill | pause-btn | pause-btn-fill | pause-circle |
| pause-circle-fill | stop | stop-fill | stop-btn |
| stop-btn-fill | stop-circle | stop-circle-fill | skip-start |
| skip-start-fill | skip-end | skip-end-fill | skip-backward |
| skip-backward-fill | skip-forward | skip-forward-fill | volume-up |
| volume-up-fill | volume-down | volume-down-fill | volume-mute |
| volume-mute-fill | volume-off | volume-off-fill | music-note |
| music-note-beamed | music-note-list | | |

### View - Zoom/Fullscreen
| Icon Name | Icon Name | Icon Name | Icon Name |
|-----------|-----------|-----------|-----------|
| zoom-in | zoom-out | fullscreen | fullscreen-exit |
| aspect-ratio | aspect-ratio-fill | pip | pip-fill |
| easel | easel-fill | easel2 | easel2-fill |
| easel3 | easel3-fill | | |

### UI - Search/Filter/Sort
| Icon Name | Icon Name | Icon Name | Icon Name |
|-----------|-----------|-----------|-----------|
| search | search-heart | search-heart-fill | filter |
| filter-circle | filter-circle-fill | filter-square | filter-square-fill |
| funnel | funnel-fill | sort-down | sort-down-alt |
| sort-up | sort-up-alt | sort-alpha-down | sort-alpha-down-alt |
| sort-alpha-up | sort-alpha-up-alt | sort-numeric-down | sort-numeric-down-alt |
| sort-numeric-up | sort-numeric-up-alt | | |

### UI - List/Grid/Layout
| Icon Name | Icon Name | Icon Name | Icon Name |
|-----------|-----------|-----------|-----------|
| list | list-ul | list-ol | list-check |
| list-task | list-nested | list-columns | list-columns-reverse |
| list-stars | grid | grid-fill | grid-3x2 |
| grid-3x2-gap | grid-3x2-gap-fill | grid-3x3 | grid-3x3-gap |
| grid-3x3-gap-fill | grid-1x2 | grid-1x2-fill | layout-text-sidebar |
| layout-text-sidebar-reverse | layout-text-window | layout-text-window-reverse | layout-sidebar |
| layout-sidebar-reverse | layout-split | layout-three-columns | columns |
| columns-gap | table | kanban | kanban-fill |

### UI - Menu/Options
| Icon Name | Icon Name | Icon Name | Icon Name |
|-----------|-----------|-----------|-----------|
| three-dots | three-dots-vertical | grip-horizontal | grip-vertical |
| border-all | border-center | border-inner | border-outer |
| border-width | | | |

### Status - Info/Warning/Error
| Icon Name | Icon Name | Icon Name | Icon Name |
|-----------|-----------|-----------|-----------|
| info | info-lg | info-circle | info-circle-fill |
| info-square | info-square-fill | question | question-lg |
| question-circle | question-circle-fill | question-square | question-square-fill |
| exclamation | exclamation-lg | exclamation-circle | exclamation-circle-fill |
| exclamation-square | exclamation-square-fill | exclamation-triangle | exclamation-triangle-fill |
| exclamation-octagon | exclamation-octagon-fill | exclamation-diamond | exclamation-diamond-fill |
| x-octagon | x-octagon-fill | x-diamond | x-diamond-fill |
| slash-circle | slash-circle-fill | ban | ban-fill |

### Status - Notifications
| Icon Name | Icon Name | Icon Name | Icon Name |
|-----------|-----------|-----------|-----------|
| bell | bell-fill | bell-slash | bell-slash-fill |
| app-indicator | activity | | |

### People/Users
| Icon Name | Icon Name | Icon Name | Icon Name |
|-----------|-----------|-----------|-----------|
| person | person-fill | person-circle | person-square |
| person-plus | person-plus-fill | person-dash | person-dash-fill |
| person-x | person-x-fill | person-check | person-check-fill |
| person-gear | person-lock | person-badge | person-badge-fill |
| person-lines-fill | person-vcard | person-vcard-fill | people |
| people-fill | person-walking | person-workspace | |

### Settings/Tools
| Icon Name | Icon Name | Icon Name | Icon Name |
|-----------|-----------|-----------|-----------|
| gear | gear-fill | gear-wide | gear-wide-connected |
| gears | sliders | sliders2 | sliders2-vertical |
| wrench | wrench-adjustable | wrench-adjustable-circle | wrench-adjustable-circle-fill |
| tools | hammer | screwdriver | nut |
| nut-fill | | | |

### Communication - Email
| Icon Name | Icon Name | Icon Name | Icon Name |
|-----------|-----------|-----------|-----------|
| envelope | envelope-fill | envelope-open | envelope-open-fill |
| envelope-at | envelope-at-fill | envelope-plus | envelope-plus-fill |
| envelope-check | envelope-check-fill | envelope-x | envelope-x-fill |
| envelope-exclamation | envelope-exclamation-fill | envelope-paper | envelope-paper-fill |

### Communication - Chat
| Icon Name | Icon Name | Icon Name | Icon Name |
|-----------|-----------|-----------|-----------|
| chat | chat-fill | chat-dots | chat-dots-fill |
| chat-left | chat-left-fill | chat-left-dots | chat-left-dots-fill |
| chat-left-text | chat-left-text-fill | chat-right | chat-right-fill |
| chat-right-dots | chat-right-dots-fill | chat-square | chat-square-fill |
| chat-square-dots | chat-square-dots-fill | chat-square-text | chat-square-text-fill |
| chat-text | chat-text-fill | chat-quote | chat-quote-fill |
| send | send-fill | reply | reply-fill |
| reply-all | reply-all-fill | forward | forward-fill |

### Communication - Phone
| Icon Name | Icon Name | Icon Name | Icon Name |
|-----------|-----------|-----------|-----------|
| telephone | telephone-fill | telephone-plus | telephone-plus-fill |
| telephone-minus | telephone-minus-fill | telephone-x | telephone-x-fill |
| telephone-inbound | telephone-inbound-fill | telephone-outbound | telephone-outbound-fill |
| telephone-forward | telephone-forward-fill | | |

### Links/External/Share
| Icon Name | Icon Name | Icon Name | Icon Name |
|-----------|-----------|-----------|-----------|
| link | link-45deg | unlink | box-arrow-up-right |
| box-arrow-up-left | box-arrow-down-right | box-arrow-down-left | box-arrow-right |
| box-arrow-left | box-arrow-up | box-arrow-down | box-arrow-in-up-right |
| box-arrow-in-down-left | box-arrow-in-right | box-arrow-in-left | share |
| share-fill | at | qr-code | qr-code-scan |
| upc-scan | barcode | | |

### Data - Upload/Download/Cloud
| Icon Name | Icon Name | Icon Name | Icon Name |
|-----------|-----------|-----------|-----------|
| download | upload | cloud | cloud-fill |
| cloud-download | cloud-download-fill | cloud-upload | cloud-upload-fill |
| cloud-check | cloud-check-fill | cloud-plus | cloud-plus-fill |
| cloud-minus | cloud-minus-fill | cloud-slash | cloud-slash-fill |
| cloud-arrow-up | cloud-arrow-up-fill | cloud-arrow-down | cloud-arrow-down-fill |

### Database/Storage/Server
| Icon Name | Icon Name | Icon Name | Icon Name |
|-----------|-----------|-----------|-----------|
| database | database-fill | database-check | database-fill-check |
| database-add | database-fill-add | database-dash | database-fill-dash |
| database-x | database-fill-x | database-up | database-fill-up |
| database-down | database-fill-down | database-gear | database-fill-gear |
| database-lock | database-fill-lock | database-exclamation | database-fill-exclamation |
| server | hdd | hdd-fill | hdd-stack |
| hdd-stack-fill | hdd-network | hdd-network-fill | device-hdd |
| device-hdd-fill | device-ssd | device-ssd-fill | |

### Security
| Icon Name | Icon Name | Icon Name | Icon Name |
|-----------|-----------|-----------|-----------|
| lock | lock-fill | unlock | unlock-fill |
| shield | shield-fill | shield-check | shield-fill-check |
| shield-plus | shield-fill-plus | shield-minus | shield-fill-minus |
| shield-x | shield-fill-x | shield-exclamation | shield-fill-exclamation |
| shield-lock | shield-lock-fill | shield-slash | shield-slash-fill |
| key | key-fill | fingerprint | incognito |
| safe | safe-fill | safe2 | safe2-fill |
| pass | pass-fill | passkey | passkey-fill |

### Time/Calendar
| Icon Name | Icon Name | Icon Name | Icon Name |
|-----------|-----------|-----------|-----------|
| calendar | calendar-fill | calendar2 | calendar2-fill |
| calendar3 | calendar3-fill | calendar4 | calendar-plus |
| calendar-plus-fill | calendar-minus | calendar-minus-fill | calendar-check |
| calendar-check-fill | calendar-x | calendar-x-fill | calendar-event |
| calendar-event-fill | calendar-date | calendar-date-fill | calendar-day |
| calendar-day-fill | calendar-week | calendar-week-fill | calendar-month |
| calendar-month-fill | calendar-range | calendar-range-fill | calendar-heart |
| calendar-heart-fill | clock | clock-fill | clock-history |
| alarm | alarm-fill | stopwatch | stopwatch-fill |
| hourglass | hourglass-top | hourglass-bottom | hourglass-split |

### Ratings/Rewards/Achievements
| Icon Name | Icon Name | Icon Name | Icon Name |
|-----------|-----------|-----------|-----------|
| star | star-fill | star-half | stars |
| heart | heart-fill | heart-half | heart-pulse |
| heart-pulse-fill | suit-heart | suit-heart-fill | award |
| award-fill | trophy | trophy-fill | patch-exclamation |
| patch-exclamation-fill | patch-minus | patch-minus-fill | patch-plus |
| patch-plus-fill | patch-question | patch-question-fill | hand-thumbs-up |
| hand-thumbs-up-fill | hand-thumbs-down | hand-thumbs-down-fill | emoji-smile |
| emoji-smile-fill | emoji-frown | emoji-frown-fill | emoji-neutral |
| emoji-neutral-fill | | | |

### Education/Learning
| Icon Name | Icon Name | Icon Name | Icon Name |
|-----------|-----------|-----------|-----------|
| mortarboard | mortarboard-fill | book | book-fill |
| book-half | journal | journal-text | journal-check |
| journal-plus | journal-minus | journal-x | journal-bookmark |
| journal-bookmark-fill | journals | journal-album | journal-arrow-down |
| journal-arrow-up | journal-medical | journal-richtext | |

### Bookmarks
| Icon Name | Icon Name | Icon Name | Icon Name |
|-----------|-----------|-----------|-----------|
| bookmark | bookmark-fill | bookmark-check | bookmark-check-fill |
| bookmark-plus | bookmark-plus-fill | bookmark-dash | bookmark-dash-fill |
| bookmark-x | bookmark-x-fill | bookmark-star | bookmark-star-fill |
| bookmark-heart | bookmark-heart-fill | bookmarks | bookmarks-fill |

### Text/Typography
| Icon Name | Icon Name | Icon Name | Icon Name |
|-----------|-----------|-----------|-----------|
| blockquote-left | blockquote-right | quote | text-paragraph |
| text-left | text-center | text-right | type |
| type-bold | type-italic | type-underline | type-strikethrough |
| type-h1 | type-h2 | type-h3 | |

### Tags/Labels
| Icon Name | Icon Name | Icon Name | Icon Name |
|-----------|-----------|-----------|-----------|
| tag | tag-fill | tags | tags-fill |
| pin | pin-fill | pin-angle | pin-angle-fill |
| flag | flag-fill | | |

### View/Visibility
| Icon Name | Icon Name | Icon Name | Icon Name |
|-----------|-----------|-----------|-----------|
| eye | eye-fill | eye-slash | eye-slash-fill |
| eyeglasses | binoculars | binoculars-fill | |

### Print
| Icon Name | Icon Name |
|-----------|-----------|
| printer | printer-fill |

### Location/Navigation
| Icon Name | Icon Name | Icon Name | Icon Name |
|-----------|-----------|-----------|-----------|
| house | house-fill | house-door | house-door-fill |
| building | buildings | buildings-fill | globe |
| globe2 | geo | geo-alt | geo-alt-fill |
| geo-fill | pin-map | pin-map-fill | signpost |
| signpost-fill | signpost-2 | signpost-2-fill | signpost-split |
| signpost-split-fill | compass | compass-fill | map |
| map-fill | | | |

### Containers/Packaging
| Icon Name | Icon Name | Icon Name | Icon Name |
|-----------|-----------|-----------|-----------|
| box | box-fill | box2 | box2-fill |
| boxes | box-seam | box-seam-fill | archive |
| archive-fill | inbox | inbox-fill | inboxes |
| inboxes-fill | | | |

### Power/System
| Icon Name | Icon Name | Icon Name | Icon Name |
|-----------|-----------|-----------|-----------|
| power | toggles | toggles2 | toggle-on |
| toggle-off | toggle2-on | toggle2-off | |

### Code/Development
| Icon Name | Icon Name | Icon Name | Icon Name |
|-----------|-----------|-----------|-----------|
| code | code-slash | code-square | terminal |
| terminal-fill | braces | braces-asterisk | bug |
| bug-fill | git | github | gitlab |
| regex | | | |

### API/Integration
| Icon Name | Icon Name | Icon Name | Icon Name |
|-----------|-----------|-----------|-----------|
| plug | plug-fill | usb-plug | usb-plug-fill |
| broadcast | broadcast-pin | rss | rss-fill |
| wifi | wifi-1 | wifi-2 | wifi-off |
| reception-0 | reception-1 | reception-2 | reception-3 |
| reception-4 | ethernet | diagram-2 | diagram-2-fill |
| diagram-3 | diagram-3-fill | bezier | bezier2 |
| hr | | | |

### AI/Automation/Charts
| Icon Name | Icon Name | Icon Name | Icon Name |
|-----------|-----------|-----------|-----------|
| robot | magic | lightbulb | lightbulb-fill |
| lightbulb-off | lightbulb-off-fill | cpu | cpu-fill |
| gpu-card | memory | motherboard | motherboard-fill |
| speedometer | speedometer2 | graph-up | graph-down |
| graph-up-arrow | graph-down-arrow | bar-chart | bar-chart-fill |
| bar-chart-line | bar-chart-line-fill | bar-chart-steps | pie-chart |
| pie-chart-fill | | | |

### Relations/Hierarchy
| Icon Name | Icon Name | Icon Name | Icon Name |
|-----------|-----------|-----------|-----------|
| repeat | repeat-1 | shuffle | collection |
| collection-fill | stack | stack-overflow | layers |
| layers-fill | layers-half | node-plus | node-plus-fill |
| node-minus | node-minus-fill | | |

### Commerce
| Icon Name | Icon Name | Icon Name | Icon Name |
|-----------|-----------|-----------|-----------|
| cash | cash-coin | cash-stack | coin |
| currency-dollar | currency-euro | credit-card | credit-card-fill |
| credit-card-2-front | credit-card-2-front-fill | credit-card-2-back | credit-card-2-back-fill |
| cart | cart-fill | cart-plus | cart-plus-fill |
| cart-check | cart-check-fill | cart-dash | cart-dash-fill |
| cart-x | cart-x-fill | bag | bag-fill |
| bag-check | bag-check-fill | bag-plus | bag-plus-fill |
| bag-dash | bag-dash-fill | bag-x | bag-x-fill |
| basket | basket-fill | basket2 | basket2-fill |
| basket3 | basket3-fill | receipt | receipt-cutoff |

### Window/UI Chrome
| Icon Name | Icon Name | Icon Name | Icon Name |
|-----------|-----------|-----------|-----------|
| window | window-dash | window-desktop | window-fullscreen |
| window-plus | window-sidebar | window-split | window-stack |
| window-x | app | bounding-box | bounding-box-circles |
| cursor | cursor-fill | cursor-text | hand-index |
| hand-index-fill | hand-index-thumb | hand-index-thumb-fill | |

## Visual Icon Browser

Open **[icons.html](icons.html)** in your browser for a visual, searchable grid of all included icons. Click any icon to copy its class name.

## Resources

- [Bootstrap Icons](https://icons.getbootstrap.com/) - Full icon browser with search
- [Bootstrap Icons GitHub](https://github.com/twbs/icons) - Source repository
- [jsDelivr CDN](https://www.jsdelivr.com/package/npm/bootstrap-icons) - CDN hosting
