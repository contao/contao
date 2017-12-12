<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * Copyright (c) 2005-2017 Leo Feyer
 *
 * @license LGPL-3.0+
 */

namespace Contao\CoreBundle\Security\Authentication;

use Contao\BackendUser;
use Contao\FrontendUser;
use Contao\User;
use Symfony\Component\Security\Core\Authentication\Token\AbstractToken;
use Symfony\Component\Security\Core\Exception\UsernameNotFoundException;
use Symfony\Component\Security\Core\Role\Role;

@trigger_error('Using the ContaoToken class has been deprecated and will no longer work in Contao 5.0. Use the UsernamePasswordToken class instead.', E_USER_DEPRECATED);

/**
 * @deprecated Deprecated since Contao 4.5, to be removed in Contao 5.0; use
 *             the UsernamePasswordToken class instead
 */
class ContaoToken extends AbstractToken
{
    /**
     * @param User $user
     *
     * @throws UsernameNotFoundException
     */
    public function __construct(User $user)
    {
        if (!$user->authenticate()) {
            throw new UsernameNotFoundException('Invalid Contao user given.');
        }

        $this->setUser($user);
        $this->setAuthenticated(true);

        parent::__construct($this->getRolesFromUser($user));
    }

    /**
     * {@inheritdoc}
     */
    public function getCredentials(): string
    {
        return '';
    }

    /**
     * Returns the roles depending on the user object.
     *
     * @param User $user
     *
     * @return Role[]
     */
    private function getRolesFromUser(User $user): array
    {
        $roles = [];

        if ($user instanceof FrontendUser) {
            $roles[] = 'ROLE_MEMBER';
        } elseif ($user instanceof BackendUser) {
            $roles[] = 'ROLE_USER';

            if ($user->isAdmin) {
                $roles[] = 'ROLE_ADMIN';
            }
        }

        return $roles;
    }
}
