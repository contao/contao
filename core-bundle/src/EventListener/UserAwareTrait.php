<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * Copyright (c) 2005-2017 Leo Feyer
 *
 * @license LGPL-3.0+
 */

namespace Contao\CoreBundle\EventListener;

use Symfony\Component\Security\Core\Authentication\Token\AnonymousToken;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;

@trigger_error('Using the UserAwareTrait has been deprecated and will no longer work in Contao 5.0.', E_USER_DEPRECATED);

/**
 * @deprecated Deprecated since Contao 4.3, to be removed in Contao 5.0
 */
trait UserAwareTrait
{
    /**
     * @var TokenStorageInterface
     */
    protected $tokenStorage;

    /**
     * Sets the token storage object.
     *
     * @param TokenStorageInterface $tokenStorage
     */
    public function setTokenStorage(TokenStorageInterface $tokenStorage): void
    {
        $this->tokenStorage = $tokenStorage;
    }

    /**
     * Checks if there is an authenticated user.
     *
     * @return bool
     */
    protected function hasUser(): bool
    {
        $user = $this->tokenStorage->getToken();

        if (null === $user) {
            return false;
        }

        return !($user instanceof AnonymousToken);
    }
}
