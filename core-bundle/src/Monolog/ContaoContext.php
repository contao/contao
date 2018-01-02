<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * Copyright (c) 2005-2017 Leo Feyer
 *
 * @license LGPL-3.0+
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

    /**
     * @var string
     */
    private $func;

    /**
     * @var string|null
     */
    private $action;

    /**
     * @var string|null
     */
    private $username;

    /**
     * @var string|null
     */
    private $ip;

    /**
     * @var string|null
     */
    private $browser;

    /**
     * @var string|null
     */
    private $source;

    /**
     * @param string      $func
     * @param string|null $action
     * @param string|null $username
     * @param string|null $ip
     * @param string|null $browser
     * @param string|null $source
     *
     * @throws \InvalidArgumentException
     */
    public function __construct(string $func, string $action = null, $username = null, $ip = null, $browser = null, $source = null)
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
     *
     * @return string
     */
    public function __toString(): string
    {
        return json_encode([
            'func' => $this->func,
            'action' => $this->action,
            'username' => $this->username,
            'ip' => $this->ip,
            'browser' => $this->browser,
        ]);
    }

    /**
     * Returns the function name.
     *
     * @return string
     */
    public function getFunc(): string
    {
        return $this->func;
    }

    /**
     * Returns the action.
     *
     * @return string|null
     */
    public function getAction(): ?string
    {
        return $this->action;
    }

    /**
     * Sets the action.
     *
     * @param string $action
     */
    public function setAction($action): void
    {
        $this->action = (string) $action;
    }

    /**
     * Returns the username.
     *
     * @return string|null
     */
    public function getUsername(): ?string
    {
        return $this->username;
    }

    /**
     * Sets the username.
     *
     * @param string $username
     */
    public function setUsername(string $username): void
    {
        $this->username = $username;
    }

    /**
     * Returns the IP address.
     *
     * @return string|null
     */
    public function getIp(): ?string
    {
        return $this->ip;
    }

    /**
     * Sets the IP address.
     *
     * @param string|null $ip
     */
    public function setIp(?string $ip): void
    {
        $this->ip = (string) $ip;
    }

    /**
     * Returns the browser.
     *
     * @return string|null
     */
    public function getBrowser(): ?string
    {
        return $this->browser;
    }

    /**
     * Sets the browser.
     *
     * @param string $browser
     */
    public function setBrowser(string $browser): void
    {
        $this->browser = $browser;
    }

    /**
     * Returns the source.
     *
     * @return string|null
     */
    public function getSource(): ?string
    {
        return $this->source;
    }

    /**
     * Sets the source.
     *
     * @param string $source
     */
    public function setSource(string $source): void
    {
        $this->source = $source;
    }
}
