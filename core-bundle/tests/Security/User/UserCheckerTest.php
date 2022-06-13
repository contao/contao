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
        $user->disable = false;
        $user->login = true;
        $user->start = '';
        $user->stop = '';

        $userChecker = new UserChecker($framework);
        $userChecker->checkPreAuth($user);
        $userChecker->checkPostAuth($user);

        $this->addToAssertionCount(1); // does not throw an exception
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
        $user = $this->mockClassWithProperties(BackendUser::class);
        $user->username = 'foo';
        $user->locked = time() + 5;

        $userChecker = new UserChecker($this->mockContaoFramework());

        $this->expectException(LockedException::class);
        $this->expectExceptionMessage('User "foo" is still locked for 5 seconds');

        $userChecker->checkPreAuth($user);
    }

    public function testThrowsAnExceptionIfTheAccountIsDisabled(): void
    {
        $user = $this->mockClassWithProperties(BackendUser::class);
        $user->username = 'foo';
        $user->locked = 0;
        $user->disable = true;

        $userChecker = new UserChecker($this->mockContaoFramework());

        $this->expectException(DisabledException::class);
        $this->expectExceptionMessage('The account has been disabled');

        $userChecker->checkPreAuth($user);
    }

    public function testThrowsAnExceptionIfTheUserIsNotAllowedToLogin(): void
    {
        $user = $this->mockClassWithProperties(FrontendUser::class);
        $user->username = 'foo';
        $user->locked = 0;
        $user->disable = false;
        $user->login = false;

        $userChecker = new UserChecker($this->mockContaoFramework());

        $this->expectException(DisabledException::class);
        $this->expectExceptionMessage('User "foo" is not allowed to log in');

        $userChecker->checkPreAuth($user);
    }

    public function testThrowsAnExceptionIfTheAccountIsNotActiveYet(): void
    {
        $time = strtotime('tomorrow');

        $user = $this->mockClassWithProperties(FrontendUser::class);
        $user->username = 'foo';
        $user->locked = 0;
        $user->disable = false;
        $user->login = true;
        $user->start = (string) $time;

        $userChecker = new UserChecker($this->mockContaoFramework());
        $message = sprintf('The account is not active yet (activation date: %s)', date('Y-m-d', $time));

        $this->expectException(DisabledException::class);
        $this->expectExceptionMessage($message);

        $userChecker->checkPreAuth($user);
    }

    public function testThrowsAnExceptionIfTheAccountIsNotActiveAnymore(): void
    {
        $time = strtotime('yesterday');

        $user = $this->mockClassWithProperties(FrontendUser::class);
        $user->username = 'foo';
        $user->locked = 0;
        $user->disable = false;
        $user->login = true;
        $user->start = '';
        $user->stop = (string) $time;

        $userChecker = new UserChecker($this->mockContaoFramework());
        $message = sprintf('The account is not active anymore (deactivation date: %s)', date('Y-m-d', $time));

        $this->expectException(DisabledException::class);
        $this->expectExceptionMessage($message);

        $userChecker->checkPreAuth($user);
    }
}
