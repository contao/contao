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
     * @var Authenticator
     */
    private $authenticator;

    /**
     * @var TwoFactorFormRendererInterface
     */
    private $formRenderer;

    /**
     * @var bool
     */
    private $enforceTwoFactor;

    /**
     * @param Authenticator                  $authenticator
     * @param TwoFactorFormRendererInterface $formRenderer
     * @param bool                           $enforceTwoFactor
     */
    public function __construct(Authenticator $authenticator, TwoFactorFormRendererInterface $formRenderer, bool $enforceTwoFactor)
    {
        $this->authenticator = $authenticator;
        $this->formRenderer = $formRenderer;
        $this->enforceTwoFactor = $enforceTwoFactor;
    }

    /**
     * {@inheritdoc}
     */
    public function beginAuthentication(AuthenticationContextInterface $context): bool
    {
        $user = $context->getUser();

        if (!$user instanceof User) {
            return false;
        }

        // Generate a secret if 2FA is enforced and the user has not yet enabled it
        if ($this->enforceTwoFactor && !$user->secret) {
            $user->secret = random_bytes(128);
            $user->save();
        }

        // Check confirmedTwoFactor since useTwoFactor does not guarantee a successfull 2FA activation
        return $this->enforceTwoFactor || $user->confirmedTwoFactor;
    }

    /**
     * {@inheritdoc}
     */
    public function validateAuthenticationCode($user, string $authenticationCode): bool
    {
        if (!$user instanceof User) {
            return false;
        }

        if (!$this->authenticator->validateCode($user, $authenticationCode)) {
            return false;
        }

        // 2FA is now confirmed, save the user flag
        if ($this->enforceTwoFactor && !$user->confirmedTwoFactor) {
            $user->useTwoFactor = true;
            $user->confirmedTwoFactor = true;
            $user->save();
        }

        return true;
    }

    /**
     * {@inheritdoc}
     */
    public function getFormRenderer(): TwoFactorFormRendererInterface
    {
        return $this->formRenderer;
    }
}
