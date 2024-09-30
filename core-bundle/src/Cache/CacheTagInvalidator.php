<?php

declare(strict_types=1);

namespace Contao\CoreBundle\Cache;

use Contao\CoreBundle\Event\InvalidateCacheTagsEvent;
use FOS\HttpCache\CacheInvalidator;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

class CacheTagInvalidator
{
    public function __construct(
        private readonly EventDispatcherInterface $eventDispatcher,
        private readonly CacheInvalidator|null $cacheInvalidator = null,
    ) {
    }

    /**
     * @param array<string> $tags
     */
    public function invalidateTags(array $tags): self
    {
        if ([] === $tags) {
            return $this;
        }

        $this->eventDispatcher->dispatch(new InvalidateCacheTagsEvent($tags));
        $this->cacheInvalidator?->invalidateTags($tags);

        return $this;
    }
}
