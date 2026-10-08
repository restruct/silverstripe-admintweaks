<?php

namespace Restruct\Silverstripe\AdminTweaks\Tests\Config;

use SilverStripe\Control\Email\Email;
use SilverStripe\Core\Environment;
use SilverStripe\Dev\SapphireTest;

/**
 * The module's _config.php fills Email.queued_job_admin_email from the environment.
 *
 * queuedjobs' EmailService sends its broken/stalled/missing-default-job reports TO that address, so
 * it must follow APP_LOG_MAIL_RECIPIENT, never the no-reply APP_LOG_MAIL_SENDER (admintweaks#57,
 * fixed in 3.20.4 and carried into 4.x; this pins it).
 *
 * _config.php runs once at boot, before any test can set the environment, so each test re-runs it
 * after arranging env and config. Config changes are rolled back by SapphireTest; env vars are
 * restored in tearDown().
 *
 * Compatibility: must run on PHPUnit 9 (SS5) and 11 (SS6). No doc-comment metadata.
 */
class QueuedJobAdminEmailTest extends SapphireTest
{
    protected $usesDatabase = false;

    private array $envBackup = [];

    protected function setUp(): void
    {
        parent::setUp();
        $this->envBackup = Environment::getVariables();
    }

    protected function tearDown(): void
    {
        Environment::setVariables($this->envBackup);
        parent::tearDown();
    }

    private function runModuleConfig(): void
    {
        require dirname(__DIR__, 2) . '/_config.php';
    }

    public function testReportsGoToTheErrorMailRecipientNotTheSender()
    {
        Environment::setEnv('APP_LOG_MAIL_RECIPIENT', 'ops@example.com');
        Environment::setEnv('APP_LOG_MAIL_SENDER', 'noreply@example.com');
        Email::config()->remove('queued_job_admin_email');

        $this->runModuleConfig();

        $this->assertSame('ops@example.com', Email::config()->get('queued_job_admin_email'));
    }

    public function testSenderAloneDoesNotBecomeTheReportRecipient()
    {
        # Only the no-reply sender configured: queuedjobs must not be pointed at it.
        Environment::setEnv('APP_LOG_MAIL_RECIPIENT', '');
        Environment::setEnv('APP_LOG_MAIL_SENDER', 'noreply@example.com');
        Email::config()->remove('queued_job_admin_email');

        $this->runModuleConfig();

        $this->assertNotSame('noreply@example.com', Email::config()->get('queued_job_admin_email'));
    }
}
