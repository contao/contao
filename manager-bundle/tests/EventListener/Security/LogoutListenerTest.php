<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao\ManagerBundle\Tests\EventListener\Security;

use Contao\ManagerBundle\EventListener\Security\LogoutListener;
use Contao\ManagerBundle\HttpKernel\JwtManager;
use Contao\TestCase\ContaoTestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Security\Http\Event\LogoutEvent;

class LogoutListenerTest extends ContaoTestCase
{
    public function testClearsCookieOnResponse(): void
    {
        $response = $this->createMock(Response::class);

        $jwtManager = $this->createMock(JwtManager::class);
        $jwtManager
            ->expects($this->once())
            ->method('clearResponseCookie')
            ->with($response)
        ;

        $event = new LogoutEvent($this->createMock(Request::class), null);
        $event->setResponse($this->createMock(Response::class));

        $listener = new LogoutListener($jwtManager);
        $listener($event);
    }

    public function testDoesNothingIfJwtManagerIsNotSet(): void
    {
        $event = new LogoutEvent($this->createMock(Request::class), null);
        $event->setResponse($this->createMock(Response::class));

        $listener = new LogoutListener();
        $listener($event);

        $this->expectNotToPerformAssertions();
    }

    public function testDoesNothingIfResponseIsNotSet(): void
    {
        $event = new LogoutEvent($this->createMock(Request::class), null);

        $listener = new LogoutListener();
        $listener($event);

        $this->expectNotToPerformAssertions();
    }
}
