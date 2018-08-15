<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao\CoreBundle\Tests\EventListener;

use Contao\CoreBundle\EventListener\ClearFormDataListener;
use Contao\CoreBundle\Tests\TestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Session\Session;
use Symfony\Component\HttpKernel\Event\FilterResponseEvent;
use Symfony\Component\HttpKernel\HttpKernelInterface;
use Symfony\Component\HttpKernel\KernelInterface;

class ClearFormDataListenerTest extends TestCase
{
    public function testCanBeInstantiated(): void
    {
        $listener = new ClearFormDataListener();

        $this->assertInstanceOf('Contao\CoreBundle\EventListener\ClearFormDataListener', $listener);
    }

    public function testClearsTheFormData(): void
    {
        $session = $this->createMock(Session::class);
        $session
            ->expects($this->once())
            ->method('isStarted')
            ->willReturn(true)
        ;

        $request = new Request();
        $request->setSession($session);

        $event = new FilterResponseEvent(
            $this->createMock(KernelInterface::class),
            $request,
            HttpKernelInterface::MASTER_REQUEST,
            new Response()
        );

        $_SESSION['FORM_DATA'] = ['foo' => 'bar'];

        $listener = new ClearFormDataListener();
        $listener->onKernelResponse($event);

        $this->assertArrayNotHasKey('FORM_DATA', $_SESSION);
    }

    public function testDoesNotClearTheFormDataUponSubrequests(): void
    {
        $request = $this->createMock(Request::class);
        $request
            ->expects($this->never())
            ->method('isMethod')
        ;

        $event = new FilterResponseEvent(
            $this->createMock(KernelInterface::class),
            $request,
            HttpKernelInterface::SUB_REQUEST,
            new Response()
        );

        $listener = new ClearFormDataListener();
        $listener->onKernelResponse($event);
    }

    public function testDoesNotClearTheFormDataUponPostRequests(): void
    {
        $session = $this->createMock(Session::class);
        $session
            ->expects($this->never())
            ->method('isStarted')
        ;

        $request = new Request();
        $request->setSession($session);
        $request->setMethod('POST');

        $event = new FilterResponseEvent(
            $this->createMock(KernelInterface::class),
            $request,
            HttpKernelInterface::MASTER_REQUEST,
            new Response()
        );

        $listener = new ClearFormDataListener();
        $listener->onKernelResponse($event);
    }

    public function testDoesNotClearTheFormDataIfTheSessionIsNotStarted(): void
    {
        $session = $this->createMock(Session::class);
        $session
            ->expects($this->once())
            ->method('isStarted')
            ->willReturn(false)
        ;

        $request = new Request();
        $request->setSession($session);

        $event = new FilterResponseEvent(
            $this->createMock(KernelInterface::class),
            $request,
            HttpKernelInterface::MASTER_REQUEST,
            new Response()
        );

        $_SESSION['FORM_DATA'] = ['foo' => 'bar'];

        $listener = new ClearFormDataListener();
        $listener->onKernelResponse($event);

        $this->assertSame(['foo' => 'bar'], $_SESSION['FORM_DATA']);
    }
}
