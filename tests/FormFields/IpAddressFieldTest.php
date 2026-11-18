<?php

namespace Restruct\Silverstripe\AdminTweaks\Tests\FormFields;

use Restruct\Silverstripe\AdminTweaks\FormFields\IpAddressField;
use SilverStripe\Dev\SapphireTest;
use SilverStripe\i18n\i18n;

/**
 * IpAddressField validation and its message (admintweaks#145).
 */
class IpAddressFieldTest extends SapphireTest
{
    protected $usesDatabase = false;

    private function messages(string $value): array
    {
        $result = IpAddressField::create('IP')->setValue($value)->validate();

        return array_column($result->getMessages(), 'message');
    }

    public function testValidAddressesAndEmptyPass(): void
    {
        $this->assertSame([], $this->messages('192.0.2.1'));
        $this->assertSame([], $this->messages('2001:db8::1'));
        $this->assertSame([], $this->messages(''));
    }

    public function testTheMessageIsEnglishByDefaultAndTranslatable(): void
    {
        // It was hard-coded Dutch.
        i18n::set_locale('en_US');
        $this->assertSame(['Not a valid IP address'], $this->messages('not-an-ip'));

        i18n::set_locale('nl_NL');
        $this->assertSame(['Geen geldig IP-adres'], $this->messages('not-an-ip'));
    }
}
