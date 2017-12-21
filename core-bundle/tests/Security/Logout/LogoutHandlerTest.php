<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * Copyright (c) 2005-2017 Leo Feyer
 *
 * @license LGPL-3.0+
 */

namespace Contao\CoreBundle\Test\Security\Logout;

use Contao\BackendUser;
use Contao\CoreBundle\Security\Logout\LogoutHandler;
use Contao\CoreBundle\Tests\TestCase;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\User\UserInterface;

class LogoutHandlerTest extends TestCase
{
    public function testCanBeInstantiated(): void
    {
        $handler = new LogoutHandler($this->mockContaoFramework());

        $this->assertInstanceOf('Contao\CoreBundle\Security\Logout\LogoutHandler', $handler);
    }

    public function testAddsTheLogEntry(): void
    {
        $framework = $this->mockContaoFramework();
        $logger = $this->createMock(LoggerInterface::class);

        $logger
            ->expects($this->once())
            ->method('info')
            ->with('User "foobar" has logged out')
        ;

        $user = $this->mockClassWithProperties(BackendUser::class, ['username' => 'foobar']);
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
}
