<?php

namespace Restruct\Silverstripe\AdminTweaks\Tests\Extensions;

use SilverStripe\Admin\LeftAndMain;
use SilverStripe\CMS\Model\SiteTree;
use SilverStripe\Core\Config\Config;
use SilverStripe\Dev\SapphireTest;
use SilverStripe\View\Requirements;
use SilverStripe\View\Requirements_Backend;

/**
 * The `page_icons` opt-out: admintweaks' SVG page icons sit in a stylesheet of their own
 * (admintweaks-page-icons.css), which LeftAndMainExtension only requires while `page_icons` is true.
 *
 * Checked against what ends up in Requirements, the one thing the CMS page actually renders from.
 * The browser spec page-icons-off.spec.ts checks what that does to the site tree.
 *
 * Compatibility: must run on PHPUnit 9 (SS5) and 11 (SS6). No doc-comment metadata.
 */
class PageIconsLoadingTest extends SapphireTest
{
    protected $usesDatabase = false;

    protected function setUp(): void
    {
        parent::setUp();
        # A fresh backend per test, so one test's Requirements::css() cannot satisfy the next
        Requirements::set_backend(Requirements_Backend::create());
    }

    /**
     * Fire LeftAndMain's init hook the way core does, then report which required stylesheets end in
     * the given file name.
     *
     * Through extend() on a real LeftAndMain rather than by calling the extension method, so the
     * hook name is covered too (see BootstrapIconsLoadingTest). By the end of the path, because
     * Requirements keys a module resource by its resolved path, which depends on where the module
     * is installed (vendor/ in a project, the root in a module checkout).
     */
    private function requiredCssEndingIn(string $fileName): array
    {
        LeftAndMain::create()->extend('init');

        return array_values(array_filter(
            array_keys(Requirements::backend()->getCSS()),
            # substr rather than str_ends_with(): this line still allows PHP 7.4 (framework ^4)
            fn (string $path) => substr(strtok($path, '?'), -strlen($fileName)) === $fileName
        ));
    }

    public function testPageIconsAreOnByDefault()
    {
        # Default per the README; the module's own YAML must not decide it for every project
        $this->assertTrue(Config::inst()->get(LeftAndMain::class, 'page_icons'));
        $this->assertCount(
            1,
            $this->requiredCssEndingIn('client/dist/css/admintweaks-page-icons.css'),
            'With page_icons left at its default, the SVG page icon stylesheet must load'
        );
    }

    public function testFalseLeavesThePageIconStylesheetOut()
    {
        Config::modify()->set(LeftAndMain::class, 'page_icons', false);

        $this->assertSame(
            [],
            $this->requiredCssEndingIn('client/dist/css/admintweaks-page-icons.css'),
            'page_icons: false must not require the SVG page icon stylesheet'
        );
    }

    public function testFalseKeepsBootstrapIconsForABiPageType()
    {
        # The opt-out is for admintweaks' SVG icons only: a page type's own bi-* glyph still needs
        # the Bootstrap Icons stylesheet, and still gets it without further config
        Config::modify()->set(LeftAndMain::class, 'page_icons', false);
        Config::modify()->set(SiteTree::class, 'icon_class', 'bi-briefcase');

        $url = Config::inst()->get(LeftAndMain::class, 'bootstrap_icons_cdn_url');
        LeftAndMain::create()->extend('init');

        $this->assertArrayHasKey($url, Requirements::backend()->getCSS());
    }
}
