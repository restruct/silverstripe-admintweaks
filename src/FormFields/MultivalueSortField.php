<?php

namespace Restruct\Silverstripe\AdminTweaks\FormFields;

use SilverStripe\View\Requirements;
use Symbiote\MultiValueField\Fields\MultiValueDropdownField;

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
