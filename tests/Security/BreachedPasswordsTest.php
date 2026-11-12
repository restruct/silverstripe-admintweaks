<?php

namespace Restruct\Silverstripe\AdminTweaks\Tests\Security;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\ConnectException;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Middleware;
use GuzzleHttp\Psr7\Request;
use GuzzleHttp\Psr7\Response;
use Restruct\Silverstripe\AdminTweaks\Security\BreachedPasswords;
use SilverStripe\Control\Controller;
use SilverStripe\Control\HTTPRequest;
use SilverStripe\Control\Session;
use SilverStripe\Core\Injector\Injector;
use SilverStripe\Dev\SapphireTest;
use SilverStripe\Security\Member;
use SilverStripe\Security\PasswordValidator;

/**
 * The breached-password check (HIBP range API) when a password is set, and the opt-in expiry of a
 * breached password at login. No request leaves the test run: every test hands BreachedPasswords
 * a Guzzle client with a MockHandler.
 *
 * Compatibility: must run on PHPUnit 9 (SS5) and 11 (SS6). No doc-comment metadata, static
 * data providers only.
 */
class BreachedPasswordsTest extends SapphireTest
{
    protected $usesDatabase = true;

    private const BREACHED = 'correct horse battery staple';

    private array $requests = [];

    protected function setUp(): void
    {
        parent::setUp();
        # passwords.yml switches the check off outside live, which includes this test run.
        BreachedPasswords::config()->set('enabled', true);
        BreachedPasswords::config()->set('min_breach_count', 1);
        BreachedPasswords::config()->set('expire_on_login', false);
        # A validator without its own rules, so only the breach check can make it fail.
        Injector::inst()->registerService(
            PasswordValidator::create()->setMinLength(0)->setHistoricCount(0)->setMinTestScore(0),
            PasswordValidator::class
        );
    }

    protected function tearDown(): void
    {
        BreachedPasswords::singleton()->setHttpClient(null);
        parent::tearDown();
    }

    /**
     * Answers the next lookup(s) with the given responses (or exceptions), and records the requests.
     */
    private function mockApi(...$responses): void
    {
        $this->requests = [];
        $stack = HandlerStack::create(new MockHandler($responses));
        $stack->push(Middleware::history($this->requests));
        BreachedPasswords::singleton()->setHttpClient(new Client(['handler' => $stack]));
    }

    /**
     * A range response that contains the given password's suffix with $count, between padding rows.
     */
    private static function rangeBody(string $password, int $count): string
    {
        $suffix = substr(strtoupper(sha1($password)), 5);
        return "0018A45C4D1DEF81644B54AB7F969B88D65:0\r\n{$suffix}:{$count}\r\nFFFFF0000000000000000000000000000000:3";
    }

    public function testOnlyTheHashPrefixIsSent(): void
    {
        $this->mockApi(new Response(200, [], self::rangeBody(self::BREACHED, 7)));

        $this->assertSame(7, BreachedPasswords::singleton()->breachCount(self::BREACHED));

        $this->assertCount(1, $this->requests);
        /** @var Request $request */
        $request = $this->requests[0]['request'];
        $hash = strtoupper(sha1(self::BREACHED));
        $this->assertStringEndsWith('/range/' . substr($hash, 0, 5), (string) $request->getUri());
        $this->assertStringNotContainsString(substr($hash, 5), (string) $request->getUri());
        $this->assertSame('true', $request->getHeaderLine('Add-Padding'));
    }

    public function testPasswordNotInTheRangeCountsZero(): void
    {
        $this->mockApi(new Response(200, [], self::rangeBody('some other password', 12)));

        $this->assertSame(0, BreachedPasswords::singleton()->breachCount(self::BREACHED));
        $this->mockApi(new Response(200, [], self::rangeBody('some other password', 12)));
        $this->assertFalse(BreachedPasswords::singleton()->isBreached(self::BREACHED));
    }

    public function testPaddingRowDoesNotCountAsBreached(): void
    {
        # Padding rows carry a count of 0; a 0 for our suffix is "not found", not "breached".
        $this->mockApi(new Response(200, [], self::rangeBody(self::BREACHED, 0)));

        $this->assertFalse(BreachedPasswords::singleton()->isBreached(self::BREACHED));
    }

    public function testMinBreachCount(): void
    {
        BreachedPasswords::config()->set('min_breach_count', 10);

        $this->mockApi(new Response(200, [], self::rangeBody(self::BREACHED, 9)));
        $this->assertFalse(BreachedPasswords::singleton()->isBreached(self::BREACHED));

        $this->mockApi(new Response(200, [], self::rangeBody(self::BREACHED, 10)));
        $this->assertTrue(BreachedPasswords::singleton()->isBreached(self::BREACHED));
    }

    public function testLookupFailureFailsOpen(): void
    {
        $this->mockApi(new ConnectException('timed out', new Request('GET', 'https://example.invalid')));
        $this->assertNull(BreachedPasswords::singleton()->breachCount(self::BREACHED));

        $this->mockApi(new Response(503, [], 'Service Unavailable'));
        $this->assertFalse(BreachedPasswords::singleton()->isBreached(self::BREACHED));
    }

    public function testDisabledSendsNoRequest(): void
    {
        BreachedPasswords::config()->set('enabled', false);
        $this->mockApi(new Response(200, [], self::rangeBody(self::BREACHED, 7)));

        $this->assertFalse(BreachedPasswords::singleton()->isBreached(self::BREACHED));
        $this->assertCount(0, $this->requests);
    }

    public function testValidatorRefusesBreachedPassword(): void
    {
        $member = Member::create();

        $this->mockApi(new Response(200, [], self::rangeBody(self::BREACHED, 7)));
        $result = Member::password_validator()->validate(self::BREACHED, $member);
        $this->assertFalse($result->isValid());
        $this->assertContains('BREACHED_PASSWORD', array_keys($result->getMessages()));

        $this->mockApi(new Response(200, [], self::rangeBody('some other password', 7)));
        $this->assertTrue(Member::password_validator()->validate(self::BREACHED, $member)->isValid());
    }

    public function testExpireOnLoginIsOffByDefault(): void
    {
        $member = $this->loginWith(self::BREACHED, self::rangeBody(self::BREACHED, 7));

        $this->assertFalse($member->isPasswordExpired());
        $this->assertCount(0, $this->requests, 'no lookup at login unless expire_on_login is on');
    }

    public function testExpireOnLoginExpiresBreachedPassword(): void
    {
        BreachedPasswords::config()->set('expire_on_login', true);

        $member = $this->loginWith(self::BREACHED, self::rangeBody(self::BREACHED, 7));

        $this->assertTrue($member->isPasswordExpired());
        $this->assertTrue(Member::get()->byID($member->ID)->isPasswordExpired(), 'expiry is written');
    }

    public function testExpireOnLoginKeepsSafePassword(): void
    {
        BreachedPasswords::config()->set('expire_on_login', true);

        $member = $this->loginWith(self::BREACHED, self::rangeBody('some other password', 7));

        $this->assertFalse($member->isPasswordExpired());
    }

    /**
     * Fires the hook MemberAuthenticator fires after a successful login, with the login form's
     * POST as the current request.
     */
    private function loginWith(string $password, string $rangeBody): Member
    {
        BreachedPasswords::config()->set('enabled', false);
        $member = Member::create(['Email' => 'breached@example.com']);
        $member->write();
        BreachedPasswords::config()->set('enabled', true);

        $this->mockApi(new Response(200, [], $rangeBody));
        $request = new HTTPRequest('POST', 'Security/login/default/LoginForm', [], ['Password' => $password]);
        $request->setSession(new Session([]));
        $controller = Controller::create();
        $controller->setRequest($request);
        $controller->pushCurrent();
        try {
            $member->extend('authenticationSucceeded');
        } finally {
            $controller->popCurrent();
        }
        return $member;
    }
}
