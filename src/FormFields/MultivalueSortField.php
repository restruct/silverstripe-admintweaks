<?php

namespace Restruct\Silverstripe\AdminTweaks\FormFields;

use SilverStripe\View\Requirements;
use Symbiote\MultiValueField\Fields\MultiValueDropdownField;

// symbiote/silverstripe-multivaluefield is OPTIONAL (it is in `suggest`, not `require`), so its
// classes may be absent. Declaring a subclass of a missing parent is not merely a dormant error
// here: silverstripe/config's PrivateStaticTransformer calls class_exists() on EVERY class in the
// manifest during bootstrap (PrivateStaticTransformer.php:43), which autoloads this file and
// fatals the entire application - CMS, front end and CLI alike - not just this field.
// Returning early leaves the class undeclared, which class_exists() reports as false and the
// transformer then skips. Same guard as SelectiveLumberjack and GridFieldSiteTreeAddNewButton.
if (!class_exists(MultiValueDropdownField::class)) {
    return;
}

/**
 * A sort-only variant of MultiValueDropdownField.
 *
 * Allows reordering items via drag-and-drop but prevents adding/removing items.
 * Useful for setting a custom sort order for a fixed set of options.
 *
 * Usage:
 * ```php
 * MultivalueSortField::create('SortOrder', 'Item Order', [
 *     '1' => 'First item',
 *     '2' => 'Second item',
 *     '3' => 'Third item',
 * ]);
 * ```
 */
class MultivalueSortField extends MultiValueDropdownField
{
    /**
     * @var array
     */
    protected $extraClasses = ['sort-only'];

    /**
     * {@inheritdoc}
     */
    public function Field($properties = [])
    {
        $this->requireCSS();
        return parent::Field($properties);
    }

    /**
     * Include the CSS for sort-only behavior.
     */
    protected function requireCSS(): void
    {
        Requirements::css('restruct/silverstripe-admintweaks:client/dist/css/multivaluesortfield.css');
    }
}
