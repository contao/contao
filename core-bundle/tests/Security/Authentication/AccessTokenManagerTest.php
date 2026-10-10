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
use Contao\CoreBundle\Security\AccessToken\AccessTokenManager;
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
            ->method('persist')
            ->willReturnCallback(
                function (PersonalAccessToken $personalAccessToken) {
                    $this->assertSame('hashed-secret', $personalAccessToken->getSecret());
                    $this->assertNull($personalAccessToken->getExpiresAt());
                    $this->assertNull($personalAccessToken->getLastUsed());

                    return true;
                },
            )
        ;

        $entityManager
            ->expects($this->once())
            ->method('flush')
        ;

        $accessTokenManager = new AccessTokenManager(
            $passwordHasherFactory,
            $entityManager,
        );

        $plainToken = $accessTokenManager->createBackendToken($user, 'foobar');

        $this->assertStringStartsWith(AccessTokenManager::TOKEN_PREFIX, $plainToken);
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
}
