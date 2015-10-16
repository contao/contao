<?php

/*
 * This file is part of Contao.
 *
 * Copyright (c) 2005-2015 Leo Feyer
 *
 * @license LGPL-3.0+
 */

namespace Contao\CoreBundle\Security\User;

use Contao\BackendUser;
use Contao\CoreBundle\ContaoCoreBundle;
use Contao\CoreBundle\ContaoFrameworkInterface;
use Contao\FrontendUser;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\Security\Core\User\UserProviderInterface;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Security\Core\Exception\UsernameNotFoundException;
use Symfony\Component\Security\Core\Exception\UnsupportedUserException;

/**
 * Provides a Contao front end or back end user object.
 *
 * @author Andreas Schempp <https://github.com/aschempp>
 *
 * @internal
 */
class ContaoUserProvider implements UserProviderInterface
{
    /**
     * @var ContainerInterface
     */
    private $container;

    /**
     * @var ContaoFrameworkInterface
     */
    private $framework;

    /**
     * Constructor.
     *
     * @param ContainerInterface       $container The service container
     * @param ContaoFrameworkInterface $framework The Contao framework service
     */
    public function __construct(ContainerInterface $container, ContaoFrameworkInterface $framework)
    {
        $this->container = $container;
        $this->framework = $framework;
    }

    /**
     * {@inheritdoc}
     *
     * @return BackendUser|FrontendUser The user object
     */
    public function loadUserByUsername($username)
    {
        $this->framework->initialize();

        if ($this->isBackendUsername($username)) {
            return BackendUser::getInstance();
        }

        if ($this->isFrontendUsername($username)) {
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

    /**
     * Checks if the given username can be mapped to a front end user.
     *
     * @param string $username The username
     *
     * @return bool True if the username can be mapped to a front end user
     */
    private function isFrontendUsername($username)
    {
        return 'frontend' === $username && $this->container->isScopeActive(ContaoCoreBundle::SCOPE_FRONTEND);
    }

    /**
     * Checks if the given username can be mapped to a back end user.
     *
     * @param string $username The username
     *
     * @return bool True if the username can be mapped to a back end user
     */
    private function isBackendUsername($username)
    {
        return 'backend' === $username && $this->container->isScopeActive(ContaoCoreBundle::SCOPE_BACKEND);
    }
}
