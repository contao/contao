<?php

/*
 * This file is part of Contao.
 *
 * Copyright (c) 2005-2015 Leo Feyer
 *
 * @license LGPL-3.0+
 */

namespace Contao\CoreBundle\Test\EventListener;

use Contao\CoreBundle\ContaoFrameworkInterface;
use Contao\CoreBundle\EventListener\MergeHttpHeadersListener;
use Contao\CoreBundle\Test\TestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Event\FilterResponseEvent;
use Symfony\Component\HttpKernel\HttpKernelInterface;

/**
 * Tests the MergeHttpHeadersListenerTest class.
 *
 * @author Yanick Witschi <https:/github.com/toflar>
 */
class MergeHttpHeadersListenerTest extends TestCase
{
    /**
     * Tests the object instantiation.
     */
    public function testInstantiation()
    {
        /** @var ContaoFrameworkInterface $framework */
        $framework =  $this->getMock('Contao\\CoreBundle\\ContaoFrameworkInterface');
        $listener = new MergeHttpHeadersListener([], $framework);

        $this->assertInstanceOf('Contao\\CoreBundle\\EventListener\\MergeHttpHeadersListener', $listener);
    }

    /**
     * Tests that the listener is skipped if the framework was not initialized.
     */
    public function testListenerIsSkippedIfFrameworkNotInitialized()
    {
        $request = new Request();
        $responseEvent = new FilterResponseEvent(
            $this->mockKernel(),
            $request,
            HttpKernelInterface::MASTER_REQUEST,
            new Response()
        );

        $framework =  $this->getMock('Contao\\CoreBundle\\ContaoFrameworkInterface');
        $framework->expects($this->once())->method('isInitialized')->willReturn(false);

        /** @var ContaoFrameworkInterface $framework */
        $listener = new MergeHttpHeadersListener([], $framework);
        $listener->setHeaders(['FOOBAR: foobar']);

        $listener->onKernelResponse($responseEvent);
        $response = $responseEvent->getResponse();
        $this->assertFalse($response->headers->has('FOOBAR'));
        $this->assertNull($response->headers->get('FOOBAR'));
    }

    /**
     * Tests that the listener is skipped if the request has no route.
     */
    public function testListenerIsSkippedIfRequestHasNoRoute()
    {
        $request = new Request();
        $responseEvent = new FilterResponseEvent(
            $this->mockKernel(),
            $request,
            HttpKernelInterface::MASTER_REQUEST,
            new Response()
        );

        $framework =  $this->getMock('Contao\\CoreBundle\\ContaoFrameworkInterface');
        $framework->expects($this->once())->method('isInitialized')->willReturn(true);

        /** @var ContaoFrameworkInterface $framework */
        $listener = new MergeHttpHeadersListener([], $framework);
        $listener->setHeaders(['FOOBAR: foobar']);

        $listener->onKernelResponse($responseEvent);
        $response = $responseEvent->getResponse();
        $this->assertFalse($response->headers->has('FOOBAR'));
        $this->assertNull($response->headers->get('FOOBAR'));
    }

    /**
     * Tests that the listener is skipped if the request has a route which is the
     * listener shall not handle.
     */
    public function testListenerIsSkippedIfRequestHasRouteTheListenerShouldNotHandle()
    {
        $request = new Request();
        $request->attributes->set('_route', 'foobar_route');
        $responseEvent = new FilterResponseEvent(
            $this->mockKernel(),
            $request,
            HttpKernelInterface::MASTER_REQUEST,
            new Response()
        );

        $framework =  $this->getMock('Contao\\CoreBundle\\ContaoFrameworkInterface');
        $framework->expects($this->once())->method('isInitialized')->willReturn(true);

        /** @var ContaoFrameworkInterface $framework */
        $listener = new MergeHttpHeadersListener(['other_route'], $framework);
        $listener->setHeaders(['FOOBAR: content']);

        $listener->onKernelResponse($responseEvent);
        $response = $responseEvent->getResponse();
        $this->assertFalse($response->headers->has('FOOBAR'));
        $this->assertNull($response->headers->get('FOOBAR'));
    }

    /**
     * Tests that the headers sent using header() are actually merged into the
     * response object.
     */
    public function testHeadersAreMerged()
    {
        $request = new Request();
        $request->attributes->set('_route', 'foobar_route');
        $responseEvent = new FilterResponseEvent(
            $this->mockKernel(),
            $request,
            HttpKernelInterface::MASTER_REQUEST,
            new Response()
        );

        $framework =  $this->getMock('Contao\\CoreBundle\\ContaoFrameworkInterface');
        $framework->expects($this->once())->method('isInitialized')->willReturn(true);

        /** @var ContaoFrameworkInterface $framework */
        $listener = new MergeHttpHeadersListener(['foobar_route'], $framework);
        $listener->setHeaders(['FOOBAR: content']);

        $listener->onKernelResponse($responseEvent);
        $response = $responseEvent->getResponse();
        $this->assertTrue($response->headers->has('FOOBAR'));
        $this->assertSame('content', $response->headers->get('FOOBAR'));
    }

    /**
     * Tests that if the response object already contains the header that shall
     * be sent using header(), it is not overridden. The response object always
     * has priority.
     */
    public function testHeadersAreNotOverridenIfAlreadyPresentInResponse()
    {
        $request = new Request();
        $response = new Response();
        $response->headers->set('FOOBAR', 'content');
        $request->attributes->set('_route', 'foobar_route');
        $responseEvent = new FilterResponseEvent(
            $this->mockKernel(),
            $request,
            HttpKernelInterface::MASTER_REQUEST,
            $response
        );

        $framework =  $this->getMock('Contao\\CoreBundle\\ContaoFrameworkInterface');
        $framework->expects($this->once())->method('isInitialized')->willReturn(true);

        /** @var ContaoFrameworkInterface $framework */
        $listener = new MergeHttpHeadersListener(['foobar_route'], $framework);
        $listener->setHeaders(['FOOBAR: new-content']);

        $listener->onKernelResponse($responseEvent);
        $response = $responseEvent->getResponse();
        $this->assertTrue($response->headers->has('FOOBAR'));
        $this->assertSame('content', $response->headers->get('FOOBAR'));
    }
}
