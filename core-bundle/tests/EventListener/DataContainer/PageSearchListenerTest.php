<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao\CoreBundle\Tests\EventListener\DataContainer;

use Contao\CoreBundle\EventListener\DataContainer\PageSearchListener;
use Contao\CoreBundle\Tests\TestCase;
use Contao\DataContainer;
use Contao\Search;
use Doctrine\DBAL\Connection;

class PageSearchListenerTest extends TestCase
{
    public function testPurgesTheSearchIndexOnAliasChange(): void
    {
        $connection = $this->createMock(Connection::class);
        $connection
            ->expects($this->once())
            ->method('fetchFirstColumn')
            ->with('SELECT url FROM tl_search WHERE pid=:pageId', ['pageId' => 17])
            ->willReturn(['uri'])
        ;

        $search = $this->mockAdapter(['removeEntry']);
        $search
            ->expects($this->once())
            ->method('removeEntry')
            ->with('uri')
        ;

        $dc = $this->mockClassWithProperties(DataContainer::class, ['id' => 17]);
        $dc
            ->method('getCurrentRecord')
            ->willReturn(['alias' => 'foo'])
        ;

        $listener = new PageSearchListener(
            $this->mockContaoFramework([Search::class => $search]),
            $connection
        );

        $listener->onSaveAlias('bar', $dc);
    }

    public function testDoesNotPurgeTheSearchIndexWithUnchangedAlias(): void
    {
        $connection = $this->createMock(Connection::class);
        $connection
            ->expects($this->never())
            ->method($this->anything())
        ;

        $search = $this->mockAdapter(['removeEntry']);
        $search
            ->expects($this->never())
            ->method($this->anything())
        ;

        $dc = $this->mockClassWithProperties(DataContainer::class, ['id' => 17]);
        $dc
            ->method('getCurrentRecord')
            ->willReturn(['alias' => 'foo'])
        ;

        $listener = new PageSearchListener(
            $this->mockContaoFramework([Search::class => $search]),
            $connection
        );

        $listener->onSaveAlias('foo', $dc);
    }

    public function testPurgesTheSearchIndexOnNoSearchChange(): void
    {
        $connection = $this->createMock(Connection::class);
        $connection
            ->expects($this->once())
            ->method('fetchFirstColumn')
            ->with('SELECT url FROM tl_search WHERE pid=:pageId', ['pageId' => 17])
            ->willReturn(['uri'])
        ;

        $search = $this->mockAdapter(['removeEntry']);
        $search
            ->expects($this->once())
            ->method('removeEntry')
            ->with('uri')
        ;

        $dc = $this->mockClassWithProperties(DataContainer::class, ['id' => 17]);
        $dc
            ->method('getCurrentRecord')
            ->willReturn(['noSearch' => 0])
        ;

        $listener = new PageSearchListener(
            $this->mockContaoFramework([Search::class => $search]),
            $connection
        );

        $listener->onSaveNoSearch('1', $dc);
    }

    public function testDoesNotPurgeTheSearchIndexIfNoSearchIsDisabled(): void
    {
        $connection = $this->createMock(Connection::class);
        $connection
            ->expects($this->never())
            ->method($this->anything())
        ;

        $search = $this->mockAdapter(['removeEntry']);
        $search
            ->expects($this->never())
            ->method($this->anything())
        ;

        $dc = $this->mockClassWithProperties(DataContainer::class, ['id' => 17]);
        $dc
            ->method('getCurrentRecord')
            ->willReturn(['noSearch' => 1])
        ;

        $listener = new PageSearchListener(
            $this->mockContaoFramework([Search::class => $search]),
            $connection
        );

        $listener->onSaveNoSearch('', $dc);
    }

    public function testDoesNotPurgeTheSearchIndexWithUnchangedNoSearch(): void
    {
        $connection = $this->createMock(Connection::class);
        $connection
            ->expects($this->never())
            ->method($this->anything())
        ;

        $search = $this->mockAdapter(['removeEntry']);
        $search
            ->expects($this->never())
            ->method($this->anything())
        ;

        $dc = $this->mockClassWithProperties(DataContainer::class, ['id' => 17]);
        $dc
            ->method('getCurrentRecord')
            ->willReturn(['noSearch' => 1])
        ;

        $listener = new PageSearchListener(
            $this->mockContaoFramework([Search::class => $search]),
            $connection
        );

        $listener->onSaveNoSearch('1', $dc);
    }

    public function testPurgesTheSearchIndexOnRobotsChange(): void
    {
        $connection = $this->createMock(Connection::class);
        $connection
            ->expects($this->once())
            ->method('fetchFirstColumn')
            ->with('SELECT url FROM tl_search WHERE pid=:pageId', ['pageId' => 17])
            ->willReturn(['uri'])
        ;

        $search = $this->mockAdapter(['removeEntry']);
        $search
            ->expects($this->once())
            ->method('removeEntry')
            ->with('uri')
        ;

        $dc = $this->mockClassWithProperties(DataContainer::class, ['id' => 17]);
        $dc
            ->method('getCurrentRecord')
            ->willReturn(['robots' => 'index,follow'])
        ;

        $listener = new PageSearchListener(
            $this->mockContaoFramework([Search::class => $search]),
            $connection
        );

        $listener->onSaveRobots('noindex,follow', $dc);
    }

    public function testDoesNotPurgeTheSearchIndexIfRobotsIsIndex(): void
    {
        $connection = $this->createMock(Connection::class);
        $connection
            ->expects($this->never())
            ->method($this->anything())
        ;

        $search = $this->mockAdapter(['removeEntry']);
        $search
            ->expects($this->never())
            ->method($this->anything())
        ;

        $dc = $this->mockClassWithProperties(DataContainer::class, ['id' => 17]);
        $dc
            ->method('getCurrentRecord')
            ->willReturn(['robots' => 'noindex,follow'])
        ;

        $listener = new PageSearchListener(
            $this->mockContaoFramework([Search::class => $search]),
            $connection
        );

        $listener->onSaveRobots('index,follow', $dc);
    }

    public function testDoesNotPurgeTheSearchIndexWithUnchangedRobots(): void
    {
        $connection = $this->createMock(Connection::class);
        $connection
            ->expects($this->never())
            ->method($this->anything())
        ;

        $search = $this->mockAdapter(['removeEntry']);
        $search
            ->expects($this->never())
            ->method($this->anything())
        ;

        $dc = $this->mockClassWithProperties(DataContainer::class, ['id' => 17]);
        $dc
            ->method('getCurrentRecord')
            ->willReturn(['robots' => 'noindex,follow'])
        ;

        $listener = new PageSearchListener(
            $this->mockContaoFramework([Search::class => $search]),
            $connection
        );

        $listener->onSaveRobots('noindex,follow', $dc);
    }

    public function testPurgesTheSearchIndexOnDelete(): void
    {
        $connection = $this->createMock(Connection::class);
        $connection
            ->expects($this->once())
            ->method('fetchFirstColumn')
            ->with('SELECT url FROM tl_search WHERE pid=:pageId', ['pageId' => 17])
            ->willReturn(['uri'])
        ;

        $search = $this->mockAdapter(['removeEntry']);
        $search
            ->expects($this->once())
            ->method('removeEntry')
            ->with('uri')
        ;

        $dc = $this->mockClassWithProperties(DataContainer::class, ['id' => 17]);

        $listener = new PageSearchListener(
            $this->mockContaoFramework([Search::class => $search]),
            $connection
        );

        $listener->onDelete($dc);
    }

    public function testDoesNotPurgeTheSearchIndexWithoutId(): void
    {
        $connection = $this->createMock(Connection::class);
        $connection
            ->expects($this->never())
            ->method($this->anything())
        ;

        $search = $this->mockAdapter(['removeEntry']);
        $search
            ->expects($this->never())
            ->method($this->anything())
        ;

        $dc = $this->mockClassWithProperties(DataContainer::class, ['id' => null]);

        $listener = new PageSearchListener(
            $this->mockContaoFramework([Search::class => $search]),
            $connection
        );

        $listener->onDelete($dc);
    }
}
