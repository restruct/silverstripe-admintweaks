<?php

namespace Restruct\Silverstripe\AdminTweaks\Extensions;

use SilverStripe\Core\Extension;
use SilverStripe\View\Requirements;

/**
 * Extension for LeftAndMain to conditionally load optional CSS.
 */
class LeftAndMainExtension extends Extension
{
    /**
     * @config
     * Whether to include Bootstrap Icons CSS for .bi-* button classes.
     * The icon font is loaded from CDN for better browser caching.
     */
    private static bool $include_bootstrap_icons = true;

    public function init(): void
    {
        if ($this->getOwner()->config()->get('include_bootstrap_icons')) {
            Requirements::css('restruct/silverstripe-admintweaks:client/dist/css/bs-icons.css');
        }
    }
}
