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

class ContaoContext
{
    public const ERROR = 'ERROR';
    public const ACCESS = 'ACCESS';
    public const GENERAL = 'GENERAL';
    public const FILES = 'FILES';
    public const CRON = 'CRON';
    public const FORMS = 'FORMS';
    public const EMAIL = 'EMAIL';
    public const CONFIGURATION = 'CONFIGURATION';
    public const NEWSLETTER = 'NEWSLETTER';
    public const REPOSITORY = 'REPOSITORY';

    private string $func;
    private ?string $action;
    private ?string $username;
    private ?string $ip;
    private ?string $browser;
    private ?string $source;

    public function __construct(string $func, string $action = null, string $username = null, string $ip = null, string $browser = null, string $source = null)
    {
        if ('' === $func) {
            throw new \InvalidArgumentException('The function name in the Contao context must not be empty');
        }

        $this->func = $func;
        $this->action = $action;
        $this->username = $username;
        $this->ip = $ip;
        $this->browser = $browser;
        $this->source = $source;
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
