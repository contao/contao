<?php

/*
 * This file is part of Contao.
 *
 * Copyright (c) 2005-2017 Leo Feyer
 *
 * @license LGPL-3.0+
 */

namespace Contao\CoreBundle\Tests\EventListener;

use Contao\CoreBundle\EventListener\AddCacheHeadersListener;
use Contao\CoreBundle\Framework\Adapter;
use Contao\CoreBundle\Framework\ContaoFrameworkInterface;
use Contao\CoreBundle\Routing\ScopeMatcher;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\ResponseHeaderBag;
use Symfony\Component\HttpKernel\Event\FilterResponseEvent;
use Symfony\Component\HttpKernel\HttpKernelInterface;
use Symfony\Component\HttpKernel\KernelInterface;

/**
 * Tests the AddCacheHeadersListenerTest class.
 *
 * @author Leo Feyer <https://github.com/leofeyer>
 */
class AddCacheHeadersListenerTest extends TestCase
{
    /**
     * @var ContaoFrameworkInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    private $framework;

    /**
     * @var ScopeMatcher|\PHPUnit_Framework_MockObject_MockObject
     */
    private $scopeMatcher;

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
            ->setMethods(['addCacheHeadersToResponse'])
            ->getMock()
        ;

        $frontendAdapter
            ->method('addCacheHeadersToResponse')
            ->willReturn(null)
        ;

        $this->framework
            ->method('getAdapter')
            ->willReturn($frontendAdapter)
        ;

        $this->scopeMatcher = $this->createMock(ScopeMatcher::class);
    }

    /**
     * Tests the object instantiation.
     */
    public function testCanBeInstantiated()
    {
        $listener = new AddCacheHeadersListener($this->framework, $this->scopeMatcher);

        $this->assertInstanceOf('Contao\CoreBundle\EventListener\AddCacheHeadersListener', $listener);
    }

    /**
     * Tests that the listener does use the response if the Contao framework is booted.
     *
     * @runInSeparateProcess
     * @preserveGlobalState disabled
     */
    public function testAddsTheCacheHeadersToTheResponse()
    {
        $this->framework
            ->method('isInitialized')
            ->willReturn(true)
        ;

        $this->scopeMatcher
            ->method('isFrontendMasterRequest')
            ->willReturn(true)
        ;

        $headerBag = $this->createMock(ResponseHeaderBag::class);

        $headerBag
            ->expects($this->once())
            ->method('remove')
            ->with('cache-control')
        ;

        $response = new Response();
        $response->headers = $headerBag;

        $event = $this->mockFilterResponseEvent();

        $event
            ->expects($this->once())
            ->method('getResponse')
            ->willReturn($response)
        ;

        $listener = new AddCacheHeadersListener($this->framework, $this->scopeMatcher);
        $listener->onKernelResponse($event);
    }

    /**
     * Tests that the listener does nothing if the Contao framework is not booted.
     */
    public function testDoesNotAddTheCacheHeadersIfTheContaoFrameworkIsNotInitialized()
    {
        $this->framework
            ->method('isInitialized')
            ->willReturn(false)
        ;

        $this->scopeMatcher
            ->expects($this->never())
            ->method('isFrontendMasterRequest')
        ;

        $event = $this->mockFilterResponseEvent();

        $event
            ->expects($this->never())
            ->method('getResponse')
        ;

        $listener = new AddCacheHeadersListener($this->framework, $this->scopeMatcher);
        $listener->onKernelResponse($event);
    }

    /**
     * Tests that the listener does nothing if not a Contao front end master request.
     */
    public function testDoesNotIndexTheResponseIfNotAContaoFrontendMasterRequest()
    {
        $this->framework
            ->method('isInitialized')
            ->willReturn(false)
        ;

        $this->scopeMatcher
            ->method('isFrontendMasterRequest')
            ->willReturn(false)
        ;

        $event = $this->mockFilterResponseEvent();

        $event
            ->expects($this->never())
            ->method('getResponse')
        ;

        $listener = new AddCacheHeadersListener($this->framework, $this->scopeMatcher);
        $listener->onKernelResponse($event);
    }

    /**
     * Tests that the listener does nothing if the request is a fragment.
     */
    public function testDoesNotAddTheCacheHeadersUponFragmentRequests()
    {
        $this->framework
            ->method('isInitialized')
            ->willReturn(true)
        ;

        $this->scopeMatcher
            ->method('isFrontendMasterRequest')
            ->willReturn(true)
        ;

        $event = $this->mockFilterResponseEvent('_fragment/foo/bar');

        $event
            ->expects($this->never())
            ->method('getResponse')
        ;

        $listener = new AddCacheHeadersListener($this->framework, $this->scopeMatcher);
        $listener->onKernelResponse($event);
    }

    /**
     * Returns a FilterResponseEvent mock object.
     *
     * @param string|null $requestUri
     * @param string      $requestMethod
     *
     * @return \PHPUnit_Framework_MockObject_MockObject|FilterResponseEvent
     */
    private function mockFilterResponseEvent($requestUri = null, $requestMethod = Request::METHOD_GET)
    {
        $request = new Request();
        $request->setMethod($requestMethod);

        if (null !== $requestUri) {
            $request->server->set('REQUEST_URI', $requestUri);
        }

        return $this
            ->getMockBuilder(FilterResponseEvent::class)
            ->setConstructorArgs([
                $this->createMock(KernelInterface::class),
                $request,
                HttpKernelInterface::MASTER_REQUEST,
                new Response(),
            ])
            ->setMethods(['getResponse'])
            ->getMock()
        ;
    }
}
