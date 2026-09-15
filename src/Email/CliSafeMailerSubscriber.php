<?php

namespace Restruct\Silverstripe\AdminTweaks\Email;

use InvalidArgumentException;
use SilverStripe\Control\Director;
use SilverStripe\Control\Email\Email;
use SilverStripe\Control\Email\MailerSubscriber;
use SilverStripe\Control\HTTP;
use Symfony\Component\Mailer\Event\MessageEvent;

/**
 * MailerSubscriber that can send email with no current controller: a backport of the SS6 fix.
 *
 * In SS5 the framework's MailerSubscriber::updateUrls() runs HTTP::absoluteURLs() on every outgoing
 * email body. absoluteURLs() first does
 * `str_replace('$CurrentPageURL', Controller::curr()->getRequest()->getURL(), ...)` (framework
 * 5.4.26 src/Control/HTTP.php:68). With no controller on the stack, Controller::curr() returns null
 * and EVERY email fatals with "Call to a member function getRequest() on null". That covers bare CLI
 * scripts and anything after Controller::popCurrent(), e.g. shutdown functions and buffered log flushes.
 * It hits error-log mails and plain Email::send() alike, including queuedjobs'
 * checkJobHealth() report (admintweaks#59).
 *
 * Upstream fixed it in SS6 only: silverstripe/framework issue #11678
 * (https://github.com/silverstripe/silverstripe-framework/issues/11678), PR #11690
 * (https://github.com/silverstripe/silverstripe-framework/pull/11690, merged 2025-04-14 into 6.0).
 * That PR deletes the `$CurrentPageURL` line because nothing uses it (no template, getter or docs).
 * This class does exactly that and nothing more: absoluteURLs() below is the SS6 version.
 *
 * WHY AN INJECTOR OVERRIDE (last resort, see SSKB core-principles "Extend, Don't Replace"):
 * no hook exists. The only extension point, `updateOnMessage`, fires AFTER updateUrls() has
 * already fatalled. A second, higher-priority MessageEvent listener cannot help either, because
 * the framework subscriber still runs and evaluates Controller::curr() unconditionally. Wired in
 * _config/mailer.yml.
 *
 * MAINTENANCE COST: onMessage() calls PRIVATE parent methods, so applyConfig(), setTo(),
 * setFrom() and updateUrls() had to be COPIED verbatim from framework 5.4.26 (~60 lines).
 * If a later 5.x release changes those methods (send_all_emails_to/cc/bcc/from handling), this
 * copy will not pick that up. Re-diff against vendor/silverstripe/framework/src/Control/Email/
 * MailerSubscriber.php on every framework minor bump. `updateOnMessage` extensions registered on
 * the parent class still apply (extensions config is inherited by subclasses).
 *
 * DRIFT GUARD: this 3.x line allows silverstripe/framework ^4 | ^5. SS4 has no MailerSubscriber, and
 * _config/mailer.yml is `Only: classexists`, so the override is inert there. SS6 cannot install 3.x.
 * Verified 2026-09-14: framework MailerSubscriber.php is byte-identical (git blob 5e5501b) in ALL
 * 126 stable 5.x tags (5.0.0-5.4.30), and the `$CurrentPageURL` line is present in all of them.
 * tests/Email/CliSafeMailerSubscriberTest pins a hash of each copied parent method and fails the
 * moment a framework bump changes one. Re-copy, then update the hashes.
 *
 * SS6 REMOVAL NOTE: delete this class and _config/mailer.yml on the SS6 line (main / 4.x), where the
 * framework already contains the fix.
 */
class CliSafeMailerSubscriber extends MailerSubscriber
{
    /**
     * Verbatim copy of the framework 5.4.26 onMessage(), needed because the helpers it calls are private.
     */
    public function onMessage(MessageEvent $event): void
    {
        $email = $event->getMessage();
        if (!($email instanceof Email)) {
            throw new InvalidArgumentException('Message is not a ' . Email::class);
        }
        $this->applyConfig($email);
        $this->updateUrls($email);
        $this->extend('updateOnMessage', $email, $event);
    }

    /**
     * SS6 HTTP::absoluteURLs() (PR #11690): the SS5 body minus the `$CurrentPageURL` str_replace
     * that dereferences Controller::curr(). Every relative URL is still rewritten to an absolute
     * one, exactly as before.
     *
     * @param string $html
     * @return string
     */
    protected static function absoluteURLs($html)
    {
        return HTTP::urlRewriter($html, function ($url) {
            //no need to rewrite, if uri has a protocol (determined here by existence of reserved URI character ":")
            if (preg_match('/^\w+:/', $url ?? '')) {
                return $url;
            }
            return Director::absoluteURL((string) $url);
        });
    }

    # --- Verbatim copies of framework 5.4.26 private helpers (see MAINTENANCE COST above) ---

    private function applyConfig(Email $email): void
    {
        $sendAllTo = Email::getSendAllEmailsTo();
        if (!empty($sendAllTo)) {
            $this->setTo($email, $sendAllTo);
        }

        $ccAllTo = Email::getCCAllEmailsTo();
        if (!empty($ccAllTo)) {
            $email->addCc(...$ccAllTo);
        }

        $bccAllTo = Email::getBCCAllEmailsTo();
        if (!empty($bccAllTo)) {
            $email->addBcc(...$bccAllTo);
        }

        $sendAllFrom = Email::getSendAllEmailsFrom();
        if (!empty($sendAllFrom)) {
            $this->setFrom($email, $sendAllFrom);
        }
    }

    private function setTo(Email $email, array $sendAllTo): void
    {
        $headers = $email->getHeaders();
        // store the old data as X-Original-* Headers for debugging
        if (!empty($email->getTo())) {
            $headers->addMailboxListHeader('X-Original-To', $email->getTo());
        }
        if (!empty($email->getCc())) {
            $headers->addMailboxListHeader('X-Original-Cc', $email->getCc());
        }
        if (!empty($email->getBcc())) {
            $headers->addMailboxListHeader('X-Original-Bcc', $email->getBcc());
        }
        // set default recipient and remove all other recipients
        $email->to(...$sendAllTo);
        $email->cc(...[]);
        $email->bcc(...[]);
    }

    private function setFrom(Email $email, array $sendAllFrom): void
    {
        $headers = $email->getHeaders();
        if (!empty($email->getFrom())) {
            $headers->addMailboxListHeader('X-Original-From', $email->getFrom());
        }
        $email->from(...$sendAllFrom);
    }

    /**
     * Framework copy, but routed through the SS6 absoluteURLs() above instead of HTTP::absoluteURLs().
     */
    private function updateUrls(Email $email): void
    {
        if ($email->getHtmlBody()) {
            $email->html(static::absoluteURLs($email->getHtmlBody()));
        }
        if ($email->getTextBody()) {
            $email->text(static::absoluteURLs($email->getTextBody()));
        }
    }
}
