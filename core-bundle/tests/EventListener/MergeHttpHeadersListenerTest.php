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

use Contao\CoreBundle\EventListener\MergeHttpHeadersListener;
use Contao\CoreBundle\Framework\ContaoFramework;
use Contao\CoreBundle\HttpKernel\Header\MemoryHeaderStorage;
use Contao\CoreBundle\Tests\TestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Event\FilterResponseEvent;
use Symfony\Component\HttpKernel\HttpKernelInterface;
use Symfony\Component\HttpKernel\KernelInterface;

class MergeHttpHeadersListenerTest extends TestCase
{
    public function testMergesTheHeadersSent(): void
    {
        $responseEvent = $this->mockResponseEvent();

        $framework = $this->createMock(ContaoFramework::class);
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

    public function testDoesNotMergeTheHeadersSentIfTheContaoFrameworkIsNotInitialized(): void
    {
        $responseEvent = $this->mockResponseEvent();

        $framework = $this->createMock(ContaoFramework::class);
        $framework
            ->expects($this->once())
            ->method('isInitialized')
            ->willReturn(false)
        ;

        $listener = new MergeHttpHeadersListener($framework, new MemoryHeaderStorage(['Content-Type: text/html']));
        $listener->onKernelResponse($responseEvent);

        $this->assertFalse($responseEvent->getResponse()->headers->has('Content-Type'));
    }

    public function testDoesNotOverrideMultiValueHeaders(): void
    {
        $response = new Response();
        $response->headers->set('Set-Cookie', 'content=foobar');

        $responseEvent = $this->mockResponseEvent($response);

        $framework = $this->createMock(ContaoFramework::class);
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

    public function testAddsAndRemovesMultiValueHeaders(): void
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

    public function testInheritsHeadersFromSubrequest(): void
    {
        $responseEvent = $this->mockResponseEvent();

        $framework = $this->createMock(ContaoFramework::class);
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

    public function testInheritsMultiHeadersFromSubrequest(): void
    {
        $responseEvent = $this->mockResponseEvent();

        $framework = $this->createMock(ContaoFramework::class);
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

    public function testDoesNotMergeCacheControlHeaders(): void
    {
        $responseEvent = $this->mockResponseEvent();

        $framework = $this->createMock(ContaoFramework::class);
        $framework
            ->expects($this->once())
            ->method('isInitialized')
            ->willReturn(true)
        ;

        $headerStorage = new MemoryHeaderStorage(['Cache-Control: public, s-maxage=10800']);

        $listener = new MergeHttpHeadersListener($framework, $headerStorage);
        $listener->onKernelResponse($responseEvent);

        $response = $responseEvent->getResponse();

        $this->assertTrue($response->headers->has('Cache-Control'));
        $this->assertSame('no-cache, private', $response->headers->get('Cache-Control'));
    }

    private function mockResponseEvent(Response $response = null): FilterResponseEvent
    {
        $kernel = $this->createMock(KernelInterface::class);

        if (null === $response) {
            $response = new Response();
        }

        return new FilterResponseEvent($kernel, new Request(), HttpKernelInterface::MASTER_REQUEST, $response);
    }
}
