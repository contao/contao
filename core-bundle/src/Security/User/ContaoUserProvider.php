<?php

/*
 * This file is part of Contao.
 *
 * Copyright (c) 2005-2016 Leo Feyer
 *
 * @license LGPL-3.0+
 */

namespace Contao\CoreBundle\Security\User;

use Contao\BackendUser;
use Contao\CoreBundle\Framework\ContaoFrameworkInterface;
use Contao\CoreBundle\Framework\ScopeAwareTrait;
use Contao\FrontendUser;
use Symfony\Component\Security\Core\Exception\UnsupportedUserException;
use Symfony\Component\Security\Core\Exception\UsernameNotFoundException;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Security\Core\User\UserProviderInterface;

/**
 * Provides a Contao front end or back end user object.
 *
 * @author Andreas Schempp <https://github.com/aschempp>
 */
class ContaoUserProvider implements UserProviderInterface
{
    use ScopeAwareTrait;

    /**
     * @var ContaoFrameworkInterface
     */
    private $framework;

    /**
     * Constructor.
     *
     * @param ContaoFrameworkInterface $framework
     */
    public function __construct(ContaoFrameworkInterface $framework)
    {
        $this->framework = $framework;
    }

    /**
     * {@inheritdoc}
     *
     * @return BackendUser|FrontendUser
     */
    public function loadUserByUsername($username)
    {
        if ($this->isBackendUsername($username)) {
            $this->framework->initialize();

            return BackendUser::getInstance();
        }

        if ($this->isFrontendUsername($username)) {
            $this->framework->initialize();

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
        return is_subclass_of($class, 'Contao\User');
    }

    /**
     * Checks if the given username can be mapped to a front end user.
     *
     * @param string $username
     *
     * @return bool
     */
    private function isFrontendUsername($username)
    {
        return 'frontend' === $username && $this->isFrontendScope();
    }

    /**
     * Checks if the given username can be mapped to a back end user.
     *
     * @param string $username
     *
     * @return bool
     */
    private function isBackendUsername($username)
    {
        return 'backend' === $username && $this->isBackendScope();
    }
}
