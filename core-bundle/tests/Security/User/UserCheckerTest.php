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
use Contao\Config;
use Contao\CoreBundle\Security\Exception\LockedException;
use Contao\CoreBundle\Security\User\UserChecker;
use Contao\CoreBundle\Tests\TestCase;
use Contao\FrontendUser;
use Symfony\Component\Security\Core\Exception\DisabledException;
use Symfony\Component\Security\Core\User\UserInterface;

class UserCheckerTest extends TestCase
{
    public function testCanBeInstantiated(): void
    {
        $userChecker = new UserChecker($this->mockContaoFramework());

        $this->assertInstanceOf('Contao\CoreBundle\Security\User\UserChecker', $userChecker);
    }

    public function testChecksAContaoUser(): void
    {
        $adapter = $this->mockConfiguredAdapter(['dateFormat' => 'Y-m-d']);
        $framework = $this->mockContaoFramework([Config::class => $adapter]);

        $properties = [
            'locked' => 0,
            'username' => 'foo',
            'disable' => '',
            'login' => '1',
            'start' => '',
            'stop' => '',
        ];

        /** @var BackendUser|\PHPUnit_Framework_MockObject_MockObject $user */
        $user = $this->mockClassWithProperties(BackendUser::class, $properties);

        $userChecker = new UserChecker($framework);
        $userChecker->checkPreAuth($user);

        $this->addToAssertionCount(1);  // does not throw an exception
    }

    public function testDoesNothingIfTheUserIsNotAContaoUser(): void
    {
        $framework = $this->mockContaoFramework();

        $framework
            ->expects($this->never())
            ->method('getAdapter')
        ;

        $userChecker = new UserChecker($framework);
        $userChecker->checkPreAuth($this->createMock(UserInterface::class));
    }

    public function testThrowsAnExceptionIfTheAccountIsLocked(): void
    {
        $properties = [
            'locked' => time() + 300,
            'username' => 'foo',
        ];

        /** @var BackendUser|\PHPUnit_Framework_MockObject_MockObject $user */
        $user = $this->mockClassWithProperties(BackendUser::class, $properties);

        $userChecker = new UserChecker($this->mockContaoFramework());

        $this->expectException(LockedException::class);
        $this->expectExceptionMessage('User "foo" has been locked for 5 minutes');

        $userChecker->checkPreAuth($user);
    }

    public function testThrowsAnExceptionIfTheAccountIsDisabled(): void
    {
        $properties = [
            'locked' => 0,
            'username' => 'foo',
            'disable' => '1',
        ];

        /** @var BackendUser|\PHPUnit_Framework_MockObject_MockObject $user */
        $user = $this->mockClassWithProperties(BackendUser::class, $properties);

        $userChecker = new UserChecker($this->mockContaoFramework());

        $this->expectException(DisabledException::class);
        $this->expectExceptionMessage('The account has been disabled');

        $userChecker->checkPreAuth($user);
    }

    public function testThrowsAnExceptionIfTheUserIsNotAllowedToLogin(): void
    {
        $properties = [
            'locked' => 0,
            'username' => 'foo',
            'disable' => '',
            'login' => '',
        ];

        /** @var FrontendUser|\PHPUnit_Framework_MockObject_MockObject $user */
        $user = $this->mockClassWithProperties(FrontendUser::class, $properties);

        $userChecker = new UserChecker($this->mockContaoFramework());

        $this->expectException(DisabledException::class);
        $this->expectExceptionMessage('User "foo" is not allowed to log in');

        $userChecker->checkPreAuth($user);
    }

    public function testThrowsAnExceptionIfTheAccountIsNotActiveYet(): void
    {
        $time = strtotime('tomorrow');

        $properties = [
            'locked' => 0,
            'username' => 'foo',
            'disable' => '',
            'login' => '1',
            'start' => $time,
        ];

        /** @var FrontendUser|\PHPUnit_Framework_MockObject_MockObject $user */
        $user = $this->mockClassWithProperties(FrontendUser::class, $properties);

        $userChecker = new UserChecker($this->mockContaoFramework());
        $message = sprintf('The account is not active yet (activation date: %s)', date('Y-m-d', $time));

        $this->expectException(DisabledException::class);
        $this->expectExceptionMessage($message);

        $userChecker->checkPreAuth($user);
    }

    public function testThrowsAnExceptionIfTheAccountIsNotActiveAnymore(): void
    {
        $time = strtotime('yesterday');

        $properties = [
            'locked' => 0,
            'username' => 'foo',
            'disable' => '',
            'login' => '1',
            'start' => '',
            'stop' => $time,
        ];

        /** @var FrontendUser|\PHPUnit_Framework_MockObject_MockObject $user */
        $user = $this->mockClassWithProperties(FrontendUser::class, $properties);

        $userChecker = new UserChecker($this->mockContaoFramework());
        $message = sprintf('The account is not active anymore (deactivation date: %s)', date('Y-m-d', $time));

        $this->expectException(DisabledException::class);
        $this->expectExceptionMessage($message);

        $userChecker->checkPreAuth($user);
    }
}
