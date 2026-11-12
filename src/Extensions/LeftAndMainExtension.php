<?php

namespace Restruct\Silverstripe\AdminTweaks\Extensions;

use Psr\Log\LoggerInterface;
use SilverStripe\Admin\LeftAndMain;
use SilverStripe\Control\Director;
use SilverStripe\Core\ClassInfo;
use SilverStripe\Core\Config\Config;
use SilverStripe\Core\Extension;
use SilverStripe\Core\Injector\Injector;
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
     *           icon (`cms_icon_class: 'bi-briefcase'`), see pageTypesUseBootstrapIcons(). Without
     *           the stylesheet such a page type has no icon at all, since admintweaks' CSS leaves
     *           out its SVG to make room for the glyph.
     *   true  - always load it.
     *   false - never load it (eg a site that serves the font itself under its own CSP).
     */
    //private static bool $include_bootstrap_icons = false;
    private static ?bool $include_bootstrap_icons = null;

    # CDN URL for the full Bootstrap Icons CSS (font + all icon classes)
    private static string $bootstrap_icons_cdn_url = 'https://cdn.jsdelivr.net/npm/bootstrap-icons@1.13.1/font/bootstrap-icons.min.css';

    /**
     * @config
     * Whether admintweaks draws its own page icons in the CMS: an SVG icon per page type, picked by
     * class name, and the default page icon for every other type, in place of core's page icons
     * (site tree, "Add page" type picker).
     *
     * Default on. Set false to keep core's page icons (and any `cms_icon` image a page type sets)
     * exactly as core renders them. A page type that picks a Bootstrap Icons class itself
     * (`cms_icon_class: 'bi-briefcase'`) still shows that glyph either way: that styling sits in
     * admintweaks.css, which always loads, and only the SVG icons sit in the file this switches.
     */
    private static bool $page_icons = true;

    # The stylesheet `page_icons` switches (client/src/scss/admintweaks-page-icons.scss)
    public const PAGE_ICONS_CSS = 'restruct/silverstripe-admintweaks:client/dist/css/admintweaks-page-icons.css';

    /**
     * @config
     * Hide the CMS sections most clients never use (Reports and Campaigns) from the left menu.
     *
     * OPT-IN since 4.1 (admintweaks#54). Up to 3.x this module hid both sections by hardcoding
     * `ignore_menuitem: true` on their controllers, which is surprising for a QoL module: the
     * section keeps working at its URL (only the menu item goes), so a project that adds its own
     * Report subclass sees it "not register" and goes looking for a manifest or flush problem.
     *
     * THREE states, deliberately nullable:
     *   null  - not decided. Sections are SHOWN, and dev installs get a one-off log notice, because
     *           this is exactly the population that may have been relying on the 3.x default.
     *   true  - hide them.
     *   false - show them, silently. Setting this is how you acknowledge the change and stop the notice.
     *
     * Applied at RUNTIME from _config.php rather than by shipping YAML, which is the point: a
     * project's own `ignore_menuitem` value then always wins. Under the 3.x YAML approach a project
     * override silently lost unless its fragment carried `After: '#admintweaks-leftandmain'`, since
     * same-key scalar config is decided by fragment order, not by project-beats-vendor.
     */
    private static ?bool $hide_rarely_used_menu_sections = null;

    /**
     * The CMS sections `hide_rarely_used_menu_sections` covers.
     *
     * Referenced by class-string rather than imported: silverstripe/reports and
     * silverstripe/campaign-admin are both optional, and campaign-admin is no longer part of
     * recipe-cms in SS6 at all.
     */
    public const RARELY_USED_ADMIN_SECTIONS = [
        'SilverStripe\\Reports\\ReportAdmin',
        'SilverStripe\\CampaignAdmin\\CampaignAdmin',
    ];

    /**
     * Was `init()`, the hook name LeftAndMain fired up to Silverstripe 5. On 6 it fires `onInit`
     * instead (AdminController::init()), so under the old name `include_bootstrap_icons: true`
     * silently loaded nothing on 4.x (found with #108).
     */
    //public function init(): void
    public function onInit(): void
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

        # admintweaks' SVG page icons, a stylesheet of their own so a project can opt out. Required
        # here rather than in extra_requirements_css, which has no off switch per entry; this hook
        # runs after LeftAndMain has required extra_requirements_css, so it still comes after
        # admintweaks.css, as it did while these rules sat inside that file.
        if ($this->getOwner()->config()->get('page_icons')) {
            Requirements::css(self::PAGE_ICONS_CSS);
        }
    }

    /**
     * Whether any page type's CMS icon class is a Bootstrap Icons class (`bi-*`), as in
     * `cms_icon_class: 'bi-briefcase'` (#108). Drives the default of `include_bootstrap_icons`.
     *
     * Reads `cms_icon_class`, the name Silverstripe 6 renders (CMSMain::getRecordIconCssClass());
     * Silverstripe 5's `icon_class` is no longer read by core on 6, so a page type upgraded from
     * 5 has to rename it.
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
            $iconClass = (string) Config::inst()->get($class, 'cms_icon_class');
            # A bi-* class anywhere in the value: 'bi-briefcase' as well as 'bi bi-briefcase'
            if (preg_match('/(^|\\s)bi-/', $iconClass)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Apply `hide_rarely_used_menu_sections` to the admin sections it covers.
     *
     * Called from _config.php at boot. It lives here rather than inline there so the three states
     * can actually be tested - code in _config.php runs once, before any test exists.
     *
     * Setting `ignore_menuitem` at RUNTIME is the fix for admintweaks#54: CMSMenu reads it off the
     * controller's config (CMSMenu::menuitem_for_controller), and a same-key scalar is decided by
     * config FRAGMENT ORDER rather than project-beats-vendor - so the YAML this replaces could only
     * be overridden by a project fragment carrying `After: '#admintweaks-leftandmain'`, and silently
     * lost otherwise. A runtime set is always beaten by a project that sets `ignore_menuitem` itself.
     */
    public static function applyMenuVisibilityConfig(): void
    {
        $hide = Config::inst()->get(LeftAndMain::class, 'hide_rarely_used_menu_sections');

        if ($hide === true) {
            foreach (self::RARELY_USED_ADMIN_SECTIONS as $adminClass) {
                // Only touch sections that are actually installed: both modules are optional, and
                // campaign-admin is not part of recipe-cms on SS6 at all.
                if (class_exists($adminClass)) {
                    Config::modify()->set($adminClass, 'ignore_menuitem', true);
                }
            }

            return;
        }

        // null means the project has not decided - exactly the population that may have been relying
        // on the 3.x default of hiding these. Say so once, on dev only; setting the config either way
        // acknowledges the change and silences it.
        if ($hide === null && Director::isDev()) {
            Injector::inst()->get(LoggerInterface::class)->notice(
                'admintweaks 4.0: the Reports and Campaigns CMS menu items are no longer hidden by '
                . 'default (admintweaks#54). Set '
                . 'SilverStripe\\Admin\\LeftAndMain.hide_rarely_used_menu_sections to true to keep '
                . 'hiding them, or to false to accept the new default and silence this notice. '
                . 'See UPGRADING.md.'
            );
        }
    }
}
