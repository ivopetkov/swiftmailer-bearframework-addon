<?php

/*
 * Swift Mailer addon for Bear Framework
 * https://github.com/ivopetkov/swiftmailer-bearframework-addon
 * Copyright (c) Ivo Petkov
 * Free to use under the MIT license.
 */

namespace IvoPetkov\BearFrameworkAddons;

/**
 * Swift Mailer.
 */
class SwiftMailer
{

    /**
     * 
     * @param \Swift_Transport $transport
     * @param \BearFramework\Emails\Email $email
     * @return bool
     */
    public function send(\Swift_Transport $transport, \BearFramework\Emails\Email $email): bool
    {
        $mailer = new \Swift_Mailer($transport);
        $message = $this->emailToSwiftMessage($email);
        return $mailer->send($message) > 0;
    }

    /**
     * 
     * @param \BearFramework\Emails\Email $email
     * @return \Swift_Message
     */
    public function emailToSwiftMessage(\BearFramework\Emails\Email $email): \Swift_Message
    {
        $message = new \Swift_Message();
        $message->setId(microtime(true) * 10000 . '.' . $email->sender->email);
        $message->setBoundary('boundary-' . md5(uniqid()));

        $headers = $email->headers->getList();
        if ($headers->count() > 0) {
            $messageHeaders = $message->getHeaders();
            foreach ($headers as $header) {
                $messageHeaders->addTextHeader($header->name, $header->value);
            }
        }

        if ($email->sender->name !== null) {
            $message->setFrom([$email->sender->email => $email->sender->name]);
        } else {
            $message->setFrom($email->sender->email);
        }

        if ($email->date !== null) {
            $dateTime = new \DateTime();
            $dateTime->setTimestamp($email->date);
            $message->setDate($dateTime);
        }

        $replyToRecipients = $email->replyToRecipients->getList();
        foreach ($replyToRecipients as $replyToRecipient) {
            $message->addReplyTo($replyToRecipient->email, $replyToRecipient->name);
        }

        $recipients = $email->recipients->getList();
        foreach ($recipients as $recipient) {
            $message->addTo($recipient->email, $recipient->name);
        }

        $ccRecipients = $email->ccRecipients->getList();
        foreach ($ccRecipients as $ccRecipient) {
            $message->addCc($ccRecipient->email, $ccRecipient->name);
        }

        $bccRecipients = $email->bccRecipients->getList();
        foreach ($bccRecipients as $bccRecipient) {
            $message->addBcc($bccRecipient->email, $bccRecipient->name);
        }

        if ($email->subject !== null) {
            $message->setSubject($email->subject);
        }

        $contentParts = $email->content->getList();

        foreach ($contentParts as $contentPart) {
            $message->attach(new \Swift_MimePart($contentPart->content, $contentPart->mimeType, $contentPart->encoding));
        }

        if ($email->returnPath !== null) {
            $message->setReturnPath($email->returnPath);
        }

        if ($email->priority !== null) {
            $message->setPriority($email->priority);
        }

        $attachments = $email->attachments->getList();
        foreach ($attachments as $attachment) {
            if ($attachment instanceof \BearFramework\Emails\Email\FileAttachment) {
                if ($attachment->filename !== null) {
                    $messageAttachment = \Swift_Attachment::fromPath($attachment->filename);
                    if ($attachment->mimeType !== null) {
                        $messageAttachment->setContentType($attachment->mimeType);
                    }
                    if ($attachment->name !== null) {
                        $messageAttachment->setFilename($attachment->name);
                    }
                    $message->attach($messageAttachment);
                }
            } elseif ($attachment instanceof \BearFramework\Emails\Email\ContentAttachment) {
                if ($attachment->content !== null) {
                    $messageAttachment = new \Swift_Attachment();
                    $messageAttachment->setBody($attachment->content);
                    if ($attachment->mimeType !== null) {
                        $messageAttachment->setContentType($attachment->mimeType);
                    }
                    if ($attachment->name !== null) {
                        $messageAttachment->setFilename($attachment->name);
                    }
                    $message->attach($messageAttachment);
                }
            }
        }

        $embeds = $email->embeds->getList();
        foreach ($embeds as $embed) {
            if ($embed instanceof \BearFramework\Emails\Email\FileEmbed) {
                if ($embed->filename !== null) {
                    $messageAttachment = \Swift_Attachment::fromPath($embed->filename);
                    if ($embed->mimeType !== null) {
                        $messageAttachment->setContentType($embed->mimeType);
                    }
                    if ($embed->name !== null) {
                        $messageAttachment->setFilename($embed->name);
                    }
                    if ($embed->cid !== null) {
                        $messageAttachment->setId($embed->cid);
                    } else {
                        $messageAttachment->setId(md5($embed->filename) . '.' . $email->sender->email);
                    }
                    $messageAttachment->setDisposition('inline');
                    $message->attach($messageAttachment);
                }
            } elseif ($embed instanceof \BearFramework\Emails\Email\ContentEmbed) {
                if ($embed->content !== null) {
                    $messageAttachment = new \Swift_Attachment();
                    $messageAttachment->setBody($embed->content);
                    if ($embed->mimeType !== null) {
                        $messageAttachment->setContentType($embed->mimeType);
                    }
                    if ($embed->name !== null) {
                        $messageAttachment->setFilename($embed->name);
                    }
                    if ($embed->cid !== null) {
                        $messageAttachment->setId($embed->cid);
                    } else {
                        $messageAttachment->setId(md5($embed->content) . '.' . $email->sender->email);
                    }
                    $messageAttachment->setDisposition('inline');
                    $message->attach($messageAttachment);
                }
            }
        }

        $signers = $email->signers->getList();
        foreach ($signers as $signer) {
            if ($signer instanceof \BearFramework\Emails\Email\DKIMSigner) {
                $message->attachSigner(new \Swift_Signers_DKIMSigner($signer->privateKey, $signer->domain, $signer->selector));
            } elseif ($signer instanceof \BearFramework\Emails\Email\SMIMESigner) {
                $certificateTempFile = tmpfile();
                fwrite($certificateTempFile, $signer->certificate);
                $certificateTempFileMetaData = stream_get_meta_data($certificateTempFile);
                $privateKeyTempFile = tmpfile();
                fwrite($privateKeyTempFile, $signer->privateKey);
                $privateKeyTempFileMetaData = stream_get_meta_data($privateKeyTempFile);
                $message->attachSigner(new \Swift_Signers_SMimeSigner($certificateTempFileMetaData['uri'], $privateKeyTempFileMetaData['uri']));
            }
        }
        return $message;
    }

}
