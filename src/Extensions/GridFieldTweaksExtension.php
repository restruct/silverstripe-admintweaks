<?php

namespace Restruct\Silverstripe\AdminTweaks\Extensions;

use SilverStripe\Core\Extension;
use SilverStripe\Forms\GridField\GridFieldPageCount;

/**
 * Removes GridFieldPageCount from the toolbar header row.
 *
 * The record count is already shown in the GridField footer via GridFieldFooter,
 * so the toolbar-header-right duplicate is redundant and wastes vertical space.
 */
class GridFieldTweaksExtension extends Extension
{
    /**
     * Remove GridFieldPageCount before GridField renders.
     */
    public function onBeforeRender()
    {
        $this->owner->getConfig()->removeComponentsByType(GridFieldPageCount::class);
    }
}
