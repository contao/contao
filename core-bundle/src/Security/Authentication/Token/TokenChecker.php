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
use Contao\FrontendUser;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Symfony\Component\Security\Core\Authentication\AuthenticationTrustResolverInterface;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;

class TokenChecker
{
    /**
     * @var SessionInterface
     */
    private $session;

    /**
     * @var AuthenticationTrustResolverInterface
     */
    private $trustResolver;

    /**
     * @param SessionInterface                     $session
     * @param AuthenticationTrustResolverInterface $trustResolveer
     */
    public function __construct(SessionInterface $session, AuthenticationTrustResolverInterface $trustResolveer)
    {
        $this->session = $session;
        $this->trustResolver = $trustResolveer;
    }

    /**
     * Checks if a front end user is authenticated.
     *
     * @return bool
     */
    public function hasFrontendUser(): bool
    {
        $token = $this->getToken(FrontendUser::SECURITY_SESSION_KEY);

        return null !== $token && $token->getUser() instanceof FrontendUser;
    }

    /**
     * Checks if a back end user is authenticated.
     *
     * @return bool
     */
    public function hasBackendUser(): bool
    {
        $token = $this->getToken(BackendUser::SECURITY_SESSION_KEY);

        return null !== $token && $token->getUser() instanceof BackendUser;
    }

    /**
     * Gets the front end username from the session.
     *
     * @return string|null
     */
    public function getFrontendUsername(): ?string
    {
        $token = $this->getToken(FrontendUser::SECURITY_SESSION_KEY);

        if (null === $token || !$token->getUser() instanceof FrontendUser) {
            return null;
        }

        return $token->getUser()->getUsername();
    }

    /**
     * Gets the back end username from the session.
     *
     * @return string|null
     */
    public function getBackendUsername(): ?string
    {
        $token = $this->getToken(BackendUser::SECURITY_SESSION_KEY);

        if (null === $token || !$token->getUser() instanceof BackendUser) {
            return null;
        }

        return $token->getUser()->getUsername();
    }

    /**
     * Tells whether the front end preview can show unpublished fragments.
     *
     * @return bool
     */
    public function isPreviewMode(): bool
    {
        $token = $this->getToken(FrontendUser::SECURITY_SESSION_KEY);

        return $token instanceof FrontendPreviewToken && $token->showUnpublished();
    }

    /**
     * Gets the token from the session storage.
     *
     * @param string $sessionKey
     *
     * @return TokenInterface|null
     */
    private function getToken(string $sessionKey): ?TokenInterface
    {
        if (!$this->session->isStarted() || !$this->session->has($sessionKey)) {
            return null;
        }

        $token = unserialize($this->session->get($sessionKey), ['allowed_classes' => true]);

        if (!$token instanceof TokenInterface || !$token->isAuthenticated()) {
            return null;
        }

        if ($this->trustResolver->isAnonymous($token)) {
            return null;
        }

        return $token;
    }
}
