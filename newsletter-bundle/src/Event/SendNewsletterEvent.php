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
    private string $recipientAddress;
    private string $text;
    private string $html;
    private bool $preventSubmission = false;
    private bool $allowHtml = true;
    private array $recipientData = [];
    private array $newsletterData = [];

    public function __construct(string $recipientAddress, string $text, string $html = '')
    {
        $this->recipientAddress = $recipientAddress;
        $this->text = $text;
        $this->html = $html;
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

    public function preventsSubmission(): bool
    {
        return $this->preventSubmission;
    }

    public function setPreventSubmission(bool $preventSubmission): void
    {
        $this->preventSubmission = $preventSubmission;
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

    public function allowsHtml(): bool
    {
        return $this->allowHtml;
    }

    public function setAllowHtml(bool $allowHtml): self
    {
        $this->allowHtml = $allowHtml;

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

    public function getRecipientValue(string $key)
    {
        return $this->recipientData[$key] ?? null;
    }

    public function setRecipientValue(string $key, $value): self
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

    public function getNewsletterValue(string $key)
    {
        return $this->newsletterData[$key] ?? null;
    }

    public function setNewsletterValue(string $key, $value): self
    {
        $this->newsletterData[$key] = $value;

        return $this;
    }
}
