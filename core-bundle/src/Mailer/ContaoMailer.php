<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao\CoreBundle\Mailer;

use Contao\PageModel;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Mailer\Envelope;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Email;
use Symfony\Component\Mime\Message;
use Symfony\Component\Mime\RawMessage;

final class ContaoMailer implements MailerInterface
{
    public function __construct(
        private readonly MailerInterface $mailer,
        private readonly AvailableTransports $transports,
        private readonly RequestStack $requestStack,
    ) {
    }

    public function send(RawMessage $message, Envelope|null $envelope = null): void
    {
        if ($message instanceof Message) {
            $this->setTransport($message);
        }

        if ($message instanceof Email) {
            $this->setFrom($message);
        }

        $this->mailer->send($message, $envelope);
    }

    /**
     * Sets the transport defined in the website root.
     */
    private function setTransport(Message $message): void
    {
        if ($message->getHeaders()->has('X-Transport')) {
            return;
        }

        $request = $this->requestStack->getCurrentRequest();

        if (!$request) {
            return;
        }

        $attributes = $request->attributes;

        if (!$attributes->has('pageModel')) {
            return;
        }

        $page = $attributes->get('pageModel');

        if (!$page instanceof PageModel) {
            return;
        }

        $page->loadDetails();

        if (empty($page->mailerTransport) || !$this->transports->getTransport($page->mailerTransport)) {
            return;
        }

        $message->getHeaders()->addTextHeader('X-Transport', $page->mailerTransport);
    }

    /**
     * Overrides the from address according to the transport.
     */
    private function setFrom(Email $message): void
    {
        if (!$message->getHeaders()->has('X-Transport')) {
            return;
        }

        $transportName = $message->getHeaders()->get('X-Transport')->getBodyAsString();
        $transport = $this->transports->getTransport($transportName);

        if (!$transport) {
            return;
        }

        $from = $transport->getFrom();

        if (null === $from) {
            return;
        }

        $message->from($from);

        // Also override "Return-Path" and "Sender" if set (see #4712)
        if ($message->getReturnPath()) {
            $message->returnPath($from);
        }

        if ($message->getSender()) {
            $message->sender($from);
        }
    }
}
