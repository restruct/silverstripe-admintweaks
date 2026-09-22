<?php

namespace Restruct\Silverstripe\AdminTweaks\Extensions;

use Psr\Log\LoggerInterface;
use SilverStripe\Admin\LeftAndMain;
use SilverStripe\Control\Director;
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
     */
    private static bool $include_bootstrap_icons = false;

    # CDN URL for the full Bootstrap Icons CSS (font + all icon classes)
    private static string $bootstrap_icons_cdn_url = 'https://cdn.jsdelivr.net/npm/bootstrap-icons@1.13.1/font/bootstrap-icons.min.css';

    /**
     * @config
     * Hide the CMS sections most clients never use (Reports and Campaigns) from the left menu.
     *
     * OPT-IN since 5.0 (admintweaks#54). Up to 3.x this module hid both sections by hardcoding
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

    public function init(): void
    {
        if ($this->getOwner()->config()->get('include_bootstrap_icons')) {
            $cdnUrl = $this->getOwner()->config()->get('bootstrap_icons_cdn_url');
            Requirements::css($cdnUrl);
        }
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
