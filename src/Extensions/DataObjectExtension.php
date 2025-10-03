<?php

namespace Restruct\Silverstripe\AdminTweaks\Extensions;

use SilverStripe\Core\Extension;

class DataObjectExtension extends Extension
{

    /**
     * Return a (translatable) fieldLabel in lowercase
     * @param $name
     * @param $style
     * @return string
     */
    public function fieldLabelToLower($name, $style=null)
    {
        return mb_strtolower((string) $this->owner->fieldLabel($name));
    }
}
