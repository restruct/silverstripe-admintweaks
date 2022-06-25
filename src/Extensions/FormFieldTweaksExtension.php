<?php

namespace Restruct\Silverstripe\AdminTweaks\Extensions;

use SilverStripe\Core\Convert;
use SilverStripe\Dev\Debug;
use SilverStripe\Forms\Form;
use SilverStripe\Forms\FormField;
use SilverStripe\ORM\FieldType\DBHTMLVarchar;
use SilverStripe\ORM\ValidationResult;

class FormFieldTweaksExtension extends \SilverStripe\Core\Extension
{
    public function onBeforeRender()
    {
        if ($this->owner->getMessage()) {
            $this->owner->addExtraClass($this->owner->getMessageType()==ValidationResult::TYPE_GOOD ? 'is-valid' : 'is-invalid');
        }
    }

    /**
     * Set an attribute on the FieldHolder of this Field
     *
     * @param string $name
     * @param string $value
     *
     * @return $this
     */
    public function setHolderAttribute($name, $value)
    {
        if(!$this->owner->holder_attributes){
            $this->owner->holder_attributes = [];
        }

        $this->owner->holder_attributes[$name] = $value;

        return $this->owner;
    }

    /**
     * Get an HTML attribute defined for the FieldHolder, added through {@link setHolderAttribute()}.
     *
     * Caution: this doesn't work on all fields, see {@link setAttribute()}.
     *
     * @param string $name
     * @return string
     */
    public function getHolderAttribute($name)
    {
        $attributes = $this->getHolderAttributes();

        if (isset($attributes[$name])) {
            return $attributes[$name];
        }

        return null;
    }

    /**
     * Allows customization through an 'updateHolderAttributes' hook on the base class.
     * Existing attributes are passed in as the first argument and can be manipulated,
     * but any attributes added through a subclass implementation won't be included.
     *
     * @return array
     */
    public function getHolderAttributes()
    {
        $attributes = $this->owner->holder_attributes;

        $this->owner->extend('updateHolderAttributes', $attributes);

        return $attributes;
    }

    /**
     * Uses {@link getHolderAttributes()}
     *
     * @return string
     */
    public function getHolderAttributesHTML()
    {
        // Create markup
        $parts = [];

        if($attr = $this->getHolderAttributes()) foreach ($attr as $name => $value) {
            if ($value === true) {
                $value = $name;
            } else if (is_scalar($value)) {
                $value = (string) $value;
            } else {
                $value = json_encode($value);
            }
            $parts[] = sprintf('%s="%s"', Convert::raw2att($name), Convert::raw2att($value));
        }

        return DBHTMLVarchar::create()->setValue( implode(' ', $parts) );
    }
}
