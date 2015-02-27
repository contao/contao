<?php

/**
 * Contao Open Source CMS
 *
 * Copyright (c) 2005-2014 Leo Feyer
 *
 * @license LGPL-3.0+
 */

namespace Contao\CoreBundle\Security\Authentication;

use Symfony\Component\Security\Core\Authentication\Token\AbstractToken;
use Symfony\Component\Security\Core\Exception\UsernameNotFoundException;
use Symfony\Component\Security\Core\Role\RoleInterface;

class ContaoToken extends AbstractToken
{

    /**
     * Constructor.
     *
     * @param \User $user A Contao user instance
     */
    public function __construct(\User $user)
    {
        $this->setUser($user);

        $roles = [];

        if (!$user->authenticate()) {
            throw new UsernameNotFoundException('Contao user not found.');
        }

        $this->setAuthenticated(true);

        if ($user instanceof \FrontendUser) {
            $roles[] = 'ROLE_MEMBER';
        } elseif ($user instanceof \BackendUser) {
            $roles[] = 'ROLE_USER';

            if ($user->isAdmin) {
                $roles[] = 'ROLE_ADMIN';
            }
        }

        parent::__construct($roles);
    }

    /**
     * Returns the user credentials.
     *
     * @return mixed The user credentials
     */
    public function getCredentials()
    {
        return '';
    }
}
