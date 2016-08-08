<?php

/*
 * This file is part of Contao.
 *
 * Copyright (c) 2005-2016 Leo Feyer
 *
 * @license LGPL-3.0+
 */

namespace Contao\CoreBundle\Security;

use Contao\CoreBundle\Framework\ScopeAwareTrait;
use Contao\CoreBundle\Security\Authentication\ContaoToken;
use Contao\User;
use Symfony\Component\DependencyInjection\ContainerAwareInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Security\Core\Authentication\Token\AnonymousToken;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Exception\AuthenticationException;
use Symfony\Component\Security\Core\Exception\UsernameNotFoundException;
use Symfony\Component\Security\Core\User\UserProviderInterface;
use Symfony\Component\Security\Http\Authentication\SimplePreAuthenticatorInterface;

/**
 * Authenticates a Contao token.
 *
 * @author Andreas Schempp <https://github.com/aschempp>
 */
class ContaoAuthenticator implements ContainerAwareInterface, SimplePreAuthenticatorInterface
{
    use ScopeAwareTrait;

    /**
     * Creates an authentication token.
     *
     * @param Request $request
     * @param string  $providerKey
     *
     * @return AnonymousToken
     */
    public function createToken(Request $request, $providerKey)
    {
        return new AnonymousToken($providerKey, 'anon.');
    }

    /**
     * Authenticates a token.
     *
     * @param TokenInterface        $token
     * @param UserProviderInterface $userProvider
     * @param string                $providerKey
     *
     * @return TokenInterface|ContaoToken|AnonymousToken
     *
     * @throws AuthenticationException
     */
    public function authenticateToken(TokenInterface $token, UserProviderInterface $userProvider, $providerKey)
    {
        if ($this->canSkipAuthentication($token)) {
            return $token;
        }

        if (!($token instanceof AnonymousToken)) {
            throw new AuthenticationException('The ContaoAuthenticator can only handle AnonymousToken.');
        }

        try {
            $user = $userProvider->loadUserByUsername($token->getSecret());

            if ($user instanceof User) {
                return new ContaoToken($user);
            }
        } catch (UsernameNotFoundException $e) {
            // ignore and return the original token
        }

        return $token;
    }

    /**
     * Checks if the token is supported.
     *
     * @param TokenInterface $token
     * @param string         $providerKey
     *
     * @return bool
     */
    public function supportsToken(TokenInterface $token, $providerKey)
    {
        return $token instanceof ContaoToken || $token instanceof AnonymousToken;
    }

    /**
     * Checks if the authentication can be skipped.
     *
     * @param TokenInterface $token
     *
     * @return bool
     *
     * @throws \LogicException
     */
    private function canSkipAuthentication(TokenInterface $token)
    {
        if ($token instanceof ContaoToken) {
            return true;
        }

        if (null === $this->container) {
            throw new \LogicException('The service container has not been set.');
        }

        return !$this->isContaoScope();
    }
}
