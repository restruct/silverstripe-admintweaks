<?php

namespace Restruct\Silverstripe\AdminTweaks\Extensions;

use SilverStripe\CMS\Model\SiteTree;
use SilverStripe\Forms\FieldList;
use SilverStripe\Forms\Tab;
use SilverStripe\Forms\TextField;
use SilverStripe\Forms\TreeDropdownField;
use SilverStripe\ORM\DataExtension;

class SiteConfigExtension extends DataExtension
{
    private static $db = [
        'Facebook' => 'Varchar(255)',
        'Instagram' => 'Varchar(255)',
        'Twitter' => 'Varchar(255)',
        'Linkedin' => 'Varchar(255)',
        'Youtube' => 'Varchar(255)',
        'Whatsapp' => 'Varchar(255)',
    ];

    private static $has_one = [
        'ContactPage' => SiteTree::class,
    ];

    /**
     * @param \SilverStripe\Forms\FieldList $fields
     */
    public function updateCMSFields(FieldList $fields)
    {
        foreach ($fields->findTab('Root.Access')->Fields() as $accessField){
            $fields->addFieldToTab('Root.Main', $accessField);
        }
        $fields->removeByName('Access');

//        $fields->addFieldToTab('Root.Main',
//
//        );

        $contactFields = [
            TreeDropdownField::create(
                    'ContactPageID',
                _t(__CLASS__ . '.ContactPage', 'Contact page'),
                    SiteTree::class
                )->setTitleField('MenuTitle'),
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
