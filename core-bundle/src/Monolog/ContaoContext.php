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
        private readonly string $func,
        private string|null $action = null,
        private string|null $username = null,
        private string|null $ip = null,
        private string|null $browser = null,
        private string|null $source = null,
        private string|null $uri = null,
        private int|null $pageId = null,
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
        return (string) json_encode(
            [
                'func' => $this->func,
                'action' => $this->action,
                'username' => $this->username,
                'browser' => $this->browser,
                'uri' => $this->uri,
                'pageId' => $this->pageId,
            ],
            JSON_THROW_ON_ERROR
        );
    }

    public function getFunc(): string
    {
        return $this->func;
    }

    public function getAction(): string|null
    {
        return $this->action;
    }

    public function setAction(string $action): void
    {
        $this->action = $action;
    }

    public function getUsername(): string|null
    {
        return $this->username;
    }

    public function setUsername(string $username): void
    {
        $this->username = $username;
    }

    public function getIp(): string|null
    {
        return $this->ip;
    }

    public function setIp(string|null $ip): void
    {
        $this->ip = (string) $ip;
    }

    public function getBrowser(): string|null
    {
        return $this->browser;
    }

    public function setBrowser(string $browser): void
    {
        $this->browser = $browser;
    }

    public function getSource(): string|null
    {
        return $this->source;
    }

    public function setSource(string $source): void
    {
        $this->source = $source;
    }

    public function getUri(): string|null
    {
        return $this->uri;
    }

    public function setUri(string $uri): void
    {
        $this->uri = $uri;
    }

    public function getPageId(): int|null
    {
        return $this->pageId;
    }

    public function setPageId(int|null $pageId): void
    {
        $this->pageId = $pageId;
    }
}
