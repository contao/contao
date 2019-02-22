<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao\ManagerBundle\Tests\HttpKernel;

use Contao\ManagerBundle\HttpKernel\ContaoCache;
use Contao\ManagerBundle\HttpKernel\ContaoKernel;
use Contao\TestCase\ContaoTestCase;
use FOS\HttpCache\SymfonyCache\CleanupCacheTagsListener;
use FOS\HttpCache\SymfonyCache\Events;
use FOS\HttpCache\SymfonyCache\PurgeListener;
use FOS\HttpCache\SymfonyCache\PurgeTagsListener;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Toflar\Psr6HttpCacheStore\Psr6Store;

class ContaoCacheTest extends ContaoTestCase
{
    public function testAddsTheEventSubscribers(): void
    {
        $cache = new ContaoCache($this->createMock(ContaoKernel::class), $this->getTempDir());
        $dispatcher = $cache->getEventDispatcher();
        $preInvalidateListeners = $dispatcher->getListeners(Events::PRE_INVALIDATE);

        $this->assertInstanceOf(PurgeListener::class, $preInvalidateListeners[0][0]);
        $this->assertInstanceOf(PurgeTagsListener::class, $preInvalidateListeners[1][0]);

        $postHandleListeners = $dispatcher->getListeners(Events::POST_HANDLE);

        $this->assertInstanceOf(CleanupCacheTagsListener::class, $postHandleListeners[0][0]);
    }

    public function testCreatesTheCacheStore(): void
    {
        $cache = new ContaoCache($this->createMock(ContaoKernel::class), $this->getTempDir());

        $this->assertInstanceOf(Psr6Store::class, $cache->getStore());
    }

    /**
     * @dataProvider requestProvider
     */
    public function testPrivateRequestsNeverHitTheCache(Request $request, bool $shouldBypassCache): void
    {
        $kernel = $this->createMock(ContaoKernel::class);
        $kernel
            ->method('getContainer')
            ->willReturn($this->mockContainer())
        ;

        $kernel
            ->method('handle')
            ->willReturn(new Response())
        ;

        $cache = new ContaoCache($kernel, $this->getTempDir());
        $cache->handle($request);

        $this->assertSame($shouldBypassCache, $cache->wasBypassed());
    }

    public function requestProvider(): \Generator
    {
        yield [
            Request::create('/foobar'),
            false,
        ];

        yield [
            Request::create('/foobar', 'GET', [], [], [], ['HTTP_ACCEPT' => 'application/json']),
            false,
        ];

        yield [
            Request::create('/foobar', 'GET', [], ['Cookie' => 'Value']),
            true,
        ];

        yield [
            Request::create('/foobar', 'GET', [], [], [], ['HTTP_AUTHORIZATION' => 'Basic foobar']),
            true,
        ];
    }
}
