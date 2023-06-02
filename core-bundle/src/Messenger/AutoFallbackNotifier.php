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

use Contao\CoreBundle\Messenger\Transport\AutoFallbackTransport;
use Psr\Cache\CacheItemInterface;
use Psr\Cache\CacheItemPoolInterface;
use Psr\Container\ContainerInterface;
use Symfony\Component\Mailer\Transport\TransportInterface;

class AutoFallbackNotifier
{
    public function __construct(
        private readonly CacheItemPoolInterface $cache,
        private readonly ContainerInterface $messengerTransportLocator,
    ) {
    }

    public function ping(string $transportName): void
    {
        if (!$this->isAutoFallbackTransport($transportName)) {
            return;
        }

        $item = $this->getCacheItemForTransportName($transportName);
        $item->expiresAfter(60);

        $this->cache->save($item);
    }

    public function isWorkerRunning(string $transportName): bool
    {
        if (!$this->isAutoFallbackTransport($transportName)) {
            return false;
        }

        return $this->getCacheItemForTransportName($transportName)->isHit();
    }

    private function isAutoFallbackTransport(string $transportName): bool
    {
        if (!$this->messengerTransportLocator->has($transportName)) {
            return false;
        }

        /** @var TransportInterface $transport */
        $transport = $this->messengerTransportLocator->get($transportName);

        return $transport instanceof AutoFallbackTransport;
    }

    private function getCacheItemForTransportName(string $transportName): CacheItemInterface
    {
        return $this->cache->getItem('auto-fallback-transport-notifier-'.$transportName);
    }
}
