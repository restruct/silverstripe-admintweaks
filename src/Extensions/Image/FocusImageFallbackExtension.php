<?php
/**
 * Extension applied from config if FocusPointImageExtension is not present
 */

namespace Restruct\Silverstripe\AdminTweaks\Extensions\Image {

    use SilverStripe\Core\Extension;

    class FocusImageFallbackExtension extends Extension
    {
        /**
         * FocusPoint's FocusFill(), without a focus point: a plain centred Fill.
         *
         * This was missing: the method was only provided as FocusFillFallback(), a name FocusPoint
         * does not have, so $Image.FocusFill(...) rendered nothing without FocusPoint (admintweaks#142).
         */
        public function FocusFill($width, $height)
        {
            return $this->owner->Fill($width, $height);
        }

        /**
         * @deprecated use FocusFill(), which works with and without FocusPoint
         */
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