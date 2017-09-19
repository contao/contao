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
use Symfony\Component\Security\Core\Role\RoleInterface;

/**
 * Provides a Contao authentication token.
 */
class ContaoToken extends AbstractToken
{
    /**
     * Constructor.
     *
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
     * @return RoleInterface[]
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
