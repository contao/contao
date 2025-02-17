<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao\CoreBundle\EventListener;

use Psr\Log\LoggerInterface;
use Symfony\Component\EventDispatcher\Attribute\AsEventListener;
use Symfony\Component\Mailer\Event\FailedMessageEvent;
use Symfony\Component\Mailer\Event\SentMessageEvent;
use Symfony\Component\Mime\Address;
use Symfony\Component\Mime\Email;

class LogEmailMessageListener
{
    public function __construct(
        private readonly LoggerInterface $contaoEmailLogger,
        private readonly LoggerInterface $contaoErrorLogger,
    ) {
    }

    #[AsEventListener]
    public function onSentMessageEvent(SentMessageEvent $event): void
    {
        $email = $event->getMessage()->getOriginalMessage();

        if (!$email instanceof Email) {
            return;
        }

        $message = 'An e-mail has been sent to '.$this->getRecipients($email);

        $this->contaoEmailLogger->info($message);
    }

    #[AsEventListener]
    public function onFailedMessagEvent(FailedMessageEvent $event): void
    {
        $email = $event->getMessage();

        if (!$email instanceof Email) {
            return;
        }

        $message = 'Failed to send e-mail to '.$this->getRecipients($email);

        $this->contaoErrorLogger->error($message);
    }

    private function getRecipients(Email $email): string
    {
        $cb = static fn (Address $address): string => $address->toString();

        $recipients = implode(', ', array_map($cb, $email->getTo()));

        if ($cc = $email->getCc()) {
            $recipients .= ', CC to '.implode(', ', array_map($cb, $cc));
        }

        if ($bcc = $email->getBcc()) {
            $recipients .= ', BCC to '.implode(', ', array_map($cb, $bcc));
        }

        return $recipients;
    }
}
