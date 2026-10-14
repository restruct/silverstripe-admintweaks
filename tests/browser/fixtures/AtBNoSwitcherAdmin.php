<?php

namespace Restruct\AtBrowser;

use Restruct\Silverstripe\AdminTweaks\SystemSwitcher;
use SilverStripe\Admin\ModelAdmin;
use SilverStripe\Core\Config\Config;

/**
 * BROWSER-TEST FIXTURE ONLY - the switcher's control: /admin/atb-noswitcher renders with NO
 * SystemSwitcher.systems, as on a site that never configured the switcher, so the spec can check the
 * core menu header and admin title come out unchanged. The fixture YAML switches the switcher on for
 * the whole host; this admin empties `systems` for its own requests before anything (including
 * SystemSwitcherExtension::onBeforeInit()) reads it. (See AtBRecord for why this never loads in a
 * real install.)
 */
class AtBNoSwitcherAdmin extends ModelAdmin
{
    private static $url_segment = 'atb-noswitcher';

    private static $menu_title = 'AdminTweaks no switcher';

    private static $managed_models = [
        'noswitcher' => ['dataClass' => AtBRecord::class, 'title' => 'No switcher'],
    ];

    public function doInit()
    {
        # Before parent::doInit(), which runs the onBeforeInit extension hooks
        Config::modify()->set(SystemSwitcher::class, 'systems', []);
        parent::doInit();
    }
}
