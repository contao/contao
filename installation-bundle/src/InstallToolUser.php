<?php

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
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
     * @var int
     */
    private $timeout = 300;

    /**
     * Constructor.
     *
     * @param Session $session
     */
    public function __construct(Session $session)
    {
        $this->session = $session;
    }

    /**
     * Checks if the user is authenticated.
     *
     * @return bool
     */
    public function isAuthenticated()
    {
        if (!$this->session->has('_auth_until') || $this->session->get('_auth_until') < time()) {
            return false;
        }

        // Update the expiration date
        $this->session->set('_auth_until', time() + $this->timeout);

        return true;
    }

    /**
     * Sets the authentication flag.
     *
     * @param bool $authenticated
     */
    public function setAuthenticated($authenticated)
    {
        if (true === $authenticated) {
            $this->session->set('_auth_until', time() + $this->timeout);
        } else {
            $this->session->remove('_auth_until');
        }
    }
}
