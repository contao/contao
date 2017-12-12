<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * Copyright (c) 2005-2017 Leo Feyer
 *
 * @license LGPL-3.0+
 */

namespace Contao\CoreBundle\EventListener;

use Contao\BackendUser;
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
     */
    public function onSwitchUser(SwitchUserEvent $event): void
    {
        /** @var BackendUser $user */
        $user = $this->tokenStorage->getToken()->getUser();

        /** @var BackendUser $targetUser */
        $targetUser = $event->getTargetUser();

        $this->logger->info(
            sprintf('User "%s" has switched to user "%s"', $user->getUsername(), $targetUser->getUsername()),
            ['contao' => new ContaoContext(__METHOD__, ContaoContext::ACCESS)]
        );
    }
}
