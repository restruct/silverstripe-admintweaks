<?php

namespace Restruct\Silverstripe\AdminTweaks\Extensions;

use SilverStripe\Core\Extension;
use Restruct\Silverstripe\AdminTweaks\Helpers\GeneralHelpers;
use SilverStripe\AssetAdmin\Forms\UploadField;
use SilverStripe\Assets\File;
use SilverStripe\CMS\Model\SiteTree;
use SilverStripe\Forms\DropdownField;
use SilverStripe\Forms\EmailField;
use SilverStripe\Forms\FieldList;
use SilverStripe\Forms\HeaderField;
use SilverStripe\Forms\TextareaField;
use SilverStripe\Forms\TextField;
use SilverStripe\Forms\TreeDropdownField;

class SiteConfigExtension extends Extension
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
    public function updateCMSFields(FieldList $fields)
    {
        # Rearrange/move site access fields if enabled
        if ($this->owner->config()->get('rearrange_access_fields')) {
            foreach ($fields->findTab('Root.Access')->Fields() as $accessField) {
                $fields->addFieldToTab('Root.Main', $accessField);
            }

            $fields->removeByName('Access');
            $fields->findTab('Root.Main')->setTitle(_t(self::class . '.MainTabTitle', 'Site'));
        }

        # Add contact/social media fields if enabled
        if ($this->owner->config()->get('enable_contact_social_media_fields')) {
            $contactFields = [
                EmailField::create('Email', _t(self::class . '.Email', 'Email')),
                TextField::create('Phone', _t(self::class . '.Phone', 'Phone')),
                TextareaField::create('Address', _t(self::class . '.Address', 'Address'))
                    ->setRows(6),
                TreeDropdownField::create(
                    'ContactPageID',
                    _t(self::class . '.ContactPage', 'Contact page'),
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
            GeneralHelpers::add_tab_if_not_exists($fields, 'Contact', _t(self::class . '.Contact', 'Contact'));
            $fields->addFieldsToTab('Root.Contact', $contactFields);
        }

        # Add logo upload fields if enabled
        if ($this->owner->config()->get('enable_logo_upload_fields')) {
            $logoFields = [
                'Logo' => UploadField::create('Logo', _t(self::class . '.Logo', 'Logo links')),
                'ExtraLogo' => UploadField::create('ExtraLogo', _t(self::class . '.ExtraLogo', 'Extra logo')),
                'FavIcon' => UploadField::create('FavIcon', _t(self::class . '.FavIcon', 'Browsericoon (favicon)')),
                'NavLogo' => UploadField::create('NavLogo', _t(self::class . '.NavLogo', 'Logo/icoon in navigatiebalk')),
                'FooterLogo' => UploadField::create('FooterLogo', _t(self::class . '.FooterLogo', 'Footer logo')),
            ];
            foreach ($logoFields as $field){
                /** @var UploadField $field */
                $field
                    ->setAllowedExtensions(array_merge(File::get_category_extensions('image/supported'), ['svg']))
                    ->setFolderName('logos');
            }

            GeneralHelpers::add_tab_if_not_exists($fields, 'Logos', _t(self::class . '.Logos', 'Logos'));
            $fields->addFieldsToTab('Root.Logos', $logoFields);
        }

        # Allow setting browser color theme (eg mobile browser chrome color)
        if ($presetcolor = $this->owner->config()->get('enable_browser_color_theme_field')) {
            GeneralHelpers::add_tab_if_not_exists($fields, 'ThemeHTML', _t(self::class . '.ThemeHTML', 'Thema/HTML'));
            $fields->addFieldToTab('Root.ThemeHTML',
                $browersThemeColorField = TextField::create('ThemeBrowserColor', _t(self::class . '.ThemeBrowserColor', '(Mobile) browser color theme'))
                    ->setAttribute('type', 'color')
                    ->setAttribute('style', 'padding: 0 2px; width: calc(1.5384em + 1.077rem + 2px);')
            );
            if(str_starts_with((string) $presetcolor, '#')){
                $browersThemeColorField->setDescription( // or setRightTitle (at right but with a large gap due to resizing the field)
                    sprintf(_t(self::class . '.ThemeBrowserColorSuggested', 'Suggested HEX: %s'), $presetcolor)
                );
            }
        }

        # Set container type (via TextField instead of Dropdown so we can set/use predefined suggestions but allow custom input as well)
        if ($containerClassOptions = $this->owner->config()->get('theme_container_classes')) {
            GeneralHelpers::add_tab_if_not_exists($fields, 'ThemeHTML', _t(self::class . '.ThemeHTML', 'Thema/HTML'));
            $fields->addFieldsToTab('Root.ThemeHTML', [

                // HTML5 <datalist> tag to provide "autocomplete" for <input>, users will see a dropdown of pre-defined options as they input data
//                $containerclassField = TextField::create('ThemeContainerClass', _t(__CLASS__ . '.ThemeContainerClass', 'Container type/class'))
//                    ->setAttribute('list', 'theme_container_class_options'),
//                LiteralField::create('theme_container_class_options',
//                    '<datalist id="theme_container_class_options"><option value="' . implode('"><option value="', $containerClassOptions) . '"></datalist>')

                // Switching to regular dropdown instead...
                $containerclassField = DropdownField::create('ThemeContainerClass', _t(self::class . '.ThemeContainerClass', 'Container type/class'))
                    ->setSource(array_combine($containerClassOptions, $containerClassOptions)),
            ]);
        }

        # Select subnav location (if any)
        if ($subNavSlotOptions = $this->owner->config()->get('theme_subnav_slots')) {
            GeneralHelpers::add_tab_if_not_exists($fields, 'ThemeHTML', _t(self::class . '.ThemeHTML', 'Thema/HTML'));
            $translatedSubNavSlotOptions = GeneralHelpers::get_options_translations($subNavSlotOptions, self::class . '.ThemeSubNavSlot_');
            $fields->addFieldsToTab('Root.ThemeHTML', [
                $subNavToggleField = DropdownField::create('ThemeSubNavSlot', _t(self::class . '.ThemeSubNavSlot', 'Locatie submenu'))
//                    ->setSource(array_combine($subNavSlotOptions, $subNavSlotOptions))
                    ->setSource($translatedSubNavSlotOptions)
                    ->setEmptyString(_t(self::class . '.ThemeSubNavSlot_none', 'Geen submenu'))
            ]);
        }

        # Add head/body RAW fields if enabled
        if ($this->owner->config()->get('enable_raw_head_body_fields')) {
            GeneralHelpers::add_tab_if_not_exists($fields, 'ThemeHTML', _t(self::class . '.ThemeHTML', 'Thema/HTML'));
            $fields->addFieldsToTab('Root.ThemeHTML', [
                HeaderField::create('ExtraHTML_HeadHeader', _t(self::class . '.ExtraHTML_HeadHeader', 'Insert extra html tags in <head> section')),
                TextareaField::create('ExtraHTML_HeadStart', _t(self::class . '.ExtraHTML_HeadStart', 'After head open-tag')),
                TextareaField::create('ExtraHTML_HeadEnd', _t(self::class . '.ExtraHTML_HeadEnd', 'Before head close-tag')),
                HeaderField::create('ExtraHTML_BodyHeader', _t(self::class . '.ExtraHTML_BodyHeader', 'Insert extra html tags in <body> section')),
                TextareaField::create('ExtraHTML_BodyStart', _t(self::class . '.ExtraHTML_BodyStart', 'After body open-tag')),
                TextareaField::create('ExtraHTML_BodyEnd', _t(self::class . '.ExtraHTML_BodyEnd', 'Before body close-tag')),
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
        return null;
    }

}
