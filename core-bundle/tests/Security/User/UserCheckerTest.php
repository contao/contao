<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao\CoreBundle\Tests\Security\User;

use Contao\BackendUser;
use Contao\Config;
use Contao\CoreBundle\Security\Exception\LockedException;
use Contao\CoreBundle\Security\User\UserChecker;
use Contao\CoreBundle\Tests\TestCase;
use Contao\FrontendUser;
use PHPUnit\Framework\MockObject\MockObject;
use Symfony\Component\Security\Core\Exception\DisabledException;
use Symfony\Component\Security\Core\User\UserInterface;

class UserCheckerTest extends TestCase
{
    public function testChecksAContaoUser(): void
    {
        $adapter = $this->mockConfiguredAdapter(['dateFormat' => 'Y-m-d']);
        $framework = $this->mockContaoFramework([Config::class => $adapter]);

        $user = $this->createMock(BackendUser::class);
        $user->username = 'foo';
        $user->locked = 0;
        $user->disable = '';
        $user->login = '1';
        $user->start = '';
        $user->stop = '';

        $userChecker = new UserChecker($framework);
        $userChecker->checkPreAuth($user);
        $userChecker->checkPostAuth($user);

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
            'username' => 'foo',
            'locked' => time() + 300,
        ];

        /** @var BackendUser|MockObject $user */
        $user = $this->mockClassWithProperties(BackendUser::class, $properties);

        $userChecker = new UserChecker($this->mockContaoFramework());

        $this->expectException(LockedException::class);
        $this->expectExceptionMessage('User "foo" has been locked for 5 minutes');

        $userChecker->checkPreAuth($user);
    }

    public function testThrowsAnExceptionIfTheAccountIsDisabled(): void
    {
        $properties = [
            'username' => 'foo',
            'locked' => 0,
            'disable' => '1',
        ];

        /** @var BackendUser|MockObject $user */
        $user = $this->mockClassWithProperties(BackendUser::class, $properties);

        $userChecker = new UserChecker($this->mockContaoFramework());

        $this->expectException(DisabledException::class);
        $this->expectExceptionMessage('The account has been disabled');

        $userChecker->checkPreAuth($user);
    }

    public function testThrowsAnExceptionIfTheUserIsNotAllowedToLogin(): void
    {
        $properties = [
            'username' => 'foo',
            'locked' => 0,
            'disable' => '',
            'login' => '',
        ];

        /** @var FrontendUser|MockObject $user */
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
            'username' => 'foo',
            'locked' => 0,
            'disable' => '',
            'login' => '1',
            'start' => (string) $time,
        ];

        /** @var FrontendUser|MockObject $user */
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
            'username' => 'foo',
            'locked' => 0,
            'disable' => '',
            'login' => '1',
            'start' => '',
            'stop' => (string) $time,
        ];

        /** @var FrontendUser|MockObject $user */
        $user = $this->mockClassWithProperties(FrontendUser::class, $properties);

        $userChecker = new UserChecker($this->mockContaoFramework());
        $message = sprintf('The account is not active anymore (deactivation date: %s)', date('Y-m-d', $time));

        $this->expectException(DisabledException::class);
        $this->expectExceptionMessage($message);

        $userChecker->checkPreAuth($user);
    }
}
