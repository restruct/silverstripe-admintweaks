<?php

namespace Restruct\Silverstripe\AdminTweaks\Forms;

use SilverStripe\Core\Injector\Injector;
use SilverStripe\Forms\HTMLEditor\HTMLEditorConfig;
use SilverStripe\Forms\HTMLEditor\TinyMCEConfig;

/**
 * The `minimal` HTML editor config (admintweaks#116): one toolbar line with bold, italic, lists
 * and links, for short rich-text fields such as FAQ answers, intro and card texts.
 *
 * ```php
 * HTMLEditorField::create('Answer')->setEditorConfig(MinimalEditorConfig::get());
 * ```
 *
 * The config is a copy of the `cms` config, made the first time MinimalEditorConfig::get() is
 * called, so it keeps the link plugins and sslink menus that admin, cms and asset-admin add to
 * `cms` in their _config.php, and what a project's _config.php adds. A copy made in this
 * module's own _config.php would depend on load order: module _config.php files run in module
 * priority order, which within the `$other_modules` group comes down to the alphabetical order
 * of the package names, and a project's _config.php runs after every module.
 *
 * Up to Silverstripe 5, HTMLEditorConfig::get() has no hook for a named config (Silverstripe 6
 * added `default_config_definitions`), so the copy is made by this getter, not by a lookup by
 * name: get() registers it with HTMLEditorConfig::set_config() when there is no `minimal` yet.
 * After that the name works everywhere (`setEditorConfig('minimal')`,
 * `HTMLEditorConfig::get('minimal')`). Before it, a lookup by name creates a blank config under
 * that name, which get() then keeps as a project's own; so use the getter.
 *
 * The copy itself is built by the Injector factory of this class (MinimalEditorConfigFactory,
 * _config/htmleditor.yml), as on the Silverstripe 6 line, so the object returned is a
 * TinyMCEConfig (the copy), not an instance of this class; this class carries the name, the
 * getter and the YAML settings.
 *
 * A project that registers its own `minimal` (HTMLEditorConfig::set_config()) keeps it.
 */
class MinimalEditorConfig extends TinyMCEConfig
{
    /**
     * The identifier to pass to setEditorConfig() or HTMLEditorConfig::get().
     */
    public const NAME = 'minimal';

    /**
     * The toolbar, one space-separated string of TinyMCE button names per line ('|' is a
     * separator). An empty string leaves the line out. Lines the `cms` config has and this list
     * does not name are emptied as well.
     *
     * Strings rather than lists on purpose: Silverstripe config APPENDS to a list when a project
     * sets it again, so overriding a list of buttons from YAML would add to the default instead
     * of replacing it. A string replaces. (A list is accepted too, for a value set in PHP.)
     *
     * Keyed line_<n> rather than by number for the same reason: numeric keys append as well.
     *
     * @config
     * @var array
     */
    private static $minimal_buttons = [
        'line_1' => 'bold italic | bullist numlist | sslink unlink | removeformat',
        'line_2' => '',
        'line_3' => '',
    ];

    /**
     * TinyMCE options set on the copy (setOptions()), on top of what `cms` has.
     * friendly_name labels the config; contextmenu replaces the cms right-click menu (tables,
     * media, anchors) with just the link menu.
     *
     * @config
     * @var array
     */
    private static $minimal_options = [
        'friendly_name' => 'Simple',
        'contextmenu' => 'sslink',
    ];

    /**
     * The `minimal` config: registered as a copy of `cms` on the first call, the same instance
     * after that. With an identifier other than `minimal` this is HTMLEditorConfig::get().
     *
     * @param string|null $identifier Leave out (or pass self::NAME) for the minimal config
     * @return HTMLEditorConfig
     */
    public static function get($identifier = null)
    {
        # Explicitly HTMLEditorConfig::get(), not parent::get(): for an unknown name that creates
        # `static::create()`, which from here would be a minimal copy under the other name.
        if ($identifier !== null && $identifier !== self::NAME) {
            return HTMLEditorConfig::get($identifier);
        }
        # Checked on the registry directly (accessible from this subclass): a lookup through
        # HTMLEditorConfig::get() would create a blank `minimal` first.
        if (!isset(HTMLEditorConfig::$configs[self::NAME])) {
            HTMLEditorConfig::set_config(self::NAME, Injector::inst()->create(self::class));
        }
        return HTMLEditorConfig::get(self::NAME);
    }
}
