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
            $connection,
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
            $connection,
        );

        $listener->onSaveAlias('foo', $dc);
    }

    public function testPurgesTheSearchIndexOnSearchIndexerChangeToNeverIndex(): void
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
            ->willReturn(['searchIndexer' => ''])
        ;

        $listener = new PageSearchListener(
            $this->mockContaoFramework([Search::class => $search]),
            $connection,
        );

        $listener->onSaveSearchIndexer('never_index', $dc);
    }

    public function testDoesNotPurgeTheSearchIndexOnSearchIndexerChangeToAlwaysIndex(): void
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
            ->willReturn(['searchIndexer' => 'never_index']) // Not sure about this value... could be empty string '' (= use robots tag) or 'never_index' here I think. Does this mean that two tests for both cases should be added?
        ;

        $listener = new PageSearchListener(
            $this->mockContaoFramework([Search::class => $search]),
            $connection,
        );

        $listener->onSaveSearchIndexer('always_index', $dc);
    }

    // Not sure about the following test (change 'searchIndexer' to blank '' [= use robots tag])
    // Case 1: When 'robots tag' is 'noindex' the search index should be purged
    // Case 2: When 'robots tag' is 'index' the search index should NOT be purged
    public function testDoesNotPurgeTheSearchIndexOnSearchIndexerChangeToEmptyString(): void
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
            ->willReturn(['searchIndexer' => '']) // Not sure about this value... could be empty string '' (= use robots tag) or 'never_index'. Does this mean that two tests for both cases should be added?
        ;

        $listener = new PageSearchListener(
            $this->mockContaoFramework([Search::class => $search]),
            $connection,
        );

        $listener->onSaveSearchIndexer('', $dc); // Change to blank option = use robots tag
    }

    // Should the following test be added for all possible cases?
    // 'searchIndexer could be an empty string '', 'always_index' or 'never_index'
    public function testDoesNotPurgeTheSearchIndexWithUnchangedSearchIndexer(): void
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
            ->willReturn(['searchIndexer' => 'never_index'])
        ;

        $listener = new PageSearchListener(
            $this->mockContaoFramework([Search::class => $search]),
            $connection,
        );

        $listener->onSaveSearchIndexer('never_index', $dc);
    }
    

    // Adjustment required? (help needed)
    // If 'robots tag' gets changed from 'index' to 'noindex' an 'searchIndexer' is set to 'always_index', the search index should not be purged
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
            $connection,
        );

        $listener->onSaveRobots('noindex,follow', $dc);
    }

    // Adjustment required? (help needed)
    // If 'robots tag' gets changed from 'noindex' to 'index' an 'searchIndexer' is set to 'never_index', the search index should be purged
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
            $connection,
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
            $connection,
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
            $connection,
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
            $connection,
        );

        $listener->onDelete($dc);
    }
}
