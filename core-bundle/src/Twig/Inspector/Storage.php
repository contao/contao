<?php

declare(strict_types=1);

namespace Contao\CoreBundle\Twig\Inspector;

use Psr\Cache\CacheItemPoolInterface;

/**
 * @internal
 */
class Storage
{
    private const CACHE_KEY = 'contao.twig.inspector';

    public function __construct(
        private readonly CacheItemPoolInterface $cachePool,
    ) {
    }

    public function get(string $path): array|null
    {
        $cache = $this->cachePool->getItem(self::CACHE_KEY)->get();

        return $cache[$path] ?? null;
    }

    public function set(string $path, array $data): void
    {
        $item = $this->cachePool->getItem(self::CACHE_KEY);

        $entries = $item->get() ?? [];
        $entries[$path] = $data;

        $item->set($entries);

        $this->cachePool->save($item);
    }
}
