<?php

use SilverStripe\Core\Environment;
use SilverStripe\Control\Email\Email;

//
// Set system email sender + queued-job report recipient from ENV.
// (Done imperatively here, not in YAML, because backtick ENV vars in YAML are only
// resolved via Injector, and these are Config API values that Email reads via Config.)
//
// FALLBACK SEMANTICS (fixed 2026-08-31, admintweaks#58): env only fills these when the
// PROJECT HAS NOT explicitly set them. An explicit YAML/config value always wins — env is
// a convenience fallback, not an override. Previously env clobbered an explicitly-assigned
// value (e.g. a project's `queued_job_admin_email: admin@example.com` was overwritten),
// which is the opposite of the intended "sane default, explicit wins" behaviour.
//

// --- System email sender (admin_email) ---
$sys_email = Environment::getEnv('APP_SYSTEM_EMAIL_ADDRESS');
$sys_name = Environment::getEnv('APP_SYSTEM_EMAIL_SENDER');
$admin_email_config = Email::config()->get('admin_email');
// '' is the framework default (Email::$admin_email = ''); [] / null also count as "unset".
$admin_email_unset = ($admin_email_config === '' || $admin_email_config === null || $admin_email_config === []);
if ($sys_email && $sys_name && $admin_email_unset) {
    Email::config()->set('admin_email', [$sys_email => $sys_name]);
}

// --- Queued-job report recipient (queued_job_admin_email) ---
// NOTE: queuedjobs' EmailService uses this as the RECIPIENT of broken/stalled/missing-
// default-job reports (createReport() 'to'), so it follows the error-mail RECIPIENT, not
// the no-reply SENDER (that was the 3.20.4 fix, admintweaks#57).
$qjobs_email_to = Environment::getEnv('APP_LOG_MAIL_RECIPIENT');
$qjobs_email_config = Email::config()->get('queued_job_admin_email');
// Fill from env only when unset. `false` stays untouched (the opt-out: no queued-job mail);
// any explicit address stays untouched (the project's deliberate choice wins).
$qjobs_email_unset = ($qjobs_email_config === null || $qjobs_email_config === '');
if ($qjobs_email_to && $qjobs_email_unset) {
    Email::config()->set('queued_job_admin_email', $qjobs_email_to);
}
