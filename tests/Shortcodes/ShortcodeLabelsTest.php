<?php

namespace Restruct\Silverstripe\AdminTweaks\Tests\Shortcodes;

use Restruct\Silverstripe\AdminTweaks\Shortcodes\CurrentYearShortcode;
use Restruct\Silverstripe\AdminTweaks\Shortcodes\FeaturedImageShortcode;
use SilverStripe\Dev\SapphireTest;
use SilverStripe\i18n\i18n;

/**
 * Shortcode output and labels (admintweaks#145).
 */
class ShortcodeLabelsTest extends SapphireTest
{
    protected $usesDatabase = false;

    public function testCurrentYearIsTheBareYear(): void
    {
        // It used to come wrapped in <div class="me">, a block element inside a copyright line.
        $this->assertSame(date('Y'), CurrentYearShortcode::parse_shortcode([], null, null, 'currentyear'));
    }

    public function testLabelsAreEnglishByDefaultAndTranslatable(): void
    {
        i18n::set_locale('en_US');
        $this->assertSame('Current year', CurrentYearShortcode::singleton()->singular_name());
        $this->assertSame('Featured image of the current page', FeaturedImageShortcode::singleton()->singular_name());

        i18n::set_locale('nl_NL');
        $this->assertSame('Actueel jaartal', CurrentYearShortcode::singleton()->singular_name());
    }
}
