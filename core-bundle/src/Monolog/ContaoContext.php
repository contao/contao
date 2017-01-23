<?php

/*
 * This file is part of Contao.
 *
 * Copyright (c) 2005-2016 Leo Feyer
 *
 * @license LGPL-3.0+
 */

namespace Contao\CoreBundle\Monolog;

/**
 * Contao-specific logger context.
 *
 * @author Andreas Schempp <https://github.com/aschempp>
 */
class ContaoContext
{
    const ERROR = 'ERROR';
    const ACCESS = 'ACCESS';
    const GENERAL = 'GENERAL';
    const FILES = 'FILES';
    const CRON = 'CRON';
    const FORMS = 'FORMS';
    const EMAIL = 'EMAIL';
    const CONFIGURATION = 'CONFIGURATION';
    const NEWSLETTER = 'NEWSLETTER';
    const REPOSITORY = 'REPOSITORY';

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
     * Constructor.
     *
     * @param string      $func
     * @param string|null $action
     * @param string|null $username
     * @param string|null $ip
     * @param string|null $browser
     * @param string|null $source
     */
    public function __construct($func, $action = null, $username = null, $ip = null, $browser = null, $source = null)
    {
        if ('' === (string) $func) {
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
     * Returns the function name.
     *
     * @return string
     */
    public function getFunc()
    {
        return $this->func;
    }

    /**
     * Returns the action.
     *
     * @return string|null
     */
    public function getAction()
    {
        return $this->action;
    }

    /**
     * Sets the action.
     *
     * @param string $action
     */
    public function setAction($action)
    {
        $this->action = (string) $action;
    }

    /**
     * Returns the username.
     *
     * @return string|null
     */
    public function getUsername()
    {
        return $this->username;
    }

    /**
     * Sets the username.
     *
     * @param string $username
     */
    public function setUsername($username)
    {
        $this->username = (string) $username;
    }

    /**
     * Returns the IP address.
     *
     * @return string|null
     */
    public function getIp()
    {
        return $this->ip;
    }

    /**
     * Sets the IP address.
     *
     * @param string|null $ip
     */
    public function setIp($ip)
    {
        $this->ip = (string) $ip;
    }

    /**
     * Returns the browser.
     *
     * @return string|null
     */
    public function getBrowser()
    {
        return $this->browser;
    }

    /**
     * Sets the browser.
     *
     * @param string $browser
     */
    public function setBrowser($browser)
    {
        $this->browser = (string) $browser;
    }

    /**
     * Returns the source.
     *
     * @return string|null
     */
    public function getSource()
    {
        return $this->source;
    }

    /**
     * Sets the source.
     *
     * @param string $source
     */
    public function setSource($source)
    {
        $this->source = (string) $source;
    }

    /**
     * Returns a JSON representation of the object.
     *
     * @return string
     */
    public function __toString()
    {
        return json_encode([
            'func' => $this->func,
            'action' => $this->action,
            'username' => $this->username,
            'ip' => $this->ip,
            'browser' => $this->browser,
        ]);
    }
}
