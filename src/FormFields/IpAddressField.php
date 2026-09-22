<?php

namespace Restruct\Silverstripe\AdminTweaks\FormFields;

use SilverStripe\Forms\TextField;

class IpAddressField
    extends TextField
{
    public function validate($validator)
    {
        if (filter_var($this->value, FILTER_VALIDATE_IP) || (trim($this->value)) == '') {
            return true;
        } else {
            $validator->validationError(
                $this->name, 'Geen valide ip adres', 'validation', false
            );
            return false;
        }
    }
}
