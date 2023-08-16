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
use Doctrine\ORM\EntityManagerInterface;
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

        $user = $this->mockClassWithProperties(BackendUser::class);
        $user->id = 1;
        $user->trustedTokenVersion = 1;

        $manager = new TrustedDeviceManager(
            $this->createMock(RequestStack::class),
            $tokenStorage,
            $this->createMock(EntityManagerInterface::class)
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
            $this->createMock(RequestStack::class),
            $tokenStorage,
            $this->createMock(EntityManagerInterface::class)
        );

        $this->assertFalse($manager->isTrustedDevice($this->createMock(UserInterface::class), 'contao_backend'));
    }
}
