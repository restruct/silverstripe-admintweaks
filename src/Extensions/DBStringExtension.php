<?php

namespace Restruct\Silverstripe\AdminTweaks\Extensions {

    use SilverStripe\Core\Extension;

    class DBStringExtension
        extends Extension
    {
        /**
         * Helper to check if string contains a substring (eg from templates)
         *
         * @param $searchString
         * @return bool
         */
        public function contains($searchString)
        {
            return strpos($this->owner->RAW(), $searchString) !== false;
        }

    }

}
