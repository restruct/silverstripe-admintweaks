<?php

namespace Restruct\Silverstripe\AdminTweaks\Tests\Logging;

use DateTimeImmutable;
use Monolog\Level;
use Monolog\LogRecord;
use Restruct\Silverstripe\AdminTweaks\Helpers\CacheHelpers;
use Restruct\Silverstripe\AdminTweaks\Logging\DedupSymfonyMailerHandler;
use SilverStripe\Control\Email\Email;
use SilverStripe\Dev\SapphireTest;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\RawMessage;

/**
 * Flood-guard behaviour: content dedup + the global rate cap that bounds total
 * error emails regardless of how the message text varies.
 */
class DedupSymfonyMailerHandlerTest extends SapphireTest
{
    protected $usesDatabase = false;

    private int $sent = 0;

    protected function setUp(): void
    {
        parent::setUp();
        $this->sent = 0;
        // Isolate every test run: wipe any counters/dedup keys from prior runs.
        CacheHelpers::load_cache()->clear();
    }

    /** A handler wired to a mailer that just counts sends (never touches SMTP). */
    private function makeHandler(int $max, int $window = 3600, int $dedup = 300): DedupSymfonyMailerHandler
    {
        DedupSymfonyMailerHandler::config()->set('max_emails_per_window', $max);
        DedupSymfonyMailerHandler::config()->set('rate_window', $window);
        DedupSymfonyMailerHandler::config()->set('dedup_time', $dedup);

        $mailer = new class($this->sent) implements MailerInterface {
            public function __construct(private int &$counter) {}
            public function send(RawMessage $message, $envelope = null): void { $this->counter++; }
        };

        $email = Email::create('from@example.com', 'to@example.com', 'Err');
        return new DedupSymfonyMailerHandler($mailer, $email, Level::Error, true);
    }

    private function record(string $message): LogRecord
    {
        return new LogRecord(new DateTimeImmutable(), 'app', Level::Error, $message);
    }

    /** Drives the protected send() the way Monolog would. */
    private function fire(DedupSymfonyMailerHandler $h, string $message): void
    {
        $m = new \ReflectionMethod($h, 'send');
        $m->setAccessible(true);
        $m->invoke($h, "body: $message", [$this->record($message)]);
    }

    public function testIdenticalErrorsAreDeduped(): void
    {
        $h = $this->makeHandler(max: 100);
        for ($i = 0; $i < 5; $i++) {
            $this->fire($h, 'the same error');
        }
        $this->assertSame(1, $this->sent, 'identical messages email once within the dedup window');
    }

    public function testVaryingMessagesAreCappedByTheGlobalLimit(): void
    {
        // The flood vector: every message is unique, so content dedup never fires.
        $h = $this->makeHandler(max: 5);
        for ($i = 0; $i < 50; $i++) {
            $this->fire($h, "unique error #$i host=www.goflex.nl.$i");
        }
        $this->assertSame(5, $this->sent,
            'the global cap bounds total emails even when every message differs');
    }

    public function testCapZeroDisablesTheCapButDedupStillApplies(): void
    {
        $h = $this->makeHandler(max: 0);
        for ($i = 0; $i < 20; $i++) {
            $this->fire($h, "unique #$i");   // all unique → no dedup
        }
        $this->assertSame(20, $this->sent, 'max=0 disables the cap');

        $this->sent = 0;
        CacheHelpers::load_cache()->clear();
        $h = $this->makeHandler(max: 0);
        for ($i = 0; $i < 20; $i++) {
            $this->fire($h, "same");         // identical → dedup to 1
        }
        $this->assertSame(1, $this->sent, 'dedup still applies with the cap disabled');
    }

    public function testFailingSendNeverThrowsIntoTheLoggingCallSiteAndFallsBackToErrorLog(): void
    {
        // admintweaks#59: with no current controller the framework's MailerSubscriber derefs
        // Controller::curr() = null inside Mailer::send(). Simulate that exact Error at the
        // transport, so the test doesn't depend on the controller stack state of the test run.
        DedupSymfonyMailerHandler::config()->set('max_emails_per_window', 100);
        $mailer = new class implements MailerInterface {
            public function send(RawMessage $message, $envelope = null): void
            {
                throw new \Error('Call to a member function getRequest() on null');
            }
        };
        $h = new DedupSymfonyMailerHandler(
            $mailer,
            Email::create('from@example.com', 'to@example.com', 'Err'),
            Level::Error,
            true
        );

        // Capture error_log() output in a temp file, restoring the ini afterwards.
        $logFile = tempnam(sys_get_temp_dir(), 'at59');
        $previous = ini_set('error_log', $logFile);
        try {
            $this->fire($h, 'original error that must not be lost');
        } finally {
            ini_set('error_log', (string) $previous);
        }

        $logged = (string) file_get_contents($logFile);
        unlink($logFile);
        $this->assertStringContainsString('[admintweaks] Error email not sent (Error: Call to a member function getRequest() on null', $logged);
        $this->assertStringContainsString('original error that must not be lost', $logged,
            'the record that triggered the mail is still recorded via error_log');
    }
}
