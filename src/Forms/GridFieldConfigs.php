<?php

namespace Restruct\Silverstripe\AdminTweaks\Forms;

use SilverStripe\Forms\GridField\GridField_ActionMenu;
use SilverStripe\Forms\GridField\GridFieldAddNewButton;
use SilverStripe\Forms\GridField\GridFieldButtonRow;
use SilverStripe\Forms\GridField\GridFieldConfig;
use SilverStripe\Forms\GridField\GridFieldDataColumns;
use SilverStripe\Forms\GridField\GridFieldDeleteAction;
use SilverStripe\Forms\GridField\GridFieldDetailForm;
use SilverStripe\Forms\GridField\GridFieldEditButton;
use SilverStripe\Forms\GridField\GridFieldFilterHeader;
use SilverStripe\Forms\GridField\GridFieldSortableHeader;
use SilverStripe\Forms\GridField\GridFieldToolbarHeader;
use Symbiote\GridFieldExtensions\GridFieldAddNewInlineButton;
use Symbiote\GridFieldExtensions\GridFieldEditableColumns;
use Symbiote\GridFieldExtensions\GridFieldOrderableRows;
use Symbiote\GridFieldExtensions\GridFieldTitleHeader;

class GridFieldConfigs
{
    /**
     * EditableOrderable gridfieldconfig
     */
    public static function editable_orderable(): GridFieldConfig
    {
        return GridFieldConfig::create()
            ->addComponent(GridFieldButtonRow::create('after'))
            ->addComponent(GridFieldToolbarHeader::create())
            ->addComponent(new GridFieldTitleHeader())
            ->addComponent(new GridFieldEditableColumns())
            ->addComponent(new GridFieldOrderableRows())
            ->addComponent(GridFieldDeleteAction::create())
            ->addComponent(new GridFieldAddNewInlineButton('buttons-after-left'));
    }

    /**
     * Copied from bigfork/silverstripe-recipe
     * @param $showAdd
     */
    public static function filterable_orderable_recordeditor($showAdd = null): GridFieldConfig
    {
        return GridFieldConfig::create()
            ->addComponent(GridFieldButtonRow::create('before'))
            ->addComponent(GridFieldAddNewButton::create('buttons-before-left'))
            ->addComponent(GridFieldToolbarHeader::create())
            ->addComponent(GridFieldSortableHeader::create())
            ->addComponent(GridFieldFilterHeader::create())
            ->addComponent(GridFieldDataColumns::create())
            ->addComponent(GridFieldEditButton::create())
            ->addComponent(GridFieldDeleteAction::create())
            ->addComponent(GridField_ActionMenu::create())
            ->addComponent(GridFieldDetailForm::create(null, null, $showAdd));
    }

}
