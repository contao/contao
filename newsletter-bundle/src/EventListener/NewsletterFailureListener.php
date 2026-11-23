<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao\NewsletterBundle\EventListener;

use Contao\NewsletterBundle\Mailer\NewsletterFailureManager;
use Symfony\Component\EventDispatcher\Attribute\AsEventListener;
use Symfony\Component\Mailer\Event\FailedMessageEvent;
use Symfony\Component\Mailer\Event\SentMessageEvent;
use Symfony\Component\Mime\Email;

class NewsletterFailureListener
{
    public function __construct(private readonly NewsletterFailureManager $failureManager)
    {
    }

    #[AsEventListener]
    public function onFailedMessageEvent(FailedMessageEvent $event): void
    {
        $email = $event->getMessage();

        if (!$email instanceof Email || !$header = $email->getHeaders()->get('Contao-Newsletter-ID')) {
            return;
        }

        if (!$newsletterId = (int) $header->getBodyAsString()) {
            return;
        }

        $error = $event->getError();

        foreach ($email->getTo() as $recipient) {
            $this->failureManager->recordFailure($newsletterId, $recipient->getAddress(), $error);
        }
    }

    #[AsEventListener]
    public function onSentMessageEvent(SentMessageEvent $event): void
    {
        $email = $event->getMessage()->getOriginalMessage();

        if (!$email instanceof Email || !$header = $email->getHeaders()->get('Contao-Newsletter-ID')) {
            return;
        }

        if (!$newsletterId = (int) $header->getBodyAsString()) {
            return;
        }

        foreach ($email->getTo() as $recipient) {
            $this->failureManager->clearFailure($newsletterId, $recipient->getAddress());
        }
    }
}
