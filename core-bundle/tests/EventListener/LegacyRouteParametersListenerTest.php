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
use Contao\CoreBundle\Routing\ResponseContext\ResponseContextAccessor;
use Contao\CoreBundle\Routing\ScopeMatcher;
use Contao\CoreBundle\Tests\TestCase;
use Contao\Input;
use Contao\UnusedArgumentsException;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\Event\ResponseEvent;
use Symfony\Component\HttpKernel\HttpKernelInterface;
use Symfony\Component\HttpKernel\KernelInterface;

class LegacyRouteParametersListenerTest extends TestCase
{
    protected function tearDown(): void
    {
        unset(
            $GLOBALS['TL_HEAD'],
            $GLOBALS['TL_BODY'],
            $GLOBALS['TL_MOOTOOLS'],
            $GLOBALS['TL_JQUERY'],
            $GLOBALS['TL_USER_CSS'],
            $GLOBALS['TL_FRAMEWORK_CSS'],
        );

        parent::tearDown();
    }

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

        $listener = new LegacyRouteParametersListener($scopeMatcher, $framework, $this->createStub(ResponseContextAccessor::class));
        $listener->onResponse($event);
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

        $listener = new LegacyRouteParametersListener($scopeMatcher, $framework, $this->createStub(ResponseContextAccessor::class));
        $listener->onResponse($event);
    }

    public function testThrowsUnusedArgumentsExceptionWithUnusedRoutParameters(): void
    {
        $this->expectException(UnusedArgumentsException::class);
        $this->expectExceptionMessage('Unused arguments: foo');

        $requestEvent = new RequestEvent(
            $this->createStub(KernelInterface::class),
            new Request(),
            HttpKernelInterface::MAIN_REQUEST,
        );

        $responseEvent = new ResponseEvent(
            $this->createStub(KernelInterface::class),
            new Request(),
            HttpKernelInterface::MAIN_REQUEST,
            new Response(),
        );

        $scopeMatcher = $this->createMock(ScopeMatcher::class);
        $scopeMatcher
            ->expects($this->exactly(2))
            ->method('isFrontendMainRequest')
            ->willReturnCallback(
                function (RequestEvent|ResponseEvent $event) use ($requestEvent, $responseEvent) {
                    $this->assertTrue(($event === $requestEvent) || ($event === $responseEvent));

                    return true;
                },
            )
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

        $responseContextAccessor = $this->createMock(ResponseContextAccessor::class);
        $responseContextAccessor
            ->expects($this->once())
            ->method('setResponseContext')
            ->with(null)
        ;

        $listener = new LegacyRouteParametersListener($scopeMatcher, $framework, $responseContextAccessor);
        $listener->onRequest($requestEvent);
        $listener->onResponse($responseEvent);
    }
}
