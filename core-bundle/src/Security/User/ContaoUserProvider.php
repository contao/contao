<?php

/**
 * This file is part of Contao.
 *
 * Copyright (c) 2005-2014 Leo Feyer
 *
 * @license LGPL-3.0+
 */

namespace Contao\CoreBundle\Security\User;

use Contao\BackendUser;
use Contao\CoreBundle\ContaoCoreBundle;
use Contao\FrontendUser;
use Symfony\Component\DependencyInjection\ContainerAware;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Security\Core\User\UserProviderInterface;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Security\Core\Exception\UsernameNotFoundException;
use Symfony\Component\Security\Core\Exception\UnsupportedUserException;

/**
 * Provides a Contao front end or back end user object.
 *
 * @author Andreas Schempp <https://github.com/aschempp>
 */
class ContaoUserProvider extends ContainerAware implements UserProviderInterface
{
    /**
     * {@inheritdoc}
     *
     * @return BackendUser|FrontendUser The user object
     */
    public function loadUserByUsername($username)
    {
        if ('backend' === $username && $this->container->isScopeActive(ContaoCoreBundle::SCOPE_BACKEND)) {
            return BackendUser::getInstance();
        }

        if ('frontend' === $username && $this->container->isScopeActive(ContaoCoreBundle::SCOPE_FRONTEND)) {
            return FrontendUser::getInstance();
        }

        throw new UsernameNotFoundException('Can only load user "frontend" or "backend".');
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
        return is_subclass_of($class, 'Contao\\User');
    }
}
