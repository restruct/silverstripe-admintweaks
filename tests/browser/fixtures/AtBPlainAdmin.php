<?php

namespace Restruct\AtBrowser;

use SilverStripe\Admin\ModelAdmin;

/**
 * BROWSER-TEST FIXTURE ONLY - the control: a ModelAdmin with ModelAdminExtension's defaults (both
 * tweaks off), /admin/atb-plain. (See AtBRecord for why this never loads in a real install.)
 */
class AtBPlainAdmin extends ModelAdmin
{
    private static $url_segment = 'atb-plain';

    private static $menu_title = 'AdminTweaks plain';

    private static $managed_models = [
        'plain' => ['dataClass' => AtBRecord::class, 'title' => 'Plain'],
    ];
}
