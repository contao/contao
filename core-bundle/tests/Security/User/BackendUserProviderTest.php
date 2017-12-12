<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * Copyright (c) 2005-2017 Leo Feyer
 *
 * @license LGPL-3.0+
 */

namespace Contao\CoreBundle\Test\Security\User;

use Contao\BackendUser;
use Contao\CoreBundle\Framework\Adapter;
use Contao\CoreBundle\Security\User\BackendUserProvider;
use Contao\CoreBundle\Tests\TestCase;
use Contao\FrontendUser;
use Symfony\Component\Security\Core\Exception\UnsupportedUserException;
use Symfony\Component\Security\Core\Exception\UsernameNotFoundException;
use Symfony\Component\Security\Core\User\UserInterface;

class BackendUserProviderTest extends TestCase
{
    public function testCanBeInstantiated(): void
    {
        $provider = new BackendUserProvider($this->mockContaoFramework());

        $this->assertInstanceOf('Contao\CoreBundle\Security\User\BackendUserProvider', $provider);
    }

    public function testLoadsAnExistingBackendUser(): void
    {
        $user = $this
            ->getMockBuilder(BackendUser::class)
            ->disableOriginalConstructor()
            ->setMethods(['getUsername'])
            ->getMock()
        ;

        $user
            ->expects($this->any())
            ->method('getUsername')
            ->willReturn('test-user')
        ;

        $adapter = $this
            ->getMockBuilder(Adapter::class)
            ->disableOriginalConstructor()
            ->setMethods(['loadUserByUsername'])
            ->getMock()
        ;

        $adapter
            ->expects($this->exactly(1))
            ->method('loadUserByUsername')
            ->with('test-user')
            ->willReturn($user)
        ;

        $framework = $this->mockContaoFramework([BackendUser::class => $adapter]);
        $provider = new BackendUserProvider($framework);

        $this->assertInstanceOf('Contao\BackendUser', $provider->loadUserByUsername('test-user'));
    }

    public function testRefreshesAnExistingBackendUser(): void
    {
        /** @var UserInterface|\PHPUnit_Framework_MockObject_MockObject $user */
        $user = $this
            ->getMockBuilder(BackendUser::class)
            ->disableOriginalConstructor()
            ->setMethods(['getUsername'])
            ->getMock()
        ;

        $user
            ->expects($this->any())
            ->method('getUsername')
            ->willReturn('test-user')
        ;

        $adapter = $this
            ->getMockBuilder(Adapter::class)
            ->disableOriginalConstructor()
            ->setMethods(['loadUserByUsername'])
            ->getMock()
        ;

        $adapter
            ->expects($this->exactly(1))
            ->method('loadUserByUsername')
            ->with('test-user')
            ->willReturn($user)
        ;

        $framework = $this->mockContaoFramework([BackendUser::class => $adapter]);
        $provider = new BackendUserProvider($framework);

        $this->assertInstanceOf('Contao\BackendUser', $provider->refreshUser($user));
    }

    public function testFailsToLoadANonExistingBackendUser(): void
    {
        $adapter = $this
            ->getMockBuilder(Adapter::class)
            ->disableOriginalConstructor()
            ->setMethods(['loadUserByUsername'])
            ->getMock()
        ;

        $adapter
            ->expects($this->once())
            ->method('loadUserByUsername')
            ->with('test-user')
            ->willReturn(null)
        ;

        $framework = $this->mockContaoFramework([BackendUser::class => $adapter]);
        $provider = new BackendUserProvider($framework);

        $this->expectException(UsernameNotFoundException::class);

        $provider->loadUserByUsername('test-user');
    }

    public function testFailsToLoadANonSupportedUser(): void
    {
        $provider = new BackendUserProvider($this->mockContaoFramework());

        $this->expectException(UnsupportedUserException::class);

        $provider->refreshUser($this->createMock(UserInterface::class));
    }

    public function testSupportsOnlyBackendUsers(): void
    {
        $provider = new BackendUserProvider($this->mockContaoFramework());

        $this->assertTrue($provider->supportsClass(BackendUser::class));
        $this->assertFalse($provider->supportsClass(FrontendUser::class));
    }
}
