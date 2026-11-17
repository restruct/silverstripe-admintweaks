# Form Fields

The module provides custom form fields and form field extensions for enhanced admin UI.

## CopyTextField

A read-only text field with a copy-to-clipboard button. Useful for displaying API keys, URLs, or other values users need to copy.

![CopyTextField example](https://user-images.githubusercontent.com/1005986/177027008-2c711cad-9c0c-47ea-a56a-1dc6f4861ba7.png)

### Basic Usage

```php
use Restruct\Silverstripe\AdminTweaks\FormFields\CopyTextField;

$field = CopyTextField::create('ApiKey', 'API Key', $this->ApiKey);
```

### Configuration Options

```php
$field = CopyTextField::create('ShareUrl', 'Share URL', $this->AbsoluteLink())
    ->setButtonLabel('Copy URL')     // Custom button text (default: 'Copy')
    ->setShowAlert(true)             // Show browser alert on copy (default: false)
    ->setDescription('Click to copy this URL to clipboard');
```

### Template

The field uses `templates/Restruct/Silverstripe/AdminTweaks/FormFields/CopyTextField.ss` with Bootstrap styling and SVG icons for copy/success states.

---

## MultivalueSortField

A field for managing sortable multi-value data. Allows users to add, remove, and reorder items.

### Usage

```php
use Restruct\Silverstripe\AdminTweaks\Forms\MinimalEditorConfig;
use SilverStripe\Forms\HTMLEditor\HTMLEditorField;

$field = HTMLEditorField::create('Answer')->setEditorConfig(MinimalEditorConfig::get());
```

`MinimalEditorConfig::get()` makes the config on its first call and returns the same object
after that. Use the getter, not the name: up to Silverstripe 5, `HTMLEditorConfig::get()` has no
hook for a named config, so `setEditorConfig('minimal')` or `HTMLEditorConfig::get('minimal')`
only finds the copy once the getter has run. Before that, a lookup by name creates an empty
config called `minimal`, and the getter then keeps it as if it were a project's own.

Getting the config is what makes the copy (see below), so do not get it in a `_config.php`: the
copy would then be made before every module has set up `cms`. Change the toolbar through YAML
instead (see Configuration).

### How it is built

The config is a copy of the `cms` config, made on the first `MinimalEditorConfig::get()`. It
keeps what `cms` has at that moment: the link plugins and their menu (page, file, external URL,
anchor, email), the skin, the content CSS, and whatever a project's own `_config.php` added to
`cms`.

The copy keeps the `cms` config's `valid_elements` and `extended_valid_elements` as well (the
iframe, object and map rules admin adds). For other rules, register a project's own `minimal`
(below).

Why not make the copy in this module's `_config.php`: module `_config.php` files run in module
priority order. admin and cms run first, but asset-admin (which adds the file link) and other
modules are in the `$other_modules` group, where the order comes down to the alphabetical order
of the package names, and a project's `_config.php` runs after every module. So a copy made there
would depend on package names.

A project that registers its own `minimal` (`HTMLEditorConfig::set_config('minimal', ...)`)
keeps it; the copy is only made when there is no `minimal` yet.

### Configuration

The toolbar is one space-separated string of TinyMCE button names per line (`|` is a
separator). An empty string leaves the line out; lines the `cms` config has and this list does
not name are emptied as well.

```yaml
Restruct\Silverstripe\AdminTweaks\Forms\MinimalEditorConfig:
  minimal_buttons:
    line_1: 'bold italic | bullist numlist | sslink unlink | removeformat'
    line_2: ''
    line_3: ''
  minimal_options:
    friendly_name: 'Simple'
    contextmenu: 'sslink'
```

These are the defaults. Use strings, not lists: Silverstripe config adds a list you set to the
module's list instead of replacing it, so a list of buttons would end up after the default ones.

`minimal_options` are TinyMCE options set on the copy. `contextmenu: 'sslink'` replaces the `cms`
right-click menu (tables, media, anchors) with the link menu.

`unlink` is in the default list, as it is in core's own `cms` toolbar, and as there it only
renders when TinyMCE's core `link` plugin is enabled, which the `cms` config does not do. Without
it the button is left out and the toolbar shows the other five buttons.

---

## Toggle switch

Show a checkbox as a toggle switch: opt in per field with the `toggle-switch` class. It is CSS
only, in the module's admin stylesheet: the native `<input type="checkbox">` itself is drawn as
the switch (`appearance: none`, the knob is its `::before`). No JavaScript, no extra markup and
no third-party library, so:

- the click target is still the checkbox: in an editable GridField a click on the switch toggles
  it and does not open the row, and the hidden input that makes an unchecked box in a GridField
  row submit `0` keeps working;
- the keyboard (Tab, Space) and screen readers see a plain checkbox, with its own label;
- it works in forms loaded later (pjax, a new GridField row), as there is nothing to initialise.

On Silverstripe 5 it looks like the admin's own Bootstrap 4 switch (`.custom-switch`): 1.75rem by 1rem, the same border,
knob and checked colour, and Bootstrap 4's focus ring. Disabled switches are faded and do not react to the pointer.

### CheckboxField

```php
CheckboxField::create('Active')->setToggleSwitch();
// the same, by hand:
CheckboxField::create('Active')->addExtraClass('toggle-switch');
```

On Silverstripe 5 (checked; Silverstripe 4 was not tested) the extra class lands on the `<input>` and on the field's holder `<div>`. The switch
lines up with plain checkboxes in the same form; its label text starts after it.

### CheckboxSetField

```php
CheckboxSetField::create('Channels', 'Channels', $options)->setToggleSwitch();
```

A CheckboxSetField's extra class lands on the element that wraps its options, not on each option's
`<input>` (the options are rendered from the template's loop and take no class of their own). The
CSS therefore also matches every checkbox *inside* an element with the class
(`.toggle-switch input[type=checkbox]`), so all options become switches. The same holds for any
container: a class on a CompositeField or FieldGroup turns every checkbox in it into a switch.

### In an editable GridField column

`GridFieldEditableColumns` builds a column's field from the column config, so a plain
`'field' => CheckboxField::class` cannot take the class. Build the field in a `callback` instead
(it gets the record, the column name and the GridField):

```php
$editable = GridFieldEditableColumns::create();
$editable->setDisplayFields([
    'Title' => ['title' => 'Title', 'field' => TextField::class],
    'Active' => [
        'title' => 'Active',
        'callback' => fn ($record, $column) => CheckboxField::create($column)->setToggleSwitch(),
    ],
]);
```

### `setToggleSwitch()`

`ToggleSwitchExtension` adds `setToggleSwitch(bool $enable = true)` (chainable; `false` removes the
class again) and `isToggleSwitch()` to `CheckboxField` and `CheckboxSetField`. It is a shorthand
for the class and changes nothing until a field calls it; adding the class by hand is equivalent.

### Read-only and disabled

- `setDisabled(true)`: still drawn as a switch, faded; it cannot be toggled.
- A read-only field (`performReadonlyTransformation()`, or a record the user cannot edit in an
  editable GridField) is rendered by core as text ("Yes"/"No", or the option titles), not as a
  checkbox, so there is no switch to show.
- The HTML `readonly` attribute does not stop a checkbox from toggling (browsers ignore it on
  checkboxes); a switch with it is faded like a disabled one, but use `setDisabled(true)` to
  actually stop changes.

### Changing the look

The sizes and colours are custom properties on the switch, so a project stylesheet can override
them, e.g. a larger green switch:

```css
input[type="checkbox"].toggle-switch,
.toggle-switch input[type="checkbox"] {
    --toggle-switch-width: 2.5rem;
    --toggle-switch-height: 1.25rem;
    --toggle-switch-checked: #008a00;
}
```

Also available: `--toggle-switch-bg` (the track when off), `--toggle-switch-border`,
`--toggle-switch-knob` (the knob when off; it is white when on) and `--toggle-switch-focus-border`.

---

## IpAddressField

A text field with IP address validation.

> **Note:** This field is marked as "untested" and may need adjustments for specific use cases.

### Usage

```php
use Restruct\Silverstripe\AdminTweaks\FormFields\IpAddressField;

$field = IpAddressField::create('AllowedIP', 'Allowed IP Address');
```

---

## Form Field Extensions

### FormFieldBootstrapExtension

Automatically adds Bootstrap form classes to form fields. Opt-in via configuration.

#### Enable

```yaml
SilverStripe\Forms\FormField:
  extensions:
    - Restruct\Silverstripe\AdminTweaks\Extensions\FormFieldBootstrapExtension
```

#### Applied Classes

| Field Type | Class Applied |
|------------|---------------|
| Text inputs, textareas, selects | `form-control` |
| Select dropdowns | `form-select` |
| Checkboxes, radios | `form-check-input` |
| Checkbox/radio labels | `form-check-label` |

---

### FormFieldTweaksExtension

Adds holder attribute management and validation message styling.

#### Enable

```yaml
SilverStripe\Forms\FormField:
  extensions:
    - Restruct\Silverstripe\AdminTweaks\Extensions\FormFieldTweaksExtension
```

#### Usage

```php
// Add attributes to the field holder (not the input)
$field->setHolderAttribute('data-custom', 'value');
$field->addHolderClass('my-custom-class');

// Use with custom holder template
$field->setFieldHolderTemplate('FormFieldTweaks_holder');
```

#### Custom Holder Template

The module provides `templates/FormFieldTweaks_holder.ss` for use with this extension.

---

## DataObjectExtension

Adds utility methods to DataObjects.

### fieldLabelToLower()

Returns a lowercase version of a field label:

```php
// In a DataObject
$label = $this->fieldLabelToLower('FirstName'); // "first name"
```

Useful for generating natural-language messages.
