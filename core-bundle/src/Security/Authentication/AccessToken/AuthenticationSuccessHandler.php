<?php

declare(strict_types=1);

namespace Contao\CoreBundle\Security\Authentication\AccessToken;

use Contao\CoreBundle\Security\Authentication\Token\TokenChecker;
use Contao\User;
use Scheb\TwoFactorBundle\Security\Authentication\Token\TwoFactorTokenInterface;
use Scheb\TwoFactorBundle\Security\Http\Authenticator\TwoFactorAuthenticator;
use Symfony\Component\HttpFoundation\Exception\SessionNotFoundException;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Exception\UserNotFoundException;
use Symfony\Component\Security\Http\Authentication\AuthenticationSuccessHandlerInterface;

class AuthenticationSuccessHandler implements AuthenticationSuccessHandlerInterface
{
    public function __construct(
        private readonly TokenStorageInterface $tokenStorage,
        private readonly TokenChecker $tokenChecker,
        private readonly RequestStack $requestStack,
    ) {
    }

    public function onAuthenticationSuccess(Request $request, TokenInterface $token): Response
    {
        $user = $token->getUser();

        if (!$user instanceof User) {
            throw new UserNotFoundException('User not found');
        }

        // Skip 2FA for access token authentication
        if ($token instanceof TwoFactorTokenInterface) {
            $authenticatedToken = $token->getAuthenticatedToken();
            $authenticatedToken->setAttribute(TwoFactorAuthenticator::FLAG_2FA_COMPLETE, true);

            if ($this->tokenChecker->isFrontendFirewall()) {
                $this->tokenStorage->setToken($authenticatedToken);
            } else {
                $this->getSession()?->set('_security_contao_frontend', serialize($authenticatedToken));
            }
        }

        return new Response();
    }

    private function getSession(): SessionInterface|null
    {
        try {
            return $this->requestStack->getSession();
        } catch (SessionNotFoundException) {
            return null;
        }
    }
}
