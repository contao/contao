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

use Contao\CoreBundle\EventListener\AddToSearchIndexListener;
use Contao\CoreBundle\Framework\ContaoFramework;
use Contao\CoreBundle\Tests\TestCase;
use Contao\Frontend;
use PHPUnit\Framework\MockObject\MockObject;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Event\TerminateEvent;
use Symfony\Component\HttpKernel\KernelInterface;

class AddToSearchIndexListenerTest extends TestCase
{
    /**
     * @var ContaoFramework&MockObject
     */
    private $framework;

    /**
     * {@inheritdoc}
     */
    protected function setUp(): void
    {
        parent::setUp();

        $adapter = $this->mockAdapter(['indexPageIfApplicable']);

        $this->framework = $this->mockContaoFramework([Frontend::class => $adapter]);
    }

    public function testIndexesTheResponse(): void
    {
        $event = $this->mockTerminateEvent();
        $event
            ->expects($this->once())
            ->method('getResponse')
            ->willReturn(new Response())
        ;

        $listener = new AddToSearchIndexListener($this->framework);
        $listener->onKernelTerminate($event);
    }

    public function testDoesNotIndexTheResponseIfTheContaoFrameworkIsNotInitialized(): void
    {
        $framework = $this->createMock(ContaoFramework::class);
        $framework
            ->method('isInitialized')
            ->willReturn(false)
        ;

        $event = $this->mockTerminateEvent();
        $event
            ->expects($this->never())
            ->method('getResponse')
        ;

        $listener = new AddToSearchIndexListener($framework);
        $listener->onKernelTerminate($event);
    }

    public function testDoesNotIndexTheResponseIfTheRequestMethodIsNotGet(): void
    {
        $event = $this->mockTerminateEvent(null, Request::METHOD_POST);
        $event
            ->expects($this->never())
            ->method('getResponse')
        ;

        $listener = new AddToSearchIndexListener($this->framework);
        $listener->onKernelTerminate($event);
    }

    public function testDoesNotIndexTheResponseUponFragmentRequests(): void
    {
        $event = $this->mockTerminateEvent('_fragment/foo/bar');
        $event
            ->expects($this->never())
            ->method('getResponse')
        ;

        $listener = new AddToSearchIndexListener($this->framework);
        $listener->onKernelTerminate($event);
    }

    /**
     * @return TerminateEvent&MockObject
     */
    private function mockTerminateEvent(string $requestUri = null, string $requestMethod = Request::METHOD_GET): TerminateEvent
    {
        $request = new Request();
        $request->setMethod($requestMethod);
        $request->server->set('REQUEST_URI', $requestUri);

        return $this
            ->getMockBuilder(TerminateEvent::class)
            ->setConstructorArgs([$this->createMock(KernelInterface::class), $request, new Response()])
            ->setMethods(['getResponse'])
            ->getMock()
        ;
    }
}
