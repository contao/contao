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
use Terminal42\HeaderReplay\SymfonyCache\HeaderReplaySubscriber;
use Toflar\Psr6HttpCacheStore\Psr6Store;

class ContaoCacheTest extends ContaoTestCase
{
    public function testAddsTheEventSubscribers(): void
    {
        $cache = new ContaoCache($this->createMock(ContaoKernel::class), $this->getTempDir());
        $dispatcher = $cache->getEventDispatcher();
        $preHandleListeners = $dispatcher->getListeners(Events::PRE_HANDLE);
        $headerReplayListener = $preHandleListeners[0][0];

        $this->assertInstanceOf(HeaderReplaySubscriber::class, $headerReplayListener);

        $reflection = new \ReflectionClass($headerReplayListener);
        $optionsProperty = $reflection->getProperty('options');
        $optionsProperty->setAccessible(true);
        $options = $optionsProperty->getValue($headerReplayListener);

        $this->assertSame(
            [
                'user_context_headers' => [
                    'cookie',
                    'authorization',
                ],
                'ignore_cookies' => ['/^csrf_./'],
            ],
            $options
        );

        $preInvalidateListeners = $dispatcher->getListeners(Events::PRE_INVALIDATE);
        $this->assertInstanceOf(PurgeListener::class, $preInvalidateListeners[0][0]);
        $this->assertInstanceOf(PurgeTagsListener::class, $preInvalidateListeners[1][0]);

        $postHandleListeners = $dispatcher->getListeners(Events::POST_HANDLE);
        $this->assertInstanceOf(CleanupCacheTagsListener::class, $postHandleListeners[1][0]);
    }

    public function testCreatesTheCacheStore(): void
    {
        $cache = new ContaoCache($this->createMock(ContaoKernel::class), $this->getTempDir());

        $this->assertInstanceOf(Psr6Store::class, $cache->getStore());
    }
}
