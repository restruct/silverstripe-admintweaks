<?php

namespace Restruct\AtBrowser;

use SilverStripe\Admin\ModelAdmin;

/**
 * BROWSER-TEST FIXTURE ONLY - a ModelAdmin with both of ModelAdminExtension's opt-in tweaks ON:
 * /admin/atb-tweaked. (See AtBRecord for why this never loads in a real install.)
 */
class AtBTweakedAdmin extends ModelAdmin
{
    private static $url_segment = 'atb-tweaked';

    private static $menu_title = 'AdminTweaks tweaked';

    # Keyed managed_models: the key becomes the URL segment and the GridField name.
    private static $managed_models = [
        'tweaked' => ['dataClass' => AtBRecord::class, 'title' => 'Tweaked'],
    ];

    # The two opt-in switches ModelAdminExtension reads off the subclass config.
    private static $auto_expand_gridfield_search = true;

    private static $hide_scaffolded_csv_buttons = true;
}
