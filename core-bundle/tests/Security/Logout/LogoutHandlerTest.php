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
use Contao\CoreBundle\Security\Logout\LogoutHandler;
use Contao\CoreBundle\Tests\TestCase;
use Contao\System;
use PHPUnit\Framework\MockObject\MockObject;
use Psr\Log\LoggerInterface;
use Scheb\TwoFactorBundle\Security\Authentication\Token\TwoFactorToken;
use Symfony\Bridge\PhpUnit\ExpectDeprecationTrait;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authentication\Token\UsernamePasswordToken;
use Symfony\Component\Security\Core\User\UserInterface;

class LogoutHandlerTest extends TestCase
{
    use ExpectDeprecationTrait;

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
     */
    public function testTriggersThePostLogoutHook(): void
    {
        $this->expectDeprecation('Since contao/core-bundle 4.5: Using the "postLogout" hook has been deprecated %s.');

        /** @var BackendUser&MockObject $user */
        $user = $this->mockClassWithProperties(BackendUser::class);
        $user->username = 'foobar';

        $systemAdapter = $this->mockAdapter(['importStatic']);
        $systemAdapter
            ->expects($this->once())
            ->method('importStatic')
            ->with(static::class)
            ->willReturn($this)
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

        $GLOBALS['TL_HOOKS']['postLogout'][] = [static::class, 'onPostLogout'];

        $handler = new LogoutHandler($framework, $logger);
        $handler->logout(new Request(), new Response(), $token);

        unset($GLOBALS['TL_HOOKS']);
    }

    public function onPostLogout(): void
    {
        // Dummy method to test the postLogout hook
    }

    public function testRemovesTargetPathFromSessionWithUsernamePasswordToken(): void
    {
        $session = $this->createMock(SessionInterface::class);
        $session
            ->expects($this->once())
            ->method('remove')
            ->with('_security.contao_frontend.target_path')
        ;

        $request = $this->createMock(Request::class);
        $request
            ->expects($this->once())
            ->method('getSession')
            ->willReturn($session)
        ;

        $request
            ->method('hasSession')
            ->willReturn(true)
        ;

        $token = $this->createMock(UsernamePasswordToken::class);
        $token
            ->expects($this->once())
            ->method(method_exists($token, 'getFirewallName') ? 'getFirewallName' : 'getProviderKey')
            ->willReturn('contao_frontend')
        ;

        $handler = new LogoutHandler($this->mockContaoFramework());
        $handler->logout($request, $this->createMock(Response::class), $token);
    }

    public function testRemovesTargetPathFromSessionWithTwoFactorToken(): void
    {
        $session = $this->createMock(SessionInterface::class);
        $session
            ->expects($this->once())
            ->method('remove')
            ->with('_security.contao_frontend.target_path')
        ;

        $request = $this->createMock(Request::class);
        $request
            ->expects($this->once())
            ->method('getSession')
            ->willReturn($session)
        ;

        $request
            ->method('hasSession')
            ->willReturn(true)
        ;

        /** @var TwoFactorToken&MockObject $token */
        $token = $this->createMock(TwoFactorToken::class);
        $token
            ->expects($this->once())
            ->method('getFirewallName')
            ->willReturn('contao_frontend')
        ;

        $handler = new LogoutHandler($this->mockContaoFramework());
        $handler->logout($request, $this->createMock(Response::class), $token);
    }
}
