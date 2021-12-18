<?php

namespace Restruct\Silverstripe\AdminTweaks\Shortcodes {

    use HttpResponse;
    use SilverStripe\Control\Controller;
    use SilverStripe\Dev\Debug;
    use SilverStripe\View\ViewableData;

    class CurrentYearShortcode extends ViewableData
    {
        /**
         * @config string
         */
        private static $shortcode = 'currentyear';

//        private static $shortcode_close_parent = true;

        public function singular_name()
        {
            return _t('MED.ReferringInfoShortcode', 'Actueel jaartal');
        }

        // Shortcode stuff
        public static function shortcode_attribute_fields()
        {
//		return FieldList::create(
//			DropdownField::create(
//				'Style',
//				'Gallery Style',
//				array('Carousel' => 'Carousel', 'Lightbox' => 'Lightbox')
//			)
//		);
        }

        // handles [year]
        public static function parse_shortcode($arguments, $content, $parser, $shortcode)
        {
            return '<div class="me">'.date("Y").'</div>';
        }

//        /**
//         * Redirect to an image OR return image data directly to be displayed as shortcode placeholder in the editor
//         * (getShortcodePlaceHolder gets loaded as/from the 'src' attribute of an <img> tag)
//         *
//         * @param array $attributes attribute key-value pairs of the shortcode
//         * @return \SilverStripe\Control\HTTPResponse
//         **/
//        public function getShortcodePlaceHolder($attributes)
//        {
////            // Flavour one: redirect to image URL (for this example we're also including the attributes array in the URL)
////            Controller::curr()->redirect('https://www.silverstripe.org/apple-touch-icon-76x76.png?attrs='.json_encode($attributes));
//
//            // Flavour two: output image/svg data directly
//            $response = Controller::curr()->getResponse();
//            $response->addHeader('Content-Type','image/svg+xml');
//            $response->addHeader('Vary','Accept-Encoding');
//            $response->setBody('<svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-code-square" viewBox="0 0 16 16">
//              <path d="M14 1a1 1 0 0 1 1 1v12a1 1 0 0 1-1 1H2a1 1 0 0 1-1-1V2a1 1 0 0 1 1-1h12zM2 0a2 2 0 0 0-2 2v12a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V2a2 2 0 0 0-2-2H2z"/>
//              <path d="M6.854 4.646a.5.5 0 0 1 0 .708L4.207 8l2.647 2.646a.5.5 0 0 1-.708.708l-3-3a.5.5 0 0 1 0-.708l3-3a.5.5 0 0 1 .708 0zm2.292 0a.5.5 0 0 0 0 .708L11.793 8l-2.647 2.646a.5.5 0 0 0 .708.708l3-3a.5.5 0 0 0 0-.708l-3-3a.5.5 0 0 0-.708 0z"/>
//            </svg>');
//            $response->output();
//
////            $params = array(
////                'txt' => 'Curr Year SC PH',
////                'w' => '400',
////                'h' => '200',
////                'txtsize' => '27',
////                'bg' => '000000',
////                'txtclr' => 'cccccc'
////            );
////            return 'https://placeholdit.imgix.net/~text?' . http_build_query($params);
//        }
    }
}
