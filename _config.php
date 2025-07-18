<?php

use SilverStripe\Core\Environment;

//
// Set system email sender via ENV (moved to _config.php because BACKTICKS ENV VARS in Yaml are only supported via Injector)
//
$sys_email = Environment::getEnv('APP_SYSTEM_EMAIL_ADDRESS');
$sys_name = Environment::getEnv('APP_SYSTEM_EMAIL_SENDER');
if ($sys_email && $sys_name) {
    SilverStripe\Control\Email\Email::config()->set('admin_email', [$sys_email => $sys_name]);
}

//
// Update queued_job_admin_email as well if defined in ENV (see note above on Injector)
//
$qjobs_email_from = Environment::getEnv('APP_LOG_MAIL_SENDER');
$qjobs_email_config = SilverStripe\Control\Email\Email::config()->get('queued_job_admin_email');
if ($qjobs_email_from && $qjobs_email_config !== false) {
    // if defined in env (and not explictly set to false on Email), update
    SilverStripe\Control\Email\Email::config()->set('queued_job_admin_email', $qjobs_email_from);
//    Config::modify()->set(Email::class, 'queued_job_admin_email', $qjobs_email_from);
}
