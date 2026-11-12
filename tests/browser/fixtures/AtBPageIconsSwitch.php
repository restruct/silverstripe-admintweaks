<?php

namespace Restruct\AtBrowser;

use SilverStripe\Control\Cookie;
use SilverStripe\Core\Config\Config;
use SilverStripe\Core\Extension;

/**
 * BROWSER-TEST FIXTURE ONLY - lets a spec switch `LeftAndMain.page_icons` off for its own requests
 * (admintweaks' page-icon opt-out), so page-icons-off.spec.ts can look at the real Pages section
 * with the setting off while every other spec runs with the default (on).
 *
 * A request carrying the cookie `atb_page_icons=off` gets `page_icons: false` on its controller's
 * config before anything reads it: onBeforeInit runs ahead of LeftAndMain::init(), which fires the
 * hook (onInit on 6, init on 5) where LeftAndMainExtension decides on the stylesheet. Registered on
 * LeftAndMain by fixtures/_config/pageicons.yml. (See AtBRecord for why this never loads in a real
 * install.)
 */
class AtBPageIconsSwitch extends Extension
{
    public const COOKIE = 'atb_page_icons';

    public function onBeforeInit(): void
    {
        if (Cookie::get(self::COOKIE) === 'off') {
            # On the owner's own class (eg CMSPagesController), the class whose config() is read
            Config::modify()->set(get_class($this->getOwner()), 'page_icons', false);
        }
    }
}
