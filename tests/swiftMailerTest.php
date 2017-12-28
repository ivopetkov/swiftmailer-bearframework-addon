<?php

/*
 * Swift Mailer addon for Bear Framework
 * https://github.com/ivopetkov/swiftmailer-bearframework-addon
 * Copyright (c) 2017 Ivo Petkov
 * Free to use under the MIT license.
 */

/**
 * @runTestsInSeparateProcesses
 */
class SwiftMailerTest extends BearFrameworkAddonTestCase
{

    /**
     * 
     */
    public function testSend()
    {
        $app = $this->getApp();

        $transport = new \Swift_SmtpTransport('', '');

        $email = $app->emails->make();
        $email->subject = 'Hi';
        $email->sender->email = 'john@example.com';
        $email->sender->name = 'John';
        $email->recipients->add('mark@example.com', 'Mark');
        $email->content->add('Hi there', 'text/plain');

        // Cannot connect error is expected.
        try {
            $app->swiftMailer->send($transport, $email);
        } catch (\Exception $e) {
            $this->assertTrue(strpos($e->getMessage(), 'Connection could not be established with host') !== false);
            return;
        }
        $this->assertFalse(true); // Should not come here.
    }

    /**
     * 
     */
    public function testEmailToSwiftMessage()
    {
        $app = $this->getApp();

        $email = $app->emails->make();
        $email->subject = 'Hi';
        $email->sender->email = 'john@example.com';
        $email->sender->name = 'John';
        $email->recipients->add('mark@example.com', 'Mark');
        $email->content->add('<strong>Hi</strong>', 'text/html');
        $email->content->add('Hi there', 'text/plain');

        $message = $app->swiftMailer->emailToSwiftMessage($email);
        $raw = $message->toString();
        $raw = preg_replace('/Message\-ID\: \<(.*)\.john\@example\.com\>/', 'Message-ID: <xxx111.john@example.com>', $raw);
        $raw = preg_replace('/Date\: (.*)/', 'Date: xxx222', $raw);
        $raw = preg_replace('/boundary\-([a-z0-9]*)/', 'boundary-xxx333', $raw);
        $raw = trim($raw);
        $raw = preg_replace('~\r\n?~', "\n", $raw);

        $expected = 'Message-ID: <xxx111.john@example.com>
Date: xxx222
Subject: Hi
From: John <john@example.com>
To: Mark <mark@example.com>
MIME-Version: 1.0
Content-Type: multipart/alternative;
 boundary=boundary-xxx333


--boundary-xxx333
Content-Type: text/plain; charset=utf-8
Content-Transfer-Encoding: quoted-printable

Hi there

--boundary-xxx333
Content-Type: text/html; charset=utf-8
Content-Transfer-Encoding: quoted-printable

<strong>Hi</strong>

--boundary-xxx333--';
        $expected = preg_replace('~\r\n?~', "\n", $expected);
        $this->assertTrue($raw === $expected);
    }

}
