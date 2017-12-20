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

use Contao\CoreBundle\Monolog\ContaoContext;
use Psr\Log\LoggerInterface;
use Symfony\Component\Security\Core\Event\AuthenticationFailureEvent;
use Symfony\Component\Security\Core\Exception\UsernameNotFoundException;

class AuthenticationListener
{
    /**
     * @var LoggerInterface|null
     */
    private $logger;

    /**
     * @param LoggerInterface|null $logger
     */
    public function __construct(LoggerInterface $logger = null)
    {
        $this->logger = $logger;
    }

    /**
     * Logs login attempts with unknown usernames.
     *
     * @param AuthenticationFailureEvent $event
     */
    public function onAuthenticationFailure(AuthenticationFailureEvent $event): void
    {
        if (null === $this->logger) {
            return;
        }

        $exception = $event->getAuthenticationException();

        while (null !== $exception && !$exception instanceof UsernameNotFoundException) {
            $exception = $exception->getPrevious();
        }

        if (!$exception instanceof UsernameNotFoundException) {
            return;
        }

        $username = $exception->getUsername() ?: $event->getAuthenticationToken()->getUsername();

        $this->logger->info(
            sprintf('Could not find user "%s"', $username),
            ['contao' => new ContaoContext(__METHOD__, ContaoContext::ACCESS, $username)]
        );
    }
}
