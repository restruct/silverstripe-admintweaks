<?php

namespace Restruct\Silverstripe\AdminTweaks\Extensions;

use SilverStripe\Core\Extension;
use SilverStripe\Core\Validation\ValidationResult;
use SilverStripe\Core\Convert;
use SilverStripe\ORM\FieldType\DBHTMLVarchar;

class FormFieldTweaksExtension extends Extension
{
    public function onBeforeRender()
    {
        if ($this->getOwner()->getMessage()) {
            $this->getOwner()->addExtraClass($this->getOwner()->getMessageType()==ValidationResult::TYPE_GOOD ? 'is-valid' : 'is-invalid');
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
        if(!$this->getOwner()->holder_attributes){
            $this->getOwner()->holder_attributes = [];
        }

        $this->getOwner()->holder_attributes[$name] = $value;

        return $this->getOwner();
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

        return $attributes[$name] ?? null;
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
        $attributes = $this->getOwner()->holder_attributes;

        $this->getOwner()->extend('updateHolderAttributes', $attributes);

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

        if ($attr = $this->getHolderAttributes()) {
            foreach ($attr as $name => $value) {
                if ($value === true) {
                    $value = $name;
                } elseif (is_scalar($value)) {
                    $value = (string) $value;
                } else {
                    $value = json_encode($value);
                }

                $parts[] = sprintf('%s="%s"', Convert::raw2att($name), Convert::raw2att($value));
            }
        }

        return DBHTMLVarchar::create()->setValue( implode(' ', $parts) );
    }
}
