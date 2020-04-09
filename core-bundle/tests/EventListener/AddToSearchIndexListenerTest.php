<?php

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao\CoreBundle\Tests\EventListener;

use Contao\CoreBundle\EventListener\AddToSearchIndexListener;
use Contao\CoreBundle\Framework\Adapter;
use Contao\CoreBundle\Framework\ContaoFrameworkInterface;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Event\PostResponseEvent;
use Symfony\Component\HttpKernel\KernelInterface;

/**
 * Tests the AddToSearchIndexListener class.
 *
 * @author Leo Feyer <https://github.com/leofeyer>
 */
class AddToSearchIndexListenerTest extends TestCase
{
    /**
     * @var ContaoFrameworkInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    private $framework;

    /**
     * {@inheritdoc}
     */
    protected function setUp()
    {
        parent::setUp();

        $this->framework = $this->createMock(ContaoFrameworkInterface::class);

        $frontendAdapter = $this
            ->getMockBuilder(Adapter::class)
            ->disableOriginalConstructor()
            ->setMethods(['indexPageIfApplicable'])
            ->getMock()
        ;

        $frontendAdapter
            ->method('indexPageIfApplicable')
            ->willReturn(null)
        ;

        $this->framework
            ->method('getAdapter')
            ->willReturn($frontendAdapter)
        ;
    }

    /**
     * Tests that the listener does use the response if the Contao framework is booted.
     */
    public function testIndexesTheResponse()
    {
        $this->framework
            ->method('isInitialized')
            ->willReturn(true)
        ;

        $event = $this->mockPostResponseEvent();

        $event
            ->expects($this->once())
            ->method('getResponse')
            ->willReturn(new Response())
        ;

        $listener = new AddToSearchIndexListener($this->framework);
        $listener->onKernelTerminate($event);
    }

    /**
     * Tests that the listener does nothing if the Contao framework is not booted.
     */
    public function testDoesNotIndexTheResponseIfTheContaoFrameworkIsNotInitialized()
    {
        $this->framework
            ->method('isInitialized')
            ->willReturn(false)
        ;

        $event = $this->mockPostResponseEvent();

        $event
            ->expects($this->never())
            ->method('getResponse')
        ;

        $listener = new AddToSearchIndexListener($this->framework);
        $listener->onKernelTerminate($event);
    }

    /**
     * Tests that the listener does nothing if the request method is not GET.
     */
    public function testDoesNotIndexTheResponseIfTheRequestMethodIsNotGet()
    {
        $this->framework
            ->method('isInitialized')
            ->willReturn(true)
        ;

        $event = $this->mockPostResponseEvent(null, Request::METHOD_POST);

        $event
            ->expects($this->never())
            ->method('getResponse')
        ;

        $listener = new AddToSearchIndexListener($this->framework);
        $listener->onKernelTerminate($event);
    }

    /**
     * Tests that the listener does nothing if the request is a fragment.
     */
    public function testDoesNotIndexTheResponseUponFragmentRequests()
    {
        $this->framework
            ->method('isInitialized')
            ->willReturn(true)
        ;

        $event = $this->mockPostResponseEvent('_fragment/foo/bar');

        $event
            ->expects($this->never())
            ->method('getResponse')
        ;

        $listener = new AddToSearchIndexListener($this->framework);
        $listener->onKernelTerminate($event);
    }

    /**
     * Tests that the listener does nothing if the response was not successful.
     */
    public function testDoesNotIndexTheResponseIfItWasNotSuccessful()
    {
        $framework = $this->createMock(ContaoFrameworkInterface::class);

        $framework
            ->method('isInitialized')
            ->willReturn(true)
        ;

        $framework
            ->expects($this->never())
            ->method('getAdapter')
        ;

        $event = $this->mockPostResponseEvent();

        $event
            ->expects($this->once())
            ->method('getResponse')
            ->willReturn(new Response('', 500))
        ;

        $listener = new AddToSearchIndexListener($framework);
        $listener->onKernelTerminate($event);
    }

    /**
     * Returns a PostResponseEvent mock object.
     *
     * @param string|null $requestUri
     * @param string      $requestMethod
     *
     * @return \PHPUnit_Framework_MockObject_MockObject|PostResponseEvent
     */
    private function mockPostResponseEvent($requestUri = null, $requestMethod = Request::METHOD_GET)
    {
        $request = new Request();
        $request->setMethod($requestMethod);

        if (null !== $requestUri) {
            $request->server->set('REQUEST_URI', $requestUri);
        }

        return $this
            ->getMockBuilder(PostResponseEvent::class)
            ->setConstructorArgs([
                $this->createMock(KernelInterface::class),
                $request,
                new Response(),
            ])
            ->setMethods(['getResponse'])
            ->getMock()
        ;
    }
}
