<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao\CoreBundle\EventListener;

use Symfony\Component\Security\Core\Authentication\Token\AnonymousToken;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;

trigger_deprecation('contao/core-bundle', '4.3', 'Using the "Contao\CoreBundle\EventListener\UserAwareTrait" trait has been deprecated and will no longer work in Contao 5.0.');

/**
 * @deprecated Deprecated since Contao 4.3, to be removed in Contao 5.0
 */
trait UserAwareTrait
{
    protected TokenStorageInterface $tokenStorage;

    public function setTokenStorage(TokenStorageInterface $tokenStorage): void
    {
        $this->tokenStorage = $tokenStorage;
    }

    /**
     * Checks if there is an authenticated user.
     */
    protected function hasUser(): bool
    {
        $user = $this->tokenStorage->getToken();

        if (null === $user) {
            return false;
        }

        return !$user instanceof AnonymousToken;
    }
}
