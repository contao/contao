<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * Copyright (c) 2005-2017 Leo Feyer
 *
 * @license LGPL-3.0+
 */

namespace Contao\CoreBundle\Security\Authentication;

use Contao\CoreBundle\Monolog\ContaoContext;
use Contao\FrontendUser;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\Security\Core\Authentication\Token\UsernamePasswordToken;
use Symfony\Component\Security\Core\Exception\UsernameNotFoundException;
use Symfony\Component\Security\Core\User\UserProviderInterface;

class FrontendPreviewAuthenticator
{
    /**
     * @var RequestStack
     */
    private $requestStack;

    /**
     * @var SessionInterface
     */
    private $session;

    /**
     * @var TokenStorageInterface
     */
    private $tokenStorage;

    /**
     * @var UserProviderInterface
     */
    private $userProvider;

    /**
     * @var LoggerInterface|null
     */
    private $logger;

    /**
     * @param RequestStack          $requestStack
     * @param SessionInterface      $session
     * @param TokenStorageInterface $tokenStorage
     * @param UserProviderInterface $userProvider
     * @param LoggerInterface|null  $logger
     */
    public function __construct(RequestStack $requestStack, SessionInterface $session, TokenStorageInterface $tokenStorage, UserProviderInterface $userProvider, LoggerInterface $logger = null)
    {
        $this->requestStack = $requestStack;
        $this->session = $session;
        $this->tokenStorage = $tokenStorage;
        $this->userProvider = $userProvider;
        $this->logger = $logger;
    }

    /**
     * Authenticates a front end user based on the username.
     *
     * @param null $username
     */
    public function authenticateFrontendUser($username = null): void
    {
        $providerKey = 'contao_frontend';
        $request = $this->requestStack->getCurrentRequest();

        // Check if a back end user is authenticated
        if (null === $this->tokenStorage->getToken() || !$this->tokenStorage->getToken()->isAuthenticated()) {
            return;
        }

        if (null === $username || !$request->hasSession()) {
            return;
        }

        try {
            $user = $this->userProvider->loadUserByUsername($username);
        } catch (UsernameNotFoundException $e) {
            if (null !== $this->logger) {
                $this->logger->info(
                    sprintf('Could not find a front end user with the username "%s"', $username),
                    ['contao' => new ContaoContext(__METHOD__, ContaoContext::ACCESS)]
                );
            }

            return;
        }

        $token = new UsernamePasswordToken($user, null, $providerKey, (array) $user->getRoles());

        if (false === $token->isAuthenticated()) {
            if ($request->hasPreviousSession()) {
                $this->session->remove(FrontendUser::SECURITY_SESSION_KEY);
            }
        } else {
            $this->session->set(FrontendUser::SECURITY_SESSION_KEY, serialize($token));
        }
    }
}
