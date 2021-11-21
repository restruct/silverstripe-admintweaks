<?php

namespace Restruct\Silverstripe\AdminTweaks\Extensions;

use SilverStripe\Core\Config\Config;
use SilverStripe\Forms\FieldList;
use SilverStripe\ORM\DataExtension;
use SilverStripe\ORM\DataObject;
use SilverStripe\ORM\DB;

class SortableExtension
    extends DataExtension
{
    private static $db = [
        'Sort' => 'Int',
    ];

    /**
     * @param \SilverStripe\Forms\FieldList $fields
     */
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