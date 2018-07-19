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
use Contao\CoreBundle\Framework\ContaoFrameworkInterface;
use Contao\CoreBundle\Tests\TestCase;
use Contao\Frontend;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Event\PostResponseEvent;
use Symfony\Component\HttpKernel\KernelInterface;

class AddToSearchIndexListenerTest extends TestCase
{
    /**
     * @var ContaoFrameworkInterface|\PHPUnit_Framework_MockObject_MockObject
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

    public function testCanBeInstantiated(): void
    {
        $listener = new AddToSearchIndexListener($this->framework);

        $this->assertInstanceOf('Contao\CoreBundle\EventListener\AddToSearchIndexListener', $listener);
    }

    /**
     * @runInSeparateProcess
     * @preserveGlobalState disabled
     */
    public function testIndexesTheResponse(): void
    {
        $event = $this->mockPostResponseEvent();
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
        $framework = $this->createMock(ContaoFrameworkInterface::class);
        $framework
            ->method('isInitialized')
            ->willReturn(false)
        ;

        $event = $this->mockPostResponseEvent();
        $event
            ->expects($this->never())
            ->method('getResponse')
        ;

        $listener = new AddToSearchIndexListener($framework);
        $listener->onKernelTerminate($event);
    }

    public function testDoesNotIndexTheResponseIfTheRequestMethodIsNotGet(): void
    {
        $event = $this->mockPostResponseEvent(null, Request::METHOD_POST);
        $event
            ->expects($this->never())
            ->method('getResponse')
        ;

        $listener = new AddToSearchIndexListener($this->framework);
        $listener->onKernelTerminate($event);
    }

    public function testDoesNotIndexTheResponseUponFragmentRequests(): void
    {
        $event = $this->mockPostResponseEvent('_fragment/foo/bar');
        $event
            ->expects($this->never())
            ->method('getResponse')
        ;

        $listener = new AddToSearchIndexListener($this->framework);
        $listener->onKernelTerminate($event);
    }

    /**
     * @return PostResponseEvent|\PHPUnit_Framework_MockObject_MockObject
     */
    private function mockPostResponseEvent(string $requestUri = null, string $requestMethod = Request::METHOD_GET): PostResponseEvent
    {
        $request = new Request();
        $request->setMethod($requestMethod);
        $request->server->set('REQUEST_URI', $requestUri);

        $event = $this
            ->getMockBuilder(PostResponseEvent::class)
            ->setConstructorArgs([$this->createMock(KernelInterface::class), $request, new Response()])
            ->setMethods(['getResponse'])
            ->getMock()
        ;

        return $event;
    }
}
