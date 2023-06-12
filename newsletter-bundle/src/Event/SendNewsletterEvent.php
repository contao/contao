<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao\NewsletterBundle\Event;

use Symfony\Contracts\EventDispatcher\Event;

class SendNewsletterEvent extends Event
{
    private bool $skipSending = false;
    private bool $htmlAllowed = true;
    private array $recipientData = [];
    private array $newsletterData = [];

    public function __construct(
        private string $recipientAddress,
        private string $text,
        private string $html = '',
    ) {
    }

    public function getRecipientAddress(): string
    {
        return $this->recipientAddress;
    }

    public function setRecipientAddress(string $recipientAddress): self
    {
        $this->recipientAddress = $recipientAddress;

        return $this;
    }

    public function isSkipSending(): bool
    {
        return $this->skipSending;
    }

    public function setSkipSending(bool $skipSending): void
    {
        $this->skipSending = $skipSending;
    }

    public function getText(): string
    {
        return $this->text ?? '';
    }

    public function setText(string $text): self
    {
        $this->text = $text;

        return $this;
    }

    public function getHtml(): string
    {
        return $this->html ?? '';
    }

    public function setHtml(string $html): self
    {
        $this->html = $html;

        return $this;
    }

    public function isHtmlAllowed(): bool
    {
        return $this->htmlAllowed;
    }

    public function setHtmlAllowed(bool $htmlAllowed): self
    {
        $this->htmlAllowed = $htmlAllowed;

        return $this;
    }

    public function getRecipientData(): array
    {
        return $this->recipientData;
    }

    public function setRecipientData(array $data): self
    {
        $this->recipientData = $data;

        return $this;
    }

    public function getRecipientValue(string $key): mixed
    {
        return $this->recipientData[$key] ?? null;
    }

    public function setRecipientValue(string $key, mixed $value): self
    {
        $this->recipientData[$key] = $value;

        return $this;
    }

    public function getNewsletterData(): array
    {
        return $this->newsletterData;
    }

    public function setNewsletterData(array $data): self
    {
        $this->newsletterData = $data;

        return $this;
    }

    public function getNewsletterValue(string $key): mixed
    {
        return $this->newsletterData[$key] ?? null;
    }

    public function setNewsletterValue(string $key, mixed $value): self
    {
        $this->newsletterData[$key] = $value;

        return $this;
    }
}
