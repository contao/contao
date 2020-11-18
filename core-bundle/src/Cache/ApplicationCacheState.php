<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao\CoreBundle\Cache;

use Psr\Cache\CacheItemPoolInterface;

class ApplicationCacheState
{
    private const CACHE_KEY = 'contao.app_cache_dirty';

    /**
     * @var CacheItemPoolInterface
     */
    private $cache;

    public function __construct(CacheItemPoolInterface $cache)
    {
        $this->cache = $cache;
    }

    public function isDirty(): bool
    {
        return $this->cache->hasItem(self::CACHE_KEY);
    }

    public function markDirty(): void
    {
        $item = $this->cache->getItem(self::CACHE_KEY);

        $item->set(true);

        $this->cache->save($item);
    }
}
