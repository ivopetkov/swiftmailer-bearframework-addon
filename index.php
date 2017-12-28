<?php

/*
 * Swift Mailer addon for Bear Framework
 * https://github.com/ivopetkov/swiftmailer-bearframework-addon
 * Copyright (c) 2017 Ivo Petkov
 * Free to use under the MIT license.
 */

use BearFramework\App;

$app = App::get();
$context = $app->context->get(__FILE__);

$context->classes->add('IvoPetkov\BearFrameworkAddons\SwiftMailer', 'classes/SwiftMailer.php');

$app->shortcuts->add('swiftMailer', function() {
    return new \IvoPetkov\BearFrameworkAddons\SwiftMailer();
});
