<?php

namespace Restruct\Silverstripe\AdminTweaks\Extensions;

use Restruct\Silverstripe\AdminTweaks\SystemSwitcher;
use SilverStripe\Core\Extension;
use SilverStripe\Core\Manifest\ModuleLoader;

/**
 * LeftAndMain side of the CMS system switcher (admintweaks#71, see SystemSwitcher).
 *
 * - Gives the menu header template its data ($AdminSystemSwitcher, read by this module's override
 *   of SilverStripe\Admin\Includes\LeftAndMain_MenuLogo.ss).
 * - Makes the admin's application_name the switcher's current label (name + environment suffix),
 *   so the browser tab reads "Example SITE (TEST) - Pages".
 *
 * Why application_name is adjusted at init: core LeftAndMain::getApplicationName() only reads the
 * `application_name` config and offers no extension hook, and it feeds the <title> (Title()), the
 * X-Title header the CMS uses to retitle the tab after every pjax navigation, and $ApplicationName
 * in the menu templates. Setting the config for the handling controller's class before its init()
 * reaches all three without a LeftAndMain subclass or a copy of the LeftAndMain.ss page template.
 * The value is computed from the configured name each time (SystemSwitcher strips a suffix that is
 * already there), so a second init in the same request, or an application_name that already ends
 * in the suffix, never doubles it. LeftAndMain's own config is not touched, so anything outside the
 * admin reading LeftAndMain.application_name (eg login-branding's SiteConfig title override) keeps
 * the configured name.
 *
 * Does nothing while no `systems` are configured.
 */
class SystemSwitcherExtension extends Extension
{
    # protected, like core's own SS6 hooks: invoked by Extension::invokeExtension(), not callable on the owner
    protected function onBeforeInit(): void
    {
        $switcher = SystemSwitcher::singleton();
        if (!$switcher->isEnabled()) {
            return;
        }

        # Config::modify() for the owner's class only, for the rest of this request
        $this->getOwner()->config()->set('application_name', $switcher->getCurrentLabel());
    }

    /**
     * The switcher for the menu header template, or null while it is off (core header unchanged).
     */
    public function AdminSystemSwitcher(): ?SystemSwitcher
    {
        $switcher = SystemSwitcher::singleton();
        return $switcher->isEnabled() ? $switcher : null;
    }

    /**
     * Core's own LeftAndMain_MenuLogo.ss, rendered from silverstripe/admin's file by path.
     *
     * This module overrides that include (templates/SilverStripe/Admin/Includes/), so a plain
     * <% include %> of it would render the override again. Rendering core's file instead of a copy
     * of its markup keeps the header byte-for-byte what the installed admin ships (it changes between
     * releases, eg the aria-label added in 6) while the switcher is off. The template engine accepts
     * a path to an .ss file as a template (SSTemplateEngine::findTemplate()).
     */
    public function AdminCoreMenuLogo()
    {
        $resource = ModuleLoader::getModule('silverstripe/admin')
            ?->getResource('templates/SilverStripe/Admin/Includes/LeftAndMain_MenuLogo.ss');
        if (!$resource || !$resource->exists()) {
            return null;
        }

        return $this->getOwner()->renderWith($resource->getPath());
    }
}
