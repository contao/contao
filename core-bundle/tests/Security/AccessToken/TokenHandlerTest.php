<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao\CoreBundle\Tests\Security\AccessToken;

use Contao\CoreBundle\Security\AccessToken\AccessTokenManager;
use Contao\CoreBundle\Security\AccessToken\TokenHandler;
use Contao\CoreBundle\Tests\TestCase;
use Contao\StringUtil;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Types\Types;
use PHPUnit\Framework\Attributes\DataProvider;
use Symfony\Bridge\Doctrine\Types\UuidType;
use Symfony\Component\Security\Core\Exception\BadCredentialsException;
use Symfony\Component\Uid\Uuid;

class TokenHandlerTest extends TestCase
{
    public function testThrowsBadCredentialsExceptionWithInvalidToken(): void
    {
        $token = 'cto_pat_123_456';

        $accessTokenManager = $this->createMock(AccessTokenManager::class);
        $accessTokenManager
            ->expects($this->once())
            ->method('parseToken')
            ->with($token)
            ->willReturn(null)
        ;

        $accessTokenHandler = new TokenHandler($accessTokenManager, $this->createStub(Connection::class), 'backend');

        $this->expectException(BadCredentialsException::class);

        $accessTokenHandler->getUserBadgeFrom($token);
    }

    #[DataProvider('scopeProvider')]
    public function testAuthenticatesToken(string $scope, string|null $table): void
    {
        if (null === $table) {
            $this->expectException(\RuntimeException::class);
        }

        $uuid = Uuid::v4()->toBase32();
        $secret = StringUtil::encodeBase32(random_bytes(16));
        $token = AccessTokenManager::TOKEN_PREFIX.$uuid.'_'.$secret;

        $accessTokenManager = $this->createMock(AccessTokenManager::class);
        $accessTokenManager
            ->expects($this->once())
            ->method('parseToken')
            ->with($token)
            ->willReturn([
                'id' => $uuid,
                'secret' => $secret,
            ])
        ;

        $accessTokenManager
            ->expects(null === $table ? $this->never() : $this->once())
            ->method('verifySecret')
            ->with('1234', $secret)
            ->willReturn(true)
        ;

        $connection = $this->createMock(Connection::class);
        $connection
            ->expects(null === $table ? $this->never() : $this->once())
            ->method('fetchAssociative')
            ->with(
                "SELECT t.*, u.username FROM PersonalAccessToken t INNER JOIN $table u ON t.userId=u.id WHERE t.scope=? AND t.id=?",
                $this->callback(static fn ($args) => $scope === $args[0] && $args[1] instanceof Uuid && $args[1]->toBase32() === $uuid),
                [Types::STRING, UuidType::NAME],
            )
            ->willReturn([
                'username' => 'k.jones',
                'expiresAt' => new \DateTimeImmutable('+1 month')->format('c'),
                'secret' => '1234',
            ])
        ;

        $accessTokenHandler = new TokenHandler(
            $accessTokenManager,
            $connection,
            $scope,
        );

        $badge = $accessTokenHandler->getUserBadgeFrom($token);

        $this->assertSame('k.jones', $badge->getUserIdentifier());
    }

    #[DataProvider('scopeProvider')]
    public function testThrowsExceptionIfTokenIsNotFoundInDatabase(string $scope, string|null $table): void
    {
        if (null === $table) {
            $this->expectException(\RuntimeException::class);
        } else {
            $this->expectException(BadCredentialsException::class);
            $this->expectExceptionMessageIs('Invalid access token.');
        }

        $uuid = Uuid::v4()->toBase32();
        $secret = StringUtil::encodeBase32(random_bytes(16));
        $token = AccessTokenManager::TOKEN_PREFIX.$uuid.'_'.$secret;

        $accessTokenManager = $this->createMock(AccessTokenManager::class);
        $accessTokenManager
            ->expects($this->once())
            ->method('parseToken')
            ->with($token)
            ->willReturn([
                'id' => $uuid,
                'secret' => $secret,
            ])
        ;

        $connection = $this->createMock(Connection::class);
        $connection
            ->expects(null === $table ? $this->never() : $this->once())
            ->method('fetchAssociative')
            ->with(
                "SELECT t.*, u.username FROM PersonalAccessToken t INNER JOIN $table u ON t.userId=u.id WHERE t.scope=? AND t.id=?",
                $this->callback(static fn ($args) => $scope === $args[0] && $args[1] instanceof Uuid && $args[1]->toBase32() === $uuid),
                [Types::STRING, UuidType::NAME],
            )
            ->willReturn(false)
        ;

        $accessTokenHandler = new TokenHandler(
            $accessTokenManager,
            $connection,
            $scope,
        );

        $badge = $accessTokenHandler->getUserBadgeFrom($token);

        $this->assertSame('k.jones', $badge->getUserIdentifier());
    }

    #[DataProvider('scopeProvider')]
    public function testThrowsExceptionIfTokenIsExpired(string $scope, string|null $table): void
    {
        if (null === $table) {
            $this->expectException(\RuntimeException::class);
        } else {
            $this->expectException(BadCredentialsException::class);
            $this->expectExceptionMessageIs('Access token is expired.');
        }

        $uuid = Uuid::v4()->toBase32();
        $secret = StringUtil::encodeBase32(random_bytes(16));
        $token = AccessTokenManager::TOKEN_PREFIX.$uuid.'_'.$secret;

        $accessTokenManager = $this->createMock(AccessTokenManager::class);
        $accessTokenManager
            ->expects($this->once())
            ->method('parseToken')
            ->with($token)
            ->willReturn([
                'id' => $uuid,
                'secret' => $secret,
            ])
        ;

        $connection = $this->createMock(Connection::class);
        $connection
            ->expects(null === $table ? $this->never() : $this->once())
            ->method('fetchAssociative')
            ->with(
                "SELECT t.*, u.username FROM PersonalAccessToken t INNER JOIN $table u ON t.userId=u.id WHERE t.scope=? AND t.id=?",
                $this->callback(static fn ($args) => $scope === $args[0] && $args[1] instanceof Uuid && $args[1]->toBase32() === $uuid),
                [Types::STRING, UuidType::NAME],
            )
            ->willReturn([
                'username' => 'k.jones',
                'expiresAt' => new \DateTimeImmutable('-1 month')->format('c'),
                'secret' => '1234',
            ])
        ;

        $accessTokenHandler = new TokenHandler(
            $accessTokenManager,
            $connection,
            $scope,
        );

        $badge = $accessTokenHandler->getUserBadgeFrom($token);

        $this->assertSame('k.jones', $badge->getUserIdentifier());
    }

    #[DataProvider('scopeProvider')]
    public function testThrowsExceptionIfTokenVerificationFails(string $scope, string|null $table): void
    {
        if (null === $table) {
            $this->expectException(\RuntimeException::class);
        } else {
            $this->expectException(BadCredentialsException::class);
            $this->expectExceptionMessageIs('Invalid credentials.');
        }

        $uuid = Uuid::v4()->toBase32();
        $secret = StringUtil::encodeBase32(random_bytes(16));
        $token = AccessTokenManager::TOKEN_PREFIX.$uuid.'_'.$secret;

        $accessTokenManager = $this->createMock(AccessTokenManager::class);
        $accessTokenManager
            ->expects($this->once())
            ->method('parseToken')
            ->with($token)
            ->willReturn([
                'id' => $uuid,
                'secret' => $secret,
            ])
        ;

        $accessTokenManager
            ->expects(null === $table ? $this->never() : $this->once())
            ->method('verifySecret')
            ->with('1234', $secret)
            ->willReturn(false)
        ;

        $connection = $this->createMock(Connection::class);
        $connection
            ->expects(null === $table ? $this->never() : $this->once())
            ->method('fetchAssociative')
            ->with(
                "SELECT t.*, u.username FROM PersonalAccessToken t INNER JOIN $table u ON t.userId=u.id WHERE t.scope=? AND t.id=?",
                $this->callback(static fn ($args) => $scope === $args[0] && $args[1] instanceof Uuid && $args[1]->toBase32() === $uuid),
                [Types::STRING, UuidType::NAME],
            )
            ->willReturn([
                'username' => 'k.jones',
                'expiresAt' => new \DateTimeImmutable('+1 month')->format('c'),
                'secret' => '1234',
            ])
        ;

        $accessTokenHandler = new TokenHandler(
            $accessTokenManager,
            $connection,
            $scope,
        );

        $badge = $accessTokenHandler->getUserBadgeFrom($token);

        $this->assertSame('k.jones', $badge->getUserIdentifier());
    }

    public static function scopeProvider(): iterable
    {
        yield ['backend', 'tl_user'];

        yield ['frontend', 'tl_member'];

        yield ['foobar', null];
    }
}
