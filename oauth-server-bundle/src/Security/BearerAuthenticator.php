<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao\OAuthServerBundle\Security;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Exception\AuthenticationException;
use Symfony\Component\Security\Core\Exception\CustomUserMessageAuthenticationException;
use Symfony\Component\Security\Http\Authenticator\AbstractAuthenticator;
use Symfony\Component\Security\Http\Authenticator\Passport\Badge\UserBadge;
use Symfony\Component\Security\Http\Authenticator\Passport\Passport;
use Symfony\Component\Security\Http\Authenticator\Passport\SelfValidatingPassport;
use Symfony\Component\Security\Http\EntryPoint\AuthenticationEntryPointInterface;

/**
 * @internal
 */
class BearerAuthenticator extends AbstractAuthenticator implements AuthenticationEntryPointInterface
{
    public function __construct(
        private readonly AccessTokenValidator $validator,
        private readonly BearerChallenge $challenge,
    ) {
    }

    public function supports(Request $request): bool
    {
        return 1 === preg_match('/^Bearer\s+\S+/i', (string) $request->headers->get('Authorization'));
    }

    public function authenticate(Request $request): Passport
    {
        $jwt = trim((string) preg_replace('/^Bearer\s+/i', '', (string) $request->headers->get('Authorization')));

        try {
            $claims = $this->validator->validate($jwt);
        } catch (InvalidAccessTokenException $e) {
            throw new CustomUserMessageAuthenticationException($e->getMessage(), [], 0, $e);
        }

        return new SelfValidatingPassport(new UserBadge($claims['user']));
    }

    public function onAuthenticationSuccess(Request $request, TokenInterface $token, string $firewallName): Response|null
    {
        return null;
    }

    public function onAuthenticationFailure(Request $request, AuthenticationException $exception): Response
    {
        return $this->challenge->unauthorized('invalid_token', $exception->getMessageKey());
    }

    public function start(Request $request, AuthenticationException|null $authException = null): Response
    {
        return $this->challenge->unauthorized();
    }
}
