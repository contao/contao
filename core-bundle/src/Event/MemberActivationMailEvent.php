<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao\CoreBundle\Event;

use Contao\CoreBundle\OptIn\OptInToken;
use Contao\MemberModel;
use Symfony\Contracts\EventDispatcher\Event;

class MemberActivationMailEvent extends Event
{
    private bool $sendOptInToken = true;

    public function __construct(
        private MemberModel $member,
        private OptInToken $optInToken,
        private string $subject,
        private string $text,
        private array $simpleTokens,
    ) {
    }

    public function getMember(): MemberModel
    {
        return $this->member;
    }

    public function getOptInToken(): OptInToken
    {
        return $this->optInToken;
    }

    public function setSubject(string $subject): self
    {
        $this->subject = $subject;

        return $this;
    }

    public function getSubject(): string
    {
        return $this->subject;
    }

    public function setText(string $text): self
    {
        $this->text = $text;

        return $this;
    }

    public function getText(): string
    {
        return $this->text;
    }

    public function setSimpleTokens(array $simpleTokens): self
    {
        $this->simpleTokens = $simpleTokens;

        return $this;
    }

    public function addSimpleToken(string $token, string $value): self
    {
        $this->simpleTokens[$token] = $value;

        return $this;
    }

    public function removeSimpleToken(string $token): self
    {
        unset($this->simpleTokens[$token]);

        return $this;
    }

    public function getSimpleTokens(): array
    {
        return $this->simpleTokens;
    }

    public function disableSendingTheOptInToken(): self
    {
        $this->sendOptInToken = false;

        return $this;
    }

    public function enableSendingTheOptInToken(): self
    {
        $this->sendOptInToken = true;

        return $this;
    }

    public function shouldSendOptInToken(): bool
    {
        return $this->sendOptInToken;
    }
}
