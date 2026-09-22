<?php

namespace Restruct\Silverstripe\AdminTweaks\Tests\Email;

use ReflectionProperty;
use Restruct\Silverstripe\AdminTweaks\Email\CliSafeMailerSubscriber;
use SilverStripe\Control\Controller;
use SilverStripe\Control\Director;
use SilverStripe\Control\Email\Email;
use SilverStripe\Control\Email\MailerSubscriber;
use SilverStripe\Core\Injector\Injector;
use SilverStripe\Dev\SapphireTest;
use Symfony\Component\Mailer\Envelope;
use Symfony\Component\Mailer\Event\MessageEvent;

/**
 * admintweaks#59: backport of silverstripe/framework#11678 / PR #11690 (SS6). Emails must process
 * with NO current controller, while relative URLs are still made absolute.
 */
class CliSafeMailerSubscriberTest extends SapphireTest
{
    protected $usesDatabase = false;

    protected function setUp(): void
    {
        parent::setUp();
        // This 3.x line also allows SS4, which has no MailerSubscriber (the yml override is inert there).
        if (!class_exists(MailerSubscriber::class)) {
            $this->markTestSkipped('SS4: no framework MailerSubscriber, backport not active');
        }
    }

    public function testInjectorResolvesTheFrameworkSubscriberToTheBackport(): void
    {
        // _config/mailer.yml swaps the service the framework's mailer dispatcher subscribes.
        $this->assertInstanceOf(
            CliSafeMailerSubscriber::class,
            Injector::inst()->get(MailerSubscriber::class)
        );
    }

    public function testEmailIsProcessedWithNoCurrentControllerAndUrlsAreStillAbsolutised(): void
    {
        $email = $this->makeEmail();

        $this->withEmptyControllerStack(function () use ($email) {
            (new CliSafeMailerSubscriber())->onMessage($this->makeEvent($email));
        });

        $this->assertStringContainsString(
            'href="' . Director::absoluteURL('/some/page') . '"',
            $email->getHtmlBody(),
            'relative URLs are still rewritten to absolute ones'
        );
    }

    public function testOutputIsIdenticalToTheFrameworkSubscriberWhenAControllerIsCurrent(): void
    {
        // A59b at unit level: with a controller on the stack (sake/TaskRunner, web requests) the
        // backport must produce exactly what the framework subscriber produces, html AND text body.
        $frameworkEmail = $this->makeEmail();
        $backportEmail = $this->makeEmail();

        // pushCurrent() reads the request's session (Controller.php:578), so the request needs one.
        $request = new \SilverStripe\Control\HTTPRequest('GET', '/');
        $request->setSession(new \SilverStripe\Control\Session([]));
        $controller = Controller::create();
        $controller->setRequest($request);
        $controller->pushCurrent();
        try {
            (new MailerSubscriber())->onMessage($this->makeEvent($frameworkEmail));
            (new CliSafeMailerSubscriber())->onMessage($this->makeEvent($backportEmail));
        } finally {
            $controller->popCurrent();
        }

        $this->assertSame($frameworkEmail->getHtmlBody(), $backportEmail->getHtmlBody());
        $this->assertSame($frameworkEmail->getTextBody(), $backportEmail->getTextBody());
    }

    public function testFrameworkSubscriberStillFatalsWithNoCurrentController(): void
    {
        // Documents WHY the backport exists. If this starts failing (framework fixed upstream in
        // this major line), the backport can go: see the SS6 removal note on CliSafeMailerSubscriber.
        $email = $this->makeEmail();
        $this->expectException(\Error::class);
        $this->expectExceptionMessage('getRequest() on null');

        $this->withEmptyControllerStack(function () use ($email) {
            // Suppress the E_USER_WARNING "No current controller available" so the Error is what surfaces.
            set_error_handler(fn () => true, E_USER_WARNING);
            try {
                (new MailerSubscriber())->onMessage($this->makeEvent($email));
            } finally {
                restore_error_handler();
            }
        });
    }

    /**
     * Drift guard (A59g): sha1 of each framework MailerSubscriber method the backport copies, normalised
     * (source lines trimmed, empty lines dropped). Taken from framework 5.4.26, whose MailerSubscriber.php
     * is byte-identical (git blob 5e5501b) to every stable 5.x tag, 5.0.0-5.4.30, checked 2026-09-14.
     */
    private const PARENT_METHOD_HASHES = [
        'onMessage' => 'bac7338b58fb90264caebbc939d16feb7a245646',
        'applyConfig' => '9564926d694f56ab59c4ac84e0991762c69c0ae2',
        'setTo' => '1b5f06fcb60e3fd8e3cfebae7234a59f4884b452',
        'setFrom' => '4fa87a05b9b911633388698871e5a6e25cb9c9f7',
        'updateUrls' => '708fc546eaacb306b44b2a73ec65d25681519d09',
    ];

    public function testCopiedFrameworkMethodsHaveNotChangedUpstream(): void
    {
        foreach (self::PARENT_METHOD_HASHES as $method => $hash) {
            $this->assertSame(
                $hash,
                $this->methodHash(MailerSubscriber::class, $method),
                "framework MailerSubscriber::{$method}() changed in this framework version: re-copy it into "
                . 'CliSafeMailerSubscriber (or drop the backport if the framework is fixed), then update the hash'
            );
        }
    }

    public function testCopiesAreVerbatimExceptTheUrlRewrite(): void
    {
        // updateUrls() is the one intended difference (static::absoluteURLs instead of HTTP::absoluteURLs).
        foreach (['onMessage', 'applyConfig', 'setTo', 'setFrom'] as $method) {
            $this->assertSame(
                $this->methodHash(MailerSubscriber::class, $method),
                $this->methodHash(CliSafeMailerSubscriber::class, $method),
                "CliSafeMailerSubscriber::{$method}() is no longer a verbatim copy of the framework method"
            );
        }
    }

    /** Normalised sha1 of a method's source as declared on $class (trimmed lines, empty lines dropped). */
    private function methodHash(string $class, string $method): string
    {
        $reflection = new \ReflectionMethod($class, $method);
        $this->assertSame($class, $reflection->getDeclaringClass()->getName(), "{$class}::{$method} must be declared there");
        $lines = file($reflection->getFileName());
        $source = array_slice($lines, $reflection->getStartLine() - 1, $reflection->getEndLine() - $reflection->getStartLine() + 1);

        return sha1(implode("\n", array_filter(array_map('trim', $source), 'strlen')));
    }

    private function makeEmail(): Email
    {
        return Email::create('from@example.com', 'to@example.com', 'Subject')
            ->html('<p><a href="/some/page">link</a></p>')
            ->text('See /text/link');
    }

    private function makeEvent(Email $email): MessageEvent
    {
        return new MessageEvent($email, Envelope::create($email), 'null://');
    }

    /** Runs $callback with Controller's stack emptied, then restores it (tests may run with one pushed). */
    private function withEmptyControllerStack(callable $callback): void
    {
        $stack = new ReflectionProperty(Controller::class, 'controller_stack');
        $saved = $stack->getValue();
        $stack->setValue(null, []);
        try {
            $callback();
        } finally {
            $stack->setValue(null, $saved);
        }
    }
}
