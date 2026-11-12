<?php

namespace Restruct\Silverstripe\AdminTweaks\Extensions;

use Restruct\Silverstripe\AdminTweaks\Security\BreachedPasswords;
use SilverStripe\Core\Extension;
use SilverStripe\Security\Member;
use SilverStripe\ORM\ValidationResult;

/**
 * Refuses a password that occurs in the Have I Been Pwned breach data, whenever a password is set
 * (CMS member edit, change-password and reset forms), through the updateValidatePassword hook of
 * SilverStripe\Security\PasswordValidator.
 *
 * @extends Extension<\SilverStripe\Security\PasswordValidator>
 */
class BreachedPasswordValidatorExtension extends Extension
{
    public function updateValidatePassword(string $password, Member $member, ValidationResult $result, $validator): void
    {
        if (!BreachedPasswords::singleton()->isBreached($password)) {
            return;
        }
        $result->addError(
            _t(
                __CLASS__ . '.BREACHED',
                'This password appears in a list of passwords exposed in data breaches. Please choose a different password.'
            ),
            'bad',
            'BREACHED_PASSWORD'
        );
    }
}
