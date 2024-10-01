<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao\CoreBundle\Tests\Cache;

use Contao\CoreBundle\Cache\CacheTagInvalidator;
use Contao\CoreBundle\Event\InvalidateCacheTagsEvent;
use Contao\CoreBundle\Tests\TestCase;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

class CacheTagInvalidatorTest extends TestCase
{
    public function testDispatchesEvent(): void
    {
        $tags = ['foo', 'bar'];

        $eventDispatcher = $this->createMock(EventDispatcherInterface::class);
        $eventDispatcher
            ->expects($this->once())
            ->method('dispatch')
            ->with($this->callback(
                function (InvalidateCacheTagsEvent $event) use ($tags) {
                    $this->assertSame($tags, $event->getTags());

                    return true;
                },
            ))
        ;

        $cacheTagInvalidator = new CacheTagInvalidator($eventDispatcher);
        $cacheTagInvalidator->invalidateTags($tags);
    }
}
