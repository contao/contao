<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao\CoreBundle\Tests\Messenger;

use Contao\CoreBundle\Messenger\AutoFallbackNotifier;
use PHPUnit\Framework\TestCase;
use Psr\Cache\CacheItemInterface;
use Psr\Cache\CacheItemPoolInterface;

class AutoFallbackNotifierTest extends TestCase
{
    public function testPing(): void
    {
        $cacheItem = $this->createMock(CacheItemInterface::class);
        $cacheItem
            ->expects($this->once())
            ->method('expiresAfter')
            ->with(60)
        ;

        $cache = $this->createMock(CacheItemPoolInterface::class);
        $cache
            ->expects($this->once())
            ->method('getItem')
            ->with('auto-fallback-transport-notifier-foobar')
            ->willReturn($cacheItem)
        ;
        $cache
            ->expects($this->once())
            ->method('save')
            ->with($cacheItem)
        ;

        $notifier = new AutoFallbackNotifier($cache);
        $notifier->ping('foobar');
    }

    public function testIsWorkerRunning(): void
    {
        $cacheItem = $this->createMock(CacheItemInterface::class);
        $cacheItem
            ->expects($this->exactly(2))
            ->method('isHit')
            ->willReturnOnConsecutiveCalls(true, false)
        ;

        $cache = $this->createMock(CacheItemPoolInterface::class);
        $cache
            ->expects($this->exactly(2))
            ->method('getItem')
            ->withConsecutive(
                ['auto-fallback-transport-notifier-running'],
                ['auto-fallback-transport-notifier-not-running']
            )
            ->willReturn($cacheItem)
        ;

        $notifier = new AutoFallbackNotifier($cache);
        $this->assertTrue($notifier->isWorkerRunning('running'));
        $this->assertFalse($notifier->isWorkerRunning('not-running'));
    }
}
