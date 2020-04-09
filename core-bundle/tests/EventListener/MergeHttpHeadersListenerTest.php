<?php

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao\CoreBundle\Tests\EventListener;

use Contao\CoreBundle\EventListener\MergeHttpHeadersListener;
use Contao\CoreBundle\Framework\ContaoFrameworkInterface;
use Contao\CoreBundle\HttpKernel\Header\MemoryHeaderStorage;
use Contao\CoreBundle\Tests\TestCase;
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
     * Tests that the headers are merged into the response object.
     */
    public function testMergesTheHeadersSent()
    {
        $responseEvent = new FilterResponseEvent(
            $this->mockKernel(),
            new Request(),
            HttpKernelInterface::MASTER_REQUEST,
            new Response()
        );

        $framework = $this->createMock(ContaoFrameworkInterface::class);

        $framework
            ->expects($this->once())
            ->method('isInitialized')
            ->willReturn(true)
        ;

        $listener = new MergeHttpHeadersListener($framework, new MemoryHeaderStorage(['Content-Type: text/html']));
        $listener->onKernelResponse($responseEvent);

        $response = $responseEvent->getResponse();

        $this->assertTrue($response->headers->has('Content-Type'));
        $this->assertSame('text/html', $response->headers->get('Content-Type'));
    }

    /**
     * Tests that the listener is skipped if the framework is not initialized.
     */
    public function testDoesNotMergeTheHeadersSentIfTheContaoFrameworkIsNotInitialized()
    {
        $responseEvent = new FilterResponseEvent(
            $this->mockKernel(),
            new Request(),
            HttpKernelInterface::MASTER_REQUEST,
            new Response()
        );

        $framework = $this->createMock(ContaoFrameworkInterface::class);

        $framework
            ->expects($this->once())
            ->method('isInitialized')
            ->willReturn(false)
        ;

        $listener = new MergeHttpHeadersListener($framework, new MemoryHeaderStorage(['Content-Type: text/html']));
        $listener->onKernelResponse($responseEvent);

        $this->assertFalse($responseEvent->getResponse()->headers->has('Content-Type'));
    }

    /**
     * Tests that multi-value headers are not overridden.
     */
    public function testDoesNotOverrideMultiValueHeaders()
    {
        $response = new Response();
        $response->headers->set('Set-Cookie', 'content=foobar');

        $responseEvent = new FilterResponseEvent(
            $this->mockKernel(),
            new Request(),
            HttpKernelInterface::MASTER_REQUEST,
            $response
        );

        $framework = $this->createMock(ContaoFrameworkInterface::class);

        $framework
            ->expects($this->once())
            ->method('isInitialized')
            ->willReturn(true)
        ;

        $headers = new MemoryHeaderStorage(['set-cookie: new-content=foobar']); // lower-case key

        $listener = new MergeHttpHeadersListener($framework, $headers);
        $listener->onKernelResponse($responseEvent);

        $response = $responseEvent->getResponse();

        $this->assertTrue($response->headers->has('Set-Cookie'));

        $allHeaders = $response->headers->get('Set-Cookie', null, false);

        $this->assertSame('content=foobar; path=/', $allHeaders[0]);
        $this->assertSame('new-content=foobar; path=/', $allHeaders[1]);
    }

    /**
     * Tests that multi-value headers can be added and removed.
     */
    public function testAddsAndRemovesMultiValueHeaders()
    {
        $listener = new MergeHttpHeadersListener($this->mockContaoFramework());

        $this->assertSame(
            $listener->getMultiHeaders(),
            [
                'set-cookie',
                'link',
                'vary',
                'pragma',
                'cache-control',
            ]
        );

        $listener->removeMultiHeader('cache-control');

        $this->assertSame(
            $listener->getMultiHeaders(),
            [
                'set-cookie',
                'link',
                'vary',
                'pragma',
            ]
        );

        $listener->addMultiHeader('dummy');

        $this->assertSame(
            $listener->getMultiHeaders(),
            [
                'set-cookie',
                'link',
                'vary',
                'pragma',
                'dummy',
            ]
        );

        $listener->setMultiHeader(['set-cookie', 'link', 'vary', 'pragma', 'cache-control']);

        $this->assertSame(
            $listener->getMultiHeaders(),
            [
                'set-cookie',
                'link',
                'vary',
                'pragma',
                'cache-control',
            ]
        );
    }

    /**
     * Tests that headers are inherited from a subrequest.
     */
    public function testInheritsHeadersFromSubrequest()
    {
        $responseEvent = new FilterResponseEvent(
            $this->mockKernel(),
            new Request(),
            HttpKernelInterface::MASTER_REQUEST,
            new Response()
        );

        $framework = $this->createMock(ContaoFrameworkInterface::class);

        $framework
            ->expects($this->atLeastOnce())
            ->method('isInitialized')
            ->willReturn(true)
        ;

        $headerStorage = new MemoryHeaderStorage(['Content-Type: text/html']);

        $listener = new MergeHttpHeadersListener($framework, $headerStorage);
        $listener->onKernelResponse($responseEvent);

        $response = $responseEvent->getResponse();

        $this->assertTrue($response->headers->has('Content-Type'));
        $this->assertSame('text/html', $response->headers->get('Content-Type'));

        $headerStorage->add('Content-Type: application/json');

        $responseEvent->setResponse(new Response());
        $listener->onKernelResponse($responseEvent);

        $response = $responseEvent->getResponse();

        $this->assertTrue($response->headers->has('Content-Type'));
        $this->assertSame('application/json', $response->headers->get('Content-Type'));
    }

    /**
     * Tests that multi headers are inherited from a subrequest.
     */
    public function testInheritsMultiHeadersFromSubrequest()
    {
        $responseEvent = new FilterResponseEvent(
            $this->mockKernel(),
            new Request(),
            HttpKernelInterface::MASTER_REQUEST,
            new Response()
        );

        $framework = $this->createMock(ContaoFrameworkInterface::class);

        $framework
            ->expects($this->atLeastOnce())
            ->method('isInitialized')
            ->willReturn(true)
        ;

        $headerStorage = new MemoryHeaderStorage(['Set-Cookie: content=foobar']);

        $listener = new MergeHttpHeadersListener($framework, $headerStorage);
        $listener->onKernelResponse($responseEvent);

        $response = $responseEvent->getResponse();
        $allHeaders = $response->headers->get('Set-Cookie', null, false);

        $this->assertTrue($response->headers->has('Set-Cookie'));
        $this->assertCount(1, $allHeaders);
        $this->assertSame('content=foobar; path=/', $allHeaders[0]);

        $headerStorage->add('Set-Cookie: new-content=foobar');

        $responseEvent->setResponse(new Response());
        $listener->onKernelResponse($responseEvent);

        $response = $responseEvent->getResponse();

        $allHeaders = $response->headers->get('Set-Cookie', null, false);

        $this->assertTrue($response->headers->has('Set-Cookie'));
        $this->assertCount(2, $allHeaders);
        $this->assertSame('content=foobar; path=/', $allHeaders[0]);
        $this->assertSame('new-content=foobar; path=/', $allHeaders[1]);
    }

    /**
     * Tests that the headers are merged into the response object.
     */
    public function testDoesNotMergeCacheControlHeaders()
    {
        $responseEvent = new FilterResponseEvent(
            $this->mockKernel(),
            new Request(),
            HttpKernelInterface::MASTER_REQUEST,
            new Response()
        );

        $framework = $this->createMock(ContaoFrameworkInterface::class);

        $framework
            ->expects($this->once())
            ->method('isInitialized')
            ->willReturn(true)
        ;

        $storage = new MemoryHeaderStorage(['Cache-Control: public, s-maxage=10800']);

        $listener = new MergeHttpHeadersListener($framework, $storage);
        $listener->onKernelResponse($responseEvent);

        $response = $responseEvent->getResponse();

        $this->assertTrue($response->headers->has('Cache-Control'));
        $this->assertSame('no-cache, private', $response->headers->get('Cache-Control'));
    }

    public function testSetsTheStatusCodeFromHttpHeader()
    {
        $responseEvent = new FilterResponseEvent(
            $this->mockKernel(),
            new Request(),
            HttpKernelInterface::MASTER_REQUEST,
            new Response()
        );

        $framework = $this->createMock(ContaoFrameworkInterface::class);

        $framework
            ->expects($this->once())
            ->method('isInitialized')
            ->willReturn(true)
        ;

        $storage = new MemoryHeaderStorage(['HTTP/1.1 404 Not Found']);

        $listener = new MergeHttpHeadersListener($framework, $storage);
        $listener->onKernelResponse($responseEvent);

        $response = $responseEvent->getResponse();

        $this->assertSame(404, $response->getStatusCode());
    }
}
