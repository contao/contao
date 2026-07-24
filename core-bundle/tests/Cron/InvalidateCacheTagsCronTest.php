<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao\CoreBundle\Tests\Cron;

use Contao\CoreBundle\Cache\CacheTagManager;
use Contao\CoreBundle\Cron\InvalidateCacheTagsCron;
use Contao\CoreBundle\Entity\CacheTagInvalidation;
use Contao\CoreBundle\Repository\CacheTagInvalidationRepository;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Clock\MockClock;

class InvalidateCacheTagsCronTest extends TestCase
{
    public function testInvalidatesTagsAndRemovesDueEntries(): void
    {
        $now = new \DateTimeImmutable('2026-07-15 12:00:00');
        $first = $this->createStub(CacheTagInvalidation::class);
        $first
            ->method('getId')
            ->willReturn(1)
        ;

        $first
            ->method('getTags')
            ->willReturn(['foo', 'bar'])
        ;

        $second = $this->createStub(CacheTagInvalidation::class);
        $second
            ->method('getId')
            ->willReturn(2)
        ;

        $second
            ->method('getTags')
            ->willReturn(['bar', 'baz'])
        ;

        $repository = $this->createMock(CacheTagInvalidationRepository::class);
        $repository
            ->expects($this->once())
            ->method('findDue')
            ->with($now)
            ->willReturn([$first, $second])
        ;

        $repository
            ->expects($this->once())
            ->method('removeByIds')
            ->with([1, 2])
        ;

        $cacheTagManager = $this->createMock(CacheTagManager::class);
        $cacheTagManager
            ->expects($this->once())
            ->method('invalidateTags')
            ->with(['foo', 'bar', 'baz'])
        ;

        new InvalidateCacheTagsCron($repository, $cacheTagManager, new MockClock($now))();
    }

    public function testDoesNothingWithoutDueEntries(): void
    {
        $repository = $this->createMock(CacheTagInvalidationRepository::class);
        $repository
            ->expects($this->once())
            ->method('findDue')
            ->willReturn([])
        ;

        $repository
            ->expects($this->never())
            ->method('removeByIds')
        ;

        $cacheTagManager = $this->createMock(CacheTagManager::class);
        $cacheTagManager
            ->expects($this->never())
            ->method('invalidateTags')
        ;

        new InvalidateCacheTagsCron($repository, $cacheTagManager)();
    }
}
