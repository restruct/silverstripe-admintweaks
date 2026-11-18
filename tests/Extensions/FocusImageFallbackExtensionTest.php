<?php

namespace Restruct\Silverstripe\AdminTweaks\Tests\Extensions;

use SilverStripe\Assets\Image;
use SilverStripe\Dev\SapphireTest;

/**
 * The FocusPoint fallbacks must carry FocusPoint's own method names, or a template written for
 * FocusPoint loses its images once the module is not installed (admintweaks#142).
 */
class FocusImageFallbackExtensionTest extends SapphireTest
{
    protected $usesDatabase = false;

    protected function setUp(): void
    {
        parent::setUp();

        if (class_exists('JonoM\FocusPoint\Extensions\FocusPointImageExtension')) {
            $this->markTestSkipped('FocusPoint is installed, so the fallback extension is not applied');
        }
    }

    public function testEveryFocusPointResizeMethodHasAFallback(): void
    {
        // The public resize methods of jonom/focuspoint 5.x and 6.x (FocusPointExtension).
        foreach (['FocusFill', 'FocusFillMax', 'FocusCropWidth', 'FocusCropHeight'] as $method) {
            $this->assertTrue(Image::singleton()->hasMethod($method), "Image has no {$method}() without FocusPoint");
        }
    }

    public function testTheOldMethodNameStillWorks(): void
    {
        // FocusFillFallback() is public API and stays as an alias.
        $this->assertTrue(Image::singleton()->hasMethod('FocusFillFallback'));
    }
}
