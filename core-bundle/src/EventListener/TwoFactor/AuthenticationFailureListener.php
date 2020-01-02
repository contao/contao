<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao\CoreBundle\EventListener\TwoFactor;

use Contao\User;
use Scheb\TwoFactorBundle\Security\Authentication\Token\TwoFactorTokenInterface;
use Scheb\TwoFactorBundle\Security\TwoFactor\Event\TwoFactorAuthenticationEvent;

class AuthenticationFailureListener
{
    /**
     * Counts the login attempts and locks the user after the first try
     * following a specific delay scheme.
     *
     * After each failed attempt A, the authentication server waits for an
     * increased T * A number of seconds, e.g. say T = 5, then after 1 attempt,
     * the server waits for 5 seconds, at the second failed attempt, it waits
     * for 5 * 2 = 10 seconds and so on.
     */
    public function __invoke(TwoFactorAuthenticationEvent $event): void
    {
        $token = $event->getToken();

        if (!$token instanceof TwoFactorTokenInterface) {
            return;
        }

        $user = $token->getUser();

        if (!$user instanceof User) {
            return;
        }

        ++$user->loginAttempts;
        $user->locked = time() + $user->loginAttempts * 5;
        $user->save();
    }
}
