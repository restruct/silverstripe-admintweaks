<?php

namespace Restruct\Silverstripe\AdminTweaks\FormFields;

use Override;
use SilverStripe\Core\Validation\ValidationResult;
use SilverStripe\Forms\TextField;

class IpAddressField extends TextField
{
    #[Override]
    public function validate(): ValidationResult
    {
        $result = ValidationResult::create();

        if (!filter_var($this->value, FILTER_VALIDATE_IP) && trim((string) $this->value) !== '') {
            $result->addFieldError($this->name, 'Geen valide ip adres');
        }

        return $result;
    }
}
