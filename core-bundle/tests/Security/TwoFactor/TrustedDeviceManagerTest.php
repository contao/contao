<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao\CoreBundle\Tests\Security\TwoFactor;

use Contao\BackendUser;
use Contao\CoreBundle\Security\TwoFactor\TrustedDeviceManager;
use Contao\CoreBundle\Tests\TestCase;
use Doctrine\DBAL\Connection;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Query;
use Doctrine\ORM\QueryBuilder;
use Scheb\TwoFactorBundle\Security\TwoFactor\Trusted\TrustedDeviceTokenStorage;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Security\Core\User\UserInterface;

class TrustedDeviceManagerTest extends TestCase
{
    public function testIsTrustedDevice(): void
    {
        $tokenStorage = $this->createMock(TrustedDeviceTokenStorage::class);
        $tokenStorage
            ->expects($this->once())
            ->method('hasTrustedToken')
            ->with('1', 'contao_backend', 1)
            ->willReturn(true)
        ;

        $user = $this->createClassWithPropertiesStub(BackendUser::class);
        $user->id = 1;
        $user->trustedTokenVersion = 1;

        $manager = new TrustedDeviceManager(
            $this->createStub(RequestStack::class),
            $tokenStorage,
            $this->createStub(EntityManagerInterface::class),
            $this->createStub(Connection::class),
        );

        $this->assertTrue($manager->isTrustedDevice($user, 'contao_backend'));
    }

    public function testIsTrustedDeviceIgnoresNonContaoUser(): void
    {
        $tokenStorage = $this->createMock(TrustedDeviceTokenStorage::class);
        $tokenStorage
            ->expects($this->never())
            ->method('hasTrustedToken')
        ;

        $manager = new TrustedDeviceManager(
            $this->createStub(RequestStack::class),
            $tokenStorage,
            $this->createStub(EntityManagerInterface::class),
            $this->createStub(Connection::class),
        );

        $this->assertFalse($manager->isTrustedDevice($this->createStub(UserInterface::class), 'contao_backend'));
    }

    public function testClearTrustedDevices(): void
    {
        $user = $this->createClassWithPropertiesStub(BackendUser::class);
        $user
            ->method('getTable')
            ->willReturn('tl_user')
        ;
        $user->id = 1;

        $query = $this->getMockBuilder(Query::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['execute'])
            ->getMock()
        ;

        $query
            ->expects($this->once())
            ->method('execute')
            ->willReturn([])
        ;

        $queryBuilder = $this->createStub(QueryBuilder::class);
        $queryBuilder
            ->method('select')
            ->willReturnSelf()
        ;

        $queryBuilder
            ->method('from')
            ->willReturnSelf()
        ;

        $queryBuilder
            ->method('andWhere')
            ->willReturnSelf()
        ;

        $queryBuilder
            ->method('setParameter')
            ->willReturnSelf()
        ;

        $queryBuilder
            ->method('getQuery')
            ->willReturn($query)
        ;

        $entityManager = $this->createMock(EntityManagerInterface::class);
        $entityManager
            ->expects($this->once())
            ->method('createQueryBuilder')
            ->willReturn($queryBuilder)
        ;

        $entityManager
            ->expects($this->once())
            ->method('flush')
        ;

        $connection = $this->createMock(Connection::class);
        $connection
            ->expects($this->once())
            ->method('executeStatement')
            ->with('UPDATE tl_user SET trustedTokenVersion = trustedTokenVersion + 1 WHERE id=?', [1])
        ;

        $manager = new TrustedDeviceManager(
            $this->createStub(RequestStack::class),
            $this->createStub(TrustedDeviceTokenStorage::class),
            $entityManager,
            $connection,
        );

        $manager->clearTrustedDevices($user);
    }
}
