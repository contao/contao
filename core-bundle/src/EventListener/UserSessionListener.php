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

use Contao\CoreBundle\Routing\ScopeMatcher;
use Contao\User;
use Doctrine\DBAL\Connection;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Session\Attribute\AttributeBagInterface;
use Symfony\Component\HttpFoundation\Session\SessionBagInterface;
use Symfony\Component\HttpKernel\Event\FilterResponseEvent;
use Symfony\Component\HttpKernel\Event\GetResponseEvent;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\Security\Core\Authentication\AuthenticationTrustResolverInterface;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;

class UserSessionListener
{
    /**
     * @var Connection
     */
    private $connection;

    /**
     * @var TokenStorageInterface
     */
    private $tokenStorage;

    /**
     * @var AuthenticationTrustResolverInterface
     */
    private $authenticationTrustResolver;

    /**
     * @var ScopeMatcher
     */
    private $scopeMatcher;

    /**
     * @var EventDispatcherInterface
     */
    private $eventDispatcher;

    public function __construct(Connection $connection, TokenStorageInterface $tokenStorage, AuthenticationTrustResolverInterface $authenticationTrustResolver, ScopeMatcher $scopeMatcher, EventDispatcherInterface $eventDispatcher)
    {
        $this->connection = $connection;
        $this->tokenStorage = $tokenStorage;
        $this->authenticationTrustResolver = $authenticationTrustResolver;
        $this->scopeMatcher = $scopeMatcher;
        $this->eventDispatcher = $eventDispatcher;
    }

    /**
     * Replaces the current session data with the stored session data.
     */
    public function onKernelRequest(GetResponseEvent $event): void
    {
        if (!$this->scopeMatcher->isContaoMasterRequest($event)) {
            return;
        }

        $token = $this->tokenStorage->getToken();

        if (null === $token || $this->authenticationTrustResolver->isAnonymous($token)) {
            return;
        }

        $user = $token->getUser();

        if (!$user instanceof User) {
            return;
        }

        $session = $user->session;

        if (\is_array($session)) {
            $this->getSessionBag($event->getRequest())->replace($session);
        }

        // Dynamically register the kernel.response listener (see #1293)
        $this->eventDispatcher->addListener(KernelEvents::RESPONSE, [$this, 'onKernelResponse']);
    }

    /**
     * Writes the current session data to the database.
     */
    public function onKernelResponse(FilterResponseEvent $event): void
    {
        if (!$this->scopeMatcher->isContaoMasterRequest($event)) {
            return;
        }

        $token = $this->tokenStorage->getToken();

        if (null === $token || $this->authenticationTrustResolver->isAnonymous($token)) {
            return;
        }

        $user = $token->getUser();

        if (!$user instanceof User) {
            return;
        }

        $data = $this->getSessionBag($event->getRequest())->all();

        $this->connection->update($user->getTable(), ['session' => serialize($data)], ['id' => $user->id]);
    }

    /**
     * Returns the session bag.
     *
     * @throws \RuntimeException
     *
     * @return AttributeBagInterface|SessionBagInterface
     */
    private function getSessionBag(Request $request): AttributeBagInterface
    {
        if (!$request->hasSession() || null === ($session = $request->getSession())) {
            throw new \RuntimeException('The request did not contain a session.');
        }

        $name = 'contao_frontend';

        if ($this->scopeMatcher->isBackendRequest($request)) {
            $name = 'contao_backend';
        }

        return $session->getBag($name);
    }
}
