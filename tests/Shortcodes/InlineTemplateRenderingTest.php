<?php

namespace Restruct\Silverstripe\AdminTweaks\Tests\Shortcodes;

use Restruct\Silverstripe\AdminTweaks\Extensions\PageHelpersExtension;
use Restruct\Silverstripe\AdminTweaks\Shortcodes\FeaturedImageShortcode;
use Restruct\Silverstripe\AdminTweaks\Tests\Stub\FeaturedImageController;
use SilverStripe\Control\HTTPRequest;
use SilverStripe\Control\Session;
use SilverStripe\Dev\SapphireTest;
use SilverStripe\Model\ArrayData;
use SilverStripe\View\Requirements;

/**
 * admintweaks#150: Silverstripe 6 removed SSViewer::fromString(). The [featuredimage] shortcode and
 * the RequireCustomTemplated* helpers rendered inline template strings with it, so a page using the
 * shortcode answered HTTP 500 ("Call to undefined method SSViewer::fromString()").
 */
class InlineTemplateRenderingTest extends SapphireTest
{
    protected $usesDatabase = true;

    public function testFeaturedImageShortcodeRendersItsWrapper(): void
    {
        $controller = FeaturedImageController::create();
        # pushCurrent() needs a request with a session.
        $request = new HTTPRequest('GET', '/');
        $request->setSession(new Session([]));
        $controller->setRequest($request);
        $controller->pushCurrent();
        try {
            $html = FeaturedImageShortcode::parse_shortcode([], null, null, 'featuredimage');
        } finally {
            $controller->popCurrent();
        }
        # The stub image has no file, so Fill() renders nothing: what matters is that the inline
        # template rendered at all, wrapper included.
        $this->assertIsString($html);
        $this->assertStringStartsWith('<div class="image">', trim($html));
    }

    public function testRequireCustomTemplatedCssRendersAgainstTheOwner(): void
    {
        Requirements::clear();
        # Extensions are not Injectable, so no ::create().
        $extension = new PageHelpersExtension();
        $extension->setOwner(ArrayData::create(['Colour' => 'rebeccapurple']));

        $extension->RequireCustomTemplatedCSS('.x { color: $Colour; }');

        $css = implode("\n", Requirements::backend()->getCustomCSS());
        $this->assertStringContainsString('.x { color: rebeccapurple; }', $css);
    }

    public function testRequireCustomTemplatedJsRendersAgainstTheOwner(): void
    {
        Requirements::clear();
        # Extensions are not Injectable, so no ::create().
        $extension = new PageHelpersExtension();
        $extension->setOwner(ArrayData::create(['Name' => 'admintweaks']));

        $extension->RequireCustomTemplatedJS('<script>console.log("$Name");</script>');

        $js = implode("\n", Requirements::backend()->getCustomScripts());
        $this->assertStringContainsString('console.log("admintweaks");', $js);
        $this->assertStringNotContainsString('<script>', $js);
    }
}
