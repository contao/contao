<?php

/**
 * Contao Open Source CMS
 *
 * Copyright (c) 2005-2014 Leo Feyer
 *
 * @license LGPL-3.0+
 */

namespace Contao\CoreBundle\Security;

use Contao\CoreBundle\Security\User\BackendUserProvider;
use Symfony\Component\Security\Core\Authentication\SimplePreAuthenticatorInterface;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authentication\Token\PreAuthenticatedToken;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Security\Core\User\UserProviderInterface;

class BackendAuthenticator implements SimplePreAuthenticatorInterface
{
    protected $userProvider;

    public function __construct(BackendUserProvider $userProvider)
    {
        $this->userProvider = $userProvider;
    }

    public function createToken(Request $request, $providerKey)
    {
        $cookie = $request->cookies->get('BE_USER_AUTH');

        if (!$cookie) {
            return null;
        }

        return new PreAuthenticatedToken(
            'anon.',
            $cookie,
            $providerKey
        );
    }

    public function authenticateToken(TokenInterface $token, UserProviderInterface $userProvider, $providerKey)
    {
        $cookie = $token->getCredentials();
        $user = $this->userProvider->loadUserByUsername('backend');

        return new PreAuthenticatedToken(
            $user,
            $cookie,
            $providerKey,
            $user->getRoles()
        );
    }

    public function supportsToken(TokenInterface $token, $providerKey)
    {
        return $token instanceof PreAuthenticatedToken && $token->getProviderKey() === $providerKey;
    }
}
