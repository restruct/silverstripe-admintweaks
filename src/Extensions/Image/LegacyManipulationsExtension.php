<?php
/**
 * Extension to alias legacy image manipulations to their SS4-era method names
 */

namespace Restruct\Silverstripe\AdminTweaks\Extensions\Image {

    use SilverStripe\Core\Extension;

    class LegacyManipulationsExtension extends Extension
    {
        public function PaddedImage($width, $height)
        {
            return $this->owner->Pad($width, $height);
        }

        public function SetWidth($width)
        {
            return $this->owner->ScaleWidth($width);
        }

        public function SetHeight($height)
        {
            return $this->owner->ScaleHeight($height);
        }
    }
}