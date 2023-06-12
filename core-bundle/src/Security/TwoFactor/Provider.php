<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao\CoreBundle\Security\TwoFactor;

use Contao\User;
use Scheb\TwoFactorBundle\Security\TwoFactor\AuthenticationContextInterface;
use Scheb\TwoFactorBundle\Security\TwoFactor\Provider\TwoFactorFormRendererInterface;
use Scheb\TwoFactorBundle\Security\TwoFactor\Provider\TwoFactorProviderInterface;

class Provider implements TwoFactorProviderInterface
{
    /**
     * @internal
     */
    public function __construct(private readonly Authenticator $authenticator)
    {
    }

    public function beginAuthentication(AuthenticationContextInterface $context): bool
    {
        $user = $context->getUser();

        if (!$user instanceof User) {
            return false;
        }

        return (bool) $user->useTwoFactor;
    }

    /**
     * @param mixed $user
     */
    public function validateAuthenticationCode($user, string $authenticationCode): bool
    {
        if (!$user instanceof User) {
            return false;
        }

        if (!$this->authenticator->validateCode($user, $authenticationCode)) {
            return false;
        }

        return true;
    }

    public function getFormRenderer(): TwoFactorFormRendererInterface
    {
        throw new \RuntimeException('The "contao" two-factor provider does not support forms');
    }

    /**
     * @param mixed $user
     */
    public function prepareAuthentication($user): void
    {
    }
}
