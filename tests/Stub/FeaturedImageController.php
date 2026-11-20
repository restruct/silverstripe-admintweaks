<?php

namespace Restruct\Silverstripe\AdminTweaks\Tests\Stub;

use SilverStripe\Assets\Image;
use SilverStripe\Control\Controller;
use SilverStripe\Dev\TestOnly;

/**
 * The current controller FeaturedImageShortcode asks for FeaturedImage() (admintweaks#150).
 */
class FeaturedImageController extends Controller implements TestOnly
{
    public function FeaturedImage(): Image
    {
        # An image record without a file: enough to render the shortcode's inline template.
        return Image::create(['Title' => 'Featured']);
    }
}
