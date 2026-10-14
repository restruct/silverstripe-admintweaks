<?php

namespace Restruct\AtBrowser;

use Restruct\Silverstripe\AdminTweaks\SystemSwitcher;
use SilverStripe\Admin\ModelAdmin;
use SilverStripe\Core\Config\Config;

/**
 * BROWSER-TEST FIXTURE ONLY - the switcher with a long system name on a "test" environment
 * (admintweaks#79): /admin/atb-longname gives the current system a display name too long for the
 * menu header and the suffix " (TEST)", so the spec can check the environment badge stays whole
 * while only the name is truncated. The host runs in dev mode, so the TEST suffix is configured for
 * dev here, which renders exactly what a test environment does. (See AtBRecord for why this never
 * loads in a real install.)
 */
class AtBLongNameAdmin extends ModelAdmin
{
    public const LONG_NAME = 'Example SITE with a rather long display name that cannot fit the menu header';

    private static $url_segment = 'atb-longname';

    private static $menu_title = 'AdminTweaks long name';

    private static $managed_models = [
        'longname' => ['dataClass' => AtBRecord::class, 'title' => 'Long name'],
    ];

    public function doInit()
    {
        # Before parent::doInit(), which runs the onBeforeInit extension hooks (the admin title)
        $systems = (array) Config::inst()->get(SystemSwitcher::class, 'systems');
        $systems['site']['display'] = self::LONG_NAME;
        Config::modify()->set(SystemSwitcher::class, 'systems', $systems);
        Config::modify()->set(SystemSwitcher::class, 'environment_suffixes', ['dev' => ' (TEST)']);
        parent::doInit();
    }
}
