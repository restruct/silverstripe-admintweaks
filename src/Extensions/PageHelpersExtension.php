<?php


namespace Restruct\Silverstripe\AdminTweaks\Extensions {

    use SilverStripe\Assets\File;
    use SilverStripe\CMS\Controllers\RootURLController;
    use SilverStripe\CMS\Model\SiteTree;
    use SilverStripe\Control\Controller;
    use SilverStripe\Control\Director;
    use SilverStripe\Control\Email\Mailer;
    use SilverStripe\Core\Extension;
    use SilverStripe\Forms\FieldList;
    use SilverStripe\Forms\FormField;
    use SilverStripe\Forms\HTMLEditor\HTMLEditorField;
    use SilverStripe\ORM\ArrayList;
    use SilverStripe\ORM\DataObject;
    use SilverStripe\Versioned\Versioned;
    use SilverStripe\View\ArrayData;
    use SilverStripe\View\Parsers\URLSegmentFilter;
    use SilverStripe\View\Requirements;
    use SilverStripe\View\SSViewer;

    class PageHelpersExtension extends Extension
    {
//        public function tmp(){
//            Mailer::class;
//        }

//        public function updateCMSFields(FieldList $fields)
//        {
//            parent::updateCMSFields($fields);
//
//            // Make more sense of the base fields
//            //        $fields->insertBefore('Title', $fields->dataFieldByName('MenuTitle'));
//            //        $fields->insertBefore('Title', $fields->dataFieldByName('URLSegment'));
//            //        $fields->dataFieldByName('Title')->setTitle('Title');
//
//        }

        public function BreadCrumbPath($append = null)
        {
            if ($append) $append = " > $append";
            $append = $this->owner->MenuTitle . "$append";
            if ($this->owner->Parent()->exists()) {
                return $this->owner->Parent()->BreadCrumbPath($append);
            } else {
                return $append;
            }
        }

        public function RequestStatusCode()
        {
            return Controller::curr()->getResponse()->getStatusCode();
        }

        public function RequestedURL()
        {
            return preg_replace("/[^a-zA-Z0-9\-\.\/\?\&\=]+/", "", $_SERVER['REQUEST_URI']);
        }

        ///// GETTERS:

        public function PageByClass($ClassName = 'Page')
        {
            return DataObject::get($ClassName)->first();
        }

        public function PageById($id = 1)
        {
            return SiteTree::get()->byID($id); // 1 = usually Home
        }

        public function HomePage()
        {
            return SiteTree::get()->filter([
                'URLSegment' => RootURLController::get_homepage_link()
            ])->first();
        }

        ////// GET ENVIRONMENT from templates
        public function IsDev()
        {
            return Director::isDev();
        }

        public function IsTest()
        {
            return Director::isTest();
        }

        public function IsLive()
        {
            return Director::isLive();
        }

        ////// FOR FLUENT/MULTI-LINGUAL

        /**
         * @TODO: somehow check if Fluent is available
         * Retrieves information about this object in the CURRENT locale
         *
         * @param string $locale The locale information to request, or null to use the default locale
         * @return ArrayData Mapped list of locale properties
         */
        public function CurrentLocaleInformation()
        {
            $locale = Fluent::current_locale();
            // Store basic locale information
            $data = array(
                'Locale' => $locale,
                'LocaleRFC1766' => i18n::convert_rfc1766($locale),
                'Alias' => Fluent::alias($locale),
                'Title' => i18n::get_locale_name($locale),
                'LanguageNative' => i18n::get_language_name(i18n::get_lang_from_locale($locale), true)
            );
            return new ArrayData($data);
        }

        ////// REQUIREMENTS (callable from templates)

        public function RequireCustomCSS($css)
        {
            Requirements::customCSS($css);
        }

        public function RequireCustomTemplatedCSS($template)
        {
            $template = SSViewer::fromString($template);
            $result = $template->process($this->owner);
            Requirements::customCSS($result);
        }

//        public function RequireThemeRelativeJS($pathrelativetotheme)
//        {
//            $themedir = SSViewer::current_theme();
//            Requirements::javascript("themes/$themedir/$pathrelativetotheme");
//        }

        public function RequireCustomJS($script)
        {
            $script = str_replace('<script>', '', $script);
            $script = str_replace('</script>', '', $script);
            Requirements::customScript($script);
        }

        public function RequireCustomTemplatedJS($template)
        {
            $template = str_replace('<script>', '', $template);
            $template = str_replace('</script>', '', $template);
            $template = SSViewer::fromString($template);
            $result = $template->process($this->owner);
            Requirements::customScript($result);
        }

        ///// COMBINED REQUIREMENTS (callable from templates)

//        public static function CombineThemeRelativeFile($combine_name, $pathrelativetotheme, $makeFirstItem = false)
//        {
//            $themedir = SSViewer::current_theme();
//            $file = "themes/$themedir/$pathrelativetotheme";
//            $to_combine = array($file);
//            $combine_name = "$themedir-$combine_name";
//            $previously_included = Requirements::get_combine_files();
//            // append to existing array, if any
//            if (array_key_exists($combine_name, $previously_included)) {
//                if ($makeFirstItem) {
//                    $to_combine = array_merge($to_combine, $previously_included[$combine_name]);
//                } else {
//                    $to_combine = array_merge($previously_included[$combine_name], $to_combine);
//                }
//            }
//            Requirements::combine_files($combine_name, $to_combine);
//        }
//
//        public function RequireCombinedThemeRelativeCSS($pathrelativetotheme, $makeFirstItem = false)
//        {
//            self::CombineThemeRelativeFile('styles.css', $pathrelativetotheme, $makeFirstItem);
//        }
//
//        public function RequireCombinedThemeRelativeJS($pathrelativetotheme, $makeFirstItem = false)
//        {
//            self::CombineThemeRelativeFile('scripts.js', $pathrelativetotheme, $makeFirstItem);
//        }

        ///// SORTABLE LISTS:

        // http://docs.silverstripe.org/en/4.0/developer_guides/model/how_tos/grouping_dataobject_sets/
        public function getTitleFirstChar()
        {
            // consider only alphanumeric (not '()' etc)
            $title = preg_replace('/\W|_/iu', '', $this->owner->Title);
            return strtolower($title[0]);
        }

        public function AlphabetChars()
        {
            $chars = ArrayList::create();
            foreach (range('a', 'z') as $char) {
                $chars->push(ArrayData::create(array('Char' => $char)));
            }
            return $chars;
//            $chars = new ArrayList();
//            //array_combine(range('A','Z'),range('A','Z'))
//            foreach (range('a', 'z') as $char) {
//                $chars->add(array('Char' => $char));
//            }
//            return $chars;
        }

        ///// DESCENDANTS, ALLOW CREATING RELATIONS TO UNPUBLISHED CHILDREN FROM (NESTED) LISTS:

        // for listing all children including unpublished
        public function allStageDescendants($parentObject = null)
        {

            // go into Stage mode to allow setting up relations to unpublished pages
            $origMode = Versioned::get_reading_mode(); // save current mode
            Versioned::set_reading_mode('Stage.Stage'); // temporarily overwrite mode

            $allDesc = $this->owner->allDescendants($parentObject);

            // Return to default mode (eg Live)
            Versioned::set_reading_mode($origMode); // reset current mode

            return $allDesc;
        }

        public function allDescendants($parentObject = null, $childRelation = 'Children')
        {
            //for first iteration
            $arrayList = ArrayList::create();
            if ($parentObject === null) {
                $parentObject = $this->owner;
            }
            $children = $parentObject->$childRelation();

            if ($children->count() > 0) {
                // add each child & subchildren to the array
                foreach ($children as $child) {
                    $arrayList->add($child);
                    if ($child->$childRelation()->count()) {
                        // call allDescendants on child object
                        $arrayList->merge($this->allDescendants($child, $childRelation));
                    }
                }
            }
            return $arrayList;
        }

    }

}