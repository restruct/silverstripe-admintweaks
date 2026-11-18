<?php

namespace Restruct\AtBrowser;

use SilverStripe\Admin\ModelAdmin;

/**
 * BROWSER-TEST FIXTURE ONLY - lists AtBParentRecord, whose edit form holds an editable GridField of
 * its children, /admin/atb-parent (editable-gridfield-save.spec.ts).
 * (See AtBRecord for why this never loads in a real install.)
 */
class AtBParentAdmin extends ModelAdmin
{
    private static $url_segment = 'atb-parent';

    private static $menu_title = 'AdminTweaks parent';

    private static $managed_models = [
        'parent' => ['dataClass' => AtBParentRecord::class, 'title' => 'Parent'],
    ];
}
