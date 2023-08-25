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
use Symfony\Component\HttpFoundation\ResponseHeaderBag;
use Symfony\Component\HttpKernel\Event\ResponseEvent;
use Symfony\Component\HttpKernel\HttpKernelInterface;
use Symfony\Component\HttpKernel\KernelInterface;
use Symfony\Contracts\Service\ResetInterface;

class MergeHttpHeadersListenerTest extends TestCase
{
    public function testMergesTheHeadersSent(): void
    {
        $responseEvent = $this->getResponseEvent();

        $framework = $this->createMock(ContaoFramework::class);
        $framework
            ->expects($this->once())
            ->method('isInitialized')
            ->willReturn(true)
        ;

        $listener = new MergeHttpHeadersListener($framework, new MemoryHeaderStorage(['Content-Type: text/html']));
        $listener($responseEvent);

        $response = $responseEvent->getResponse();

        $this->assertTrue($response->headers->has('Content-Type'));
        $this->assertSame('text/html', $response->headers->get('Content-Type'));
    }

    public function testDoesNotMergeTheHeadersSentIfTheContaoFrameworkIsNotInitialized(): void
    {
        $responseEvent = $this->getResponseEvent();

        $framework = $this->createMock(ContaoFramework::class);
        $framework
            ->expects($this->once())
            ->method('isInitialized')
            ->willReturn(false)
        ;

        $listener = new MergeHttpHeadersListener($framework, new MemoryHeaderStorage(['Content-Type: text/html']));
        $listener($responseEvent);

        $this->assertFalse($responseEvent->getResponse()->headers->has('Content-Type'));
    }

    public function testDoesNotOverrideMultiValueHeaders(): void
    {
        $response = new Response();
        $response->headers->set('Set-Cookie', 'content=foobar');

        $responseEvent = $this->getResponseEvent($response);

        $framework = $this->createMock(ContaoFramework::class);
        $framework
            ->expects($this->once())
            ->method('isInitialized')
            ->willReturn(true)
        ;

        $headers = new MemoryHeaderStorage(['set-cookie: new-content=foobar']); // lower-case key

        $listener = new MergeHttpHeadersListener($framework, $headers);
        $listener($responseEvent);

        $response = $responseEvent->getResponse();

        $this->assertTrue($response->headers->has('Set-Cookie'));

        $allHeaders = $response->headers->all('Set-Cookie');

        $this->assertSame('content=foobar; path=/', $allHeaders[0]);
        $this->assertSame('new-content=foobar; path=/', $allHeaders[1]);
    }

    public function testAddsAndRemovesMultiValueHeaders(): void
    {
        $listener = new MergeHttpHeadersListener($this->mockContaoFramework());

        $this->assertSame(
            [
                'set-cookie',
                'link',
                'vary',
                'pragma',
                'cache-control',
            ],
            $listener->getMultiHeaders(),
        );

        $listener->removeMultiHeader('cache-control');

        $this->assertSame(
            [
                'set-cookie',
                'link',
                'vary',
                'pragma',
            ],
            $listener->getMultiHeaders(),
        );

        $listener->addMultiHeader('dummy');

        $this->assertSame(
            [
                'set-cookie',
                'link',
                'vary',
                'pragma',
                'dummy',
            ],
            $listener->getMultiHeaders(),
        );

        $listener->setMultiHeader(['set-cookie', 'link', 'vary', 'pragma', 'cache-control']);

        $this->assertSame(
            [
                'set-cookie',
                'link',
                'vary',
                'pragma',
                'cache-control',
            ],
            $listener->getMultiHeaders(),
        );
    }

    public function testInheritsHeadersFromSubrequest(): void
    {
        $responseEvent = $this->getResponseEvent();

        $framework = $this->createMock(ContaoFramework::class);
        $framework
            ->expects($this->atLeastOnce())
            ->method('isInitialized')
            ->willReturn(true)
        ;

        $headerStorage = new MemoryHeaderStorage(['Content-Type: text/html']);

        $listener = new MergeHttpHeadersListener($framework, $headerStorage);
        $listener($responseEvent);

        $response = $responseEvent->getResponse();

        $this->assertTrue($response->headers->has('Content-Type'));
        $this->assertSame('text/html', $response->headers->get('Content-Type'));

        $headerStorage->add('Content-Type: application/json');

        $responseEvent->setResponse(new Response());
        $listener($responseEvent);

        $response = $responseEvent->getResponse();

        $this->assertTrue($response->headers->has('Content-Type'));
        $this->assertSame('application/json', $response->headers->get('Content-Type'));
    }

    public function testInheritsMultiHeadersFromSubrequest(): void
    {
        $responseEvent = $this->getResponseEvent();

        $framework = $this->createMock(ContaoFramework::class);
        $framework
            ->expects($this->atLeastOnce())
            ->method('isInitialized')
            ->willReturn(true)
        ;

        $headerStorage = new MemoryHeaderStorage(['Set-Cookie: content=foobar']);

        $listener = new MergeHttpHeadersListener($framework, $headerStorage);
        $listener($responseEvent);

        $response = $responseEvent->getResponse();
        $allHeaders = $response->headers->all('Set-Cookie');

        $this->assertTrue($response->headers->has('Set-Cookie'));
        $this->assertCount(1, $allHeaders);
        $this->assertSame('content=foobar; path=/', $allHeaders[0]);

        $headerStorage->add('Set-Cookie: new-content=foobar');

        $responseEvent->setResponse(new Response());
        $listener($responseEvent);

        $response = $responseEvent->getResponse();

        $allHeaders = $response->headers->all('Set-Cookie');

        $this->assertTrue($response->headers->has('Set-Cookie'));
        $this->assertCount(2, $allHeaders);
        $this->assertSame('content=foobar; path=/', $allHeaders[0]);
        $this->assertSame('new-content=foobar; path=/', $allHeaders[1]);
    }

    public function testDoesNotMergeCacheControlHeaders(): void
    {
        $responseEvent = $this->getResponseEvent();

        $framework = $this->createMock(ContaoFramework::class);
        $framework
            ->expects($this->once())
            ->method('isInitialized')
            ->willReturn(true)
        ;

        $headerStorage = new MemoryHeaderStorage(['Cache-Control: public, s-maxage=10800']);

        $listener = new MergeHttpHeadersListener($framework, $headerStorage);
        $listener($responseEvent);

        $response = $responseEvent->getResponse();

        $this->assertTrue($response->headers->has('Cache-Control'));
        $this->assertSame('no-cache, private', $response->headers->get('Cache-Control'));
    }

    public function testSetsTheStatusCodeFromHttpHeader(): void
    {
        $responseEvent = $this->getResponseEvent();

        $framework = $this->createMock(ContaoFramework::class);
        $framework
            ->expects($this->once())
            ->method('isInitialized')
            ->willReturn(true)
        ;

        $storage = new MemoryHeaderStorage(['HTTP/1.1 404 Not Found']);

        $listener = new MergeHttpHeadersListener($framework, $storage);
        $listener($responseEvent);

        $response = $responseEvent->getResponse();

        $this->assertSame(404, $response->getStatusCode());
    }

    public function testServiceIsResetable(): void
    {
        $response = new Response();
        $response->headers = $this->createMock(ResponseHeaderBag::class);

        $response->headers
            ->expects($this->exactly(2))
            ->method('set')
            ->with('foo', 'Bar')
        ;

        $framework = $this->createMock(ContaoFramework::class);
        $framework
            ->expects($this->exactly(3))
            ->method('isInitialized')
            ->willReturn(true)
        ;

        $headerStorage = new MemoryHeaderStorage(['Foo: Bar']);

        $listener = new MergeHttpHeadersListener($framework, $headerStorage);

        $this->assertInstanceOf(ResetInterface::class, $listener);

        $listener($this->getResponseEvent($response));
        $listener($this->getResponseEvent($response));

        $listener->reset();
        $listener($this->getResponseEvent($response));
    }

    private function getResponseEvent(Response|null $response = null): ResponseEvent
    {
        $kernel = $this->createMock(KernelInterface::class);

        return new ResponseEvent($kernel, new Request(), HttpKernelInterface::MAIN_REQUEST, $response ?? new Response());
    }
}
