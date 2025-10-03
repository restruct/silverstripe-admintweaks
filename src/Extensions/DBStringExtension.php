<?php

namespace Restruct\Silverstripe\AdminTweaks\Extensions {

    use SilverStripe\Core\Extension;

    class DBStringExtension extends Extension
    {
        /**
         * Helper to check if string contains a substring (eg from templates)
         *
         * @param $searchString
         * @return bool
         */
        public function contains($searchString)
        {
            return str_contains((string) $this->getOwner()->RAW(), (string) $searchString);
        }

    }

}
