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

use Contao\Config;
use Contao\CoreBundle\Framework\ContaoFramework;
use Contao\PageModel;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Mailer\Envelope;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Email;
use Symfony\Component\Mime\Message;
use Symfony\Component\Mime\RawMessage;

final class ContaoMailer implements MailerInterface
{
    public const EMBED_IMAGES_HEADER = 'X-Contao-Embed-Images';

    public function __construct(
        private readonly MailerInterface $mailer,
        private readonly AvailableTransports $transports,
        private readonly RequestStack $requestStack,
        private readonly string|null $overrideFrom = null,
        private readonly ContaoFramework|null $framework = null,
        private readonly InlineImageEmbedder|null $inlineImageEmbedder = null,
    ) {
    }

    public function send(RawMessage $message, Envelope|null $envelope = null): void
    {
        if ($message instanceof Message) {
            $this->setTransport($message);
        }

        if ($message instanceof Email) {
            $this->setFrom($message);
            $this->embedImages($message);
        }

        $this->mailer->send($message, $envelope);
    }

    private function embedImages(Email $message): void
    {
        if (!$message->getHeaders()->has(self::EMBED_IMAGES_HEADER)) {
            return;
        }

        $message->getHeaders()->remove(self::EMBED_IMAGES_HEADER);

        if (!$this->inlineImageEmbedder) {
            return;
        }

        $request = $this->requestStack->getCurrentRequest();
        $baseUrl = $request ? $request->getSchemeAndHttpHost().$request->getBasePath().'/' : '';

        $this->inlineImageEmbedder->embedImages($message, $baseUrl);
    }

    /**
     * Sets the transport defined in the website root.
     */
    private function setTransport(Message $message): void
    {
        if ($message->getHeaders()->has('X-Transport')) {
            return;
        }

        $page = $this->getCurrentPage();

        if (!$page) {
            return;
        }

        if (empty($page->mailerTransport) || !$this->transports->getTransport($page->mailerTransport)) {
            return;
        }

        $message->getHeaders()->addTextHeader('X-Transport', $page->mailerTransport);
    }

    /**
     * Overrides the from address according to config.
     */
    private function setFrom(Email $message): void
    {
        if (null !== $this->overrideFrom) {
            $this->doSetFrom($message, $this->overrideFrom);
        }

        if (null !== $from = $this->getTransportFrom($message)) {
            $this->doSetFrom($message, $from);
        }

        if (!$message->getFrom() && !$message->getSender()) {
            $this->setDefaultFrom($message);
        }
    }

    private function getTransportFrom(Email $message): string|null
    {
        if (!$message->getHeaders()->has('X-Transport')) {
            return null;
        }

        $transportName = $message->getHeaders()->get('X-Transport')->getBodyAsString();

        if (!$transport = $this->transports->getTransport($transportName)) {
            return null;
        }

        return $transport->getFrom();
    }

    private function doSetFrom(Email $message, string $from): void
    {
        $message->from($from);

        // Also override "Return-Path" and "Sender" if set (see #4712)
        if ($message->getReturnPath()) {
            $message->returnPath($from);
        }

        if ($message->getSender()) {
            $message->sender($from);
        }
    }

    private function getCurrentPage(): PageModel|null
    {
        if (!$request = $this->requestStack->getCurrentRequest()) {
            return null;
        }

        $attributes = $request->attributes;

        if (!$attributes->has('pageModel')) {
            return null;
        }

        $page = $attributes->get('pageModel');

        if (!$page instanceof PageModel) {
            return null;
        }

        $page->loadDetails();

        return $page;
    }

    private function setDefaultFrom(Email $message): void
    {
        $page = $this->getCurrentPage();

        if ($page && $page->adminEmail) {
            $this->doSetFrom($message, $page->adminEmail);

            return;
        }

        if (!$this->framework) {
            return;
        }

        $this->framework->initialize();

        // Do not throw if there is no admin e-mail address, so the From header can still
        // be set elsewhere (e.g. via "framework.mailer.headers")
        if ($adminEmail = $this->framework->getAdapter(Config::class)->get('adminEmail')) {
            $this->doSetFrom($message, $adminEmail);
        }
    }
}
