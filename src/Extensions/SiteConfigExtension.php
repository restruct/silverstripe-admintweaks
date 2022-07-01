<?php

namespace Restruct\Silverstripe\AdminTweaks\Extensions;

use SilverStripe\CMS\Model\SiteTree;
use SilverStripe\Forms\EmailField;
use SilverStripe\Forms\FieldList;
use SilverStripe\Forms\HeaderField;
use SilverStripe\Forms\LiteralField;
use SilverStripe\Forms\Tab;
use SilverStripe\Forms\TextareaField;
use SilverStripe\Forms\TextField;
use SilverStripe\Forms\TreeDropdownField;
use SilverStripe\ORM\DataExtension;

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

        'BrowserColorTheme' => 'Varchar(7)', // including '#'
    ];

    private static $has_one = [
        'ContactPage' => SiteTree::class,
    ];

    /**
     * @config Rearrange/move site access fields to main tab?
     * @var boolean
     */
    private static $rearrange_access_fields = false;

    /**
     * @config Allow setting browser color theme (eg mobile browser chrome color)
     * @var string hexadecimal (html) color representation
     */
    private static $enable_browser_color_theme_field = false;

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
     * @param \SilverStripe\Forms\FieldList $fields
     */
    public function updateCMSFields(FieldList $fields)
    {
        # Allow setting browser color theme (eg mobile browser chrome color)
        if ($presetcolor = $this->owner->config()->get('enable_browser_color_theme_field')) {
            $fields->addFieldToTab(
                'Root.Main',
                $browersThemeColorField = TextField::create('BrowserColorTheme', _t(__CLASS__ . '.BrowserColorTheme', 'Browser theme color'))
                    ->setAttribute('type', 'color')
                    ->setAttribute('style', 'padding: 0 2px; width: calc(1.5384em + 1.077rem + 2px);')
            );
            if(strpos($presetcolor, '#')===0){
                $browersThemeColorField->setDescription( // or setRightTitle (at right but with a large gap due to resizing the field)
                    sprintf(_t(__CLASS__ . '.BrowserColorThemeSuggested', 'Suggested HEX: %s'), $presetcolor)
                );
            }
        }

        # Rearrange/move site access fields if enabled
        if ($this->owner->config()->get('rearrange_access_fields')) {
            foreach ($fields->findTab('Root.Access')->Fields() as $accessField) {
                $fields->addFieldToTab('Root.Main', $accessField);
            }
            $fields->removeByName('Access');
        }

        # Add head/body RAW fields if enabled
        if ($this->owner->config()->get('enable_raw_head_body_fields')) {
            $fields->insertAfter('Main',
                Tab::create('ExtraHTMLTags', _t(__CLASS__ . '.ExtraHTMLTags', 'Extra HTML')),
                true
            );
            $fields->addFieldsToTab(
                'Root.ExtraHTMLTags',
                [
                    HeaderField::create('ExtraHTML_HeadHeader', _t(__CLASS__ . '.ExtraHTML_HeadHeader', 'Insert extra html tags in <head> section')),
                    TextareaField::create('ExtraHTML_HeadStart', _t(__CLASS__ . '.ExtraHTML_HeadStart', 'After head open-tag')),
                    TextareaField::create('ExtraHTML_HeadEnd', _t(__CLASS__ . '.ExtraHTML_HeadEnd', 'Before head close-tag')),
                    HeaderField::create('ExtraHTML_BodyHeader', _t(__CLASS__ . '.ExtraHTML_BodyHeader', 'Insert extra html tags in <body> section')),
                    TextareaField::create('ExtraHTML_BodyStart', _t(__CLASS__ . '.ExtraHTML_BodyStart', 'After body open-tag')),
                    TextareaField::create('ExtraHTML_BodyEnd', _t(__CLASS__ . '.ExtraHTML_BodyEnd', 'Before body close-tag')),
                ]
            );
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

            $fields->insertAfter('Main',
                Tab::create('Contact', _t(__CLASS__ . '.Contact', 'Contact')),
                true
            );
            $fields->addFieldsToTab('Root.Contact', $contactFields);
        }
    }

}
