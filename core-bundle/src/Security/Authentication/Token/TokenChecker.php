<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao\CoreBundle\Security\Authentication\Token;

use Contao\BackendUser;
use Contao\CoreBundle\Security\Authentication\FrontendPreviewAuthenticator;
use Contao\FrontendUser;
use Symfony\Bundle\SecurityBundle\Security\FirewallConfig;
use Symfony\Bundle\SecurityBundle\Security\FirewallMap;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Security\Core\Authentication\AuthenticationTrustResolverInterface;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\VoterInterface;
use Symfony\Component\Security\Http\FirewallMapInterface;

class TokenChecker
{
    private const FRONTEND_FIREWALL = 'contao_frontend';
    private const BACKEND_FIREWALL = 'contao_backend';

    /**
     * @internal Do not inherit from this class; decorate the "contao.security.token_checker" service instead
     */
    public function __construct(
        private RequestStack $requestStack,
        private FirewallMapInterface $firewallMap,
        private TokenStorageInterface $tokenStorage,
        private AuthenticationTrustResolverInterface $trustResolver,
        private VoterInterface $roleVoter,
    ) {
    }

    /**
     * Checks if a front end user is authenticated.
     */
    public function hasFrontendUser(): bool
    {
        $token = $this->getToken(self::FRONTEND_FIREWALL);

        return null !== $token && VoterInterface::ACCESS_GRANTED === $this->roleVoter->vote($token, null, ['ROLE_MEMBER']);
    }

    /**
     * Checks if a back end user is authenticated.
     */
    public function hasBackendUser(): bool
    {
        $token = $this->getToken(self::BACKEND_FIREWALL);

        return null !== $token && VoterInterface::ACCESS_GRANTED === $this->roleVoter->vote($token, null, ['ROLE_USER']);
    }

    /**
     * Checks if a front end guest user is "authenticated".
     */
    public function hasFrontendGuest(): bool
    {
        if ((!$request = $this->requestStack->getMainRequest()) || !$request->hasSession()) {
            return false;
        }

        return $request->getSession()->has(FrontendPreviewAuthenticator::SESSION_NAME);
    }

    /**
     * Gets the front end username from the session.
     */
    public function getFrontendUsername(): string|null
    {
        $token = $this->getToken(self::FRONTEND_FIREWALL);

        if (null === $token || !$token->getUser() instanceof FrontendUser) {
            return null;
        }

        return $token->getUser()->getUserIdentifier();
    }

    /**
     * Gets the back end username from the session.
     */
    public function getBackendUsername(): string|null
    {
        $token = $this->getToken(self::BACKEND_FIREWALL);

        if (null === $token || !$token->getUser() instanceof BackendUser) {
            return null;
        }

        return $token->getUser()->getUserIdentifier();
    }

    /**
     * Tells whether the front end preview can show unpublished fragments.
     */
    public function isPreviewMode(): bool
    {
        $request = $this->requestStack->getMainRequest();

        if (null === $request || !$request->attributes->get('_preview', false)) {
            return false;
        }

        if (!$request->hasPreviousSession()) {
            return false;
        }

        $session = $request->getSession();

        if (!$session->has(FrontendPreviewAuthenticator::SESSION_NAME)) {
            return false;
        }

        $preview = $session->get(FrontendPreviewAuthenticator::SESSION_NAME);

        return (bool) $preview['showUnpublished'];
    }

    private function getToken(string $context): TokenInterface|null
    {
        $token = $this->getTokenFromStorage($context);

        if (null === $token) {
            $token = $this->getTokenFromSession('_security_'.$context);
        }

        if (!$token instanceof TokenInterface) {
            return null;
        }

        if (!$this->trustResolver->isAuthenticated($token) && !$this->trustResolver->isFullFledged($token) && !$this->trustResolver->isRememberMe($token)) {
            return null;
        }

        return $token;
    }

    private function getTokenFromStorage(string $context): TokenInterface|null
    {
        $request = $this->requestStack->getMainRequest();

        if (!$this->firewallMap instanceof FirewallMap || null === $request) {
            return null;
        }

        $config = $this->firewallMap->getFirewallConfig($request);

        if (!$config instanceof FirewallConfig || $config->getContext() !== $context) {
            return null;
        }

        return $this->tokenStorage->getToken();
    }

    private function getTokenFromSession(string $sessionKey): TokenInterface|null
    {
        if ((!$request = $this->requestStack->getMainRequest()) || !$request->hasSession()) {
            return null;
        }

        $session = $request->getSession();

        if (!$session->isStarted() && !$request->hasPreviousSession()) {
            return null;
        }

        // This will start the session if Request::hasPreviousSession() was true
        if (!$session->has($sessionKey)) {
            return null;
        }

        $token = unserialize($session->get($sessionKey), ['allowed_classes' => true]);

        if (!$token instanceof TokenInterface) {
            return null;
        }

        return $token;
    }
}
