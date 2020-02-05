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

use Contao\CoreBundle\Monolog\ContaoContext;
use Psr\Log\LoggerInterface;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Security\Http\Event\SwitchUserEvent;

/**
 * @internal
 */
class SwitchUserListener
{
    /**
     * @var TokenStorageInterface
     */
    private $tokenStorage;

    /**
     * @var LoggerInterface
     */
    private $logger;

    public function __construct(TokenStorageInterface $tokenStorage, LoggerInterface $logger)
    {
        $this->tokenStorage = $tokenStorage;
        $this->logger = $logger;
    }

    /**
     * Logs successful user impersonations.
     *
     * @throws \RuntimeException
     */
    public function __invoke(SwitchUserEvent $event): void
    {
        $token = $this->tokenStorage->getToken();

        if (null === $token) {
            throw new \RuntimeException('The token storage did not contain a token.');
        }

        $sourceUser = $token->getUsername();
        $targetUser = $event->getTargetUser();

        if ($targetUser instanceof UserInterface) {
            $targetUser = $targetUser->getUsername();
        }

        $this->logger->info(
            sprintf('User "%s" has switched to user "%s"', $sourceUser, $targetUser),
            ['contao' => new ContaoContext(__METHOD__, ContaoContext::ACCESS, $sourceUser)]
        );
    }
}
