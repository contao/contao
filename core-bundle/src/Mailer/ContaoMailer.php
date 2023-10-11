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
    private MailerInterface $mailer;
    private AvailableTransports $transports;
    private RequestStack $requestStack;

    public function __construct(MailerInterface $mailer, AvailableTransports $transports, RequestStack $requestStack)
    {
        $this->mailer = $mailer;
        $this->transports = $transports;
        $this->requestStack = $requestStack;
    }

    public function send(RawMessage $message, Envelope $envelope = null): void
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

        if (null === $request) {
            return;
        }

        $attributes = $this->requestStack->getCurrentRequest()->attributes;

        if (!$attributes->has('pageModel')) {
            return;
        }

        $page = $attributes->get('pageModel');

        if (!$page instanceof PageModel) {
            return;
        }

        $page->loadDetails();

        if (empty($page->mailerTransport) || null === $this->transports->getTransport($page->mailerTransport)) {
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

        if (null === $transport) {
            return;
        }

        $from = $transport->getFrom();

        if (null === $from) {
            return;
        }

        $message->from($from);

        // Also override "Return-Path" and "Sender" if set (see #4712)
        if (null !== $message->getReturnPath()) {
            $message->returnPath($from);
        }

        if (null !== $message->getSender()) {
            $message->sender($from);
        }
    }
}
