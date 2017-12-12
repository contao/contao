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
use Contao\CoreBundle\Routing\ScopeMatcher;
use Contao\User;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpKernel\Event\GetResponseEvent;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\Security\Core\Authentication\Token\UsernamePasswordToken;

class TokenLifetimeListener
{
    /**
     * @var TokenStorageInterface
     */
    private $tokenStorage;

    /**
     * @var ScopeMatcher
     */
    private $scopeMatcher;

    /**
     * @var int
     */
    private $tokenLifetime;

    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * @param TokenStorageInterface $tokenStorage
     * @param ScopeMatcher          $scopeMatcher
     * @param int                   $tokenLifetime
     * @param LoggerInterface       $logger
     */
    public function __construct(TokenStorageInterface $tokenStorage, ScopeMatcher $scopeMatcher, int $tokenLifetime, LoggerInterface $logger)
    {
        $this->tokenStorage = $tokenStorage;
        $this->scopeMatcher = $scopeMatcher;
        $this->tokenLifetime = $tokenLifetime;
        $this->logger = $logger;
    }

    /**
     * Checks if the current tokens lifetime is still valid.
     *
     * @param GetResponseEvent $event
     */
    public function onKernelRequest(GetResponseEvent $event): void
    {
        if (!$this->scopeMatcher->isContaoMasterRequest($event)) {
            return;
        }

        $token = $this->tokenStorage->getToken();

        if (!$token instanceof UsernamePasswordToken) {
            return;
        }

        $user = $token->getUser();

        if (!$user instanceof User) {
            return;
        }

        if (!$token->hasAttribute('lifetime') || $token->getAttribute('lifetime') > time()) {
            $token->setAttribute('lifetime', time() + $this->tokenLifetime);

            return;
        }

        $this->logger->info(
            sprintf('User "%s" has been logged out automatically due to inactivity', $user->getUsername()),
            ['contao' => new ContaoContext(__METHOD__, ContaoContext::ACCESS)]
        );

        $this->tokenStorage->setToken();
    }
}
