<?php

/**
 * Contao Open Source CMS
 *
 * Copyright (c) 2005-2014 Leo Feyer
 *
 * @license LGPL-3.0+
 */

namespace Contao\CoreBundle\Security\User;

use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Security\Core\User\EquatableInterface;

class BackendUser implements UserInterface, EquatableInterface
{
    private $user;

    public function __construct(\BackendUser $user)
    {
        $this->user = $user;
    }

    public function getRoles()
    {
        $roles = ['ROLE_USER'];

        if ($this->user->isAdmin) {
            $roles[] = 'ROLE_ADMIN';
        }

        return $roles;
    }

    public function getPassword()
    {
        return $this->user->password;
    }

    public function getSalt()
    {
        list(, $salt) = explode(':', $this->user->password);

        return $salt;
    }

    public function getUsername()
    {
        return $this->user->username;
    }

    public function eraseCredentials()
    {
    }

    public function isEqualTo(UserInterface $user)
    {
        if (!$user instanceof FrontendUser) {
            return false;
        }

        if ($this->getPassword() !== $user->getPassword()) {
            return false;
        }

        if ($this->getSalt() !== $user->getSalt()) {
            return false;
        }

        if ($this->getUsername() !== $user->getUsername()) {
            return false;
        }

        return true;
    }

    /**
     * @return \FrontendUser
     */
    public function getFrontendUser()
    {
        return $this->user;
    }
}
