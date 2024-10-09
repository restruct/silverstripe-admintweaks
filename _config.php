<?php

use SilverStripe\Core\Environment;

//
// Set system email sender via ENV (moved to _config.php because BACKTICKS ENV VARS in Yaml are only supported via Injector)
//
use SilverStripe\Core\Environment;

$sys_email = Environment::getEnv('APP_SYSTEM_EMAIL_ADDRESS');
$sys_name = Environment::getEnv('APP_SYSTEM_EMAIL_SENDER');
if ($sys_email && $sys_name) {
    SilverStripe\Control\Email\Email::config()->set('admin_email', [$sys_email => $sys_name]);
}
