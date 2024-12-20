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

use Psr\Log\LoggerInterface;
use Symfony\Component\EventDispatcher\Attribute\AsEventListener;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\Security\Core\Authentication\Token\SwitchUserToken;
use Symfony\Component\Security\Http\Event\SwitchUserEvent;

/**
 * @internal
 */
#[AsEventListener]
class SwitchUserListener
{
    public function __construct(
        private readonly TokenStorageInterface $tokenStorage,
        private readonly LoggerInterface $logger,
    ) {
    }

    /**
     * Logs successful user impersonations.
     */
    public function __invoke(SwitchUserEvent $event): void
    {
        if (!$token = $this->tokenStorage->getToken()) {
            throw new \RuntimeException('The token storage did not contain a token.');
        }

        $sourceUser = $token->getUserIdentifier();
        $targetUser = $event->getTargetUser()->getUserIdentifier();

        $originalUser = null;

        if ($token instanceof SwitchUserToken) {
            $originalUser = $token->getOriginalToken()->getUserIdentifier();
        }

        if ($originalUser === $targetUser) {
            $this->logger->info(\sprintf('User "%s" has quit the impersonation of user "%s"', $originalUser, $sourceUser));
        } else {
            if ($originalUser) {
                $sourceUser = $originalUser;
            }

            $this->logger->info(\sprintf('User "%s" has switched to user "%s"', $sourceUser, $targetUser));
        }
    }
}
