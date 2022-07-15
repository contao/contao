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

use Contao\CoreBundle\EventListener\HttpCache\StripCookiesSubscriber;
use Contao\ManagerBundle\HttpKernel\ContaoCache;
use Contao\ManagerBundle\HttpKernel\ContaoKernel;
use Contao\TestCase\ContaoTestCase;
use FOS\HttpCache\SymfonyCache\CleanupCacheTagsListener;
use FOS\HttpCache\SymfonyCache\Events;
use FOS\HttpCache\SymfonyCache\PurgeListener;
use FOS\HttpCache\SymfonyCache\PurgeTagsListener;
use Symfony\Component\DependencyInjection\Container;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Toflar\Psr6HttpCacheStore\Psr6Store;

class ContaoCacheTest extends ContaoTestCase
{
    public function testAddsTheEventSubscribers(): void
    {
        $cache = new ContaoCache($this->createMock(ContaoKernel::class), $this->getTempDir());
        $dispatcher = $cache->getEventDispatcher();
        $preHandle = $dispatcher->getListeners(Events::PRE_HANDLE);
        $preInvalidateListeners = $dispatcher->getListeners(Events::PRE_INVALIDATE);

        $this->assertInstanceOf(StripCookiesSubscriber::class, $preHandle[0][0]);
        $this->assertInstanceOf(PurgeListener::class, $preInvalidateListeners[0][0]);
        $this->assertInstanceOf(PurgeTagsListener::class, $preInvalidateListeners[1][0]);

        $postHandleListeners = $dispatcher->getListeners(Events::POST_HANDLE);

        $this->assertInstanceOf(CleanupCacheTagsListener::class, $postHandleListeners[0][0]);
    }

    /**
     * @dataProvider cookieAllowListProvider
     */
    public function testCookieAllowListEnvVariable(string $env, array $expectedList): void
    {
        $_SERVER['COOKIE_ALLOW_LIST'] = $env;

        $cache = new ContaoCache($this->createMock(ContaoKernel::class), $this->getTempDir());
        $dispatcher = $cache->getEventDispatcher();
        $preHandle = $dispatcher->getListeners(Events::PRE_HANDLE);

        /** @var StripCookiesSubscriber $cookieSubscriber */
        $cookieSubscriber = $preHandle[0][0];

        $this->assertSame($expectedList, $cookieSubscriber->getAllowList());

        // Cleanup
        unset($_SERVER['COOKIE_ALLOW_LIST']);
    }

    public function cookieAllowListProvider(): \Generator
    {
        yield [
            '',
            [],
        ];

        yield [
            'PHPSESSID',
            ['PHPSESSID'],
        ];

        yield [
            'PHPSESSID,^my-regex$,another_cookie',
            ['PHPSESSID', '^my-regex$', 'another_cookie'],
        ];
    }

    public function testCreatesTheCacheStore(): void
    {
        $cache = new ContaoCache($this->createMock(ContaoKernel::class), $this->getTempDir());

        $this->assertInstanceOf(Psr6Store::class, $cache->getStore());
    }

    public function testDoesNotDispatchTerminateEventOnCacheHit(): void
    {
        $response = (new Response())->setSharedMaxAge(500);

        $kernel = $this->createMock(ContaoKernel::class);
        $kernel
            ->expects($this->once())
            ->method('getContainer')
            ->willReturn(new Container())
        ;

        $kernel
            ->expects($this->once()) // Second is coming from the cache
            ->method('handle')
            ->willReturnOnConsecutiveCalls($response, $response)
        ;

        $kernel
            ->expects($this->once()) // Second is coming from the cache
            ->method('terminate')
            ->willReturnOnConsecutiveCalls($response, $response)
        ;

        $cache = new ContaoCache($kernel, $this->getTempDir());
        $request = Request::create('/foobar');

        $cache->handle($request);
        $cache->terminate($request, $response);

        $cache->handle($request);
        $cache->terminate($request, $response);
    }
}
