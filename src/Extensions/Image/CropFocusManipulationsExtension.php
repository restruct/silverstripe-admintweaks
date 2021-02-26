<?php
/**
 * Placeholder extension to use until we update the CroppedFocused Image module to SS4 (temporarily removes this functionality)
 */

namespace Restruct\Silverstripe\AdminTweaks\Extensions\Image {

    use SilverStripe\Core\Extension;

    class CropFocusManipulationsExtension extends Extension
    {
        public function CroppedFocusedImage($width, $height)
        {
            return $this->owner->Fill($width, $height);
        }

    }
}