<?php

namespace Restruct\Silverstripe\AdminTweaks\Extensions;

use SilverStripe\Forms\CurrencyField;
use SilverStripe\Forms\DateField;
use SilverStripe\Forms\DropdownField;
use SilverStripe\Forms\EmailField;
use SilverStripe\Forms\NumericField;
use SilverStripe\Forms\PasswordField;
use SilverStripe\Forms\TextareaField;
use SilverStripe\Forms\TextField;

class FormFieldBootstrapExtension extends \SilverStripe\Core\Extension
{
    public function onBeforeRender( $field )
    {
        $form_control = [
            TextField::class,
            CurrencyField::class,
            DateField::class,
            EmailField::class,
            PasswordField::class,
            TextareaField::class,
            NumericField::class,
        ];
        if ( in_array( get_class( $field ), $form_control ) ) {
            $field->addExtraClass( 'form-control' );
        }

        $form_select = [
            DropdownField::class,
        ];
        if ( in_array( get_class( $field ), $form_select ) ) {
            $field->addExtraClass( 'form-select' );
        }

        $form_check = [
            CheckboxField::class,
            CheckboxSetField::class,
            OptionsetField::class,
        ];
        if ( in_array( get_class( $field ), $form_check ) ) {
            $field->addExtraClass( 'form-check' );
        }
    }
}
