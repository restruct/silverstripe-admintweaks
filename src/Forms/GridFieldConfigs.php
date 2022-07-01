<?php

namespace Restruct\Silverstripe\AdminTweaks\Forms;

use App\Forms\GridFieldVersionedOrderableRows;
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

    /**
     * Copied from bigfork/silverstripe-recipe
     * @param $showAdd
     * @return GridFieldConfig
     */
    public static function filterable_orderable_recordeditor($showAdd = null)
    {
        $conf = GridFieldConfig::create()
            ->addComponent(new GridFieldButtonRow('before'))
            ->addComponent(new GridFieldAddNewButton('buttons-before-left'))
            ->addComponent(new GridFieldToolbarHeader())
            ->addComponent($sort = new GridFieldSortableHeader())
            ->addComponent($filter = new GridFieldFilterHeader())
            ->addComponent(new GridFieldDataColumns())
            ->addComponent(new GridFieldEditButton())
            ->addComponent(new GridFieldDeleteAction())
            ->addComponent(new GridField_ActionMenu())
            ->addComponent(new GridFieldDetailForm(null, null, $showAdd))
            ->addComponent(new GridFieldVersionedOrderableRows());

        $sort->setThrowExceptionOnBadDataType(false);
        $filter->setThrowExceptionOnBadDataType(false);

        return $conf;
    }

}
