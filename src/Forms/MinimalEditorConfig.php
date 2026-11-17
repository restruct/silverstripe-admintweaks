<?php

namespace Restruct\Silverstripe\AdminTweaks\Forms;

use SilverStripe\Forms\HTMLEditor\HTMLEditorConfig;
use SilverStripe\Forms\HTMLEditor\TinyMCEConfig;

/**
 * The `minimal` HTML editor config (admintweaks#116): one toolbar line with bold, italic, lists
 * and links, for short rich-text fields such as FAQ answers, intro and card texts.
 *
 * ```php
 * HTMLEditorField::create('Answer')->setEditorConfig(MinimalEditorConfig::NAME);
 * HTMLEditorConfig::get(MinimalEditorConfig::NAME);
 * ```
 *
 * The config is a copy of the `cms` config, made the first time `minimal` is requested, so it
 * keeps the link plugins and sslink menus that admin, cms and asset-admin add to `cms` in their
 * _config.php. A copy made in this module's own _config.php could run before some of those and
 * miss them: module _config.php files run in module priority order, and this module and
 * asset-admin both sit in the `$other_modules` group, which no rule orders among itself (a
 * project's _config.php, which may also change `cms`, runs after all of them).
 *
 * How it is wired: up to Silverstripe 5, HTMLEditorConfig::get() has no hook for a named config
 * (Silverstripe 6 added `default_config_definitions`). It only asks its registry, the protected
 * static HTMLEditorConfig::$configs, whether the name is set (`isset()`), and creates a blank
 * config when it is not. installLazyRegistry() (called from _config.php) swaps that array for a
 * MinimalEditorConfigRegistry, an ArrayObject that answers the `isset()` for `minimal` by building
 * the copy first. Every other name behaves as in the plain array. The copy itself is built by the
 * Injector factory of this class (MinimalEditorConfigFactory, _config/htmleditor.yml), as on the
 * Silverstripe 6 line, so the object returned is a TinyMCEConfig (the copy), not an instance of
 * this class; this class carries the name and the YAML settings, and reaches the protected
 * registry by being an HTMLEditorConfig subclass.
 *
 * A project that registers its own `minimal` (HTMLEditorConfig::set_config()) keeps it: the copy
 * is only made for a name that has no config yet.
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
     * Swap HTMLEditorConfig's registry for one that builds `minimal` when it is first asked for.
     * Called once from this module's _config.php; idempotent. The configs already registered
     * (`cms`, set up by admin's _config.php) are carried over, and a `minimal` that is already
     * there (a project's own) is left as it is.
     */
    public static function installLazyRegistry(): void
    {
        if (HTMLEditorConfig::$configs instanceof MinimalEditorConfigRegistry) {
            return;
        }
        HTMLEditorConfig::$configs = new MinimalEditorConfigRegistry((array) HTMLEditorConfig::$configs);
    }
}
