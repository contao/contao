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

use Contao\CoreBundle\EventListener\LegacyRouteParametersListener;
use Contao\CoreBundle\Framework\ContaoFramework;
use Contao\CoreBundle\Routing\ScopeMatcher;
use Contao\CoreBundle\Tests\TestCase;
use Contao\Input;
use Contao\UnusedArgumentsException;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Event\ResponseEvent;
use Symfony\Component\HttpKernel\HttpKernelInterface;
use Symfony\Component\HttpKernel\KernelInterface;

class LegacyRouteParametersListenerTest extends TestCase
{
    public function testDoesNotExecuteOutsideContaoFrontendMainRequest(): void
    {
        $event = new ResponseEvent(
            $this->createStub(KernelInterface::class),
            new Request(),
            HttpKernelInterface::MAIN_REQUEST,
            new Response(),
        );

        $scopeMatcher = $this->createMock(ScopeMatcher::class);
        $scopeMatcher
            ->expects($this->once())
            ->method('isFrontendMainRequest')
            ->with($event)
            ->willReturn(false)
        ;

        $framework = $this->createMock(ContaoFramework::class);
        $framework
            ->expects($this->never())
            ->method('getAdapter')
        ;

        $listener = new LegacyRouteParametersListener($scopeMatcher, $framework);
        $listener($event);
    }

    public function testDoesNotThrowExceptionIfNoUnusedArguments(): void
    {
        $event = new ResponseEvent(
            $this->createStub(KernelInterface::class),
            new Request(),
            HttpKernelInterface::MAIN_REQUEST,
            new Response(),
        );

        $scopeMatcher = $this->createMock(ScopeMatcher::class);
        $scopeMatcher
            ->expects($this->once())
            ->method('isFrontendMainRequest')
            ->with($event)
            ->willReturn(true)
        ;

        $inputAdapter = $this->createAdapterMock(['getUnusedRouteParameters', 'setUnusedRouteParameters']);
        $inputAdapter
            ->expects($this->once())
            ->method('getUnusedRouteParameters')
            ->willReturn([])
        ;

        $inputAdapter
            ->expects($this->never())
            ->method('setUnusedRouteParameters')
        ;

        $framework = $this->createMock(ContaoFramework::class);
        $framework
            ->expects($this->once())
            ->method('getAdapter')
            ->with(Input::class)
            ->willReturn($inputAdapter)
        ;

        $listener = new LegacyRouteParametersListener($scopeMatcher, $framework);
        $listener($event);
    }

    public function testThrowsUnusedArgumentsExceptionWithUnusedRoutParameters(): void
    {
        $this->expectException(UnusedArgumentsException::class);
        $this->expectExceptionMessage('Unused arguments: foo');

        $responseEvent = new ResponseEvent(
            $this->createStub(KernelInterface::class),
            new Request(),
            HttpKernelInterface::MAIN_REQUEST,
            new Response(),
        );

        $scopeMatcher = $this->createMock(ScopeMatcher::class);
        $scopeMatcher
            ->expects($this->once())
            ->method('isFrontendMainRequest')
            ->with($responseEvent)
            ->willReturn(true)
        ;

        $inputAdapter = $this->createAdapterMock(['getUnusedRouteParameters', 'setUnusedRouteParameters']);
        $inputAdapter
            ->expects($this->once())
            ->method('getUnusedRouteParameters')
            ->willReturn(['foo'])
        ;

        $inputAdapter
            ->expects($this->once())
            ->method('setUnusedRouteParameters')
            ->with([])
        ;

        $framework = $this->createMock(ContaoFramework::class);
        $framework
            ->expects($this->once())
            ->method('getAdapter')
            ->with(Input::class)
            ->willReturn($inputAdapter)
        ;

        $listener = new LegacyRouteParametersListener($scopeMatcher, $framework);
        $listener($responseEvent);
    }

    public function testDoesNotThrowUnusedArgumentsExceptionWithUnusedRoutParametersOnUnsuccessfulResponses(): void
    {
        $responseEvent = new ResponseEvent(
            $this->createStub(KernelInterface::class),
            new Request(),
            HttpKernelInterface::MAIN_REQUEST,
            new Response(status: Response::HTTP_INTERNAL_SERVER_ERROR),
        );

        $scopeMatcher = $this->createMock(ScopeMatcher::class);
        $scopeMatcher
            ->expects($this->once())
            ->method('isFrontendMainRequest')
            ->with($responseEvent)
            ->willReturn(true)
        ;

        $inputAdapter = $this->createAdapterMock(['getUnusedRouteParameters', 'setUnusedRouteParameters']);
        $inputAdapter
            ->expects($this->never())
            ->method('getUnusedRouteParameters')
        ;

        $inputAdapter
            ->expects($this->never())
            ->method('setUnusedRouteParameters')
        ;

        $framework = $this->createMock(ContaoFramework::class);
        $framework
            ->expects($this->never())
            ->method('getAdapter')
        ;

        $listener = new LegacyRouteParametersListener($scopeMatcher, $framework);
        $listener($responseEvent);
    }
}
