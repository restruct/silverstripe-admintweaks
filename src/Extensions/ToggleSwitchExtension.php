<?php

namespace Restruct\Silverstripe\AdminTweaks\Extensions;

use SilverStripe\Core\Extension;
use SilverStripe\Forms\FormField;

/**
 * Show a CheckboxField, or every option of a CheckboxSetField, as a toggle switch.
 *
 * A shorthand for `->addExtraClass('toggle-switch')`; the switch itself is plain CSS in
 * client/dist/css/admintweaks.css, which draws the native checkbox as a switch. Applied to
 * CheckboxField and CheckboxSetField in _config/config.yml, so both have the method.
 *
 * ```php
 * CheckboxField::create('Active')->setToggleSwitch();
 * CheckboxSetField::create('Channels', 'Channels', $options)->setToggleSwitch();
 * ```
 *
 * See docs/form-fields.md for where the class lands and for editable GridField columns.
 *
 * @extends Extension<FormField>
 */
class ToggleSwitchExtension extends Extension
{
    /**
     * The marker class the CSS looks for, on the input or on any element around it.
     */
    public const CSS_CLASS = 'toggle-switch';

    /**
     * Show this field as a toggle switch (or as a plain checkbox again, with false).
     *
     * @param bool $enable
     * @return FormField The field, for chaining
     */
    public function setToggleSwitch(bool $enable = true): FormField
    {
        $field = $this->getOwner();
        if ($enable) {
            $field->addExtraClass(self::CSS_CLASS);
        } else {
            $field->removeExtraClass(self::CSS_CLASS);
        }

        return $field;
    }

    /**
     * Whether this field is shown as a toggle switch, also when the class was added by hand.
     *
     * @return bool
     */
    public function isToggleSwitch(): bool
    {
        # From extraClass(), not hasExtraClass(): framework 4.8 has no hasExtraClass() yet
        # (checked), and this line supports ^4
        $classes = preg_split('/\s+/', (string) $this->getOwner()->extraClass(), -1, PREG_SPLIT_NO_EMPTY);

        return in_array(self::CSS_CLASS, $classes, true);
    }
}
