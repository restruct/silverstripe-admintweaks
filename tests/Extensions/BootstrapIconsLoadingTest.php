<?php

namespace Restruct\Silverstripe\AdminTweaks\Tests\Extensions;

use Restruct\Silverstripe\AdminTweaks\Extensions\LeftAndMainExtension;
use SilverStripe\Admin\LeftAndMain;
use SilverStripe\CMS\Model\SiteTree;
use SilverStripe\Core\ClassInfo;
use SilverStripe\Core\Config\Config;
use SilverStripe\Dev\SapphireTest;
use SilverStripe\View\Requirements;
use SilverStripe\View\Requirements_Backend;

/**
 * When the Bootstrap Icons stylesheet is loaded in the CMS (#108).
 *
 * A page type can pick a Bootstrap Icons glyph as its site-tree icon (`icon_class:
 * 'bi-briefcase'`). admintweaks' CSS then leaves out its own SVG for that type, so without the
 * stylesheet the type has no icon at all. `include_bootstrap_icons` therefore defaults to null,
 * meaning "load it when a page type needs it"; true and false still force it either way.
 *
 * Checked against what ends up in Requirements, the one thing the CMS page actually renders from.
 *
 * Compatibility: must run on PHPUnit 9 (SS5) and 11 (SS6). No doc-comment metadata.
 */
class BootstrapIconsLoadingTest extends SapphireTest
{
    protected $usesDatabase = false;

    protected function setUp(): void
    {
        parent::setUp();
        # A fresh backend per test, so one test's Requirements::css() cannot satisfy the next
        Requirements::set_backend(Requirements_Backend::create());
    }

    /**
     * Fire LeftAndMain's init hook the way core does and report whether the stylesheet was required.
     *
     * Through extend() on a real LeftAndMain, not by calling the extension method directly, so the
     * hook name is covered too: Silverstripe 4 and 5 fire `init` (LeftAndMain::init()); 6 renamed
     * it to `onInit`, which is how the 4.x line lost the stylesheet unnoticed.
     */
    private function stylesheetLoaded(): bool
    {
        LeftAndMain::create()->extend('init');

        $url = Config::inst()->get(LeftAndMain::class, 'bootstrap_icons_cdn_url');
        return array_key_exists($url, Requirements::backend()->getCSS());
    }

    /**
     * Give EVERY page type in the manifest this icon class, so a test does not depend on which
     * page types the host project happens to have (a project's own bi-* page type would otherwise
     * turn the "nothing uses Bootstrap Icons" cases green-to-red when run from that project).
     */
    private function setEveryPageTypeIconClass(string $iconClass): void
    {
        foreach (ClassInfo::subclassesFor(SiteTree::class) as $class) {
            Config::modify()->set($class, 'icon_class', $iconClass);
        }
    }

    public function testNoPageTypeWithABootstrapIconClassMeansNoStylesheet()
    {
        # Core's own page types all use font-icon-* classes; that must not trigger the CDN request
        $this->setEveryPageTypeIconClass('font-icon-page');

        $this->assertFalse(LeftAndMainExtension::pageTypesUseBootstrapIcons());
        $this->assertFalse(
            $this->stylesheetLoaded(),
            'With no bi-* page icon and the setting left at its default, the CDN stylesheet must not load'
        );
    }

    public function testAPageTypeWithABootstrapIconClassLoadsTheStylesheet()
    {
        Config::modify()->set(SiteTree::class, 'icon_class', 'bi-briefcase');

        $this->assertTrue(LeftAndMainExtension::pageTypesUseBootstrapIcons());
        $this->assertTrue(
            $this->stylesheetLoaded(),
            'A page type with a bi-* icon_class must get the stylesheet without further config'
        );
    }

    public function testTheTwoClassFormIsRecognisedToo()
    {
        # 'bi bi-name' is how the Bootstrap docs write it; the glyph comes from the bi-name part
        Config::modify()->set(SiteTree::class, 'icon_class', 'bi bi-briefcase');

        $this->assertTrue(LeftAndMainExtension::pageTypesUseBootstrapIcons());
    }

    public function testAClassMerelyContainingBiIsNotABootstrapIcon()
    {
        # Must-match control for the pattern: "bi-" only counts at the start of a class name
        $this->setEveryPageTypeIconClass('font-icon-combi-thing');

        $this->assertFalse(LeftAndMainExtension::pageTypesUseBootstrapIcons());
    }

    public function testFalseNeverLoadsItEvenWhenAPageTypeUsesBootstrapIcons()
    {
        Config::modify()->set(SiteTree::class, 'icon_class', 'bi-briefcase');
        Config::modify()->set(LeftAndMain::class, 'include_bootstrap_icons', false);

        $this->assertFalse($this->stylesheetLoaded(), 'false is an explicit opt-out and must win');
    }

    public function testTrueAlwaysLoadsIt()
    {
        Config::modify()->set(LeftAndMain::class, 'include_bootstrap_icons', true);

        $this->assertTrue($this->stylesheetLoaded());
    }
}
