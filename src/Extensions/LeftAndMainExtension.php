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
     * Whether to include Bootstrap Icons from CDN for .bi-* button classes.
     * When enabled, loads the full Bootstrap Icons CSS from jsDelivr CDN.
     */
    private static bool $include_bootstrap_icons = false;

    # CDN URL for the full Bootstrap Icons CSS (font + all icon classes)
    private static string $bootstrap_icons_cdn_url = 'https://cdn.jsdelivr.net/npm/bootstrap-icons@1.13.1/font/bootstrap-icons.min.css';

    public function init(): void
    {
        if ($this->getOwner()->config()->get('include_bootstrap_icons')) {
            $cdnUrl = $this->getOwner()->config()->get('bootstrap_icons_cdn_url');
            Requirements::css($cdnUrl);
        }
    }
}
