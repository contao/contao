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
     * @dataProvider cookeWhitelistProvider
     */
    public function testCookieWhiteListEnvVariable(string $env, array $expectedList): void
    {
        putenv('COOKIE_WHITELIST='.$env);

        $cache = new ContaoCache($this->createMock(ContaoKernel::class), $this->getTempDir());
        $dispatcher = $cache->getEventDispatcher();
        $preHandle = $dispatcher->getListeners(Events::PRE_HANDLE);

        /** @var StripCookiesSubscriber $cookieSubscriber */
        $cookieSubscriber = $preHandle[0][0];

        $this->assertSame($expectedList, $cookieSubscriber->getWhitelist());

        // Cleanup
        putenv('COOKIE_WHITELIST=null');
    }

    public function testCreatesTheCacheStore(): void
    {
        $cache = new ContaoCache($this->createMock(ContaoKernel::class), $this->getTempDir());

        $this->assertInstanceOf(Psr6Store::class, $cache->getStore());
    }

    public function cookeWhitelistProvider(): \Generator
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
}
