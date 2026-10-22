<?php

namespace Restruct\Silverstripe\AdminTweaks\Tests\Extensions;

use Restruct\Silverstripe\AdminTweaks\Extensions\FormFieldTweaksExtension;
use SilverStripe\Core\Validation\ValidationResult;
use SilverStripe\Dev\SapphireTest;
use SilverStripe\Forms\TextField;

/**
 * FormFieldTweaksExtension::onBeforeRender() maps the field's message type onto a
 * Bootstrap-style is-valid / is-invalid class. It references ValidationResult::TYPE_GOOD,
 * so rendering a field that carries a message is the code path that fatals when the import
 * points at a class this framework line does not have (admintweaks#90).
 */
class FormFieldTweaksExtensionTest extends SapphireTest
{
    protected $usesDatabase = false;

    protected static $required_extensions = [
        TextField::class => [
            FormFieldTweaksExtension::class,
        ],
    ];

    public function testGoodMessageAddsIsValidClass(): void
    {
        $field = TextField::create('Name');
        $field->setMessage('Looks fine', ValidationResult::TYPE_GOOD);

        # Field() fires the onBeforeRender extension hook
        $field->Field();

        $this->assertTrue($field->hasExtraClass('is-valid'));
        $this->assertFalse($field->hasExtraClass('is-invalid'));
    }

    public function testErrorMessageAddsIsInvalidClass(): void
    {
        $field = TextField::create('Name');
        $field->setMessage('Required', ValidationResult::TYPE_ERROR);

        $field->Field();

        $this->assertTrue($field->hasExtraClass('is-invalid'));
        $this->assertFalse($field->hasExtraClass('is-valid'));
    }

    public function testNoMessageAddsNoValidationClass(): void
    {
        $field = TextField::create('Name');

        $field->Field();

        $this->assertFalse($field->hasExtraClass('is-valid'));
        $this->assertFalse($field->hasExtraClass('is-invalid'));
    }
}
