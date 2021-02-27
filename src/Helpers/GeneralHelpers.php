<?php

namespace Restruct\Silverstripe\AdminTweaks\Helpers {
    class GeneralHelpers
    {

        // safely get nested properties (returns null if not exists at some point)
        public static function safelyGetProperty($object, $prop_arr)
        {
            // convert to array if string
            if ( is_string($prop_arr) ) {
                $prop_arr = explode('->', $prop_arr);
            }
            // get property to test for
            $prop = array_shift($prop_arr);
            // return null if not property exists
            if ( !is_object($object) || !property_exists($object, $prop) ) {
                return null;
            }

            if ( count($prop_arr) == 0 ) {
                return $object->{$prop};
            } // else, if final property, return value

            // else, call self recursively on existing property
            return self::safelyGetProperty($object->{$prop}, $prop_arr);
        }

    }
}
