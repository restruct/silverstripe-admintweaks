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
 * on the input of a CheckboxField, on the options' wrapper of a CheckboxSetField. The inverted
 * variant (->setToggleSwitchInverted()) adds the toggle-switch--inverted modifier next to it.
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

    public function testSetToggleSwitchInvertedAddsBothClasses(): void
    {
        $field = CheckboxField::create('Hidden');
        $this->assertFalse($field->isToggleSwitchInverted());

        # Chainable, and a switch as well: the modifier draws nothing on its own
        $this->assertSame($field, $field->setToggleSwitchInverted());
        $this->assertContains('toggle-switch', $this->classes($field));
        $this->assertContains('toggle-switch--inverted', $this->classes($field));
        $this->assertTrue($field->isToggleSwitch());
        $this->assertTrue($field->isToggleSwitchInverted());

        # false removes the inversion only: a plain switch is left
        $field->setToggleSwitchInverted(false);
        $this->assertNotContains('toggle-switch--inverted', $this->classes($field));
        $this->assertTrue($field->isToggleSwitch());
        $this->assertFalse($field->isToggleSwitchInverted());
    }

    public function testSetToggleSwitchFalseAlsoRemovesTheInversion(): void
    {
        $field = CheckboxField::create('Hidden')->setToggleSwitchInverted();
        $field->setToggleSwitch(false);
        $this->assertNotContains('toggle-switch', $this->classes($field));
        $this->assertNotContains('toggle-switch--inverted', $this->classes($field));

        # So a later setToggleSwitch() gives a plain switch, not an inverted one
        $field->setToggleSwitch();
        $this->assertTrue($field->isToggleSwitch());
        $this->assertFalse($field->isToggleSwitchInverted());
    }

    public function testSetToggleSwitchKeepsItsSignature(): void
    {
        # Backward compatible: one optional bool, as in 4.8.0
        $params = (new \ReflectionMethod(ToggleSwitchExtension::class, 'setToggleSwitch'))->getParameters();
        $this->assertCount(1, $params);
        $this->assertTrue($params[0]->isOptional());
    }

    public function testInvertedClassesAddedByHandCount(): void
    {
        $field = CheckboxField::create('Hidden')->addExtraClass('toggle-switch toggle-switch--inverted');
        $this->assertTrue($field->isToggleSwitchInverted());

        # The modifier alone is not a switch
        $field = CheckboxField::create('Hidden')->addExtraClass('toggle-switch--inverted');
        $this->assertFalse($field->isToggleSwitchInverted());
    }

    public function testInvertedRendersBothClassesOnInputAndWrapper(): void
    {
        $html = (string) CheckboxField::create('Hidden')->setToggleSwitchInverted()->Field();
        $this->assertMatchesRegularExpression('/<input[^>]*class="[^"]*\btoggle-switch\b[^"]*\btoggle-switch--inverted\b/', $html);

        # The input itself is unchanged: no checked attribute for an unset value, the usual value
        $this->assertDoesNotMatchRegularExpression('/<input[^>]*\bchecked\b/', $html);
        $this->assertMatchesRegularExpression('/<input[^>]*value="1"/', $html);

        $field = CheckboxSetField::create('HiddenTags', 'Hidden tags', ['red' => 'Red', 'blue' => 'Blue'])
            ->setToggleSwitchInverted();
        $html = (string) $field->Field();
        $this->assertMatchesRegularExpression('/^\s*<[a-z]+[^>]*class="[^"]*\btoggle-switch--inverted\b/', $html);
    }

    /**
     * The field's extra classes as a list (framework 4.8 has no hasExtraClass(), and a substring
     * check cannot tell toggle-switch from toggle-switch--inverted).
     *
     * @return string[]
     */
    private function classes(CheckboxField $field): array
    {
        return preg_split('/\s+/', (string) $field->extraClass(), -1, PREG_SPLIT_NO_EMPTY);
    }
}
