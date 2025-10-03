<?php

namespace Restruct\Silverstripe\AdminTweaks\Extensions;

use SilverStripe\Core\Extension;
use SilverStripe\Forms\FieldList;
use SilverStripe\ORM\DataObject;

class SortableExtension extends Extension
{
    private static $db = [
        'Sort' => 'Int',
    ];

    public function updateCMSFields(FieldList $fields)
    {
        $fields->removeByName('Sort');
    }

    public function onBeforeWrite()
    {
        if ($this->owner->Sort === null) {
            $this->owner->Sort = (int) DataObject::get($this->owner->ClassName)->max('Sort') + 1;
        }
    }
}
