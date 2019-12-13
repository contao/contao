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

use Contao\CoreBundle\Security\Exception\LockedException;
use Contao\User;
use Scheb\TwoFactorBundle\Security\Authentication\Token\TwoFactorTokenInterface;
use Scheb\TwoFactorBundle\Security\TwoFactor\Event\TwoFactorAuthenticationEvent;

class AuthenticationAttemptListener
{
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

        $lockedSeconds = $user->locked - time();

        if ($lockedSeconds <= 0) {
            return;
        }

        $exception = new LockedException(
            $lockedSeconds,
            sprintf('User "%s" has been locked for %s seconds', $user->username, $lockedSeconds)
        );

        $exception->setUser($user);

        throw $exception;
    }
}
