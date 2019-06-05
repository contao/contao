<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao\CoreBundle\Tests\Security\Logout;

use Contao\BackendUser;
use Contao\Controller;
use Contao\CoreBundle\Security\Logout\LogoutHandler;
use Contao\CoreBundle\Tests\TestCase;
use Contao\System;
use PHPUnit\Framework\MockObject\MockObject;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\User\UserInterface;

class LogoutHandlerTest extends TestCase
{
    public function testAddsTheLogEntry(): void
    {
        $framework = $this->mockContaoFramework();

        $logger = $this->createMock(LoggerInterface::class);
        $logger
            ->expects($this->once())
            ->method('info')
            ->with('User "foobar" has logged out')
        ;

        /** @var BackendUser&MockObject $user */
        $user = $this->mockClassWithProperties(BackendUser::class);
        $user->username = 'foobar';

        $token = $this->createMock(TokenInterface::class);
        $token
            ->expects($this->once())
            ->method('getUser')
            ->willReturn($user)
        ;

        $handler = new LogoutHandler($framework, $logger);
        $handler->logout(new Request(), new Response(), $token);
    }

    public function testDoesNotAddALogEntryIfTheUserIsNotSupported(): void
    {
        $framework = $this->mockContaoFramework();

        $logger = $this->createMock(LoggerInterface::class);
        $logger
            ->expects($this->never())
            ->method('info')
        ;

        $token = $this->createMock(TokenInterface::class);
        $token
            ->expects($this->once())
            ->method('getUser')
            ->willReturn($this->createMock(UserInterface::class))
        ;

        $handler = new LogoutHandler($framework, $logger);
        $handler->logout(new Request(), new Response(), $token);
    }

    /**
     * @group legacy
     *
     * @expectedDeprecation Using the "postLogout" hook has been deprecated %s.
     */
    public function testTriggersThePostLogoutHook(): void
    {
        /** @var BackendUser&MockObject $user */
        $user = $this->mockClassWithProperties(BackendUser::class);
        $user->username = 'foobar';

        $listener = $this->createPartialMock(Controller::class, ['onPostLogout']);
        $listener
            ->expects($this->once())
            ->method('onPostLogout')
            ->with($user)
        ;

        $systemAdapter = $this->mockAdapter(['importStatic']);
        $systemAdapter
            ->expects($this->once())
            ->method('importStatic')
            ->with('HookListener')
            ->willReturn($listener)
        ;

        $framework = $this->mockContaoFramework([System::class => $systemAdapter]);
        $framework
            ->expects($this->once())
            ->method('initialize')
        ;

        $logger = $this->createMock(LoggerInterface::class);
        $logger
            ->expects($this->once())
            ->method('info')
            ->with('User "foobar" has logged out')
        ;

        $token = $this->createMock(TokenInterface::class);
        $token
            ->expects($this->once())
            ->method('getUser')
            ->willReturn($user)
        ;

        $GLOBALS['TL_HOOKS']['postLogout'] = [['HookListener', 'onPostLogout']];

        $handler = new LogoutHandler($framework, $logger);
        $handler->logout(new Request(), new Response(), $token);

        unset($GLOBALS['TL_HOOKS']);
    }
}
