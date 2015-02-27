<?php

/**
 * This file is part of Contao.
 *
 * Copyright (c) 2005-2014 Leo Feyer
 *
 * @license LGPL-3.0+
 */

namespace Contao\CoreBundle\Security\User;

use Symfony\Component\Security\Core\User\UserProviderInterface;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Security\Core\Exception\UsernameNotFoundException;
use Symfony\Component\Security\Core\Exception\UnsupportedUserException;

/**
 * Provides a Contao front end or back end user object.
 *
 * @author Andreas Schempp <https://github.com/aschempp>
 */
class ContaoUserProvider implements UserProviderInterface
{
    /**
     * {@inheritdoc}
     *
     * @return \BackendUser|\FrontendUser The user object
     */
    public function loadUserByUsername($username)
    {
        if ('backend' === $username) {
            return \BackendUser::getInstance();
        }

        if ('frontend' === $username) {
            return \FrontendUser::getInstance();
        }

        throw new UsernameNotFoundException('Can only load "frontend" or "backend" user');
    }

    /**
     * {@inheritdoc}
     */
    public function refreshUser(UserInterface $user)
    {
        throw new UnsupportedUserException('Cannot refresh a Contao user');
    }

    /**
     * {@inheritdoc}
     */
    public function supportsClass($class)
    {
        return is_subclass_of($class, 'Contao\User');
    }
}
