<?php

declare(strict_types=1);

namespace Contao\CoreBundle\Security\Authentication\AccessToken;

use Contao\User;
use League\Uri\Modifier;
use Scheb\TwoFactorBundle\Security\Authentication\Token\TwoFactorTokenInterface;
use Scheb\TwoFactorBundle\Security\Http\Authenticator\TwoFactorAuthenticator;
use Symfony\Component\HttpFoundation\Exception\SessionNotFoundException;
use Symfony\Component\HttpFoundation\RedirectResponse;
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

            $this->tokenStorage->setToken($authenticatedToken);

            if ($request->query->has('access_token')) {
                $redirect = Modifier::from($request->getRequestUri())->removeQueryParameters('access_token');

                return new RedirectResponse((string) $redirect);
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
