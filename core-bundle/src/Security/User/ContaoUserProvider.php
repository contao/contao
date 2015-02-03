<?php

/**
 * Contao Open Source CMS
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
 * ContaoUserProvider provides Contao frontend or backend user instance
 *
 * @author Andreas Schempp <andreas.schempp@terminal42.ch>
 */
class ContaoUserProvider implements UserProviderInterface
{
    /**
     * {@inheritdoc}
     */
    public function loadUserByUsername($username)
    {
        if ('backend' === $username) {
            return \BackendUser::getInstance();

        } elseif ('frontend' === $username) {
            return \FrontendUser::getInstance();

        } else {
            throw new UsernameNotFoundException('Can only load "frontend" or "backend" user.');
        }
    }

    /**
     * {@inheritdoc}
     */
    public function refreshUser(UserInterface $user)
    {
        throw new UnsupportedUserException('Cannot refresh a Contao user.');
    }

    /**
     * {@inheritdoc}
     */
    public function supportsClass($class)
    {
        return is_subclass_of($class, 'Contao\User');
    }
}
