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
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\Security\Http\Event\SwitchUserEvent;

/**
 * @internal
 */
class SwitchUserListener
{
    public function __construct(private TokenStorageInterface $tokenStorage, private LoggerInterface $logger)
    {
    }

    /**
     * Logs successful user impersonations.
     */
    public function __invoke(SwitchUserEvent $event): void
    {
        $token = $this->tokenStorage->getToken();

        if (null === $token) {
            throw new \RuntimeException('The token storage did not contain a token.');
        }

        $sourceUser = $token->getUserIdentifier();
        $targetUser = $event->getTargetUser()->getUserIdentifier();

        $this->logger->info(sprintf('User "%s" has switched to user "%s"', $sourceUser, $targetUser));
    }
}
