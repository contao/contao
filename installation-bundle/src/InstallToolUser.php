<?php

/*
 * This file is part of Contao.
 *
 * Copyright (c) 2005-2015 Leo Feyer
 *
 * @license LGPL-3.0+
 */

namespace Contao\InstallationBundle;

use Symfony\Component\HttpFoundation\Session\Session;

/**
 * Handles the user authentication.
 *
 * @author Leo Feyer <https://github.com/leofeyer>
 */
class InstallToolUser
{
    /**
     * @var Session
     */
    private $session;

    /**
     * Constructor.
     *
     * @param Session $session The session object
     */
    public function __construct(Session $session)
    {
        $this->session = $session;
    }

    /**
     * Checks if the user is authenticated.
     *
     * @return bool True if the user is authenticated
     */
    public function isAuthenticated()
    {
        return $this->session->has('_auth_until') && $this->session->get('_auth_until') >= time();
    }

    /**
     * Sets the authentication flag.
     *
     * @param bool $authenticated The authentication status
     */
    public function setAuthenticated($authenticated)
    {
        if (true === $authenticated) {
            $this->session->set('_auth_until', time() + 300);
        } else {
            $this->session->remove('_auth_until');
        }
    }
}
