<?php

namespace Restruct\Silverstripe\AdminTweaks\Tests\FormFields;

use Restruct\Silverstripe\AdminTweaks\FormFields\CopyTextField;
use SilverStripe\Dev\SapphireTest;
use SilverStripe\Forms\FieldList;
use SilverStripe\Forms\Form;

/**
 * Tests for CopyTextField form field.
 *
 * Focus: Field construction, configuration, and template data.
 */
class CopyTextFieldTest extends SapphireTest
{
    protected $usesDatabase = false;

    public function testFieldCanBeCreated(): void
    {
        $field = CopyTextField::create('TestField', 'Test Label', 'Test Value');

        $this->assertInstanceOf(CopyTextField::class, $field);
        $this->assertEquals('TestField', $field->getName());
        // SS6 removed FormField::Value() (0 declarations in framework/src, against getValue()
        // as the control). getValue(): mixed is the surviving accessor.
        $this->assertEquals('Test Value', $field->getValue());
    }

    public function testFieldIsReadonlyByDefault(): void
    {
        $field = CopyTextField::create('TestField', 'Label', 'Value');

        $this->assertTrue($field->getIsReadonly());
    }

    public function testShowAlertIsFalseByDefault(): void
    {
        $field = CopyTextField::create('TestField', 'Label', 'Value');

        $this->assertFalse($field->getShowAlert());
    }

    public function testSetShowAlert(): void
    {
        $field = CopyTextField::create('TestField', 'Label', 'Value')
            ->setShowAlert(true);

        $this->assertTrue($field->getShowAlert());
    }

    public function testSetAlertMessage(): void
    {
        $field = CopyTextField::create('TestField', 'Label', 'Value')
            ->setAlertMessage('Copied!');

        $this->assertEquals('Copied!', $field->getAlertMessage());
    }

    public function testSetButtonLabel(): void
    {
        $field = CopyTextField::create('TestField', 'Label', 'Value')
            ->setButtonLabel('Kopieer');

        $this->assertEquals('Kopieer', $field->getButtonLabel());
    }

    public function testSetButtonTitle(): void
    {
        $field = CopyTextField::create('TestField', 'Label', 'Value')
            ->setButtonTitle('Click to copy');

        $this->assertEquals('Click to copy', $field->getButtonTitle());
    }

    public function testSetButtonClasses(): void
    {
        $field = CopyTextField::create('TestField', 'Label', 'Value')
            ->setButtonClasses('btn btn-primary');

        $this->assertEquals('btn btn-primary', $field->getButtonClasses());
    }

    public function testSetIsReadonly(): void
    {
        $field = CopyTextField::create('TestField', 'Label', 'Value')
            ->setIsReadonly(false);

        $this->assertFalse($field->getIsReadonly());
    }

    public function testGetTemplateDataReturnsAllProperties(): void
    {
        $field = CopyTextField::create('TestField', 'Label', 'Value')
            ->setShowAlert(true)
            ->setAlertMessage('Test alert')
            ->setButtonLabel('Copy')
            ->setButtonTitle('Tooltip')
            ->setButtonClasses('btn btn-primary')
            ->setIsReadonly(true);

        $data = $field->getTemplateData();

        $this->assertTrue($data['ShowAlert']);
        $this->assertEquals('Test alert', $data['AlertMessage']);
        $this->assertEquals('Copy', $data['ButtonLabel']);
        $this->assertEquals('Tooltip', $data['ButtonTitle']);
        $this->assertEquals('btn btn-primary', $data['ButtonClasses']);
        $this->assertTrue($data['IsReadonly']);
    }

    public function testFluentInterface(): void
    {
        $field = CopyTextField::create('TestField', 'Label', 'Value');

        // All setters should return the field instance for fluent chaining
        $result = $field
            ->setShowAlert(true)
            ->setAlertMessage('Alert')
            ->setButtonLabel('Label')
            ->setButtonTitle('Title')
            ->setButtonClasses('classes')
            ->setIsReadonly(false);

        $this->assertSame($field, $result);
    }

    public function testGetTemplatesReturnsCorrectPath(): void
    {
        $field = CopyTextField::create('TestField', 'Label', 'Value');

        $templates = $field->getTemplates();

        $this->assertContains(
            'Restruct/Silverstripe/AdminTweaks/FormFields/CopyTextField',
            $templates
        );
    }

    // ------------------------------------------------------------- rendering

    public function testFieldRendersItsValue()
    {
        // The template writes value="$Value", and SS6 REMOVED FormField::Value() - only
        // getValue() survives. Asserting getTemplates() contains a path (above) would not have
        // caught that; only rendering does. A FormField must belong to a Form before it can
        // render: FieldHolder() calls Link().
        $field = CopyTextField::create('TestField', 'Test Label', 'SENTINEL-VALUE-123');
        Form::create(null, 'TestForm', FieldList::create($field), FieldList::create());

        $html = (string) $field->FieldHolder();

        $this->assertStringContainsString(
            'SENTINEL-VALUE-123',
            $html,
            'The field must render its value; a removed template accessor would silently blank it'
        );
    }

    public function testFieldRendersItsButtonLabel()
    {
        $field = CopyTextField::create('TestField', 'Test Label', 'value')
            ->setButtonLabel('SENTINEL-BUTTON');
        Form::create(null, 'TestForm', FieldList::create($field), FieldList::create());

        $this->assertStringContainsString('SENTINEL-BUTTON', (string) $field->FieldHolder());
    }
}
