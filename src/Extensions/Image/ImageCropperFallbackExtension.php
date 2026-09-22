<?php

namespace Restruct\Silverstripe\AdminTweaks\Extensions\Image {

    use SilverStripe\Core\Extension;

    class ImageCropperFallbackExtension extends Extension
    {
        public function CroppedFocusedImage($width, $height)
        {
            if(class_exists('JonoM\FocusPoint\Extensions\FocusPointImageExtension')){
                return $this->owner->FocusFill($width, $height);
            }
            return $this->owner->Fill($width, $height);
        }

        public function CroppedImage($width, $height)
        {
            return $this->owner->Fill($width, $height);
        }
    }
}