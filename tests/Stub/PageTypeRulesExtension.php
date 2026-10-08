<?php

namespace Restruct\Silverstripe\AdminTweaks\Tests\Stub;

use SilverStripe\Core\Config\Config;
use SilverStripe\Core\Extension;
use SilverStripe\Dev\TestOnly;

/**
 * Test-only SiteTree extension standing in for a project's own page-type rules: it can drop page
 * types through the updateAllowedSubClasses() hook and deny canCreate() per class. Both lists are
 * empty unless a test sets them with Config::modify(), which SapphireTest rolls back.
 *
 * An Extension, not a DataObject, so it adds no table to a consumer's test database.
 */
class PageTypeRulesExtension extends Extension implements TestOnly
{
    /** @config Page types removed from the allowed subclasses */
    private static array $dropped_classes = [];

    /** @config Page types for which canCreate() returns false */
    private static array $denied_create = [];

    protected function updateAllowedSubClasses(array &$classes): void
    {
        $classes = array_diff($classes, Config::inst()->get(self::class, 'dropped_classes') ?? []);
    }

    protected function canCreate($member = null, $context = [])
    {
        # null = no opinion, so the owner's own permission model decides.
        return in_array(get_class($this->getOwner()), Config::inst()->get(self::class, 'denied_create') ?? [], true)
            ? false
            : null;
    }
}
