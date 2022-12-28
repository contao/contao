<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao\CoreBundle\Messenger;

use Psr\Cache\CacheItemInterface;
use Psr\Cache\CacheItemPoolInterface;

class AutoFallbackNotifier
{
    public function __construct(private CacheItemPoolInterface $cache)
    {
    }

    public function ping(string $transportName): void
    {
        $item = $this->getCacheItemForTransportName($transportName);
        $item->expiresAfter(60);

        $this->cache->save($item);
    }

    public function isWorkerRunning(string $transportName): bool
    {
        return $this->getCacheItemForTransportName($transportName)->isHit();
    }

    private function getCacheItemForTransportName(string $transportName): CacheItemInterface
    {
        return $this->cache->getItem('auto-fallback-transport-notifier-'.$transportName);
    }
}
