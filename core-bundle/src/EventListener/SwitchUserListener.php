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
use Symfony\Component\Security\Http\Event\SwitchUserEvent;

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

    /**
     * @param TokenStorageInterface $tokenStorage
     * @param LoggerInterface       $logger
     */
    public function __construct(TokenStorageInterface $tokenStorage, LoggerInterface $logger)
    {
        $this->tokenStorage = $tokenStorage;
        $this->logger = $logger;
    }

    /**
     * Logs successful user impersonations.
     *
     * @param SwitchUserEvent $event
     *
     * @throws \RuntimeException
     */
    public function onSwitchUser(SwitchUserEvent $event): void
    {
        $token = $this->tokenStorage->getToken();

        if (null === $token) {
            throw new \RuntimeException('The token storage did not contain a token.');
        }

        $sourceUser = $token->getUser()->getUsername();
        $targetUser = $event->getTargetUser()->getUsername();

        $this->logger->info(
            sprintf('User "%s" has switched to user "%s"', $sourceUser, $targetUser),
            ['contao' => new ContaoContext(__METHOD__, ContaoContext::ACCESS, $sourceUser)]
        );
    }
}
