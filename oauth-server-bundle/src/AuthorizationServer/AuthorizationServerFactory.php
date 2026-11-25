<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao\OAuthServerBundle\AuthorizationServer;

use Contao\OAuthServerBundle\AuthorizationServer\Repository\AccessTokenRepository;
use Contao\OAuthServerBundle\AuthorizationServer\Repository\AuthCodeRepository;
use Contao\OAuthServerBundle\AuthorizationServer\Repository\ClientRepository;
use Contao\OAuthServerBundle\AuthorizationServer\Repository\RefreshTokenRepository;
use Contao\OAuthServerBundle\AuthorizationServer\Repository\ScopeRepository;
use Contao\OAuthServerBundle\KeyProvider;
use League\OAuth2\Server\AuthorizationServer;
use League\OAuth2\Server\Grant\AuthCodeGrant;
use League\OAuth2\Server\Grant\RefreshTokenGrant;

/**
 * @internal
 */
class AuthorizationServerFactory
{
    public function __construct(
        private readonly ClientRepository $clients,
        private readonly AccessTokenRepository $accessTokens,
        private readonly ScopeRepository $scopes,
        private readonly AuthCodeRepository $authCodes,
        private readonly RefreshTokenRepository $refreshTokens,
        private readonly KeyProvider $keys,
        private readonly string $accessTokenTtl = 'PT1H',
        private readonly string $refreshTokenTtl = 'P30D',
    ) {
    }

    public function create(): AuthorizationServer
    {
        $server = new AuthorizationServer(
            $this->clients,
            $this->accessTokens,
            $this->scopes,
            $this->keys->getSigningKey(),
            $this->keys->getEncryptionKey(),
        );

        $authCode = new AuthCodeGrant($this->authCodes, $this->refreshTokens, new \DateInterval('PT10M'));
        $authCode->setRefreshTokenTTL(new \DateInterval($this->refreshTokenTtl));

        $server->enableGrantType($authCode, new \DateInterval($this->accessTokenTtl));

        // Refresh token rotation is on by default (old refresh token is revoked)
        $refresh = new RefreshTokenGrant($this->refreshTokens);
        $refresh->setRefreshTokenTTL(new \DateInterval($this->refreshTokenTtl));

        $server->enableGrantType($refresh, new \DateInterval($this->accessTokenTtl));
        $server->setDefaultScope($this->scopes->getDefaultScope());

        return $server;
    }
}
