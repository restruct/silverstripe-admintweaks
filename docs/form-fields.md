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
