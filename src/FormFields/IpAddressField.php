<?php

namespace Restruct\Silverstripe\AdminTweaks\FormFields;

use SilverStripe\Core\Validation\ValidationResult;
use SilverStripe\Forms\TextField;

class IpAddressField
    extends TextField
{
    /**
     * SS6 changed the contract: validate() takes no validator and RETURNS a ValidationResult
     * (FormField::validate(): ValidationResult). The SS5 form was `validate($validator)` returning
     * a bool and calling $validator->validationError() for the message.
     *
     * parent::validate() is combined in rather than skipped, so the field validators SS6 attaches
     * to a TextField (maxlength, for one) still run - returning a bare result would silently drop them.
     */
    public function validate(): ValidationResult
    {
        $result = parent::validate();

        // An empty value is left to the Required* validators to judge, exactly as before.
        if (!filter_var($this->value, FILTER_VALIDATE_IP) && trim((string) $this->value) !== '') {
            $result->addFieldError($this->name, 'Geen valide ip adres', 'validation');
        }

        return $result;
    }
}
