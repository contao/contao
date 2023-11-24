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

use Contao\CoreBundle\Repository\RememberMeRepository;
use Contao\User;
use Symfony\Component\Security\Http\Event\TokenDeauthenticatedEvent;

class TokenDeauthenticatedListener
{
    public function __construct(private readonly RememberMeRepository $rememberMeRepository)
    {
    }

    public function __invoke(TokenDeauthenticatedEvent $tokenDeauthenticatedEvent): void
    {
        $user = $tokenDeauthenticatedEvent->getOriginalToken()->getUser();

        if (!$user instanceof User) {
            return;
        }

        $this->rememberMeRepository->deleteByUserIdentifier($user->getUserIdentifier());
    }
}
