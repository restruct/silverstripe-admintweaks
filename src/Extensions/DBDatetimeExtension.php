<?php

namespace Restruct\Silverstripe\AdminTweaks\Extensions;

use SilverStripe\Core\Extension;
use SilverStripe\ORM\FieldType\DBDatetime;

class DBDatetimeExtension
    extends Extension
{
    /**
     * Bring back support for the PHP date formatting strings that Silverstripe 3 used.
     * Saves from having to go through all your templates and classes to change the formatting strings.
     * Also some 'shorthands' are not supported in CLDR, such as 'c' for ISO 8601
     * (CLDR https://unicode-org.github.io/icu/userguide/format_parse/datetime/#date-field-symbol-table)
     *
     * @param string $formatString SS3/PHP datetime formatting: https://www.php.net/manual/en/datetime.format.php
     * @return false|string
     */
    public function LegacyFormat($formatString)
    {
        /** @var DBDatetime $this->owner */
        return date($formatString, $this->owner->getTimestamp());
    }

    public function Iso8601()
    {
        return $this->LegacyFormat('c');
    }
}
