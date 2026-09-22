# GridField Configurations

The module provides pre-configured GridField setups for common use cases.

## GridFieldConfigs

Helper class with static methods returning ready-to-use GridField configurations.

### editable_orderable()

Inline editing with drag-drop ordering. Ideal for simple related records.

```php
use Restruct\Silverstripe\AdminTweaks\Forms\GridFieldConfigs;
use SilverStripe\Forms\GridField\GridField;

$grid = GridField::create(
    'Items',
    'Items',
    $this->Items(),
    GridFieldConfigs::editable_orderable()
);
```

**Includes:**
- `GridFieldEditableColumns` - Inline editing in the grid
- `GridFieldOrderableRows` - Drag-drop reordering (requires `undefinedoffset/sortablegridfield`)
- `GridFieldAddNewInlineButton` - Add new records inline
- `GridFieldDeleteAction` - Delete records
- `GridFieldToolbarHeader` - Toolbar

### filterable_orderable_recordeditor()

Full-featured grid with filtering, ordering, and record editor.

```php
$grid = GridField::create(
    'Products',
    'Products',
    $this->Products(),
    GridFieldConfigs::filterable_orderable_recordeditor()
);
```

**Includes:**
- `GridFieldFilterHeader` - Column filtering
- `GridFieldSortableHeader` - Column sorting
- `GridFieldOrderableRows` - Drag-drop reordering
- `GridFieldEditButton` - Edit in modal/page
- `GridFieldDeleteAction` - Delete records
- `GridFieldAddNewButton` - Add new button
- `GridFieldDetailForm` - Full record editing

---

## GridFieldConfig_VersionedOrderableRows

A GridField configuration for versioned (staged) records with ordering support.

```php
use Restruct\Silverstripe\AdminTweaks\Forms\GridFieldConfig_VersionedOrderableRows;

$grid = GridField::create(
    'Slides',
    'Slides',
    $this->Slides(),
    GridFieldConfig_VersionedOrderableRows::create()
);
```

---

## GridFieldSiteTreeAddNewButton

Custom "Add New" button for SiteTree-based records in GridFields.

```php
use Restruct\Silverstripe\AdminTweaks\Forms\GridFieldSiteTreeAddNewButton;

$config->addComponent(GridFieldSiteTreeAddNewButton::create());
```

---

## SortableExtension

Auto-incrementing `Sort` field for DataObjects. Use with orderable GridFields.

### Enable

```yaml
MyNamespace\Model\MyRecord:
  extensions:
    - Restruct\Silverstripe\AdminTweaks\Extensions\SortableExtension
```

### Behavior

- Adds `Sort` integer field
- Auto-sets `Sort` value on create (max + 1)
- Works with `GridFieldOrderableRows`

### Usage

```php
// In your DataObject
private static $default_sort = 'Sort ASC';
```

---

## Dependencies

Some GridField features require additional modules:

| Feature | Required Module |
|---------|-----------------|
| `GridFieldOrderableRows` | `undefinedoffset/sortablegridfield` |
| `GridFieldEditableColumns` | `symbiote/silverstripe-gridfieldextensions` |
| `GridFieldAddNewInlineButton` | `symbiote/silverstripe-gridfieldextensions` |

Install as needed:

```bash
composer require undefinedoffset/sortablegridfield
composer require symbiote/silverstripe-gridfieldextensions
```
