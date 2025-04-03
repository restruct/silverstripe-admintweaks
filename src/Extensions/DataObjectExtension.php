<?php

namespace Restruct\Silverstripe\AdminTweaks\Extensions;

use SilverStripe\ORM\DataExtension;

class DataObjectExtension
    extends DataExtension
{

    /**
     * Return a (translatable) fieldLabel in lowercase
     * @param $name
     * @param $style
     * @return string
     */
    public function fieldLabelToLower($name, $style=null)
    {
        return mb_strtolower($this->owner->fieldLabel($name));
    }
}