<?php

/*
 * This file is part of Contao.
 *
 * Copyright (c) 2005-2017 Leo Feyer
 *
 * @license LGPL-3.0+
 */

namespace Contao\CoreBundle\EventListener;

use Contao\BackendUser;
use Contao\CoreBundle\Routing\ScopeMatcher;
use Contao\FrontendUser;
use Contao\User;
use Doctrine\DBAL\Connection;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Session\Attribute\AttributeBagInterface;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Symfony\Component\HttpKernel\Event\FilterResponseEvent;
use Symfony\Component\HttpKernel\Event\GetResponseEvent;
use Symfony\Component\Security\Core\Authentication\AuthenticationTrustResolverInterface;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;

/**
 * Stores and restores the user session.
 *
 * @author Yanick Witschi <https://github.com/toflar>
 * @author Leo Feyer <https://github.com/leofeyer>
 */
class UserSessionListener
{
    /**
     * @var TokenStorageInterface
     */
    protected $tokenStorage;
    /**
     * @var SessionInterface
     */
    private $session;

    /**
     * @var Connection
     */
    private $connection;

    /**
     * @var AuthenticationTrustResolverInterface
     */
    private $authenticationTrustResolver;

    /**
     * @var ScopeMatcher
     */
    private $scopeMatcher;

    /**
     * Constructor.
     *
     * @param SessionInterface                     $session
     * @param Connection                           $connection
     * @param TokenStorageInterface                $tokenStorage
     * @param AuthenticationTrustResolverInterface $authenticationTrustResolver
     * @param ScopeMatcher                         $scopeMatcher
     */
    public function __construct(SessionInterface $session, Connection $connection, TokenStorageInterface $tokenStorage, AuthenticationTrustResolverInterface $authenticationTrustResolver, ScopeMatcher $scopeMatcher)
    {
        $this->session = $session;
        $this->connection = $connection;
        $this->tokenStorage = $tokenStorage;
        $this->authenticationTrustResolver = $authenticationTrustResolver;
        $this->scopeMatcher = $scopeMatcher;
    }

    /**
     * Replaces the current session data with the stored session data.
     *
     * @param GetResponseEvent $event
     */
    public function onKernelRequest(GetResponseEvent $event)
    {
        if (!$this->scopeMatcher->isContaoMasterRequest($event)) {
            return;
        }

        $token = $this->tokenStorage->getToken();

        if (null === $token || $this->authenticationTrustResolver->isAnonymous($token)) {
            return;
        }

        $user = $this->getUserObject();

        if (!$user instanceof User) {
            return;
        }

        $session = $user->session;

        if (\is_array($session)) {
            $this->getSessionBag($event->getRequest())->replace($session);
        }
    }

    /**
     * Writes the current session data to the database.
     *
     * @param FilterResponseEvent $event
     */
    public function onKernelResponse(FilterResponseEvent $event)
    {
        if (!$this->scopeMatcher->isContaoMasterRequest($event)) {
            return;
        }

        $token = $this->tokenStorage->getToken();

        if (null === $token || $this->authenticationTrustResolver->isAnonymous($token)) {
            return;
        }

        $user = $this->getUserObject();

        if (!$user instanceof User) {
            return;
        }

        $this->connection->update(
            $user->getTable(),
            ['session' => serialize($this->getSessionBag($event->getRequest())->all())],
            ['id' => $user->id]
        );
    }

    /**
     * Returns the user object depending on the container scope.
     *
     * @return FrontendUser|BackendUser|null
     */
    private function getUserObject()
    {
        return $this->tokenStorage->getToken()->getUser();
    }

    /**
     * Returns the session bag.
     *
     * @param Request $request
     *
     * @return AttributeBagInterface
     */
    private function getSessionBag(Request $request)
    {
        if ($this->scopeMatcher->isBackendRequest($request)) {
            $name = 'contao_backend';
        } else {
            $name = 'contao_frontend';
        }

        /** @var AttributeBagInterface $bag */
        $bag = $this->session->getBag($name);

        return $bag;
    }
}
