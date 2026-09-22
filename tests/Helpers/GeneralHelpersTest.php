<?php

namespace Restruct\Silverstripe\AdminTweaks\Tests\Helpers;

use Restruct\Silverstripe\AdminTweaks\Helpers\GeneralHelpers;
use SilverStripe\Dev\SapphireTest;

/**
 * Tests for GeneralHelpers utility methods.
 *
 * Focus: safelyGetProperty() which has non-trivial nested property access logic.
 */
class GeneralHelpersTest extends SapphireTest
{
    protected $usesDatabase = false;

    public function testSafelyGetPropertyReturnsValueForSimpleProperty(): void
    {
        $obj = (object) ['name' => 'John'];

        $result = GeneralHelpers::safelyGetProperty($obj, 'name');

        $this->assertEquals('John', $result);
    }

    public function testSafelyGetPropertyReturnsNullForMissingProperty(): void
    {
        $obj = (object) ['name' => 'John'];

        $result = GeneralHelpers::safelyGetProperty($obj, 'missing');

        $this->assertNull($result);
    }

    public function testSafelyGetPropertyReturnsNullForNonObject(): void
    {
        $result = GeneralHelpers::safelyGetProperty('not an object', 'property');

        $this->assertNull($result);
    }

    public function testSafelyGetPropertyReturnsNullForNullInput(): void
    {
        $result = GeneralHelpers::safelyGetProperty(null, 'property');

        $this->assertNull($result);
    }

    public function testSafelyGetPropertyHandlesNestedPropertiesFromString(): void
    {
        $obj = (object) [
            'level1' => (object) [
                'level2' => (object) [
                    'value' => 'deep value',
                ],
            ],
        ];

        $result = GeneralHelpers::safelyGetProperty($obj, 'level1->level2->value');

        $this->assertEquals('deep value', $result);
    }

    public function testSafelyGetPropertyHandlesNestedPropertiesFromArray(): void
    {
        $obj = (object) [
            'level1' => (object) [
                'level2' => 'nested value',
            ],
        ];

        $result = GeneralHelpers::safelyGetProperty($obj, ['level1', 'level2']);

        $this->assertEquals('nested value', $result);
    }

    public function testSafelyGetPropertyReturnsNullWhenNestedPathBreaks(): void
    {
        $obj = (object) [
            'level1' => (object) [
                'other' => 'value',
            ],
        ];

        $result = GeneralHelpers::safelyGetProperty($obj, 'level1->level2->level3');

        $this->assertNull($result);
    }

    public function testSafelyGetPropertyReturnsNullWhenIntermediateIsNotObject(): void
    {
        $obj = (object) [
            'level1' => 'just a string',
        ];

        $result = GeneralHelpers::safelyGetProperty($obj, 'level1->level2');

        $this->assertNull($result);
    }

    public function testGetOptionsTranslationsReturnsKeyValuePairs(): void
    {
        $options = ['left', 'right', 'center'];

        $result = GeneralHelpers::get_options_translations($options);

        // Without translations defined, values should be same as keys
        $this->assertEquals('left', $result['left']);
        $this->assertEquals('right', $result['right']);
        $this->assertEquals('center', $result['center']);
    }

    public function testGetOptionsTranslationsReturnsArrayWithCorrectKeys(): void
    {
        $options = ['option1', 'option2'];

        $result = GeneralHelpers::get_options_translations($options);

        $this->assertArrayHasKey('option1', $result);
        $this->assertArrayHasKey('option2', $result);
        $this->assertCount(2, $result);
    }
}
