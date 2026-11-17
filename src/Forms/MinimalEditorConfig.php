<?php

namespace Restruct\Silverstripe\AdminTweaks\Forms;

use SilverStripe\Forms\HTMLEditor\HTMLEditorConfig;
use SilverStripe\TinyMCE\TinyMCEConfig;

# Silverstripe 6 ships TinyMCE in its own module (silverstripe/htmleditor-tinymce), which this
# module only suggests. Without it there is nothing to extend, so the class is left undefined
# (the class manifest still lists it; class_exists() then autoloads this file and gets false).
if (!class_exists(TinyMCEConfig::class)) {
    return;
}

/**
 * The `minimal` HTML editor config (admintweaks#116): one toolbar line with bold, italic, lists
 * and links, for short rich-text fields such as FAQ answers, intro and card texts.
 *
 * ```php
 * HTMLEditorField::create('Answer')->setEditorConfig(MinimalEditorConfig::get());
 * // On Silverstripe 6 the name works as well, also before the getter has been called:
 * HTMLEditorField::create('Answer')->setEditorConfig(MinimalEditorConfig::NAME);
 * ```
 *
 * The getter is the usage the 3.x line (Silverstripe 4/5) needs, where the name only works once
 * the getter has run; it is offered here too so code reads the same on both lines.
 *
 * The config is a copy of the `cms` config, made the first time `minimal` is requested, so it
 * keeps the link plugins and sslink menus that htmleditor-tinymce (and anything else) adds to
 * `cms` in a _config.php. A copy made in this module's own _config.php could run before those
 * and miss them: module _config.php files run in module priority order, and both this module
 * and htmleditor-tinymce sit in the `$other_modules` group, which no rule orders among itself.
 *
 * How it is wired (_config/htmleditor.yml): HTMLEditorConfig::get() creates an unknown name from
 * its `default_config_definitions`, by calling create() on the definition's `configClass`. This
 * class is that configClass, and an Injector `factory` (MinimalEditorConfigFactory) turns that
 * create() into the copy of `cms`. So the object returned is a TinyMCEConfig (the copy), not an
 * instance of this class; this class carries the name, the YAML settings and, through config
 * inheritance, the `default_element_rules` that get() applies to the copy afterwards.
 *
 * Element rules: get() resets the copy's element rules to the definition's `elementRules`, or to
 * `default_element_rules` when it has none, plus any `extraElementRules`. So `minimal` allows the
 * framework defaults, not the extra iframe/object/map rules `cms` adds; set
 * `HTMLEditorConfig.default_config_definitions.minimal.extraElementRules` to allow more.
 *
 * A project that registers its own `minimal` (HTMLEditorConfig::set_config()) keeps it: get()
 * only consults the definitions for a name that has no config yet.
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
     */
    private static array $minimal_buttons = [
        'line_1' => 'bold italic | bullist numlist | sslink unlink | removeformat',
        'line_2' => '',
        'line_3' => '',
    ];

    /**
     * TinyMCE options set on the copy (setOptions()), on top of what `cms` has.
     * friendly_name labels the config; contextmenu replaces the cms right-click menu (tables,
     * media, anchors) with just the link menu.
     */
    private static array $minimal_options = [
        'friendly_name' => 'Simple',
        'contextmenu' => 'sslink',
    ];

    /**
     * The `minimal` config, the same instance on every call: the copy of `cms` is made through
     * `default_config_definitions` on the first lookup. With an identifier other than `minimal`
     * this is HTMLEditorConfig::get().
     *
     * Overrides the inherited HTMLEditorConfig::get() so that a call without an identifier means
     * `minimal`, not the active config.
     *
     * @param string|null $identifier Leave out (or pass self::NAME) for the minimal config
     */
    public static function get($identifier = null): HTMLEditorConfig
    {
        # Explicitly HTMLEditorConfig::get(), not parent::get(): for a name without a definition
        # that creates `static::create()`, which from here would be a minimal copy under that name.
        return HTMLEditorConfig::get($identifier ?? self::NAME);
    }
}
