<?php

namespace Restruct\Silverstripe\AdminTweaks\Extensions;

use Psr\Log\LoggerInterface;
use Restruct\Silverstripe\AdminTweaks\Security\BreachedPasswords;
use SilverStripe\Control\Controller;
use SilverStripe\Core\Extension;
use SilverStripe\Core\Injector\Injector;

/**
 * Opt-in (BreachedPasswords.expire_on_login): when a member logs in with a password that occurs
 * in the Have I Been Pwned breach data, expire that password. Core's LoginHandler then sends the
 * member to the change-password form straight after this login (it checks isPasswordExpired()
 * after authenticating), where BreachedPasswordValidatorExtension refuses another breached one.
 *
 * Catches passwords set before the check existed; new ones are already refused when set.
 *
 * @extends Extension<\SilverStripe\Security\Member>
 */
class BreachedPasswordMemberExtension extends Extension
{
    /**
     * Fired by MemberAuthenticator::recordLoginAttempt() after the password was verified.
     */
    public function onAuthenticationSucceeded(): void
    {
        $breachedPasswords = BreachedPasswords::singleton();
        if (!$breachedPasswords->isEnabled() || !BreachedPasswords::config()->get('expire_on_login')) {
            return;
        }

        # The hook carries no login data, so the password is read from the login form submission.
        # Only a form login posts one; other authenticators (eg. SSO) are skipped.
        if (!Controller::has_curr()) {
            return;
        }
        $password = Controller::curr()->getRequest()->postVar('Password');
        if (!is_string($password) || !$breachedPasswords->isBreached($password)) {
            return;
        }

        $member = $this->getOwner();
        # isPasswordExpired() compares against date('Y-m-d') and is true from the expiry date on,
        # so today's date expires it now.
        $member->PasswordExpiry = date('Y-m-d');
        $member->write();

        Injector::inst()->get(LoggerInterface::class)->notice(sprintf(
            'Password of member #%d expired at login: it occurs in breached-password data',
            $member->ID
        ));
    }
}
