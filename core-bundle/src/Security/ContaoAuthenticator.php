<?php

/**
 * Contao Open Source CMS
 *
 * Copyright (c) 2005-2014 Leo Feyer
 *
 * @license LGPL-3.0+
 */

namespace Contao\CoreBundle\Security;

use Contao\CoreBundle\Security\Authentication\ContaoToken;
use Contao\CoreBundle\Security\User\ContaoUserProvider;
use Symfony\Component\Security\Core\Authentication\SimplePreAuthenticatorInterface;
use Symfony\Component\Security\Core\Authentication\Token\AnonymousToken;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Security\Core\Exception\AuthenticationException;
use Symfony\Component\Security\Core\User\UserProviderInterface;

class ContaoAuthenticator implements SimplePreAuthenticatorInterface
{
    protected $userProvider;

    public function __construct(ContaoUserProvider $userProvider)
    {
        $this->userProvider = $userProvider;
    }

    public function createToken(Request $request, $providerKey)
    {
        return new AnonymousToken(
            $providerKey,
            'guest'
        );
    }

    public function authenticateToken(TokenInterface $token, UserProviderInterface $userProvider, $providerKey)
    {
        if ($token instanceof ContaoToken) {
            return $token;
        } elseif (!$token instanceof AnonymousToken) {
            throw new AuthenticationException('ContaoAuthenticator can only handle AnonymousToken');
        }

        $providerKey = $token->getKey();
        $user        = $this->userProvider->loadUserByUsername($providerKey);

        return new ContaoToken($user);
    }

    public function supportsToken(TokenInterface $token, $providerKey)
    {
        return $token instanceof ContaoToken || $token instanceof AnonymousToken;
    }
}
