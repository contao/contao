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

use Contao\CoreBundle\ContaoCoreBundle;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Types\Types;
use Symfony\Bridge\Doctrine\Types\UuidType;
use Symfony\Component\Security\Core\Exception\BadCredentialsException;
use Symfony\Component\Security\Http\AccessToken\AccessTokenHandlerInterface;
use Symfony\Component\Security\Http\Authenticator\Passport\Badge\UserBadge;
use Symfony\Component\Uid\Uuid;

class TokenHandler implements AccessTokenHandlerInterface
{
    public function __construct(
        private readonly AccessTokenManager $accessTokenManager,
        private readonly Connection $connection,
        private readonly string $scope,
    ) {
    }

    public function getUserBadgeFrom(string $accessToken): UserBadge
    {
        $parsedToken = $this->accessTokenManager->parseToken($accessToken);

        if (!$parsedToken) {
            throw new BadCredentialsException('Invalid access token.');
        }

        $table = match ($this->scope) {
            ContaoCoreBundle::SCOPE_BACKEND => 'tl_user',
            ContaoCoreBundle::SCOPE_FRONTEND => 'tl_member',
            default => throw new \RuntimeException('Unsupported scope '.$this->scope),
        };

        // Cannot use Doctrine ORM when joining Contao table
        $record = $this->connection->fetchAssociative(
            "SELECT t.*, u.username FROM PersonalAccessToken t INNER JOIN $table u ON t.userId=u.id WHERE t.scope=? AND t.id=?",
            [$this->scope, Uuid::fromString($parsedToken['id'])],
            [Types::STRING, UuidType::NAME],
        );

        if (false === $record) {
            throw new BadCredentialsException('Invalid access token.');
        }

        if (null !== $record['expiresAt'] && strtotime($record['expiresAt']) < time()) {
            throw new BadCredentialsException('Access token is expired.');
        }

        if (!$this->accessTokenManager->verifySecret($record['secret'], $parsedToken['secret'])) {
            throw new BadCredentialsException('Invalid credentials.');
        }

        return new UserBadge($record['username'], attributes: ['access_token_id' => $record['id']]);
    }
}
