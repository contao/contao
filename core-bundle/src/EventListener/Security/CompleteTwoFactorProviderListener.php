<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao\CoreBundle\EventListener\Security;

use Scheb\TwoFactorBundle\Security\Authentication\Token\TwoFactorTokenInterface;
use Symfony\Component\EventDispatcher\Attribute\AsEventListener;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;

/**
 * @internal
 */
#[AsEventListener('scheb_two_factor.authentication.code_valid')]
#[AsEventListener('scheb_two_factor.backup_code.valid')]
class CompleteTwoFactorProviderListener
{
    public function __construct(private readonly TokenStorageInterface $tokenStorage)
    {
    }

    public function __invoke(): void
    {
        $token = $this->tokenStorage->getToken();

        if (!$token instanceof TwoFactorTokenInterface) {
            return;
        }

        $currentProvider = $token->getCurrentTwoFactorProvider();

        if (null !== $currentProvider) {
            $token->setTwoFactorProviderComplete($currentProvider);
        }
    }
}
