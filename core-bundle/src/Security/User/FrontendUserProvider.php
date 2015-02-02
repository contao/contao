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

class FrontendUserProvider implements UserProviderInterface
{
    public function loadUserByUsername($username)
    {
        if ($username !== 'frontend') {
            throw new UsernameNotFoundException('Can only load "frontend" user.');
        }

        $user = \FrontendUser::getInstance();

        if (!$user->authenticate()) {
            throw new UsernameNotFoundException('Contao user not logged in.');
        }

        return new FrontendUser($user);
    }

    public function refreshUser(UserInterface $user)
    {
        throw new UnsupportedUserException('Contao will handle user loading.');
    }

    public function supportsClass($class)
    {
        return $class === 'Contao\CoreBundle\Security\User\FrontendUser';
    }
}
