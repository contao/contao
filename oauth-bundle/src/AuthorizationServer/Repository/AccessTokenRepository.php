<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao\OAuthBundle\AuthorizationServer\Repository;

use Contao\OAuthBundle\AuthorizationServer\Entity\AccessToken;
use Contao\OAuthBundle\Entity\OAuthToken;
use Contao\OAuthBundle\Repository\OAuthTokenRepository;
use Contao\OAuthBundle\ResourceContext;
use League\OAuth2\Server\Entities\AccessTokenEntityInterface;
use League\OAuth2\Server\Entities\ClientEntityInterface;
use League\OAuth2\Server\Repositories\AccessTokenRepositoryInterface;

/**
 * @internal
 */
class AccessTokenRepository implements AccessTokenRepositoryInterface
{
    public function __construct(
        private readonly OAuthTokenRepository $tokenRepository,
        private readonly ResourceContext $context,
    ) {
    }

    public function getNewToken(ClientEntityInterface $clientEntity, array $scopes, string|null $userIdentifier = null): AccessTokenEntityInterface
    {
        $token = new AccessToken($this->context->getIssuer(), $this->context->getResource());
        $token->setClient($clientEntity);

        foreach ($scopes as $scope) {
            $token->addScope($scope);
        }

        if (null !== $userIdentifier) {
            $token->setUserIdentifier($userIdentifier);
        }

        return $token;
    }

    public function persistNewAccessToken(AccessTokenEntityInterface $accessTokenEntity): void
    {
        $this->tokenRepository->add(new OAuthToken(
            OAuthToken::TYPE_ACCESS,
            $accessTokenEntity->getIdentifier(),
            $accessTokenEntity->getExpiryDateTime(),
            $accessTokenEntity->getClient()->getIdentifier(),
            $accessTokenEntity->getUserIdentifier(),
        ));
    }

    public function revokeAccessToken(string $tokenId): void
    {
        $this->tokenRepository->revoke(OAuthToken::TYPE_ACCESS, $tokenId);
    }

    public function isAccessTokenRevoked(string $tokenId): bool
    {
        return $this->tokenRepository->isRevoked(OAuthToken::TYPE_ACCESS, $tokenId);
    }
}
