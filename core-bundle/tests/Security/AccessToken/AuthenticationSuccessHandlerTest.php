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

use Contao\CoreBundle\Entity\PersonalAccessToken;
use Contao\CoreBundle\Security\AccessToken\AuthenticationSuccessHandler;
use Contao\CoreBundle\Tests\TestCase;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\EntityRepository;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;

class AuthenticationSuccessHandlerTest extends TestCase
{
    public function testUpdatesLastUsedAndPersistsPersonalAccessToken(): void
    {
        $personalAccessToken = new PersonalAccessToken('backend', 42, 'foobar', 'hashed-secret');
        $personalAccessToken->setLastUsed(new \DateTimeImmutable('2000-01-01'));

        $before = new \DateTimeImmutable();

        $token = $this->createMock(TokenInterface::class);
        $token
            ->expects($this->once())
            ->method('hasAttribute')
            ->with('access_token_id')
            ->willReturn(true)
        ;

        $token
            ->expects($this->once())
            ->method('getAttribute')
            ->with('access_token_id')
            ->willReturn('uuid')
        ;

        $repository = $this->createMock(EntityRepository::class);
        $repository
            ->expects($this->once())
            ->method('find')
            ->with('uuid')
            ->willReturn($personalAccessToken)
        ;

        $entityManager = $this->createMock(EntityManagerInterface::class);
        $entityManager
            ->expects($this->once())
            ->method('getRepository')
            ->with(PersonalAccessToken::class)
            ->willReturn($repository)
        ;

        $entityManager
            ->expects($this->once())
            ->method('persist')
            ->with($this->identicalTo($personalAccessToken))
        ;

        $entityManager
            ->expects($this->once())
            ->method('flush')
        ;

        $handler = new AuthenticationSuccessHandler($entityManager);
        $handler->onAuthenticationSuccess(new Request(), $token);

        $this->assertInstanceOf(\DateTimeImmutable::class, $personalAccessToken->getLastUsed());
        $this->assertGreaterThanOrEqual($before, $personalAccessToken->getLastUsed());
        $this->assertLessThanOrEqual(new \DateTimeImmutable(), $personalAccessToken->getLastUsed());
    }

    public function testIgnoresInvalidPersonalAccessToken(): void
    {
        $token = $this->createMock(TokenInterface::class);
        $token
            ->expects($this->once())
            ->method('hasAttribute')
            ->with('access_token_id')
            ->willReturn(true)
        ;

        $token
            ->expects($this->once())
            ->method('getAttribute')
            ->with('access_token_id')
            ->willReturn('uuid')
        ;

        $repository = $this->createMock(EntityRepository::class);
        $repository
            ->expects($this->once())
            ->method('find')
            ->with('uuid')
            ->willReturn(null)
        ;

        $entityManager = $this->createMock(EntityManagerInterface::class);
        $entityManager
            ->expects($this->once())
            ->method('getRepository')
            ->with(PersonalAccessToken::class)
            ->willReturn($repository)
        ;

        $entityManager
            ->expects($this->never())
            ->method('persist')
        ;

        $entityManager
            ->expects($this->never())
            ->method('flush')
        ;

        $handler = new AuthenticationSuccessHandler($entityManager);
        $handler->onAuthenticationSuccess(new Request(), $token);
    }

    public function testIgnoresTokenWithoutAccessTokenId(): void
    {
        $token = $this->createMock(TokenInterface::class);
        $token
            ->expects($this->once())
            ->method('hasAttribute')
            ->with('access_token_id')
            ->willReturn(false)
        ;

        $token
            ->expects($this->never())
            ->method('getAttribute')
        ;

        $entityManager = $this->createMock(EntityManagerInterface::class);
        $entityManager
            ->expects($this->never())
            ->method('getRepository')
        ;

        $entityManager
            ->expects($this->never())
            ->method('persist')
        ;

        $entityManager
            ->expects($this->never())
            ->method('flush')
        ;

        $handler = new AuthenticationSuccessHandler($entityManager);
        $handler->onAuthenticationSuccess(new Request(), $token);
    }
}
