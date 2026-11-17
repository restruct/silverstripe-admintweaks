<?php

namespace Restruct\Silverstripe\AdminTweaks\Tests\Extensions;

use Restruct\Silverstripe\AdminTweaks\Extensions\ToggleSwitchExtension;
use SilverStripe\Dev\SapphireTest;
use SilverStripe\Forms\CheckboxField;
use SilverStripe\Forms\CheckboxSetField;
use SilverStripe\Forms\TextField;

/**
 * ToggleSwitchExtension: ->setToggleSwitch() is a shorthand for addExtraClass('toggle-switch'),
 * the class the CSS toggle switch looks for. Checks that the module config applies it to both
 * checkbox fields (and not to every field), and where the class ends up in the rendered markup:
 * on the input of a CheckboxField, on the options' wrapper of a CheckboxSetField.
 */
class ToggleSwitchExtensionTest extends SapphireTest
{
    protected $usesDatabase = false;

    public function testAppliedToCheckboxFieldsOnly(): void
    {
        # From _config/config.yml, not from $required_extensions: the method must exist in a real install
        $this->assertTrue(CheckboxField::has_extension(ToggleSwitchExtension::class));
        $this->assertTrue(CheckboxSetField::has_extension(ToggleSwitchExtension::class));
        $this->assertFalse(TextField::has_extension(ToggleSwitchExtension::class));
    }

    public function testSetToggleSwitchAddsAndRemovesTheClass(): void
    {
        $field = CheckboxField::create('Active');
        $this->assertFalse($field->isToggleSwitch());

        # Chainable: returns the field itself
        $this->assertSame($field, $field->setToggleSwitch());
        $this->assertStringContainsString('toggle-switch', $field->extraClass());
        $this->assertTrue($field->isToggleSwitch());

        $field->setToggleSwitch(false);
        $this->assertStringNotContainsString('toggle-switch', $field->extraClass());
        $this->assertFalse($field->isToggleSwitch());
    }

    public function testClassAddedByHandCounts(): void
    {
        $field = CheckboxField::create('Active')->addExtraClass('toggle-switch');
        $this->assertTrue($field->isToggleSwitch());
    }

    public function testCheckboxFieldRendersTheClassOnTheInput(): void
    {
        $html = (string) CheckboxField::create('Active')->setToggleSwitch()->Field();
        $this->assertMatchesRegularExpression('/<input[^>]*type="checkbox"/', $html);
        $this->assertMatchesRegularExpression('/<input[^>]*class="[^"]*\btoggle-switch\b/', $html);
    }

    public function testCheckboxSetFieldRendersTheClassOnTheWrapper(): void
    {
        $field = CheckboxSetField::create('Tags', 'Tags', ['red' => 'Red', 'blue' => 'Blue'])->setToggleSwitch();
        $html = (string) $field->Field();

        # The options are inside the element that carries the class; the CSS uses a descendant
        # selector (.toggle-switch input[type=checkbox]) for them
        $this->assertMatchesRegularExpression('/^\s*<[a-z]+[^>]*class="[^"]*\btoggle-switch\b/', $html);
        $this->assertSame(2, preg_match_all('/<input[^>]*type="checkbox"/', $html));
    }
}
