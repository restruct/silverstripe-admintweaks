<?php

namespace Restruct\Silverstripe\AdminTweaks\Extensions;

use SilverStripe\Core\ClassInfo;
use SilverStripe\Core\Config\Config;
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
     *
     * THREE states since #108 (it was a plain bool defaulting to false):
     *   null  - default: load it only when a page type picks a Bootstrap Icons class as its CMS
     *           icon (`icon_class: 'bi-briefcase'`), see pageTypesUseBootstrapIcons(). Without
     *           the stylesheet such a page type has no icon at all, since admintweaks' CSS leaves
     *           out its SVG to make room for the glyph.
     *   true  - always load it.
     *   false - never load it (eg a site that serves the font itself under its own CSP).
     */
    //private static bool $include_bootstrap_icons = false;
    private static ?bool $include_bootstrap_icons = null;

    # CDN URL for the full Bootstrap Icons CSS (font + all icon classes)
    private static string $bootstrap_icons_cdn_url = 'https://cdn.jsdelivr.net/npm/bootstrap-icons@1.13.1/font/bootstrap-icons.min.css';

    public function init(): void
    {
        $includeBootstrapIcons = $this->getOwner()->config()->get('include_bootstrap_icons');
        # null = not decided by the project: load the icons only where a page type needs them (#108)
        if ($includeBootstrapIcons === null) {
            $includeBootstrapIcons = static::pageTypesUseBootstrapIcons();
        }
        //if ($this->getOwner()->config()->get('include_bootstrap_icons')) {
        if ($includeBootstrapIcons) {
            $cdnUrl = $this->getOwner()->config()->get('bootstrap_icons_cdn_url');
            Requirements::css($cdnUrl);
        }
    }

    /**
     * Whether any page type's CMS icon class is a Bootstrap Icons class (`bi-*`), as in
     * `icon_class: 'bi-briefcase'` (#108). Drives the default of `include_bootstrap_icons`.
     *
     * Reads `icon_class`, the name Silverstripe 4 and 5 render (SiteTree::getTreeTitle(),
     * CMSPageAddController). Silverstripe 6 renamed it to `cms_icon_class`.
     */
    public static function pageTypesUseBootstrapIcons(): bool
    {
        # By class-string: silverstripe/cms is not a requirement of this module
        $siteTree = 'SilverStripe\\CMS\\Model\\SiteTree';
        if (!class_exists($siteTree)) {
            return false;
        }

        # Config is cached, and a site has tens of page types rather than thousands, so this is
        # cheap enough to run on each admin request instead of caching the answer separately
        foreach (ClassInfo::subclassesFor($siteTree) as $class) {
            $iconClass = (string) Config::inst()->get($class, 'icon_class');
            # A bi-* class anywhere in the value: 'bi-briefcase' as well as 'bi bi-briefcase'
            if (preg_match('/(^|\\s)bi-/', $iconClass)) {
                return true;
            }
        }

        return false;
    }
}
