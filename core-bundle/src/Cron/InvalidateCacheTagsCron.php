<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao\CoreBundle\Cron;

use Contao\CoreBundle\Cache\CacheTagManager;
use Contao\CoreBundle\DependencyInjection\Attribute\AsCronJob;
use Contao\CoreBundle\Repository\CacheTagInvalidationRepository;
use Symfony\Component\Clock\ClockInterface;
use Symfony\Component\Clock\NativeClock;

#[AsCronJob('minutely')]
class InvalidateCacheTagsCron
{
    public function __construct(
        private readonly CacheTagInvalidationRepository $repository,
        private readonly CacheTagManager $cacheTagManager,
        private readonly ClockInterface $clock = new NativeClock(),
    ) {
    }

    public function __invoke(): void
    {
        $invalidations = $this->repository->findDue($this->clock->now());

        if ([] === $invalidations) {
            return;
        }

        $tags = [];
        $ids = [];

        foreach ($invalidations as $invalidation) {
            $tags = [...$tags, ...$invalidation->getTags()];
            $ids[] = $invalidation->getId();
        }

        $this->cacheTagManager->invalidateTags(array_values(array_unique($tags)));
        $this->repository->removeByIds($ids);
    }
}
