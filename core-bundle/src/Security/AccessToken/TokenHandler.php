<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao\CoreBundle\Security\AccessToken;

use Contao\CoreBundle\Security\User\ContaoUserProvider;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Security\Core\Exception\BadCredentialsException;
use Symfony\Component\Security\Http\AccessToken\AccessTokenHandlerInterface;
use Symfony\Component\Security\Http\Authenticator\Passport\Badge\UserBadge;

class TokenHandler implements AccessTokenHandlerInterface
{
    public function __construct(
        private readonly AccessTokenManager $accessTokenManager,
        private readonly ContaoUserProvider $userProvider,
        private readonly RequestStack $requestStack,
        private readonly string $scope,
    ) {
    }

    public function getUserBadgeFrom(string $accessToken): UserBadge
    {
        $token = $this->accessTokenManager->parseAndValidateToken($accessToken, $this->scope);

        if (!$token) {
            throw new BadCredentialsException('Invalid access token.');
        }

        $this->requestStack->getCurrentRequest()->attributes->set('_access_token', $token);

        return new UserBadge(AccessTokenManager::TOKEN_PREFIX.$token->getId()->toBase32(), fn () => $this->userProvider->loadUserById($token->getUserId()));
    }
}
