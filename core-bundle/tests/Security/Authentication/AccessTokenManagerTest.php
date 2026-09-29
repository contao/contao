<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao\CoreBundle\Tests\Security\Authentication;

use Contao\BackendUser;
use Contao\CoreBundle\Entity\PersonalAccessToken;
use Contao\CoreBundle\Repository\PersonalAccessTokenRepository;
use Contao\CoreBundle\Security\Authentication\AccessTokenManager;
use Contao\CoreBundle\Tests\TestCase;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\PasswordHasher\Hasher\PasswordHasherFactoryInterface;
use Symfony\Component\PasswordHasher\PasswordHasherInterface;

class AccessTokenManagerTest extends TestCase
{
    public function testCreatesPersonalAccessTokenWithHashedSecret(): void
    {
        $user = $this->createClassWithPropertiesStub(BackendUser::class);
        $user->id = 42;

        $passwordHasher = $this->createMock(PasswordHasherInterface::class);
        $passwordHasher
            ->expects($this->once())
            ->method('hash')
            ->willReturn('hashed-secret')
        ;

        $passwordHasherFactory = $this->createMock(PasswordHasherFactoryInterface::class);
        $passwordHasherFactory
            ->expects($this->once())
            ->method('getPasswordHasher')
            ->willReturn($passwordHasher)
        ;

        $entityManager = $this->createMock(EntityManagerInterface::class);
        $entityManager
            ->expects($this->once())
            ->method('persist')
            ->with($this->callback(
                static fn (PersonalAccessToken $personalAccessToken): bool => true,
            ))
        ;

        $entityManager
            ->expects($this->once())
            ->method('flush')
        ;

        $entityManager
            ->expects($this->once())
            ->method('persist')
        ;

        $accessTokenManager = new AccessTokenManager(
            $passwordHasherFactory,
            $entityManager,
        );

        $personalAccessToken = $accessTokenManager->createToken($user, 'foobar');

        $this->assertStringStartsWith(AccessTokenManager::TOKEN_PREFIX, $personalAccessToken->getPlainToken());
        $this->assertSame('hashed-secret', $personalAccessToken->getSecret());
        $this->assertNull($personalAccessToken->getExpiresAt());
        $this->assertNull($personalAccessToken->getLastUsed());
    }

    public function testParserSkipsTokensWithWrongFormats(): void
    {
        $accessTokenManager = new AccessTokenManager(
            $this->createStub(PasswordHasherFactoryInterface::class),
            $this->createStub(EntityManagerInterface::class),
        );

        $this->assertNull($accessTokenManager->parseToken('foobar'));
        $this->assertNull($accessTokenManager->parseToken(AccessTokenManager::TOKEN_PREFIX.'foo'));
    }

    public function testParsesToken(): void
    {
        $accessTokenManager = new AccessTokenManager(
            $this->createStub(PasswordHasherFactoryInterface::class),
            $this->createStub(EntityManagerInterface::class),
        );

        $parsedToken = $accessTokenManager->parseToken(AccessTokenManager::TOKEN_PREFIX.'foo_bar');

        $this->assertSame(['id' => 'foo', 'secret' => 'bar'], $parsedToken);
    }

    public function testSkipsInvalidTokens(): void
    {
        $accessTokenManager = new AccessTokenManager(
            $this->createStub(PasswordHasherFactoryInterface::class),
            $this->createStub(EntityManagerInterface::class),
        );

        $this->assertNull($accessTokenManager->getValidPersonalAccessToken('foobar'));
    }

    public function testSkipsInvalidPersonalAccessTokenRecord(): void
    {
        $personalAccessTokenRepository = $this->createMock(PersonalAccessTokenRepository::class);
        $personalAccessTokenRepository
            ->expects($this->once())
            ->method('findOneValidById')
            ->willReturn(null)
        ;

        $entityManager = $this->createMock(EntityManagerInterface::class);
        $entityManager
            ->expects($this->once())
            ->method('getRepository')
            ->willReturn($personalAccessTokenRepository)
        ;

        $accessTokenManager = new AccessTokenManager(
            $this->createStub(PasswordHasherFactoryInterface::class),
            $entityManager,
        );

        $this->assertNull($accessTokenManager->getValidPersonalAccessToken(AccessTokenManager::TOKEN_PREFIX.'foo_bar'));
    }

    public function testSkipsPersonalAccessTokenRecordsWithUnverifiedSecret(): void
    {
        $personalAccessToken = $this->createMock(PersonalAccessToken::class);
        $personalAccessToken
            ->expects($this->once())
            ->method('getSecret')
            ->willReturn('hashed-secret')
        ;

        $personalAccessTokenRepository = $this->createMock(PersonalAccessTokenRepository::class);
        $personalAccessTokenRepository
            ->expects($this->once())
            ->method('findOneValidById')
            ->willReturn($personalAccessToken)
        ;

        $entityManager = $this->createMock(EntityManagerInterface::class);
        $entityManager
            ->expects($this->once())
            ->method('getRepository')
            ->willReturn($personalAccessTokenRepository)
        ;

        $passwordHasher = $this->createMock(PasswordHasherInterface::class);
        $passwordHasher
            ->expects($this->once())
            ->method('verify')
            ->with('hashed-secret', 'bar')
            ->willReturn(false)
        ;

        $passwordHasherFactory = $this->createMock(PasswordHasherFactoryInterface::class);
        $passwordHasherFactory
            ->expects($this->once())
            ->method('getPasswordHasher')
            ->willReturn($passwordHasher)
        ;

        $accessTokenManager = new AccessTokenManager(
            $passwordHasherFactory,
            $entityManager,
        );

        $this->assertNull($accessTokenManager->getValidPersonalAccessToken(AccessTokenManager::TOKEN_PREFIX.'foo_bar'));
    }

    public function testReturnsVerifiedPersonalAccessToken(): void
    {
        $personalAccessToken = $this->createMock(PersonalAccessToken::class);
        $personalAccessToken
            ->expects($this->once())
            ->method('getSecret')
            ->willReturn('hashed-secret')
        ;

        $personalAccessTokenRepository = $this->createMock(PersonalAccessTokenRepository::class);
        $personalAccessTokenRepository
            ->expects($this->once())
            ->method('findOneValidById')
            ->willReturn($personalAccessToken)
        ;

        $entityManager = $this->createMock(EntityManagerInterface::class);
        $entityManager
            ->expects($this->once())
            ->method('getRepository')
            ->willReturn($personalAccessTokenRepository)
        ;

        $passwordHasher = $this->createMock(PasswordHasherInterface::class);
        $passwordHasher
            ->expects($this->once())
            ->method('verify')
            ->with('hashed-secret', 'bar')
            ->willReturn(true)
        ;

        $passwordHasherFactory = $this->createMock(PasswordHasherFactoryInterface::class);
        $passwordHasherFactory
            ->expects($this->once())
            ->method('getPasswordHasher')
            ->willReturn($passwordHasher)
        ;

        $accessTokenManager = new AccessTokenManager(
            $passwordHasherFactory,
            $entityManager,
        );

        $this->assertSame($personalAccessToken, $accessTokenManager->getValidPersonalAccessToken(AccessTokenManager::TOKEN_PREFIX.'foo_bar'));
    }
}
