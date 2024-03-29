<?php

/*
 * Swift Mailer addon for Bear Framework
 * https://github.com/ivopetkov/swiftmailer-bearframework-addon
 * Copyright (c) Ivo Petkov
 * Free to use under the MIT license.
 */

use BearFramework\App;

$app = App::get();
$context = $app->contexts->get(__DIR__);

$context->classes->add('IvoPetkov\BearFrameworkAddons\SwiftMailer', 'classes/SwiftMailer.php');

require_once __DIR__ . '/lib/swiftmailer/lib/swift_required.php';

$app->shortcuts->add('swiftMailer', function () {
    return new \IvoPetkov\BearFrameworkAddons\SwiftMailer();
});
