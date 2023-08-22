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
use Doctrine\DBAL\Connection;
use Symfony\Bundle\SecurityBundle\Security\FirewallConfig;
use Symfony\Bundle\SecurityBundle\Security\FirewallMap;
use Symfony\Component\HttpFoundation\Exception\SessionNotFoundException;
use Symfony\Component\HttpFoundation\Request;
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

    private array $previewLinks = [];

    /**
     * @internal
     */
    public function __construct(
        private readonly RequestStack $requestStack,
        private readonly FirewallMapInterface $firewallMap,
        private readonly TokenStorageInterface $tokenStorage,
        private readonly AuthenticationTrustResolverInterface $trustResolver,
        private readonly VoterInterface $roleVoter,
        private readonly Connection $connection,
    ) {
    }

    /**
     * Checks if a front end user is authenticated.
     */
    public function hasFrontendUser(): bool
    {
        $token = $this->getToken(self::FRONTEND_FIREWALL);

        return $token && VoterInterface::ACCESS_GRANTED === $this->roleVoter->vote($token, null, ['ROLE_MEMBER']);
    }

    /**
     * Checks if a back end user is authenticated.
     */
    public function hasBackendUser(): bool
    {
        $token = $this->getToken(self::BACKEND_FIREWALL);

        return $token && VoterInterface::ACCESS_GRANTED === $this->roleVoter->vote($token, null, ['ROLE_USER']);
    }

    /**
     * Checks if a front end guest user is "authenticated".
     */
    public function hasFrontendGuest(): bool
    {
        try {
            $session = $this->requestStack->getSession();
        } catch (SessionNotFoundException) {
            return false;
        }

        if (!$preview = $session->get(FrontendPreviewAuthenticator::SESSION_NAME)) {
            return false;
        }

        return $this->isValidPreviewLink($preview);
    }

    /**
     * Gets the front end username from the session.
     */
    public function getFrontendUsername(): string|null
    {
        if (!$token = $this->getToken(self::FRONTEND_FIREWALL)) {
            return null;
        }

        $user = $token->getUser();

        if (!$user instanceof FrontendUser) {
            return null;
        }

        return $user->getUserIdentifier();
    }

    /**
     * Gets the back end username from the session.
     */
    public function getBackendUsername(): string|null
    {
        if (!$token = $this->getToken(self::BACKEND_FIREWALL)) {
            return null;
        }

        $user = $token->getUser();

        if (!$user instanceof BackendUser) {
            return null;
        }

        return $user->getUserIdentifier();
    }

    /**
     * Tells whether the front end preview can be accessed.
     */
    public function canAccessPreview(): bool
    {
        return $this->hasBackendUser() || $this->hasFrontendGuest();
    }

    /**
     * Tells whether the front end preview can show unpublished fragments.
     */
    public function isPreviewMode(): bool
    {
        $request = $this->requestStack->getCurrentRequest();

        if (!$request || !$request->attributes->get('_preview', false) || !$this->canAccessPreview()) {
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

    public function isFrontendFirewall(): bool
    {
        return self::FRONTEND_FIREWALL === $this->getFirewallContext();
    }

    public function isBackendFirewall(): bool
    {
        return self::BACKEND_FIREWALL === $this->getFirewallContext();
    }

    private function getToken(string $context): TokenInterface|null
    {
        $token = $this->getTokenFromStorage($context);

        if (!$token) {
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
        if ($this->getFirewallContext() !== $context) {
            return null;
        }

        return $this->tokenStorage->getToken();
    }

    private function getFirewallContext(): string|null
    {
        $request = $this->requestStack->getCurrentRequest();

        if (!$this->firewallMap instanceof FirewallMap || !$request) {
            return null;
        }

        $config = $this->firewallMap->getFirewallConfig($request);

        if (!$config instanceof FirewallConfig) {
            return null;
        }

        return $config->getContext();
    }

    private function getTokenFromSession(string $sessionKey): TokenInterface|null
    {
        if ((!$request = $this->requestStack->getCurrentRequest()) || !$request->hasSession()) {
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

    private function isValidPreviewLink(array $token): bool
    {
        if (!isset($token['previewLinkId'])) {
            return false;
        }

        $id = (int) $token['previewLinkId'];

        if (!isset($this->previewLinks[$id])) {
            $this->previewLinks[$id] = $this->connection->fetchAssociative(
                "
                    SELECT
                        url,
                        showUnpublished,
                        restrictToUrl
                    FROM tl_preview_link
                    WHERE
                        id = :id
                        AND published = '1'
                        AND expiresAt > UNIX_TIMESTAMP()
                ",
                ['id' => $id],
            );
        }

        $previewLink = $this->previewLinks[$id];

        if (!$previewLink) {
            return false;
        }

        if ((bool) $previewLink['showUnpublished'] !== (bool) $token['showUnpublished']) {
            return false;
        }

        if (!$previewLink['restrictToUrl']) {
            return true;
        }

        $request = $this->requestStack->getMainRequest();

        return $request && strtok($request->getUri(), '?') === strtok(Request::create($previewLink['url'])->getUri(), '?');
    }
}
