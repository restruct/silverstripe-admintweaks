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
use Restruct\Silverstripe\AdminTweaks\FormFields\MultivalueSortField;

$field = MultivalueSortField::create('Tags', 'Tags');
```

### Styles

Include the field's CSS in your admin:

```yaml
SilverStripe\Admin\LeftAndMain:
  extra_requirements_css:
    - 'restruct/silverstripe-admintweaks:client/dist/css/multivaluesortfield.css'
```

---

## The minimal HTML editor config

An HTML editor config named `minimal` for short rich-text fields (FAQ answers, intro texts, card
texts): one toolbar line with bold, italic, bullet and numbered lists, the link menu and "clear
formatting". The full `cms` toolbar (alignment, indent, tables, headings, embeds) is more than such
a field needs, and it keeps the content clean.

### Usage

```php
use Restruct\Silverstripe\AdminTweaks\Forms\MinimalEditorConfig;
use SilverStripe\Forms\HTMLEditor\HTMLEditorConfig;
use SilverStripe\Forms\HTMLEditor\HTMLEditorField;

$field = HTMLEditorField::create('Answer')->setEditorConfig(MinimalEditorConfig::NAME);

// The config object itself
$config = HTMLEditorConfig::get(MinimalEditorConfig::NAME);
```

Getting the config is what makes the copy (see below), so do not get it in a `_config.php`: the
copy would then be made before every module has set up `cms`. Change the toolbar through YAML
instead (see Configuration).

On Silverstripe 6 TinyMCE is a separate module; the config is only registered when
`silverstripe/htmleditor-tinymce` is installed.

### How it is built

The config is a copy of the `cms` config, made the first time `minimal` is requested. It keeps
what `cms` has at that moment: the link plugins and their menu (page, file, external URL, anchor,
email), the skin, the content CSS, and whatever a project's own `_config.php` added to `cms`. A
copy made in a `_config.php` could miss some of that, because the `_config.php` that sets up
`cms` can run after it.

On Silverstripe 6 the copy gets the framework's default element rules, not the extra iframe,
object and map rules of `cms`. Allow more HTML with `extraElementRules`, as core does for `cms`:

```yaml
SilverStripe\Forms\HTMLEditor\HTMLEditorConfig:
  default_config_definitions:
    minimal:
      extraElementRules:
        span:
          attributes:
            class: true
```

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
