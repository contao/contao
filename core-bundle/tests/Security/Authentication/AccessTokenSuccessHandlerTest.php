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

use Contao\CoreBundle\Entity\PersonalAccessToken;
use Contao\CoreBundle\Security\Authentication\AccessTokenSuccessHandler;
use Contao\CoreBundle\Tests\TestCase;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;

class AccessTokenSuccessHandlerTest extends TestCase
{
    public function testUpdatesLastUsedAndPersistsPersonalAccessToken(): void
    {
        $personalAccessToken = new PersonalAccessToken(42, 'foobar', 'hashed-secret');
        $personalAccessToken->setLastUsed(new \DateTimeImmutable('2000-01-01'));
        $before = new \DateTimeImmutable();

        $token = $this->createMock(TokenInterface::class);
        $token
            ->expects($this->once())
            ->method('getAttribute')
            ->with('access_token')
            ->willReturn($personalAccessToken)
        ;

        $entityManager = $this->createMock(EntityManagerInterface::class);
        $entityManager
            ->expects($this->once())
            ->method('persist')
            ->with($this->identicalTo($personalAccessToken))
        ;

        $entityManager
            ->expects($this->once())
            ->method('flush')
        ;

        $handler = new AccessTokenSuccessHandler($entityManager);
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
            ->method('getAttribute')
            ->with('access_token')
            ->willReturn(new \stdClass())
        ;

        $entityManager = $this->createMock(EntityManagerInterface::class);
        $entityManager
            ->expects($this->never())
            ->method('persist')
        ;

        $entityManager
            ->expects($this->never())
            ->method('flush')
        ;

        $handler = new AccessTokenSuccessHandler($entityManager);
        $handler->onAuthenticationSuccess(new Request(), $token);
    }
}
