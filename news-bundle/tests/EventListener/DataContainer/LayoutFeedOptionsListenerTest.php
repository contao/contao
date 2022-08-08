<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace EventListener\DataContainer;

use Contao\NewsBundle\EventListener\DataContainer\LayoutFeedOptionsListener;
use Contao\PageModel;
use Contao\TestCase\ContaoTestCase;

class LayoutFeedOptionsListenerTest extends ContaoTestCase
{
    public function testReturnsAllNewsFeeds(): void
    {
        $pageAdapter = $this->mockAdapter(['findByType']);
        $pageAdapter
            ->expects($this->once())
            ->method('findByType')
            ->with('news_feed')
            ->willReturn(
                [
                    $this->mockClassWithProperties(PageModel::class, [
                        'id' => 1,
                        'title' => 'Example Feed',
                        'feedFormat' => 'rss',
                    ]),
                    $this->mockClassWithProperties(PageModel::class, [
                        'id' => 2,
                        'title' => 'Example Feed',
                        'feedFormat' => 'atom',
                    ]),
                    $this->mockClassWithProperties(PageModel::class, [
                        'id' => 3,
                        'title' => 'Example Feed',
                        'feedFormat' => 'json',
                    ]),
                ]
            )
        ;

        $framework = $this->mockContaoFramework([PageModel::class => $pageAdapter]);
        $listener = new LayoutFeedOptionsListener($framework);

        $this->assertSame(
            [
                1 => 'Example Feed (RSS 2.0)',
                2 => 'Example Feed (Atom)',
                3 => 'Example Feed (JSON)',
            ],
            $listener()
        );
    }

    public function testReturnsEmptyArrayIfNoFeedsExist(): void
    {
        $pageAdapter = $this->mockAdapter(['findByType']);
        $pageAdapter
            ->expects($this->once())
            ->method('findByType')
            ->with('news_feed')
            ->willReturn(null)
        ;

        $framework = $this->mockContaoFramework([PageModel::class => $pageAdapter]);
        $listener = new LayoutFeedOptionsListener($framework);

        $this->assertSame([], $listener());
    }
}
