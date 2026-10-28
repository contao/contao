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
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;

class AuthenticationSuccessHandlerTest extends TestCase
{
    public function testUpdatesLastUsedAndPersistsPersonalAccessToken(): void
    {
        $personalAccessToken = new PersonalAccessToken('backend', 42, 'foobar', 'hashed-secret');
        $personalAccessToken->setLastUsed(new \DateTimeImmutable('2000-01-01'));

        $before = new \DateTimeImmutable();

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

        $request = new Request();
        $request->attributes->set('_access_token', $personalAccessToken);

        $handler = new AuthenticationSuccessHandler($entityManager);
        $handler->onAuthenticationSuccess($request, $this->createStub(TokenInterface::class));

        $this->assertInstanceOf(\DateTimeImmutable::class, $personalAccessToken->getLastUsed());
        $this->assertGreaterThanOrEqual($before, $personalAccessToken->getLastUsed());
        $this->assertLessThanOrEqual(new \DateTimeImmutable(), $personalAccessToken->getLastUsed());
    }

    public function testDoesNothingWithoutAccessToken(): void
    {
        $entityManager = $this->createMock(EntityManagerInterface::class);
        $entityManager
            ->expects($this->never())
            ->method('persist')
        ;
        $entityManager
            ->expects($this->never())
            ->method('flush')
        ;

        $handler = new AuthenticationSuccessHandler($entityManager);
        $handler->onAuthenticationSuccess(new Request(), $this->createStub(TokenInterface::class));
    }

    public function testDoesNotUpdateTheDatabaseIfTheTokenIsCurrent(): void
    {
        $personalAccessToken = new PersonalAccessToken('backend', 42, 'foobar', 'hashed-secret');
        $personalAccessToken->setLastUsed(new \DateTimeImmutable('-10 seconds'));

        $entityManager = $this->createMock(EntityManagerInterface::class);
        $entityManager
            ->expects($this->never())
            ->method('persist')
        ;
        $entityManager
            ->expects($this->never())
            ->method('flush')
        ;

        $request = new Request();
        $request->attributes->set('_access_token', $personalAccessToken);

        $handler = new AuthenticationSuccessHandler($entityManager);
        $handler->onAuthenticationSuccess($request, $this->createStub(TokenInterface::class));
    }
}
