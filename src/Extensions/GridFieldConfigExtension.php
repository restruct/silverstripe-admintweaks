<?php

namespace Restruct\Silverstripe\AdminTweaks\Extensions;

use SilverStripe\Core\Extension;
use SilverStripe\Forms\GridField\GridFieldConfig;
use SilverStripe\Forms\GridField\GridFieldPageCount;

/**
 * Extension on GridFieldConfig to remove the duplicate record count from the toolbar header.
 *
 * GridFieldPageCount renders "View X–Y of Z" in toolbar-header-right, but the same
 * information is already shown in the GridField footer via GridFieldFooter.
 * This extension removes the duplicate via the updateConfig hook.
 */
class GridFieldConfigExtension extends Extension
{
    /**
     * Remove GridFieldPageCount from any GridFieldConfig as it's constructed.
     */
    public function updateConfig()
    {
        $this->owner->removeComponentsByType(GridFieldPageCount::class);
    }
}
