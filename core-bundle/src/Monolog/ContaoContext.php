<?php

/*
 * This file is part of Contao.
 *
 * Copyright (c) 2005-2015 Leo Feyer
 *
 * @license LGPL-3.0+
 */

namespace Contao\CoreBundle\Monolog;

/**
 * ContaoContext
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

    private $func;
    private $action;
    private $username;
    private $ip;
    private $browser;
    private $source;

    /**
     * Constructor.
     *
     * @param string      $func
     * @param string      $action
     * @param string|null $username
     * @param string|null $ip
     * @param string|null $browser
     * @param string|null $source
     */
    public function __construct($func, $action = null, $username = null, $ip = null, $browser = null, $source = null)
    {
        if ('' === (string) $func) {
            throw new \InvalidArgumentException('Function for Contao context must not be empty');
        }

        $this->func     = $func;
        $this->action   = $action;
        $this->username = $username;
        $this->ip       = $ip;
        $this->browser  = $browser;
        $this->source = $source;
    }

    /**
     * @return string
     */
    public function getFunc()
    {
        return $this->func;
    }

    /**
     * @return string|null
     */
    public function getAction()
    {
        return $this->action;
    }

    /**
     * @param string $action
     */
    public function setAction($action)
    {
        $this->action = (string) $action;
    }

    /**
     * @return string|null
     */
    public function getUsername()
    {
        return $this->username;
    }

    /**
     * @param string $username
     */
    public function setUsername($username)
    {
        $this->username = (string) $username;
    }

    /**
     * @return string|null
     */
    public function getIp()
    {
        return $this->ip;
    }

    /**
     * @param string|null $ip
     */
    public function setIp($ip)
    {
        $this->ip = (string) $ip;
    }

    /**
     * @return string|null
     */
    public function getBrowser()
    {
        return $this->browser;
    }

    /**
     * @param string $browser
     */
    public function setBrowser($browser)
    {
        $this->browser = (string) $browser;
    }

    /**
     * @return string|null
     */
    public function getSource()
    {
        return $this->source;
    }

    /**
     * @param string $source
     */
    public function setSource($source)
    {
        $this->source = (string) $source;
    }

    /**
     * @return string
     */
    public function __toString()
    {
        return json_encode(
            [
                'func'     => $this->func,
                'action'   => $this->action,
                'username' => $this->username,
                'ip'       => $this->ip,
                'browser'  => $this->browser,
            ]
        );
    }

    /**
     * @param string      $function
     * @param string      $action
     * @param string|null $username
     * @param string|null $ip
     * @param string|null $browser
     * @param string|null $source
     *
     * @return static
     */
    public static function create(
        $function,
        $action = self::GENERAL,
        $username = null,
        $ip = null,
        $browser = null,
        $source = null
    ) {
        return new static($function, $action, $username, $ip, $browser, $source);
    }
}
