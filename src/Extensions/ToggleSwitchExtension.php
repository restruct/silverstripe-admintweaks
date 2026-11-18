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
 * # Inverted: shown ON while the box is unchecked, for a stored negative flag
 * CheckboxField::create('Hidden', 'Hidden')->setToggleSwitchInverted();
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
     * The modifier class for an inverted switch (ON while the box is unchecked). A modifier only:
     * it takes effect together with CSS_CLASS, on the same element or on one around the input.
     */
    public const CSS_CLASS_INVERTED = 'toggle-switch--inverted';

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
            # No switch, so no inverted switch either: a modifier left behind would turn the field
            # inverted again on a later setToggleSwitch()
            $field->removeExtraClass(self::CSS_CLASS_INVERTED);
        }

        return $field;
    }

    /**
     * Show this field as an inverted toggle switch: ON while the box is UNCHECKED, for a checkbox
     * that stores a negative flag ("Hidden", "Exclude from ...") but should read positively.
     *
     * Display only: the checkbox's checked state, value and submission are unchanged, and assistive
     * tech announces the real state (a switch shown ON is "not checked"); see docs/form-fields.md.
     * With false the inversion is removed and the field stays a plain toggle switch; use
     * setToggleSwitch(false) for a plain checkbox.
     *
     * A separate method rather than a second argument to setToggleSwitch(), so a call site reads
     * as what it does, and there is no meaningless setToggleSwitch(false, true).
     *
     * @param bool $enable
     * @return FormField The field, for chaining
     */
    public function setToggleSwitchInverted(bool $enable = true): FormField
    {
        $field = $this->getOwner();
        if ($enable) {
            # Both classes: the modifier only changes a switch, and a project's restyle of
            # .toggle-switch (its custom properties) then applies to the inverted one as well
            $field->addExtraClass(self::CSS_CLASS);
            $field->addExtraClass(self::CSS_CLASS_INVERTED);
        } else {
            $field->removeExtraClass(self::CSS_CLASS_INVERTED);
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
        return $this->hasClass(self::CSS_CLASS);
    }

    /**
     * Whether this field is shown as an inverted toggle switch: both classes, also when they were
     * added by hand (the modifier alone draws nothing).
     *
     * @return bool
     */
    public function isToggleSwitchInverted(): bool
    {
        return $this->isToggleSwitch() && $this->hasClass(self::CSS_CLASS_INVERTED);
    }

    /**
     * Whether the field has this extra class.
     *
     * @param string $class
     * @return bool
     */
    private function hasClass(string $class): bool
    {
        # From extraClass(), not hasExtraClass(): framework 4.8 has no hasExtraClass() yet
        # (checked), and this line supports ^4
        $classes = preg_split('/\s+/', (string) $this->getOwner()->extraClass(), -1, PREG_SPLIT_NO_EMPTY);

        return in_array($class, $classes, true);
    }
}
