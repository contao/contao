<?php

/*
 * This file is part of Contao.
 *
 * Copyright (c) 2005-2015 Leo Feyer
 *
 * @license LGPL-3.0+
 */

namespace Contao\CoreBundle\Security;

use Contao\CoreBundle\Security\Authentication\ContaoToken;
use Contao\User;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Security\Core\Authentication\SimplePreAuthenticatorInterface;
use Symfony\Component\Security\Core\Authentication\Token\AnonymousToken;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Exception\AuthenticationException;
use Symfony\Component\Security\Core\Exception\UsernameNotFoundException;
use Symfony\Component\Security\Core\User\UserProviderInterface;

/**
 * Authenticates a Contao token.
 *
 * @author Andreas Schempp <https://github.com/aschempp>
 */
class ContaoAuthenticator implements SimplePreAuthenticatorInterface
{
    /**
     * Creates an authentication token.
     *
     * @param Request $request     The request object
     * @param string  $providerKey The provider key
     *
     * @return AnonymousToken The token object
     */
    public function createToken(Request $request, $providerKey)
    {
        return new AnonymousToken($providerKey, 'anon.');
    }

    /**
     * Authenticates a token.
     *
     * @param TokenInterface        $token        The token object
     * @param UserProviderInterface $userProvider The user provider object
     * @param string                $providerKey  The provider key
     *
     * @return ContaoToken|TokenInterface The token object
     *
     * @throws AuthenticationException If the token cannot be handled
     */
    public function authenticateToken(TokenInterface $token, UserProviderInterface $userProvider, $providerKey)
    {
        if ($token instanceof ContaoToken) {
            return $token;
        }

        if (!$token instanceof AnonymousToken) {
            throw new AuthenticationException('The ContaoAuthenticator can only handle AnonymousToken.');
        }

        try {
            $user = $userProvider->loadUserByUsername($token->getKey());

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
     * @param TokenInterface $token       The token object
     * @param string         $providerKey The provider key
     *
     * @return bool True if the token is supported
     */
    public function supportsToken(TokenInterface $token, $providerKey)
    {
        return $token instanceof ContaoToken || $token instanceof AnonymousToken;
    }
}
