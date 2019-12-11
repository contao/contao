<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao\InstallationBundle;

use Symfony\Component\HttpFoundation\Session\Session;

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
     * @internal Do not inherit from this class; decorate the "contao.install_tool_user" service instead
     */
    public function __construct(Session $session)
    {
        $this->session = $session;
    }

    public function isAuthenticated(): bool
    {
        if (!$this->session->has('_auth_until') || $this->session->get('_auth_until') < time()) {
            return false;
        }

        // Update the expiration date
        $this->session->set('_auth_until', time() + $this->timeout);

        return true;
    }

    public function setAuthenticated(bool $authenticated): void
    {
        if (true === $authenticated) {
            $this->session->set('_auth_until', time() + $this->timeout);
        } else {
            $this->session->remove('_auth_until');
        }
    }
}
