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
use Contao\CoreBundle\Security\User\UserChecker;
use Contao\CoreBundle\Tests\TestCase;
use Contao\FrontendUser;
use Symfony\Component\Security\Core\Exception\DisabledException;
use Symfony\Component\Security\Core\User\UserInterface;

class UserCheckerTest extends TestCase
{
    public function testChecksAContaoUser(): void
    {
        $adapter = $this->createConfiguredAdapterStub(['dateFormat' => 'Y-m-d']);
        $framework = $this->createContaoFrameworkStub([Config::class => $adapter]);

        $user = $this->createMock(BackendUser::class);
        $user->username = 'foo';
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
        $framework = $this->createContaoFrameworkMock();
        $framework
            ->expects($this->never())
            ->method('getAdapter')
        ;

        $userChecker = new UserChecker($framework);
        $userChecker->checkPreAuth($this->createMock(UserInterface::class));
    }

    public function testThrowsAnExceptionIfTheAccountIsDisabled(): void
    {
        $user = $this->createClassWithPropertiesStub(BackendUser::class);
        $user->username = 'foo';
        $user->disable = true;

        $userChecker = new UserChecker($this->createContaoFrameworkStub());

        $this->expectException(DisabledException::class);
        $this->expectExceptionMessage('The account has been disabled');

        $userChecker->checkPreAuth($user);
    }

    public function testThrowsAnExceptionIfTheUserIsNotAllowedToLogin(): void
    {
        $user = $this->createClassWithPropertiesStub(FrontendUser::class);
        $user->username = 'foo';
        $user->disable = false;
        $user->login = false;

        $userChecker = new UserChecker($this->createContaoFrameworkStub());

        $this->expectException(DisabledException::class);
        $this->expectExceptionMessage('User "foo" is not allowed to log in');

        $userChecker->checkPreAuth($user);
    }

    public function testThrowsAnExceptionIfTheAccountIsNotActiveYet(): void
    {
        $time = strtotime('tomorrow');

        $user = $this->createClassWithPropertiesStub(FrontendUser::class);
        $user->username = 'foo';
        $user->disable = false;
        $user->login = true;
        $user->start = (string) $time;

        $userChecker = new UserChecker($this->createContaoFrameworkStub());
        $message = \sprintf('The account is not active yet (activation date: %s)', date('Y-m-d', $time));

        $this->expectException(DisabledException::class);
        $this->expectExceptionMessage($message);

        $userChecker->checkPreAuth($user);
    }

    public function testThrowsAnExceptionIfTheAccountIsNotActiveAnymore(): void
    {
        $time = strtotime('yesterday');

        $user = $this->createClassWithPropertiesStub(FrontendUser::class);
        $user->username = 'foo';
        $user->disable = false;
        $user->login = true;
        $user->start = '';
        $user->stop = (string) $time;

        $userChecker = new UserChecker($this->createContaoFrameworkStub());
        $message = \sprintf('The account is not active anymore (deactivation date: %s)', date('Y-m-d', $time));

        $this->expectException(DisabledException::class);
        $this->expectExceptionMessage($message);

        $userChecker->checkPreAuth($user);
    }
}
