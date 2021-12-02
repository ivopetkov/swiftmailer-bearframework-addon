<?php

/*
 * Swift Mailer addon for Bear Framework
 * https://github.com/ivopetkov/swiftmailer-bearframework-addon
 * Copyright (c) Ivo Petkov
 * Free to use under the MIT license.
 */

/**
 * @runTestsInSeparateProcesses
 */
class SwiftMailerTest extends BearFramework\AddonTests\PHPUnitTestCase
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
        $tempDir = $this->getTempDir();

        $email = $app->emails->make();
        $email->subject = 'Hi';
        $email->sender->email = 'john@example.com';
        $email->sender->name = 'John';
        $email->recipients->add('mark@example.com', 'Mark');
        $email->replyToRecipients->add('bill@example.com', 'Mark');
        $email->ccRecipients->add('tom@example.com', 'Mark');
        $email->bccRecipients->add('sam@example.com', 'Mark');
        $email->content->add('<strong>Hi</strong>', 'text/html');
        $email->content->add('Hi there', 'text/plain');
        $email->date = 1531392986;
        $email->returnPath = 'return-path@example.com';
        $email->priority = 1;
        $email->headers->add('X-Custom-1', 'value1');
        $email->headers->add('X-Custom-2', 'value2');
        $this->makeFile($tempDir . '/file1.png', 'content1');
        $email->attachments->addFile($tempDir . '/file1.png', 'file1.png', 'image/png');
        $email->attachments->addContent('content2', 'file2.png', 'image/png');
        $this->makeFile($tempDir . '/fileA.jpg', 'contentA');
        $email->embeds->addFile('fileA@exaple.com', $tempDir . '/fileA.jpg', 'fileA.jpg', 'image/jpg');
        $email->embeds->addContent('fileB@exaple.com', 'contentB', 'fileB.jpg', 'image/jpg');

        $message = $app->swiftMailer->emailToSwiftMessage($email);
        $raw = $message->toString();
        $raw = preg_replace('/Message\-ID\: \<(.*)\.john\@example\.com\>/', 'Message-ID: <xxx111.john@example.com>', $raw);
        $raw = preg_replace('/Date\: (.*)/', 'Date: xxx222', $raw);
        $raw = preg_replace('/boundary\-([a-z0-9=]*)/', 'boundary-xxx333', $raw);
        $raw = preg_replace('/\_\=\_swift\_(.*?)\_\=\_/', 'boundary-xxx444', $raw);
        $raw = trim($raw);
        $raw = preg_replace('~\r\n?~', "\n", $raw);

        $expected = 'Return-Path: <return-path@example.com>
Message-ID: <xxx111.john@example.com>
Date: xxx222
Subject: Hi
From: John <john@example.com>
Reply-To: Mark <bill@example.com>
To: Mark <mark@example.com>
Cc: Mark <tom@example.com>
Bcc: Mark <sam@example.com>
MIME-Version: 1.0
Content-Type: multipart/mixed;
 boundary=boundary-xxx333
X-Priority: 1 (Highest)
X-Custom-2: value2
X-Custom-1: value1


--boundary-xxx333
Content-Type: multipart/alternative;
 boundary="boundary-xxx444"


--boundary-xxx444
Content-Type: text/plain; charset=utf-8
Content-Transfer-Encoding: quoted-printable

Hi there

--boundary-xxx444
Content-Type: text/html; charset=utf-8
Content-Transfer-Encoding: quoted-printable

<strong>Hi</strong>

--boundary-xxx444--


--boundary-xxx333
Content-Type: image/png; name=file1.png
Content-Transfer-Encoding: base64
Content-Disposition: attachment; filename=file1.png

Y29udGVudDE=

--boundary-xxx333
Content-Type: image/png; name=file2.png
Content-Transfer-Encoding: base64
Content-Disposition: attachment; filename=file2.png

Y29udGVudDI=

--boundary-xxx333
Content-Type: image/jpg; name=fileA.jpg
Content-Transfer-Encoding: base64
Content-ID: <fileA@exaple.com>
Content-Disposition: inline; filename=fileA.jpg

Y29udGVudEE=

--boundary-xxx333
Content-Type: image/jpg; name=fileB.jpg
Content-Transfer-Encoding: base64
Content-ID: <fileB@exaple.com>
Content-Disposition: inline; filename=fileB.jpg

Y29udGVudEI=

--boundary-xxx333--';
        $expected = preg_replace('~\r\n?~', "\n", $expected);
        $this->assertTrue($raw === $expected);
    }
}
