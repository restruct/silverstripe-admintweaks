<?php

namespace Restruct\AtBrowser;

use SilverStripe\Admin\ModelAdmin;

/**
 * BROWSER-TEST FIXTURE ONLY - lists AtBEditorRecord at /admin/atb-editor, for
 * minimal-editor.spec.ts (admintweaks#116). (See AtBRecord for why this never loads in a real
 * install.)
 */
class AtBEditorAdmin extends ModelAdmin
{
    private static $url_segment = 'atb-editor';

    private static $menu_title = 'AdminTweaks editor';

    # Keyed managed_models: the key becomes the URL segment and the GridField name.
    private static $managed_models = [
        'editor' => ['dataClass' => AtBEditorRecord::class, 'title' => 'Editor'],
    ];
}
