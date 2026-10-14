<?php

namespace Restruct\Silverstripe\AdminTweaks;

use SilverStripe\Admin\LeftAndMain;
use SilverStripe\Control\Director;
use SilverStripe\Model\ArrayData;
use SilverStripe\Model\List\ArrayList;
use SilverStripe\Model\ModelData;

/**
 * CMS system switcher: turns the admin menu header (core LeftAndMain_MenuLogo) into a dropdown
 * that names the CMS you are in and links to the related CMSes (admintweaks#71).
 *
 * Configured once in YAML; the same `systems` block can be shared by every CMS, each one only
 * setting its own `current` key. Per-environment URLs go in `Only: environment:` fragments that
 * override only `url`: Silverstripe merges string-keyed config arrays recursively
 * (silverstripe/config Priority::mergeArray), so the base `display` is kept.
 *
 * ```yaml
 * Restruct\Silverstripe\AdminTweaks\SystemSwitcher:
 *   current: site
 *   systems:
 *     site: { display: 'Example SITE', url: 'https://www.example.org/admin' }
 *     docs: { display: 'Example DOCS', url: 'https://docs.example.org/admin' }
 * ```
 *
 * Without `systems` the switcher is off: the core header renders unchanged and the admin title is
 * left alone. When on, the current system's name (its `display`, defaulting to
 * LeftAndMain.application_name) plus an environment suffix (' (TEST)' / ' (DEV)', nothing on live)
 * is shown in the header and becomes the admin's application_name, so the browser tab reads
 * "Example SITE (TEST) - Pages" (see Extensions\SystemSwitcherExtension).
 */
class SystemSwitcher extends ModelData
{
    # (ModelData brings Configurable and Injectable)

    /**
     * @config Key (in `systems`) of the CMS this installation is. Unset or unknown: the header
     * shows LeftAndMain.application_name and the dropdown lists every configured system.
     */
    private static ?string $current = null;

    /**
     * @config The related CMSes, key => ['display' => name, 'url' => absolute http(s) URL of its
     * admin]. An entry without a valid `display` and `url` is left out of the dropdown.
     */
    private static array $systems = [];

    /**
     * @config Suffix appended to the current system's name, per environment type
     * (Director::get_environment_type()). Empty = no suffix.
     */
    private static array $environment_suffixes = [
        'dev' => ' (DEV)',
        'test' => ' (TEST)',
        'live' => '',
    ];

    # Every configured string is a plain string, escaped by the template (Text casting = HTML-escaped)
    private static array $casting = [
        'CurrentName' => 'Text',
        'CurrentLabel' => 'Text',
        'CurrentInitials' => 'Text',
        'EnvironmentSuffix' => 'Text',
    ];

    /**
     * The switcher is on as soon as `systems` holds at least one entry.
     */
    public function isEnabled(): bool
    {
        $systems = static::config()->get('systems');
        return is_array($systems) && count($systems) > 0;
    }

    /**
     * Template truthiness (`<% if $AdminSystemSwitcher %>`): only when enabled.
     */
    public function exists(): bool
    {
        return $this->isEnabled();
    }

    /**
     * The `current` key, or null when unset.
     */
    public function getCurrentKey(): ?string
    {
        $current = static::config()->get('current');
        return is_string($current) && $current !== '' ? $current : null;
    }

    /**
     * The suffix for the current environment type, '' when none is configured for it.
     */
    public function getEnvironmentSuffix(): string
    {
        $suffixes = (array) static::config()->get('environment_suffixes');
        $suffix = $suffixes[Director::get_environment_type()] ?? '';
        return is_string($suffix) ? $suffix : '';
    }

    /**
     * The current system's name WITHOUT the environment suffix: its `display`, else
     * LeftAndMain.application_name (as configured, ie read from LeftAndMain itself, which the
     * extension never modifies; a suffix already in it is stripped so it is never doubled).
     */
    public function getCurrentName(): string
    {
        $system = $this->getSystem($this->getCurrentKey());
        $name = $system['display'] ?? null;
        if (!is_string($name) || trim($name) === '') {
            $name = (string) LeftAndMain::config()->get('application_name');
        }

        return $this->stripSuffix(trim($name));
    }

    /**
     * The current system's name with the environment suffix: the header text and the admin's
     * application_name while the switcher is on.
     */
    public function getCurrentLabel(): string
    {
        return $this->addSuffix($this->getCurrentName());
    }

    /**
     * Short label for the collapsed menu: the system's `initials` when configured, else the first
     * letter of the first two words of its name ("Example DOCS" -> "ED"), or the first two letters
     * of a one-word name ("Docs" -> "DO").
     */
    public function getCurrentInitials(): string
    {
        $system = $this->getSystem($this->getCurrentKey());
        if (isset($system['initials']) && is_string($system['initials']) && trim($system['initials']) !== '') {
            return trim($system['initials']);
        }

        $words = preg_split('/\s+/u', $this->getCurrentName(), -1, PREG_SPLIT_NO_EMPTY) ?: [];
        if (count($words) >= 2) {
            $initials = mb_substr($words[0], 0, 1) . mb_substr($words[1], 0, 1);
        } else {
            $initials = mb_substr($words[0] ?? '', 0, 2);
        }

        return mb_strtoupper($initials);
    }

    /**
     * The OTHER systems, for the dropdown: every valid entry except `current`, in config order.
     * Each item has Key, Display and URL.
     */
    public function getOtherSystems(): ArrayList
    {
        $current = $this->getCurrentKey();
        $list = ArrayList::create();
        foreach ((array) static::config()->get('systems') as $key => $system) {
            if ((string) $key === $current || !is_array($system)) {
                continue;
            }
            $display = isset($system['display']) && is_string($system['display']) ? trim($system['display']) : '';
            $url = isset($system['url']) && is_string($system['url']) ? trim($system['url']) : '';
            # Skip incomplete entries and anything that is not an absolute http(s) URL (no javascript:,
            # data: or relative links end up in the admin header)
            if ($display === '' || !static::isValidUrl($url)) {
                continue;
            }
            $list->push(ArrayData::create([
                'Key' => (string) $key,
                'Display' => $display,
                'URL' => $url,
            ]));
        }

        return $list;
    }

    /**
     * Whether $url is an absolute http:// or https:// URL with a host.
     */
    public static function isValidUrl(string $url): bool
    {
        if (filter_var($url, FILTER_VALIDATE_URL) === false) {
            return false;
        }
        $scheme = strtolower((string) parse_url($url, PHP_URL_SCHEME));
        return in_array($scheme, ['http', 'https'], true) && (string) parse_url($url, PHP_URL_HOST) !== '';
    }

    /**
     * Appends the environment suffix to $name, unless it is empty or $name already ends with it
     * (an application_name configured with the suffix, or a value the extension already adjusted).
     */
    public function addSuffix(string $name): string
    {
        $suffix = $this->getEnvironmentSuffix();
        if ($suffix === '' || str_ends_with($name, $suffix)) {
            return $name;
        }

        return $name . $suffix;
    }

    /**
     * $name without a trailing environment suffix (trimmed).
     */
    protected function stripSuffix(string $name): string
    {
        $suffix = $this->getEnvironmentSuffix();
        if ($suffix !== '' && str_ends_with($name, $suffix)) {
            $name = substr($name, 0, -strlen($suffix));
        }

        return trim($name);
    }

    /**
     * The configured entry for $key, or [] when there is none.
     */
    protected function getSystem(?string $key): array
    {
        if ($key === null) {
            return [];
        }
        $systems = (array) static::config()->get('systems');
        return isset($systems[$key]) && is_array($systems[$key]) ? $systems[$key] : [];
    }
}
