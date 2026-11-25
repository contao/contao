<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao\OAuthServerBundle\AuthorizationServer\Repository;

use Contao\OAuthServerBundle\AuthorizationServer\Entity\RefreshToken;
use Contao\OAuthServerBundle\Entity\OAuthServerToken;
use Contao\OAuthServerBundle\Repository\OAuthServerTokenRepository;
use League\OAuth2\Server\Entities\RefreshTokenEntityInterface;
use League\OAuth2\Server\Repositories\RefreshTokenRepositoryInterface;

/**
 * @internal
 */
class RefreshTokenRepository implements RefreshTokenRepositoryInterface
{
    public function __construct(private readonly OAuthServerTokenRepository $tokenRepository)
    {
    }

    public function getNewRefreshToken(): RefreshTokenEntityInterface|null
    {
        return new RefreshToken();
    }

    public function persistNewRefreshToken(RefreshTokenEntityInterface $refreshTokenEntity): void
    {
        $accessToken = $refreshTokenEntity->getAccessToken();

        $this->tokenRepository->add(new OAuthServerToken(
            OAuthServerToken::TYPE_REFRESH,
            $refreshTokenEntity->getIdentifier(),
            $refreshTokenEntity->getExpiryDateTime(),
            $accessToken->getClient()->getIdentifier(),
            $accessToken->getUserIdentifier(),
        ));
    }

    public function revokeRefreshToken(string $tokenId): void
    {
        $this->tokenRepository->revoke(OAuthServerToken::TYPE_REFRESH, $tokenId);
    }

    public function isRefreshTokenRevoked(string $tokenId): bool
    {
        return $this->tokenRepository->isRevoked(OAuthServerToken::TYPE_REFRESH, $tokenId);
    }
}
