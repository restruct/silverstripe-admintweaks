<?php

namespace Restruct\Silverstripe\AdminTweaks\Forms;

use SilverStripe\Forms\GridField\GridFieldButtonRow;
    use SilverStripe\Forms\GridField\GridFieldConfig;
    use SilverStripe\Forms\GridField\GridFieldDeleteAction;
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
   public static function editable_orderable()
    {
        return GridFieldConfig::create()
            ->addComponent(new GridFieldButtonRow('after'))
            ->addComponent(new GridFieldToolbarHeader())
            ->addComponent(new GridFieldTitleHeader())
            ->addComponent(new GridFieldEditableColumns())
            ->addComponent(new GridFieldOrderableRows())
            ->addComponent(new GridFieldDeleteAction())
            ->addComponent(new GridFieldAddNewInlineButton('buttons-after-left'));
    }


}