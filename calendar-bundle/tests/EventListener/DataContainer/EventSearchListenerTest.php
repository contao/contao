<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao\CalendarBundle\Tests\EventListener\DataContainer;

use Contao\CalendarBundle\EventListener\DataContainer\EventSearchListener;
use Contao\CalendarEventsModel;
use Contao\CoreBundle\Routing\ContentUrlGenerator;
use Contao\CoreBundle\Tests\TestCase;
use Contao\DataContainer;
use Contao\Search;
use Doctrine\DBAL\Connection;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

class EventSearchListenerTest extends TestCase
{
    public function testPurgesTheSearchIndexOnAliasChange(): void
    {
        $eventModel = $this->createMock(CalendarEventsModel::class);

        $search = $this->mockAdapter(['removeEntry']);
        $search
            ->expects($this->once())
            ->method('removeEntry')
            ->with('uri')
        ;

        $adapters = [
            CalendarEventsModel::class => $this->mockConfiguredAdapter(['findById' => $eventModel]),
            Search::class => $search,
        ];

        $framework = $this->mockContaoFramework($adapters);

        $connection = $this->createMock(Connection::class);
        $connection
            ->expects($this->never())
            ->method($this->anything())
        ;

        $urlGenerator = $this->createMock(ContentUrlGenerator::class);
        $urlGenerator
            ->expects($this->once())
            ->method('generate')
            ->with($eventModel, [], UrlGeneratorInterface::ABSOLUTE_URL)
            ->willReturn('uri')
        ;

        $dc = $this->mockClassWithProperties(DataContainer::class, ['id' => 17]);
        $dc
            ->method('getCurrentRecord')
            ->willReturn(['alias' => 'foo'])
        ;

        $listener = new EventSearchListener(
            $framework,
            $connection,
            $urlGenerator,
        );

        $listener->onSaveAlias('bar', $dc);
    }

    public function testDoesNotPurgeTheSearchIndexWithUnchangedAlias(): void
    {
        $eventModel = $this->createMock(CalendarEventsModel::class);

        $search = $this->mockAdapter(['removeEntry']);
        $search
            ->expects($this->never())
            ->method($this->anything())
        ;

        $adapters = [
            CalendarEventsModel::class => $this->mockConfiguredAdapter(['findById' => $eventModel]),
            Search::class => $search,
        ];

        $framework = $this->mockContaoFramework($adapters);

        $connection = $this->createMock(Connection::class);
        $connection
            ->expects($this->never())
            ->method($this->anything())
        ;

        $urlGenerator = $this->createMock(ContentUrlGenerator::class);
        $urlGenerator
            ->expects($this->never())
            ->method($this->anything())
        ;

        $dc = $this->mockClassWithProperties(DataContainer::class, ['id' => 17]);
        $dc
            ->method('getCurrentRecord')
            ->willReturn(['alias' => 'foo'])
        ;

        $listener = new EventSearchListener(
            $framework,
            $connection,
            $urlGenerator,
        );

        $listener->onSaveAlias('foo', $dc);
    }

    public function testDoesNotPurgeTheSearchIndexWithUnchangedSearchIndexer(): void
    {
        $eventModel = $this->createMock(CalendarEventsModel::class);

        $search = $this->mockAdapter(['removeEntry']);
        $search
            ->expects($this->never())
            ->method($this->anything())
        ;

        $adapters = [
            CalendarEventsModel::class => $this->mockConfiguredAdapter(['findById' => $eventModel]),
            Search::class => $search,
        ];

        $framework = $this->mockContaoFramework($adapters);

        $connection = $this->createMock(Connection::class);
        $connection
            ->expects($this->never())
            ->method($this->anything())
        ;

        $urlGenerator = $this->createMock(ContentUrlGenerator::class);
        $urlGenerator
            ->expects($this->never())
            ->method($this->anything())
        ;

        $dc = $this->mockClassWithProperties(DataContainer::class, ['id' => 17]);
        $dc
            ->method('getCurrentRecord')
            ->willReturn(['searchIndexer' => 'always_index'])
        ;

        $listener = new EventSearchListener(
            $framework,
            $connection,
            $urlGenerator,
        );

        $listener->onSaveSearchIndexer('always_index', $dc);
    }

    public function testDoesNotPurgeTheSearchIndexOnSearchIndexerChangeToAlwaysIndex(): void
    {
        $eventModel = $this->createMock(CalendarEventsModel::class);

        $search = $this->mockAdapter(['removeEntry']);
        $search
            ->expects($this->never())
            ->method($this->anything())
        ;

        $adapters = [
            CalendarEventsModel::class => $this->mockConfiguredAdapter(['findById' => $eventModel]),
            Search::class => $search,
        ];

        $framework = $this->mockContaoFramework($adapters);

        $connection = $this->createMock(Connection::class);
        $connection
            ->expects($this->never())
            ->method($this->anything())
        ;

        $urlGenerator = $this->createMock(ContentUrlGenerator::class);
        $urlGenerator
            ->expects($this->never())
            ->method($this->anything())
        ;

        $dc = $this->mockClassWithProperties(DataContainer::class, ['id' => 17]);
        $dc
            ->method('getCurrentRecord')
            ->willReturn(['searchIndexer' => ''])
        ;

        $listener = new EventSearchListener(
            $framework,
            $connection,
            $urlGenerator,
        );

        $listener->onSaveSearchIndexer('always_index', $dc);
    }

    public function testPurgesTheSearchIndexOnSearchIndexerChangeToNeverIndex(): void
    {
        $eventModel = $this->createMock(CalendarEventsModel::class);

        $search = $this->mockAdapter(['removeEntry']);
        $search
            ->expects($this->once())
            ->method('removeEntry')
            ->with('uri')
        ;

        $adapters = [
            CalendarEventsModel::class => $this->mockConfiguredAdapter(['findById' => $eventModel]),
            Search::class => $search,
        ];

        $framework = $this->mockContaoFramework($adapters);

        $connection = $this->createMock(Connection::class);
        $connection
            ->expects($this->never())
            ->method($this->anything())
        ;

        $urlGenerator = $this->createMock(ContentUrlGenerator::class);
        $urlGenerator
            ->expects($this->once())
            ->method('generate')
            ->with($eventModel, [], UrlGeneratorInterface::ABSOLUTE_URL)
            ->willReturn('uri')
        ;

        $dc = $this->mockClassWithProperties(DataContainer::class, ['id' => 17]);
        $dc
            ->method('getCurrentRecord')
            ->willReturn(['searchIndexer' => ''])
        ;

        $listener = new EventSearchListener(
            $framework,
            $connection,
            $urlGenerator,
        );

        $listener->onSaveSearchIndexer('never_index', $dc);
    }

    public function testDoesNotPurgeTheSearchIndexOnSearchIndexerChangeToBlankAndReaderPageHasSearchIndexerAlways(): void
    {
        $eventModel = $this->createMock(CalendarEventsModel::class);

        $search = $this->mockAdapter(['removeEntry']);
        $search
            ->expects($this->never())
            ->method($this->anything())
        ;

        $adapters = [
            CalendarEventsModel::class => $this->mockConfiguredAdapter(['findById' => $eventModel]),
            Search::class => $search,
        ];

        $framework = $this->mockContaoFramework($adapters);

        $connection = $this->createMock(Connection::class);
        $connection
            ->expects($this->once())
            ->method('fetchAssociative')
            ->willReturn([
                'searchIndexer' => 'always_index',
            ])
        ;

        $urlGenerator = $this->createMock(ContentUrlGenerator::class);
        $urlGenerator
            ->expects($this->never())
            ->method($this->anything())
        ;

        $dc = $this->mockClassWithProperties(DataContainer::class, ['id' => 17]);
        $dc
            ->method('getCurrentRecord')
            ->willReturn([
                'searchIndexer' => 'always_index',
                'pid' => 5,
            ])
        ;

        $listener = new EventSearchListener(
            $framework,
            $connection,
            $urlGenerator,
        );

        $listener->onSaveSearchIndexer('', $dc);
    }

    public function testPurgesTheSearchIndexOnSearchIndexerChangeToBlankAndReaderPageHasSearchIndexerNever(): void
    {
        $eventModel = $this->createMock(CalendarEventsModel::class);

        $search = $this->mockAdapter(['removeEntry']);
        $search
            ->expects($this->once())
            ->method('removeEntry')
            ->with('uri')
        ;

        $adapters = [
            CalendarEventsModel::class => $this->mockConfiguredAdapter(['findById' => $eventModel]),
            Search::class => $search,
        ];

        $framework = $this->mockContaoFramework($adapters);

        $connection = $this->createMock(Connection::class);
        $connection
            ->expects($this->once())
            ->method('fetchAssociative')
            ->willReturn([
                'searchIndexer' => 'never_index',
            ])
        ;

        $urlGenerator = $this->createMock(ContentUrlGenerator::class);
        $urlGenerator
            ->expects($this->once())
            ->method('generate')
            ->with($eventModel, [], UrlGeneratorInterface::ABSOLUTE_URL)
            ->willReturn('uri')
        ;

        $dc = $this->mockClassWithProperties(DataContainer::class, ['id' => 17]);
        $dc
            ->method('getCurrentRecord')
            ->willReturn([
                'searchIndexer' => 'always_index',
                'pid' => 5,
            ])
        ;

        $listener = new EventSearchListener(
            $framework,
            $connection,
            $urlGenerator,
        );

        $listener->onSaveSearchIndexer('', $dc);
    }

    public function testDoesNotPurgeTheSearchIndexOnSearchIndexerChangeToBlankWithRobotsIndexAndReaderPageHasSearchIndexerBlank(): void
    {
        $eventModel = $this->createMock(CalendarEventsModel::class);

        $search = $this->mockAdapter(['removeEntry']);
        $search
            ->expects($this->never())
            ->method($this->anything())
        ;

        $adapters = [
            CalendarEventsModel::class => $this->mockConfiguredAdapter(['findById' => $eventModel]),
            Search::class => $search,
        ];

        $framework = $this->mockContaoFramework($adapters);

        $connection = $this->createMock(Connection::class);
        $connection
            ->expects($this->once())
            ->method('fetchAssociative')
            ->willReturn([
                'searchIndexer' => '',
            ])
        ;

        $urlGenerator = $this->createMock(ContentUrlGenerator::class);
        $urlGenerator
            ->expects($this->never())
            ->method($this->anything())
        ;

        $dc = $this->mockClassWithProperties(DataContainer::class, ['id' => 17]);
        $dc
            ->method('getCurrentRecord')
            ->willReturn([
                'robots' => 'index,follow',
                'searchIndexer' => 'always_index',
                'pid' => 5,
            ])
        ;

        $listener = new EventSearchListener(
            $framework,
            $connection,
            $urlGenerator,
        );

        $listener->onSaveSearchIndexer('', $dc);
    }

    public function testPurgesTheSearchIndexOnSearchIndexerChangeToBlankWithRobotsNoindexAndReaderPageHasSearchIndexerBlank(): void
    {
        $eventModel = $this->createMock(CalendarEventsModel::class);

        $search = $this->mockAdapter(['removeEntry']);
        $search
            ->expects($this->once())
            ->method('removeEntry')
            ->with('uri')
        ;

        $adapters = [
            CalendarEventsModel::class => $this->mockConfiguredAdapter(['findById' => $eventModel]),
            Search::class => $search,
        ];

        $framework = $this->mockContaoFramework($adapters);

        $connection = $this->createMock(Connection::class);
        $connection
            ->expects($this->once())
            ->method('fetchAssociative')
            ->willReturn([
                'searchIndexer' => '',
            ])
        ;

        $urlGenerator = $this->createMock(ContentUrlGenerator::class);
        $urlGenerator
            ->expects($this->once())
            ->method('generate')
            ->with($eventModel, [], UrlGeneratorInterface::ABSOLUTE_URL)
            ->willReturn('uri')
        ;

        $dc = $this->mockClassWithProperties(DataContainer::class, ['id' => 17]);
        $dc
            ->method('getCurrentRecord')
            ->willReturn([
                'robots' => 'noindex,follow',
                'searchIndexer' => 'always_index',
                'pid' => 5,
            ])
        ;

        $listener = new EventSearchListener(
            $framework,
            $connection,
            $urlGenerator,
        );

        $listener->onSaveSearchIndexer('', $dc);
    }

    public function testDoesNotPurgeTheSearchIndexOnSearchIndexerChangeToBlankWithRobotsBlankAndReaderPageHasSearchIndexerBlankAndRobotsIndex(): void
    {
        $eventModel = $this->createMock(CalendarEventsModel::class);

        $search = $this->mockAdapter(['removeEntry']);
        $search
            ->expects($this->never())
            ->method($this->anything())
        ;

        $adapters = [
            CalendarEventsModel::class => $this->mockConfiguredAdapter(['findById' => $eventModel]),
            Search::class => $search,
        ];

        $framework = $this->mockContaoFramework($adapters);

        $connection = $this->createMock(Connection::class);
        $connection
            ->expects($this->once())
            ->method('fetchAssociative')
            ->willReturn([
                'searchIndexer' => '',
                'robots' => 'index,follow',
            ])
        ;

        $urlGenerator = $this->createMock(ContentUrlGenerator::class);
        $urlGenerator
            ->expects($this->never())
            ->method($this->anything())
        ;

        $dc = $this->mockClassWithProperties(DataContainer::class, ['id' => 17]);
        $dc
            ->method('getCurrentRecord')
            ->willReturn([
                'searchIndexer' => 'always_index',
                'pid' => 5,
            ])
        ;

        $listener = new EventSearchListener(
            $framework,
            $connection,
            $urlGenerator,
        );

        $listener->onSaveSearchIndexer('', $dc);
    }

    public function testPurgesTheSearchIndexOnSearchIndexerChangeToBlankWithRobotsBlankAndReaderPageHasSearchIndexerBlankAndRobotsNoindex(): void
    {
        $eventModel = $this->createMock(CalendarEventsModel::class);

        $search = $this->mockAdapter(['removeEntry']);
        $search
            ->expects($this->once())
            ->method('removeEntry')
            ->with('uri')
        ;

        $adapters = [
            CalendarEventsModel::class => $this->mockConfiguredAdapter(['findById' => $eventModel]),
            Search::class => $search,
        ];

        $framework = $this->mockContaoFramework($adapters);

        $connection = $this->createMock(Connection::class);
        $connection
            ->expects($this->once())
            ->method('fetchAssociative')
            ->willReturn([
                'searchIndexer' => '',
                'robots' => 'noindex,follow',
            ])
        ;

        $urlGenerator = $this->createMock(ContentUrlGenerator::class);
        $urlGenerator
            ->expects($this->once())
            ->method('generate')
            ->with($eventModel, [], UrlGeneratorInterface::ABSOLUTE_URL)
            ->willReturn('uri')
        ;

        $dc = $this->mockClassWithProperties(DataContainer::class, ['id' => 17]);
        $dc
            ->method('getCurrentRecord')
            ->willReturn([
                'searchIndexer' => 'always_index',
                'pid' => 5,
            ])
        ;

        $listener = new EventSearchListener(
            $framework,
            $connection,
            $urlGenerator,
        );

        $listener->onSaveSearchIndexer('', $dc);
    }

    public function testDoesNotPurgeTheSearchIndexWithUnchangedRobots(): void
    {
        $eventModel = $this->createMock(CalendarEventsModel::class);

        $search = $this->mockAdapter(['removeEntry']);
        $search
            ->expects($this->never())
            ->method($this->anything())
        ;

        $adapters = [
            CalendarEventsModel::class => $this->mockConfiguredAdapter(['findById' => $eventModel]),
            Search::class => $search,
        ];

        $framework = $this->mockContaoFramework($adapters);

        $connection = $this->createMock(Connection::class);
        $connection
            ->expects($this->never())
            ->method($this->anything())
        ;

        $urlGenerator = $this->createMock(ContentUrlGenerator::class);
        $urlGenerator
            ->expects($this->never())
            ->method($this->anything())
        ;

        $dc = $this->mockClassWithProperties(DataContainer::class, ['id' => 17]);
        $dc
            ->method('getCurrentRecord')
            ->willReturn([
                'robots' => 'index,follow',
                'pid' => 5,
            ])
        ;

        $listener = new EventSearchListener(
            $framework,
            $connection,
            $urlGenerator,
        );

        $listener->onSaveRobots('index,follow', $dc);
    }

    public function testDoesNotPurgeTheSearchIndexOnRobotsChangeToIndexWhenSearchIndexerHasAlwaysIndex(): void
    {
        $eventModel = $this->createMock(CalendarEventsModel::class);

        $search = $this->mockAdapter(['removeEntry']);
        $search
            ->expects($this->never())
            ->method($this->anything())
        ;

        $adapters = [
            CalendarEventsModel::class => $this->mockConfiguredAdapter(['findById' => $eventModel]),
            Search::class => $search,
        ];

        $framework = $this->mockContaoFramework($adapters);

        $connection = $this->createMock(Connection::class);
        $connection
            ->expects($this->never())
            ->method($this->anything())
        ;

        $urlGenerator = $this->createMock(ContentUrlGenerator::class);
        $urlGenerator
            ->expects($this->never())
            ->method($this->anything())
        ;

        $dc = $this->mockClassWithProperties(DataContainer::class, ['id' => 17]);
        $dc
            ->method('getCurrentRecord')
            ->willReturn([
                'robots' => '',
                'searchIndexer' => 'always_index',
                'pid' => 5,
            ])
        ;

        $listener = new EventSearchListener(
            $framework,
            $connection,
            $urlGenerator,
        );

        $listener->onSaveRobots('index,follow', $dc);
    }

    public function testPurgesTheSearchIndexOnRobotsChangeToIndexWhenSearchIndexerHasNeverIndex(): void
    {
        $eventModel = $this->createMock(CalendarEventsModel::class);

        $search = $this->mockAdapter(['removeEntry']);
        $search
            ->expects($this->once())
            ->method('removeEntry')
            ->with('uri')
        ;

        $adapters = [
            CalendarEventsModel::class => $this->mockConfiguredAdapter(['findById' => $eventModel]),
            Search::class => $search,
        ];

        $framework = $this->mockContaoFramework($adapters);

        $connection = $this->createMock(Connection::class);
        $connection
            ->expects($this->never())
            ->method($this->anything())
        ;

        $urlGenerator = $this->createMock(ContentUrlGenerator::class);
        $urlGenerator
            ->expects($this->once())
            ->method('generate')
            ->with($eventModel, [], UrlGeneratorInterface::ABSOLUTE_URL)
            ->willReturn('uri')
        ;

        $dc = $this->mockClassWithProperties(DataContainer::class, ['id' => 17]);
        $dc
            ->method('getCurrentRecord')
            ->willReturn([
                'robots' => '',
                'searchIndexer' => 'never_index',
                'pid' => 5,
            ])
        ;

        $listener = new EventSearchListener(
            $framework,
            $connection,
            $urlGenerator,
        );

        $listener->onSaveRobots('index,follow', $dc);
    }

    public function testDoesNotPurgeTheSearchIndexOnRobotsChangeToBlankWhenSearchIndexerHasAlwaysIndex(): void
    {
        $eventModel = $this->createMock(CalendarEventsModel::class);

        $search = $this->mockAdapter(['removeEntry']);
        $search
            ->expects($this->never())
            ->method($this->anything())
        ;

        $adapters = [
            CalendarEventsModel::class => $this->mockConfiguredAdapter(['findById' => $eventModel]),
            Search::class => $search,
        ];

        $framework = $this->mockContaoFramework($adapters);

        $connection = $this->createMock(Connection::class);
        $connection
            ->expects($this->never())
            ->method($this->anything())
        ;

        $urlGenerator = $this->createMock(ContentUrlGenerator::class);
        $urlGenerator
            ->expects($this->never())
            ->method($this->anything())
        ;

        $dc = $this->mockClassWithProperties(DataContainer::class, ['id' => 17]);
        $dc
            ->method('getCurrentRecord')
            ->willReturn([
                'robots' => 'index,follow',
                'searchIndexer' => 'always_index',
                'pid' => 5,
            ])
        ;

        $listener = new EventSearchListener(
            $framework,
            $connection,
            $urlGenerator,
        );

        $listener->onSaveRobots('', $dc);
    }

    public function testPurgesTheSearchIndexOnRobotsChangeToBlankWhenSearchIndexerHasNeverIndex(): void
    {
        $eventModel = $this->createMock(CalendarEventsModel::class);

        $search = $this->mockAdapter(['removeEntry']);
        $search
            ->expects($this->once())
            ->method('removeEntry')
            ->with('uri')
        ;

        $adapters = [
            CalendarEventsModel::class => $this->mockConfiguredAdapter(['findById' => $eventModel]),
            Search::class => $search,
        ];

        $framework = $this->mockContaoFramework($adapters);

        $connection = $this->createMock(Connection::class);
        $connection
            ->expects($this->never())
            ->method($this->anything())
        ;

        $urlGenerator = $this->createMock(ContentUrlGenerator::class);
        $urlGenerator
            ->expects($this->once())
            ->method('generate')
            ->with($eventModel, [], UrlGeneratorInterface::ABSOLUTE_URL)
            ->willReturn('uri')
        ;

        $dc = $this->mockClassWithProperties(DataContainer::class, ['id' => 17]);
        $dc
            ->method('getCurrentRecord')
            ->willReturn([
                'robots' => 'index,follow',
                'searchIndexer' => 'never_index',
                'pid' => 5,
            ])
        ;

        $listener = new EventSearchListener(
            $framework,
            $connection,
            $urlGenerator,
        );

        $listener->onSaveRobots('', $dc);
    }

    public function testDoesNotPurgeTheSearchIndexOnRobotsChangeToNoindexWhenSearchIndexerHasAlwaysIndex(): void
    {
        $eventModel = $this->createMock(CalendarEventsModel::class);

        $search = $this->mockAdapter(['removeEntry']);
        $search
            ->expects($this->never())
            ->method($this->anything())
        ;

        $adapters = [
            CalendarEventsModel::class => $this->mockConfiguredAdapter(['findById' => $eventModel]),
            Search::class => $search,
        ];

        $framework = $this->mockContaoFramework($adapters);

        $connection = $this->createMock(Connection::class);
        $connection
            ->expects($this->never())
            ->method($this->anything())
        ;

        $urlGenerator = $this->createMock(ContentUrlGenerator::class);
        $urlGenerator
            ->expects($this->never())
            ->method($this->anything())
        ;

        $dc = $this->mockClassWithProperties(DataContainer::class, ['id' => 17]);
        $dc
            ->method('getCurrentRecord')
            ->willReturn([
                'robots' => '',
                'searchIndexer' => 'always_index',
                'pid' => 5,
            ])
        ;

        $listener = new EventSearchListener(
            $framework,
            $connection,
            $urlGenerator,
        );

        $listener->onSaveRobots('noindex,follow', $dc);
    }

    public function testPurgesTheSearchIndexOnRobotsChangeToNoindexWhenSearchIndexerHasNeverIndex(): void
    {
        $eventModel = $this->createMock(CalendarEventsModel::class);

        $search = $this->mockAdapter(['removeEntry']);
        $search
            ->expects($this->once())
            ->method('removeEntry')
            ->with('uri')
        ;

        $adapters = [
            CalendarEventsModel::class => $this->mockConfiguredAdapter(['findById' => $eventModel]),
            Search::class => $search,
        ];

        $framework = $this->mockContaoFramework($adapters);

        $connection = $this->createMock(Connection::class);
        $connection
            ->expects($this->never())
            ->method($this->anything())
        ;

        $urlGenerator = $this->createMock(ContentUrlGenerator::class);
        $urlGenerator
            ->expects($this->once())
            ->method('generate')
            ->with($eventModel, [], UrlGeneratorInterface::ABSOLUTE_URL)
            ->willReturn('uri')
        ;

        $dc = $this->mockClassWithProperties(DataContainer::class, ['id' => 17]);
        $dc
            ->method('getCurrentRecord')
            ->willReturn([
                'robots' => '',
                'searchIndexer' => 'never_index',
                'pid' => 5,
            ])
        ;

        $listener = new EventSearchListener(
            $framework,
            $connection,
            $urlGenerator,
        );

        $listener->onSaveRobots('noindex,follow', $dc);
    }

    public function testDoesNotPurgeTheSearchIndexOnRobotsChangeToIndexWhenSearchIndexerIsBlankAndReaderPageHasSearchIndexerBlank(): void
    {
        $eventModel = $this->createMock(CalendarEventsModel::class);

        $search = $this->mockAdapter(['removeEntry']);
        $search
            ->expects($this->never())
            ->method($this->anything())
        ;

        $adapters = [
            CalendarEventsModel::class => $this->mockConfiguredAdapter(['findById' => $eventModel]),
            Search::class => $search,
        ];

        $framework = $this->mockContaoFramework($adapters);

        $connection = $this->createMock(Connection::class);
        $connection
            ->expects($this->once())
            ->method('fetchAssociative')
            ->willReturn([
                'searchIndexer' => '',
            ])
        ;

        $urlGenerator = $this->createMock(ContentUrlGenerator::class);
        $urlGenerator
            ->expects($this->never())
            ->method($this->anything())
        ;

        $dc = $this->mockClassWithProperties(DataContainer::class, ['id' => 17]);
        $dc
            ->method('getCurrentRecord')
            ->willReturn([
                'robots' => '',
                'searchIndexer' => '',
                'pid' => 5,
            ])
        ;

        $listener = new EventSearchListener(
            $framework,
            $connection,
            $urlGenerator,
        );

        $listener->onSaveRobots('index,follow', $dc);
    }

    public function testDoesNotPurgeTheSearchIndexOnRobotsChangeToIndexWhenSearchIndexerIsBlankAndReaderPageHasSearchIndexerSetToAlwaysIndex(): void
    {
        $eventModel = $this->createMock(CalendarEventsModel::class);

        $search = $this->mockAdapter(['removeEntry']);
        $search
            ->expects($this->never())
            ->method($this->anything())
        ;

        $adapters = [
            CalendarEventsModel::class => $this->mockConfiguredAdapter(['findById' => $eventModel]),
            Search::class => $search,
        ];

        $framework = $this->mockContaoFramework($adapters);

        $connection = $this->createMock(Connection::class);
        $connection
            ->expects($this->once())
            ->method('fetchAssociative')
            ->willReturn([
                'searchIndexer' => 'always_index',
            ])
        ;

        $urlGenerator = $this->createMock(ContentUrlGenerator::class);
        $urlGenerator
            ->expects($this->never())
            ->method($this->anything())
        ;

        $dc = $this->mockClassWithProperties(DataContainer::class, ['id' => 17]);
        $dc
            ->method('getCurrentRecord')
            ->willReturn([
                'robots' => '',
                'searchIndexer' => '',
                'pid' => 5,
            ])
        ;

        $listener = new EventSearchListener(
            $framework,
            $connection,
            $urlGenerator,
        );

        $listener->onSaveRobots('index,follow', $dc);
    }

    public function testPurgesTheSearchIndexOnRobotsChangeToIndexWhenSearchIndexerIsBlankAndReaderPageHasSearchIndexerSetToNeverIndex(): void
    {
        $eventModel = $this->createMock(CalendarEventsModel::class);

        $search = $this->mockAdapter(['removeEntry']);
        $search
            ->expects($this->once())
            ->method('removeEntry')
            ->with('uri')
        ;

        $adapters = [
            CalendarEventsModel::class => $this->mockConfiguredAdapter(['findById' => $eventModel]),
            Search::class => $search,
        ];

        $framework = $this->mockContaoFramework($adapters);

        $connection = $this->createMock(Connection::class);
        $connection
            ->expects($this->once())
            ->method('fetchAssociative')
            ->willReturn([
                'searchIndexer' => 'never_index',
            ])
        ;

        $urlGenerator = $this->createMock(ContentUrlGenerator::class);
        $urlGenerator
            ->expects($this->once())
            ->method('generate')
            ->with($eventModel, [], UrlGeneratorInterface::ABSOLUTE_URL)
            ->willReturn('uri')
        ;

        $dc = $this->mockClassWithProperties(DataContainer::class, ['id' => 17]);
        $dc
            ->method('getCurrentRecord')
            ->willReturn([
                'robots' => '',
                'searchIndexer' => '',
                'pid' => 5,
            ])
        ;

        $listener = new EventSearchListener(
            $framework,
            $connection,
            $urlGenerator,
        );

        $listener->onSaveRobots('index,follow', $dc);
    }

    public function testPurgesTheSearchIndexOnRobotsChangeToNoindexWhenSearchIndexerIsBlankAndReaderPageHasSearchIndexerSetToBlank(): void
    {
        $eventModel = $this->createMock(CalendarEventsModel::class);

        $search = $this->mockAdapter(['removeEntry']);
        $search
            ->expects($this->once())
            ->method('removeEntry')
            ->with('uri')
        ;

        $adapters = [
            CalendarEventsModel::class => $this->mockConfiguredAdapter(['findById' => $eventModel]),
            Search::class => $search,
        ];

        $framework = $this->mockContaoFramework($adapters);

        $connection = $this->createMock(Connection::class);
        $connection
            ->expects($this->once())
            ->method('fetchAssociative')
            ->willReturn([
                'searchIndexer' => '',
            ])
        ;

        $urlGenerator = $this->createMock(ContentUrlGenerator::class);
        $urlGenerator
            ->expects($this->once())
            ->method('generate')
            ->with($eventModel, [], UrlGeneratorInterface::ABSOLUTE_URL)
            ->willReturn('uri')
        ;

        $dc = $this->mockClassWithProperties(DataContainer::class, ['id' => 17]);
        $dc
            ->method('getCurrentRecord')
            ->willReturn([
                'robots' => '',
                'searchIndexer' => '',
                'pid' => 5,
            ])
        ;

        $listener = new EventSearchListener(
            $framework,
            $connection,
            $urlGenerator,
        );

        $listener->onSaveRobots('noindex,follow', $dc);
    }

    public function testDoesNotPurgeTheSearchIndexOnRobotsChangeToNoindexWhenSearchIndexerIsBlankAndReaderPageHasSearchIndexerSetToAlwaysIndex(): void
    {
        $eventModel = $this->createMock(CalendarEventsModel::class);

        $search = $this->mockAdapter(['removeEntry']);
        $search
            ->expects($this->never())
            ->method($this->anything())
        ;

        $adapters = [
            CalendarEventsModel::class => $this->mockConfiguredAdapter(['findById' => $eventModel]),
            Search::class => $search,
        ];

        $framework = $this->mockContaoFramework($adapters);

        $connection = $this->createMock(Connection::class);
        $connection
            ->expects($this->once())
            ->method('fetchAssociative')
            ->willReturn([
                'searchIndexer' => 'always_index',
            ])
        ;

        $urlGenerator = $this->createMock(ContentUrlGenerator::class);
        $urlGenerator
            ->expects($this->never())
            ->method($this->anything())
        ;

        $dc = $this->mockClassWithProperties(DataContainer::class, ['id' => 17]);
        $dc
            ->method('getCurrentRecord')
            ->willReturn([
                'robots' => '',
                'searchIndexer' => '',
                'pid' => 5,
            ])
        ;

        $listener = new EventSearchListener(
            $framework,
            $connection,
            $urlGenerator,
        );

        $listener->onSaveRobots('noindex,follow', $dc);
    }

    public function testPurgesTheSearchIndexOnRobotsChangeToNoindexWhenSearchIndexerIsBlankAndReaderPageHasSearchIndexerSetToNeverIndex(): void
    {
        $eventModel = $this->createMock(CalendarEventsModel::class);

        $search = $this->mockAdapter(['removeEntry']);
        $search
            ->expects($this->once())
            ->method('removeEntry')
            ->with('uri')
        ;

        $adapters = [
            CalendarEventsModel::class => $this->mockConfiguredAdapter(['findById' => $eventModel]),
            Search::class => $search,
        ];

        $framework = $this->mockContaoFramework($adapters);

        $connection = $this->createMock(Connection::class);
        $connection
            ->expects($this->once())
            ->method('fetchAssociative')
            ->willReturn([
                'searchIndexer' => 'never_index',
            ])
        ;

        $urlGenerator = $this->createMock(ContentUrlGenerator::class);
        $urlGenerator
            ->expects($this->once())
            ->method('generate')
            ->with($eventModel, [], UrlGeneratorInterface::ABSOLUTE_URL)
            ->willReturn('uri')
        ;

        $dc = $this->mockClassWithProperties(DataContainer::class, ['id' => 17]);
        $dc
            ->method('getCurrentRecord')
            ->willReturn([
                'robots' => '',
                'searchIndexer' => '',
                'pid' => 5,
            ])
        ;

        $listener = new EventSearchListener(
            $framework,
            $connection,
            $urlGenerator,
        );

        $listener->onSaveRobots('noindex,follow', $dc);
    }

    public function testDoesNotPurgeTheSearchIndexOnRobotsChangeToBlankWhenSearchIndexerIsBlankAndReaderPageHasSearchIndexerBlankAndRobotsIndex(): void
    {
        $eventModel = $this->createMock(CalendarEventsModel::class);

        $search = $this->mockAdapter(['removeEntry']);
        $search
            ->expects($this->never())
            ->method($this->anything())
        ;

        $adapters = [
            CalendarEventsModel::class => $this->mockConfiguredAdapter(['findById' => $eventModel]),
            Search::class => $search,
        ];

        $framework = $this->mockContaoFramework($adapters);

        $connection = $this->createMock(Connection::class);
        $connection
            ->expects($this->once())
            ->method('fetchAssociative')
            ->willReturn([
                'robots' => 'index,follow',
                'searchIndexer' => '',
            ])
        ;

        $urlGenerator = $this->createMock(ContentUrlGenerator::class);
        $urlGenerator
            ->expects($this->never())
            ->method($this->anything())
        ;

        $dc = $this->mockClassWithProperties(DataContainer::class, ['id' => 17]);
        $dc
            ->method('getCurrentRecord')
            ->willReturn([
                'robots' => 'index,follow',
                'searchIndexer' => '',
                'pid' => 5,
            ])
        ;

        $listener = new EventSearchListener(
            $framework,
            $connection,
            $urlGenerator,
        );

        $listener->onSaveRobots('', $dc);
    }

    public function testDoesNotPurgeTheSearchIndexOnRobotsChangeToBlankWhenSearchIndexerIsBlankAndReaderPageHasSearchIndexerSetToAlwaysIndex(): void
    {
        $eventModel = $this->createMock(CalendarEventsModel::class);

        $search = $this->mockAdapter(['removeEntry']);
        $search
            ->expects($this->never())
            ->method($this->anything())
        ;

        $adapters = [
            CalendarEventsModel::class => $this->mockConfiguredAdapter(['findById' => $eventModel]),
            Search::class => $search,
        ];

        $framework = $this->mockContaoFramework($adapters);

        $connection = $this->createMock(Connection::class);
        $connection
            ->expects($this->once())
            ->method('fetchAssociative')
            ->willReturn([
                'searchIndexer' => 'always_index',
            ])
        ;

        $urlGenerator = $this->createMock(ContentUrlGenerator::class);
        $urlGenerator
            ->expects($this->never())
            ->method($this->anything())
        ;

        $dc = $this->mockClassWithProperties(DataContainer::class, ['id' => 17]);
        $dc
            ->method('getCurrentRecord')
            ->willReturn([
                'robots' => 'index,follow',
                'searchIndexer' => '',
                'pid' => 5,
            ])
        ;

        $listener = new EventSearchListener(
            $framework,
            $connection,
            $urlGenerator,
        );

        $listener->onSaveRobots('', $dc);
    }

    public function testPurgesTheSearchIndexOnRobotsChangeToBlankWhenSearchIndexerIsBlankAndReaderPageHasSearchIndexerSetToNeverIndex(): void
    {
        $eventModel = $this->createMock(CalendarEventsModel::class);

        $search = $this->mockAdapter(['removeEntry']);
        $search
            ->expects($this->once())
            ->method('removeEntry')
            ->with('uri')
        ;

        $adapters = [
            CalendarEventsModel::class => $this->mockConfiguredAdapter(['findById' => $eventModel]),
            Search::class => $search,
        ];

        $framework = $this->mockContaoFramework($adapters);

        $connection = $this->createMock(Connection::class);
        $connection
            ->expects($this->once())
            ->method('fetchAssociative')
            ->willReturn([
                'searchIndexer' => 'never_index',
            ])
        ;

        $urlGenerator = $this->createMock(ContentUrlGenerator::class);
        $urlGenerator
            ->expects($this->once())
            ->method('generate')
            ->with($eventModel, [], UrlGeneratorInterface::ABSOLUTE_URL)
            ->willReturn('uri')
        ;

        $dc = $this->mockClassWithProperties(DataContainer::class, ['id' => 17]);
        $dc
            ->method('getCurrentRecord')
            ->willReturn([
                'robots' => 'index,follow',
                'searchIndexer' => '',
                'pid' => 5,
            ])
        ;

        $listener = new EventSearchListener(
            $framework,
            $connection,
            $urlGenerator,
        );

        $listener->onSaveRobots('', $dc);
    }

    public function testPurgesTheSearchIndexOnRobotsChangeToBlankWhenSearchIndexerIsBlankAndReaderPageHasSearchIndexerBlankAndRobotsNoindex(): void
    {
        $eventModel = $this->createMock(CalendarEventsModel::class);

        $search = $this->mockAdapter(['removeEntry']);
        $search
            ->expects($this->once())
            ->method('removeEntry')
            ->with('uri')
        ;

        $adapters = [
            CalendarEventsModel::class => $this->mockConfiguredAdapter(['findById' => $eventModel]),
            Search::class => $search,
        ];

        $framework = $this->mockContaoFramework($adapters);

        $connection = $this->createMock(Connection::class);
        $connection
            ->expects($this->once())
            ->method('fetchAssociative')
            ->willReturn([
                'robots' => 'noindex,follow',
                'searchIndexer' => '',
            ])
        ;

        $urlGenerator = $this->createMock(ContentUrlGenerator::class);
        $urlGenerator
            ->expects($this->once())
            ->method('generate')
            ->with($eventModel, [], UrlGeneratorInterface::ABSOLUTE_URL)
            ->willReturn('uri')
        ;

        $dc = $this->mockClassWithProperties(DataContainer::class, ['id' => 17]);
        $dc
            ->method('getCurrentRecord')
            ->willReturn([
                'robots' => 'index,follow',
                'searchIndexer' => '',
                'pid' => 5,
            ])
        ;

        $listener = new EventSearchListener(
            $framework,
            $connection,
            $urlGenerator,
        );

        $listener->onSaveRobots('', $dc);
    }

    public function testPurgesTheSearchIndexOnDelete(): void
    {
        $eventModel = $this->createMock(CalendarEventsModel::class);

        $search = $this->mockAdapter(['removeEntry']);
        $search
            ->expects($this->once())
            ->method('removeEntry')
            ->with('uri')
        ;

        $adapters = [
            CalendarEventsModel::class => $this->mockConfiguredAdapter(['findById' => $eventModel]),
            Search::class => $search,
        ];

        $framework = $this->mockContaoFramework($adapters);

        $connection = $this->createMock(Connection::class);
        $connection
            ->expects($this->never())
            ->method($this->anything())
        ;

        $urlGenerator = $this->createMock(ContentUrlGenerator::class);
        $urlGenerator
            ->expects($this->once())
            ->method('generate')
            ->with($eventModel, [], UrlGeneratorInterface::ABSOLUTE_URL)
            ->willReturn('uri')
        ;

        $dc = $this->mockClassWithProperties(DataContainer::class, ['id' => 17]);

        $listener = new EventSearchListener(
            $framework,
            $connection,
            $urlGenerator,
        );

        $listener->onDelete($dc);
    }

    public function testDoesNotPurgeTheSearchIndexWithoutId(): void
    {
        $eventModel = $this->createMock(CalendarEventsModel::class);

        $search = $this->mockAdapter(['removeEntry']);
        $search
            ->expects($this->never())
            ->method($this->anything())
        ;

        $adapters = [
            CalendarEventsModel::class => $this->mockConfiguredAdapter(['findById' => $eventModel]),
            Search::class => $search,
        ];

        $framework = $this->mockContaoFramework($adapters);

        $connection = $this->createMock(Connection::class);
        $connection
            ->expects($this->never())
            ->method($this->anything())
        ;

        $urlGenerator = $this->createMock(ContentUrlGenerator::class);
        $urlGenerator
            ->expects($this->never())
            ->method($this->anything())
        ;

        $dc = $this->mockClassWithProperties(DataContainer::class, ['id' => null]);

        $listener = new EventSearchListener(
            $framework,
            $connection,
            $urlGenerator,
        );

        $listener->onDelete($dc);
    }
}
