<?php

namespace Restruct\Silverstripe\AdminTweaks\Extensions;

use Restruct\Silverstripe\AdminTweaks\Helpers\GeneralHelpers;
use SilverStripe\AssetAdmin\Forms\UploadField;
use SilverStripe\Assets\File;
use SilverStripe\Assets\Image;
use SilverStripe\CMS\Forms\AnchorSelectorField;
use SilverStripe\CMS\Model\SiteTree;
use SilverStripe\Core\Convert;
use SilverStripe\Forms\DropdownField;
use SilverStripe\Forms\EmailField;
use SilverStripe\Forms\FieldList;
use SilverStripe\Forms\HeaderField;
use SilverStripe\Forms\ListboxField;
use SilverStripe\Forms\LiteralField;
use SilverStripe\Forms\SingleLookupField;
use SilverStripe\Forms\Tab;
use SilverStripe\Forms\TextareaField;
use SilverStripe\Forms\TextField;
use SilverStripe\Forms\TreeDropdownField;
use SilverStripe\ORM\DataExtension;
use SilverStripe\ORM\FieldType\DBHTMLVarchar;

class SiteConfigExtension extends DataExtension
{
    // Inspiration: https://github.com/silverstripe/cwp-agencyextensions/blob/2/src/Extensions/CWPSiteConfigExtension.php
    private static $db = [
        'Email' => 'Varchar(100)',
        'Phone' => 'Varchar(50)',
        'Address' => 'Text',
        'Facebook' => 'Varchar(255)',
        'Instagram' => 'Varchar(255)',
        'Twitter' => 'Varchar(255)',
        'Linkedin' => 'Varchar(255)',
        'Youtube' => 'Varchar(255)',
        'Whatsapp' => 'Varchar(255)',

        'ExtraHTML_HeadStart' => 'Text',
        'ExtraHTML_HeadEnd' => 'Text',
        'ExtraHTML_BodyStart' => 'Text',
        'ExtraHTML_BodyEnd' => 'Text',

        'ThemeBrowserColor' => 'Varchar(7)', // including '#'
        'ThemeContainerClass' => 'Varchar(50)',
        'ThemeSubNavSlot' => 'Varchar(10)',
    ];

    private static $has_one = [
        'ContactPage' => SiteTree::class,
        'Logo' => File::class,
        'ExtraLogo' => File::class,
        'FavIcon' => File::class,
        'NavLogo' => File::class,
        'FooterLogo' => File::class,
    ];

    private static $owns = [
        'Logo',
        'ExtraLogo',
        'FavIcon',
        'NavLogo',
        'FooterLogo',
    ];

    /**
     * @config Rearrange/move site access fields to main tab?
     * @var boolean
     */
    private static $rearrange_access_fields = false;

    /**
     * @config Allow setting browser color theme (eg mobile browser chrome color)
     * @var string | boolean hexadecimal (html) color representation
     */
    private static $enable_browser_color_theme_field = false;

    /**
     * @config Allow activation/inclusion of subnav menu in templates
     * @var boolean
     */
    private static $enable_subnav_activation_field = false;

    /**
     * @config Enable contact/social media fields?
     * @var boolean
     */
    private static $enable_contact_social_media_fields = false;

    /**
     * @config Rearrange/move site access fields to main tab?
     * @var boolean
     */
    private static $enable_raw_head_body_fields = false;

    /**
     * @config Enable logo upload fields in siteconfig
     * @var boolean
     */
    private static $enable_logo_upload_fields = false;

    /**
     * @config Classes available for container elements (eg Bootstrap: container, container-fluid, container-breakpoint etc)
     * @var boolean
     */
    private static $theme_container_classes = [
        'container-fluid', // => '.container-fluid (fluid/100% width at all breakpoints)',
        'container', // => '.container (specific max-width at each responsive breakpoint)',
        'container-sm', // => '.container (fluid/100% width until the sm breakpoint)',
        'container-md', // => '.container (fluid/100% width until the md breakpoint)',
        'container-lg', // => '.container (fluid/100% width until the lg breakpoint)',
        'container-xl', // => '.container (fluid/100% width until the xl breakpoint)',
        'container-xxl', // => '.container (fluid/100% width until the xxl breakpoint)',
    ];

    /**
     * @config Slots available for subnavigation menus
     * @var boolean
     */
    private static $theme_subnav_slots = false;
//    [
//        'before', //
//        'after', //
//        'below', //
//    ];

    /**
     * @param \SilverStripe\Forms\FieldList $fields
     */
    public function updateCMSFields(FieldList $fields)
    {
        # Rearrange/move site access fields if enabled
        if ($this->owner->config()->get('rearrange_access_fields')) {
            foreach ($fields->findTab('Root.Access')->Fields() as $accessField) {
                $fields->addFieldToTab('Root.Main', $accessField);
            }
            $fields->removeByName('Access');
            $fields->findTab('Root.Main')->setTitle(_t(__CLASS__ . '.MainTabTitle', 'Site'));
        }

        # Add contact/social media fields if enabled
        if ($this->owner->config()->get('enable_contact_social_media_fields')) {
            $contactFields = [
                EmailField::create('Email', _t(__CLASS__ . '.Email', 'Email')),
                TextField::create('Phone', _t(__CLASS__ . '.Phone', 'Phone')),
                TextareaField::create('Address', _t(__CLASS__ . '.Address', 'Address'))
                    ->setRows(6),
                TreeDropdownField::create(
                    'ContactPageID',
                    _t(__CLASS__ . '.ContactPage', 'Contact page'),
                    SiteTree::class
                )
                    ->setTitleField('MenuTitle'),
                TextField::create('Facebook'),
                TextField::create('Instagram'),
                TextField::create('Twitter'),
                TextField::create('Linkedin'),
                TextField::create('Youtube'),
                TextField::create('Whatsapp'),
            ];
            GeneralHelpers::add_tab_if_not_exists($fields, 'Contact', _t(__CLASS__ . '.Contact', 'Contact'));
            $fields->addFieldsToTab('Root.Contact', $contactFields);
        }

        # Add logo upload fields if enabled
        if ($this->owner->config()->get('enable_logo_upload_fields')) {
            $logoFields = [
                'Logo' => UploadField::create('Logo', _t(__CLASS__ . '.Logo', 'Logo links')),
                'ExtraLogo' => UploadField::create('ExtraLogo', _t(__CLASS__ . '.ExtraLogo', 'Extra logo')),
                'FavIcon' => UploadField::create('FavIcon', _t(__CLASS__ . '.FavIcon', 'Browsericoon (favicon)')),
                'NavLogo' => UploadField::create('NavLogo', _t(__CLASS__ . '.NavLogo', 'Logo/icoon in navigatiebalk')),
                'FooterLogo' => UploadField::create('FooterLogo', _t(__CLASS__ . '.FooterLogo', 'Footer logo')),
            ];
            foreach ($logoFields as $fieldName => $field){
                /** @var UploadField $field */
                $field
                    ->setAllowedExtensions(array_merge(File::get_category_extensions('image/supported'), ['svg']))
                    ->setFolderName('logos');
            }
            GeneralHelpers::add_tab_if_not_exists($fields, 'Logos', _t(__CLASS__ . '.Logos', 'Logos'));
            $fields->addFieldsToTab('Root.Logos', $logoFields);
        }

        # Allow setting browser color theme (eg mobile browser chrome color)
        if ($presetcolor = $this->owner->config()->get('enable_browser_color_theme_field')) {
            GeneralHelpers::add_tab_if_not_exists($fields, 'ThemeHTML', _t(__CLASS__ . '.ThemeHTML', 'Thema/HTML'));
            $fields->addFieldToTab('Root.ThemeHTML',
                $browersThemeColorField = TextField::create('ThemeBrowserColor', _t(__CLASS__ . '.ThemeBrowserColor', '(Mobile) browser color theme'))
                    ->setAttribute('type', 'color')
                    ->setAttribute('style', 'padding: 0 2px; width: calc(1.5384em + 1.077rem + 2px);')
            );
            if(strpos($presetcolor, '#')===0){
                $browersThemeColorField->setDescription( // or setRightTitle (at right but with a large gap due to resizing the field)
                    sprintf(_t(__CLASS__ . '.ThemeBrowserColorSuggested', 'Suggested HEX: %s'), $presetcolor)
                );
            }
        }

        # Set container type (via TextField instead of Dropdown so we can set/use predefined suggestions but allow custom input as well)
        if ($containerClassOptions = $this->owner->config()->get('theme_container_classes')) {
            GeneralHelpers::add_tab_if_not_exists($fields, 'ThemeHTML', _t(__CLASS__ . '.ThemeHTML', 'Thema/HTML'));
            $fields->addFieldsToTab('Root.ThemeHTML', [

                // HTML5 <datalist> tag to provide "autocomplete" for <input>, users will see a dropdown of pre-defined options as they input data
//                $containerclassField = TextField::create('ThemeContainerClass', _t(__CLASS__ . '.ThemeContainerClass', 'Container type/class'))
//                    ->setAttribute('list', 'theme_container_class_options'),
//                LiteralField::create('theme_container_class_options',
//                    '<datalist id="theme_container_class_options"><option value="' . implode('"><option value="', $containerClassOptions) . '"></datalist>')

                // Switching to regular dropdown instead...
                $containerclassField = DropdownField::create('ThemeContainerClass', _t(__CLASS__ . '.ThemeContainerClass', 'Container type/class'))
                    ->setSource(array_combine($containerClassOptions, $containerClassOptions)),
            ]);
        }

        # Select subnav location (if any)
        if ($subNavSlotOptions = $this->owner->config()->get('theme_subnav_slots')) {
            GeneralHelpers::add_tab_if_not_exists($fields, 'ThemeHTML', _t(__CLASS__ . '.ThemeHTML', 'Thema/HTML'));
            $translatedSubNavSlotOptions = GeneralHelpers::get_options_translations($subNavSlotOptions, __CLASS__ . '.ThemeSubNavSlot_');
            $fields->addFieldsToTab('Root.ThemeHTML', [
                $subNavToggleField = DropdownField::create('ThemeSubNavSlot', _t(__CLASS__ . '.ThemeSubNavSlot', 'Locatie submenu'))
//                    ->setSource(array_combine($subNavSlotOptions, $subNavSlotOptions))
                    ->setSource($translatedSubNavSlotOptions)
                    ->setEmptyString(_t(__CLASS__ . '.ThemeSubNavSlot_none', 'Geen submenu'))
            ]);
        }

        # Add head/body RAW fields if enabled
        if ($this->owner->config()->get('enable_raw_head_body_fields')) {
            GeneralHelpers::add_tab_if_not_exists($fields, 'ThemeHTML', _t(__CLASS__ . '.ThemeHTML', 'Thema/HTML'));
            $fields->addFieldsToTab('Root.ThemeHTML', [
                HeaderField::create('ExtraHTML_HeadHeader', _t(__CLASS__ . '.ExtraHTML_HeadHeader', 'Insert extra html tags in <head> section')),
                TextareaField::create('ExtraHTML_HeadStart', _t(__CLASS__ . '.ExtraHTML_HeadStart', 'After head open-tag')),
                TextareaField::create('ExtraHTML_HeadEnd', _t(__CLASS__ . '.ExtraHTML_HeadEnd', 'Before head close-tag')),
                HeaderField::create('ExtraHTML_BodyHeader', _t(__CLASS__ . '.ExtraHTML_BodyHeader', 'Insert extra html tags in <body> section')),
                TextareaField::create('ExtraHTML_BodyStart', _t(__CLASS__ . '.ExtraHTML_BodyStart', 'After body open-tag')),
                TextareaField::create('ExtraHTML_BodyEnd', _t(__CLASS__ . '.ExtraHTML_BodyEnd', 'Before body close-tag')),
            ]);
        }
    }

    public function ContainerClass()
    {
        if($this->owner->ThemeContainerClass) {
            return $this->owner->ThemeContainerClass;
        }
        // else fallback to first option
        $containerClassOptions = $this->owner->config()->get('theme_container_classes');
        if(is_array($containerClassOptions) && count($containerClassOptions)){
            return $containerClassOptions[0];
        }
    }

    public function ImagePlaceholder($W, $H, $Label='', $AddClass='', $DataUriBase64=false)
    {
        $svgStr = $this->owner
            ->customise([
                'W' => (int) $W,
                'H' => (int) $H,
                'Label' => Convert::raw2xml($Label),
                'AddClass' => Convert::raw2att($AddClass)
            ])
            ->renderWith('Includes\ImagePlaceholder');
        $svgStr = str_replace('<br />', '', $svgStr);
        $svgStr = str_replace("\n", '', $svgStr);

        // just always base64 escape to circumvent issues with svg-in-html-attribute (eg '"'s)
//        return $Base64 ? "data:image/svg+xml;base64,".base64_encode($svgStr) : "data:image/svg+xml;utf8,{$svgStr}";
        return $DataUriBase64 ? "data:image/svg+xml;base64,".base64_encode($svgStr) : DBHTMLVarchar::create()->setValue($svgStr);
    }

}
