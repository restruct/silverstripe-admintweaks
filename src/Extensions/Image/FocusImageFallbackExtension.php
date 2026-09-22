<?php
/**
 * Extension applied from config if FocusPointImageExtension is not present
 */

namespace Restruct\Silverstripe\AdminTweaks\Extensions\Image {

    use SilverStripe\Core\Extension;

    class FocusImageFallbackExtension extends Extension
    {
        public function FocusFillFallback($width, $height)
        {
            return $this->owner->Fill($width, $height);
        }

        public function FocusFillMax($width, $height)
        {
            return $this->owner->FillMax($width, $height);
        }

        public function FocusCropWidth($width)
        {
            return $this->owner->CropWidth($width);
        }

        public function FocusCropHeight($height)
        {
            return $this->owner->CropHeight($height);
        }
    }
}