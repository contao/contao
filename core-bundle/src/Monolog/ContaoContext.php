<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao\CoreBundle\Monolog;

class ContaoContext implements \Stringable
{
    final public const ERROR = 'ERROR';
    final public const ACCESS = 'ACCESS';
    final public const GENERAL = 'GENERAL';
    final public const FILES = 'FILES';
    final public const CRON = 'CRON';
    final public const FORMS = 'FORMS';
    final public const EMAIL = 'EMAIL';
    final public const CONFIGURATION = 'CONFIGURATION';
    final public const NEWSLETTER = 'NEWSLETTER';
    final public const REPOSITORY = 'REPOSITORY';

    public function __construct(
        private string $func,
        private ?string $action = null,
        private ?string $username = null,
        private ?string $ip = null,
        private ?string $browser = null,
        private ?string $source = null
    ) {
        if ('' === $func) {
            throw new \InvalidArgumentException('The function name in the Contao context must not be empty');
        }
    }

    /**
     * Returns a JSON representation of the object.
     */
    public function __toString(): string
    {
        return (string) json_encode([
            'func' => $this->func,
            'action' => $this->action,
            'username' => $this->username,
            'browser' => $this->browser,
        ]);
    }

    public function getFunc(): string
    {
        return $this->func;
    }

    public function getAction(): ?string
    {
        return $this->action;
    }

    public function setAction(string $action): void
    {
        $this->action = $action;
    }

    public function getUsername(): ?string
    {
        return $this->username;
    }

    public function setUsername(string $username): void
    {
        $this->username = $username;
    }

    public function getIp(): ?string
    {
        return $this->ip;
    }

    public function setIp(?string $ip): void
    {
        $this->ip = (string) $ip;
    }

    public function getBrowser(): ?string
    {
        return $this->browser;
    }

    public function setBrowser(string $browser): void
    {
        $this->browser = $browser;
    }

    public function getSource(): ?string
    {
        return $this->source;
    }

    public function setSource(string $source): void
    {
        $this->source = $source;
    }
}
