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
use Contao\CoreBundle\Security\User\FrontendUserProvider;
use Contao\CoreBundle\Tests\TestCase;
use Contao\FrontendUser;
use Symfony\Component\Security\Core\Exception\UnsupportedUserException;
use Symfony\Component\Security\Core\Exception\UsernameNotFoundException;
use Symfony\Component\Security\Core\User\UserInterface;

class FrontendUserProviderTest extends TestCase
{
    public function testCanBeInstantiated(): void
    {
        $provider = new FrontendUserProvider($this->mockContaoFramework());

        $this->assertInstanceOf('Contao\CoreBundle\Security\User\FrontendUserProvider', $provider);
    }

    public function testLoadsAnExistingFrontendUser(): void
    {
        /** @var UserInterface|\PHPUnit_Framework_MockObject_MockObject $user */
        $user = $this
            ->getMockBuilder(FrontendUser::class)
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
            ->expects($this->exactly(2))
            ->method('loadUserByUsername')
            ->with('test-user')
            ->willReturn($user)
        ;

        $framework = $this->mockContaoFramework([FrontendUser::class => $adapter]);
        $provider = new FrontendUserProvider($framework);

        $this->assertInstanceOf('Contao\FrontendUser', $provider->loadUserByUsername('test-user'));
        $this->assertInstanceOf('Contao\FrontendUser', $provider->refreshUser($user));
    }

    public function testFailsToLoadANonExistingFrontendUser(): void
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

        $framework = $this->mockContaoFramework([FrontendUser::class => $adapter]);
        $provider = new FrontendUserProvider($framework);

        $this->expectException(UsernameNotFoundException::class);

        $provider->loadUserByUsername('test-user');
    }

    public function testFailsToLoadANonSupportedUser(): void
    {
        $provider = new FrontendUserProvider($this->mockContaoFramework());

        $this->expectException(UnsupportedUserException::class);

        $provider->refreshUser($this->createMock(UserInterface::class));
    }

    public function testSupportsOnlyFrontendUsers(): void
    {
        $provider = new FrontendUserProvider($this->mockContaoFramework());

        $this->assertTrue($provider->supportsClass(FrontendUser::class));
        $this->assertFalse($provider->supportsClass(BackendUser::class));
    }
}
