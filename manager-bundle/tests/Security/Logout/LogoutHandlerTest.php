<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao\ManagerBundle\Tests\Security\Logout;

use Contao\ManagerBundle\HttpKernel\JwtManager;
use Contao\ManagerBundle\Security\Logout\LogoutHandler;
use Contao\TestCase\ContaoTestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;

class LogoutHandlerTest extends ContaoTestCase
{
    public function testClearsCookieOnResponse()
    {
        $response = $this->createMock(Response::class);

        $jwtManager = $this->createMock(JwtManager::class);
        $jwtManager
            ->expects($this->once())
            ->method('clearResponseCookie')
            ->with($response)
        ;

        $handler = new LogoutHandler($jwtManager);

        $handler->logout(
            $this->createMock(Request::class),
            $this->createMock(Response::class),
            $this->createMock(TokenInterface::class)
        );
    }

    public function testDoesNothingIfJwtManagerIsNotSet()
    {
        $handler = new LogoutHandler();

        $handler->logout(
            $this->createMock(Request::class),
            $this->createMock(Response::class),
            $this->createMock(TokenInterface::class)
        );

        $this->expectNotToPerformAssertions();
    }
}
