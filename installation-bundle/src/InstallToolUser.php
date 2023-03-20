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
    private Session $session;
    private int $timeout = 300;

    /**
     * @internal
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
