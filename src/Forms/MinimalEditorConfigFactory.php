<?php

namespace Restruct\Silverstripe\AdminTweaks\Forms;

use SilverStripe\Core\Injector\Factory;
use SilverStripe\Forms\HTMLEditor\HTMLEditorConfig;
use SilverStripe\Forms\HTMLEditor\TinyMCEConfig;

/**
 * Builds the `minimal` HTML editor config: a copy of the `cms` config, taken at the moment
 * MinimalEditorConfig::get() is first called, with the toolbar and options from
 * MinimalEditorConfig's YAML (admintweaks#116). Registered as the Injector factory of
 * MinimalEditorConfig (_config/htmleditor.yml); MinimalEditorConfig::get() calls it.
 */
class MinimalEditorConfigFactory implements Factory
{
    /**
     * @param string $service The service name (MinimalEditorConfig)
     * @param array $params Constructor arguments; unused, the copy is not constructed
     * @return TinyMCEConfig
     */
    public function create($service, array $params = [])
    {
        $cms = HTMLEditorConfig::get('cms');
        # `cms` is a TinyMCEConfig unless a project swapped the editor; the toolbar API below is
        # TinyMCE's, so in that case start from a fresh TinyMCE config (without the cms plugins).
        # A shallow clone is a real copy here: the config's state is arrays and scalars only.
        $config = $cms instanceof TinyMCEConfig ? clone $cms : TinyMCEConfig::create();

        $settings = MinimalEditorConfig::config();
        $config->setOptions($settings->get('minimal_options') ?: []);

        $lines = static::buttonLines($settings->get('minimal_buttons') ?: []);
        # Empty every line the copy has first, so a line `cms` has and the config does not name
        # (a project's line 4, say) does not survive into the minimal toolbar.
        foreach (array_keys($config->getButtons()) as $line) {
            $config->setButtonsForLine((int) $line, []);
        }
        foreach ($lines as $line => $buttons) {
            $config->setButtonsForLine($line, $buttons);
        }

        return $config;
    }

    /**
     * Turn the `minimal_buttons` config (line_<n> => space-separated string or list) into
     * line number => list of button names.
     *
     * @param array $config The minimal_buttons config value
     * @return array<int, string[]>
     */
    public static function buttonLines(array $config): array
    {
        $lines = [];
        foreach ($config as $key => $buttons) {
            # Only line_<n> keys; anything else is not a toolbar line.
            if (!preg_match('/^line_(\d+)$/', (string) $key, $match)) {
                continue;
            }
            if (is_string($buttons)) {
                $buttons = preg_split('/\s+/', trim($buttons), -1, PREG_SPLIT_NO_EMPTY);
            }
            $lines[(int) $match[1]] = array_values(array_filter((array) $buttons, 'strlen'));
        }
        ksort($lines);
        return $lines;
    }
}
