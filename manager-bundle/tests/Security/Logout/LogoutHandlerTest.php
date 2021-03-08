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
    /**
     * @group legacy
     *
     * @expectedDeprecation %s class implements "Symfony\Component\Security\Http\Logout\LogoutHandlerInterface" that is deprecated %s
     */
    public static function setUpBeforeClass(): void
    {
        parent::setUpBeforeClass();

        spl_autoload_call(LogoutHandler::class);
    }

    public function testClearsCookieOnResponse(): void
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

    public function testDoesNothingIfJwtManagerIsNotSet(): void
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
