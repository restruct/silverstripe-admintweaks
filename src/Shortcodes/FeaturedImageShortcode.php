<?php

namespace Restruct\Silverstripe\AdminTweaks\Shortcodes {

    use SilverStripe\Control\Controller;
    use SilverStripe\Forms\CheckboxField;
    use SilverStripe\Forms\DropdownField;
    use SilverStripe\Forms\FieldList;
    use SilverStripe\Forms\TextField;
    use SilverStripe\ORM\DataObject;
    use SilverStripe\View\ViewableData;

    class FeaturedImageShortcode extends ViewableData
    {
        /**
         * @config string the actual shortcode used/inserted into the HTML editor
         */
        private static $shortcode = 'featuredimage';

        /**
         * @config string alternative parse_shortcode callback function (if not parse_shortcode)
         */
//        private static $shortcode_callback = 'parse_shortcode';

        /**
         * @return string the 'label' to use for this shortcode in the shortcodable dropdown
         */
        public function getShortcodeLabel()
        {
            return $this->singular_name();
        }

        /**
         * @return string fallback for shortcode type/label (may be different from shortcode_label on eg DataObjects)
         */
        public function singular_name()
        {
            return _t('MED.FeaturedImageShortcode', 'Pagina afbeelding van huidige pagina invoegen');
        }

        /**
         * @return FieldList fields to use as attributes when inserting this item as shortcode
         */
        public static function getShortcodeFields()
        {
            return FieldList::create(
                TextField::create('title', 'Image title')->setDescription('Optional, shown when hovering image'),
                DropdownField::create('size', 'Image size', ['md' => 'Medium width', 'full' => 'Full width']),
                CheckboxField::create('webm', 'Provide WEBM version of image for supporting browsers (optional)')
            );
        }

        /**
         * Callback function to parse this item's shortcode into HTML
         *
         * @param $arguments
         * @param $content
         * @param $parser
         * @param $shortcode
         * @return string
         */
        public static function parse_shortcode($attrs, $content=null, $parser=null, $shortcode, $info=null)
        {
            $ctrl = Controller::curr();
    //		if($ctrl->FeaturedImageID && $ctrl->FeaturedImages()->first()->exists()){
            if ( $ctrl->FeaturedImage() ) {
                return '<div class="image">' . $ctrl->FeaturedImage()->Fill(900, 500)->forTemplate() . '</div>';
            }
        }

//        /**
//         * Return a link to an image to be displayed as a placeholder in the editor
//         *
//         * @param array $attributes the list of attributes of the shortcode
//         * @return String
//         **/
//        public function getShortcodePlaceHolder($attributes)
//        {
////            $text = $this->Title;
////            if (isset($attributes['Style'])) {
////                $text .= ' (' . $attributes['Style'] . ')';
////            }
//
//            $params = array(
//                'txt' => 'Placeholder',
//                'w' => '400',
//                'h' => '200',
//                'txtsize' => '27',
//                'bg' => '000000',
//                'txtclr' => 'cccccc'
//            );
//
//            return 'https://placeholdit.imgix.net/~text?' . http_build_query($params);
//        }
    }
}
