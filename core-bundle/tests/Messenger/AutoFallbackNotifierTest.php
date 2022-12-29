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
use Contao\CoreBundle\Messenger\Transport\AutoFallbackTransport;
use PHPUnit\Framework\TestCase;
use Psr\Cache\CacheItemInterface;
use Psr\Cache\CacheItemPoolInterface;
use Symfony\Component\DependencyInjection\Container;
use Symfony\Component\Messenger\Transport\TransportInterface;

class AutoFallbackNotifierTest extends TestCase
{
    public function testPing(): void
    {
        $container = new Container();
        $container->set('auto-fallback', $this->createMock(AutoFallbackTransport::class));
        $container->set('regular', $this->createMock(TransportInterface::class));

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
            ->with('auto-fallback-transport-notifier-auto-fallback')
            ->willReturn($cacheItem)
        ;
        $cache
            ->expects($this->once())
            ->method('save')
            ->with($cacheItem)
        ;

        $notifier = new AutoFallbackNotifier($cache, $container);
        $notifier->ping('auto-fallback');
        $notifier->ping('regular');
    }

    public function testIsWorkerRunning(): void
    {
        $container = new Container();
        $container->set('running-auto-fallback', $this->createMock(AutoFallbackTransport::class));
        $container->set('not-running-auto-fallback', $this->createMock(AutoFallbackTransport::class));
        $container->set('regular', $this->createMock(TransportInterface::class));

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
                ['auto-fallback-transport-notifier-running-auto-fallback'],
                ['auto-fallback-transport-notifier-not-running-auto-fallback']
            )
            ->willReturn($cacheItem)
        ;

        $notifier = new AutoFallbackNotifier($cache, $container);
        $this->assertTrue($notifier->isWorkerRunning('running-auto-fallback'));
        $this->assertFalse($notifier->isWorkerRunning('not-running-auto-fallback'));
        $this->assertFalse($notifier->isWorkerRunning('regular'));
    }
}
