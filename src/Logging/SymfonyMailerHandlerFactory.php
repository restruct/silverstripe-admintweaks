<?php

namespace Restruct\Silverstripe\AdminTweaks\Logging;

use Monolog\Handler\SymfonyMailerHandler;
use Monolog\Level;
use SilverStripe\Control\Email\Email;
use SilverStripe\Core\Environment;
use SilverStripe\Core\Injector\Factory;
use SilverStripe\Core\Injector\Injector;
use Symfony\Component\Mailer\MailerInterface;

/**
 * Factory for creating a SymfonyMailerHandler that uses the configured Symfony Mailer.
 *
 * This ensures error emails go through the same mail transport as regular emails
 * (configured via MAILER_DSN), instead of bypassing it with PHP's native mail().
 *
 * Uses SilverStripe's Email class to be compatible with MailerSubscriber.
 *
 * Environment variables:
 * - APP_LOG_MAIL_RECIPIENT: Email address to send errors to
 * - APP_LOG_MAIL_SUBJECT: Subject line for error emails
 * - APP_LOG_MAIL_SENDER: From address for error emails
 * - APP_LOG_MAIL_LEVEL: Minimum log level (error, warning, info)
 */
class SymfonyMailerHandlerFactory implements Factory
{
    public function create($service, array $params = [])
    {
        // Get the Symfony Mailer (configured via MAILER_DSN)
        $mailer = Injector::inst()->get(MailerInterface::class);

        // Get config from environment
        $recipient = Environment::getEnv('APP_LOG_MAIL_RECIPIENT');
        $subject = Environment::getEnv('APP_LOG_MAIL_SUBJECT') ?: 'Error on website';
        $sender = Environment::getEnv('APP_LOG_MAIL_SENDER');
        $levelStr = Environment::getEnv('APP_LOG_MAIL_LEVEL') ?: 'error';

        // Map string level to Monolog Level
        $level = match (strtolower($levelStr)) {
            'debug' => Level::Debug,
            'info' => Level::Info,
            'notice' => Level::Notice,
            'warning' => Level::Warning,
            'error' => Level::Error,
            'critical' => Level::Critical,
            'alert' => Level::Alert,
            'emergency' => Level::Emergency,
            default => Level::Error,
        };

        // Create email template using SilverStripe's Email class
        // (required for compatibility with SilverStripe's MailerSubscriber)
        $email = Email::create()
            ->setFrom($sender)
            ->setTo($recipient)
            ->setSubject($subject);

        // bubble: false — prevent $record->formatted (email HTML) from leaking to
        // downstream handlers like HTTPOutputHandler, which would output it to the browser
        return new SymfonyMailerHandler($mailer, $email, $level, bubble: false);
    }
}
