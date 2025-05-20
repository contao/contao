<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao\NewsBundle\Tests\EventListener\DataContainer;

use Contao\CoreBundle\Routing\ContentUrlGenerator;
use Contao\CoreBundle\Tests\TestCase;
use Contao\DataContainer;
use Contao\NewsBundle\EventListener\DataContainer\NewsSearchListener;
use Contao\NewsModel;
use Contao\Search;
use Doctrine\DBAL\Connection;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

class NewsSearchListenerTest extends TestCase
{
    public function testPurgesTheSearchIndexOnAliasChange(): void
    {
        $newsModel = $this->createMock(NewsModel::class);

        $search = $this->mockAdapter(['removeEntry']);
        $search
            ->expects($this->once())
            ->method('removeEntry')
            ->with('uri')
        ;

        $adapters = [
            NewsModel::class => $this->mockConfiguredAdapter(['findById' => $newsModel]),
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
            ->with($newsModel, [], UrlGeneratorInterface::ABSOLUTE_URL)
            ->willReturn('uri')
        ;

        $dc = $this->mockClassWithProperties(DataContainer::class, ['id' => 17]);
        $dc
            ->method('getCurrentRecord')
            ->willReturn(['alias' => 'foo'])
        ;

        $listener = new NewsSearchListener(
            $framework,
            $connection,
            $urlGenerator,
        );

        $listener->onSaveAlias('bar', $dc);
    }

    public function testDoesNotPurgeTheSearchIndexWithUnchangedAlias(): void
    {
        $newsModel = $this->createMock(NewsModel::class);

        $search = $this->mockAdapter(['removeEntry']);
        $search
            ->expects($this->never())
            ->method($this->anything())
        ;

        $adapters = [
            NewsModel::class => $this->mockConfiguredAdapter(['findById' => $newsModel]),
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

        $listener = new NewsSearchListener(
            $framework,
            $connection,
            $urlGenerator,
        );

        $listener->onSaveAlias('foo', $dc);
    }

    public function testDoesNotPurgeTheSearchIndexWithUnchangedSearchIndexer(): void
    {
        $newsModel = $this->createMock(NewsModel::class);

        $search = $this->mockAdapter(['removeEntry']);
        $search
            ->expects($this->never())
            ->method($this->anything())
        ;

        $adapters = [
            NewsModel::class => $this->mockConfiguredAdapter(['findById' => $newsModel]),
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

        $listener = new NewsSearchListener(
            $framework,
            $connection,
            $urlGenerator,
        );

        $listener->onSaveSearchIndexer('always_index', $dc);
    }

    public function testDoesNotPurgeTheSearchIndexOnSearchIndexerChangeToAlwaysIndex(): void
    {
        $newsModel = $this->createMock(NewsModel::class);

        $search = $this->mockAdapter(['removeEntry']);
        $search
            ->expects($this->never())
            ->method($this->anything())
        ;

        $adapters = [
            NewsModel::class => $this->mockConfiguredAdapter(['findById' => $newsModel]),
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

        $listener = new NewsSearchListener(
            $framework,
            $connection,
            $urlGenerator,
        );

        $listener->onSaveSearchIndexer('always_index', $dc);
    }

    public function testPurgesTheSearchIndexOnSearchIndexerChangeToNeverIndex(): void
    {
        $newsModel = $this->createMock(NewsModel::class);

        $search = $this->mockAdapter(['removeEntry']);
        $search
            ->expects($this->once())
            ->method('removeEntry')
            ->with('uri')
        ;

        $adapters = [
            NewsModel::class => $this->mockConfiguredAdapter(['findById' => $newsModel]),
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
            ->with($newsModel, [], UrlGeneratorInterface::ABSOLUTE_URL)
            ->willReturn('uri')
        ;

        $dc = $this->mockClassWithProperties(DataContainer::class, ['id' => 17]);
        $dc
            ->method('getCurrentRecord')
            ->willReturn(['searchIndexer' => ''])
        ;

        $listener = new NewsSearchListener(
            $framework,
            $connection,
            $urlGenerator,
        );

        $listener->onSaveSearchIndexer('never_index', $dc);
    }

    public function testDoesNotPurgeTheSearchIndexOnSearchIndexerChangeToBlankAndReaderPageHasSearchIndexerAlways(): void
    {
        $newsModel = $this->createMock(NewsModel::class);

        $search = $this->mockAdapter(['removeEntry']);
        $search
            ->expects($this->never())
            ->method($this->anything())
        ;

        $adapters = [
            NewsModel::class => $this->mockConfiguredAdapter(['findById' => $newsModel]),
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

        $listener = new NewsSearchListener(
            $framework,
            $connection,
            $urlGenerator,
        );

        $listener->onSaveSearchIndexer('', $dc);
    }

    public function testPurgesTheSearchIndexOnSearchIndexerChangeToBlankAndReaderPageHasSearchIndexerNever(): void
    {
        $newsModel = $this->createMock(NewsModel::class);

        $search = $this->mockAdapter(['removeEntry']);
        $search
            ->expects($this->once())
            ->method('removeEntry')
            ->with('uri')
        ;

        $adapters = [
            NewsModel::class => $this->mockConfiguredAdapter(['findById' => $newsModel]),
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
            ->with($newsModel, [], UrlGeneratorInterface::ABSOLUTE_URL)
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

        $listener = new NewsSearchListener(
            $framework,
            $connection,
            $urlGenerator,
        );

        $listener->onSaveSearchIndexer('', $dc);
    }

    public function testDoesNotPurgeTheSearchIndexOnSearchIndexerChangeToBlankWithRobotsIndexAndReaderPageHasSearchIndexerBlank(): void
    {
        $newsModel = $this->createMock(NewsModel::class);

        $search = $this->mockAdapter(['removeEntry']);
        $search
            ->expects($this->never())
            ->method($this->anything())
        ;

        $adapters = [
            NewsModel::class => $this->mockConfiguredAdapter(['findById' => $newsModel]),
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

        $listener = new NewsSearchListener(
            $framework,
            $connection,
            $urlGenerator,
        );

        $listener->onSaveSearchIndexer('', $dc);
    }

    public function testPurgesTheSearchIndexOnSearchIndexerChangeToBlankWithRobotsNoindexAndReaderPageHasSearchIndexerBlank(): void
    {
        $newsModel = $this->createMock(NewsModel::class);

        $search = $this->mockAdapter(['removeEntry']);
        $search
            ->expects($this->once())
            ->method('removeEntry')
            ->with('uri')
        ;

        $adapters = [
            NewsModel::class => $this->mockConfiguredAdapter(['findById' => $newsModel]),
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
            ->with($newsModel, [], UrlGeneratorInterface::ABSOLUTE_URL)
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

        $listener = new NewsSearchListener(
            $framework,
            $connection,
            $urlGenerator,
        );

        $listener->onSaveSearchIndexer('', $dc);
    }

    public function testDoesNotPurgeTheSearchIndexOnSearchIndexerChangeToBlankWithRobotsBlankAndReaderPageHasSearchIndexerBlankAndRobotsIndex(): void
    {
        $newsModel = $this->createMock(NewsModel::class);

        $search = $this->mockAdapter(['removeEntry']);
        $search
            ->expects($this->never())
            ->method($this->anything())
        ;

        $adapters = [
            NewsModel::class => $this->mockConfiguredAdapter(['findById' => $newsModel]),
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

        $listener = new NewsSearchListener(
            $framework,
            $connection,
            $urlGenerator,
        );

        $listener->onSaveSearchIndexer('', $dc);
    }

    public function testPurgesTheSearchIndexOnSearchIndexerChangeToBlankWithRobotsBlankAndReaderPageHasSearchIndexerBlankAndRobotsNoindex(): void
    {
        $newsModel = $this->createMock(NewsModel::class);

        $search = $this->mockAdapter(['removeEntry']);
        $search
            ->expects($this->once())
            ->method('removeEntry')
            ->with('uri')
        ;

        $adapters = [
            NewsModel::class => $this->mockConfiguredAdapter(['findById' => $newsModel]),
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
            ->with($newsModel, [], UrlGeneratorInterface::ABSOLUTE_URL)
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

        $listener = new NewsSearchListener(
            $framework,
            $connection,
            $urlGenerator,
        );

        $listener->onSaveSearchIndexer('', $dc);
    }

    public function testDoesNotPurgeTheSearchIndexWithUnchangedRobots(): void
    {
        $newsModel = $this->createMock(NewsModel::class);

        $search = $this->mockAdapter(['removeEntry']);
        $search
            ->expects($this->never())
            ->method($this->anything())
        ;

        $adapters = [
            NewsModel::class => $this->mockConfiguredAdapter(['findById' => $newsModel]),
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

        $listener = new NewsSearchListener(
            $framework,
            $connection,
            $urlGenerator,
        );

        $listener->onSaveRobots('index,follow', $dc);
    }

    public function testDoesNotPurgeTheSearchIndexOnRobotsChangeToIndexWhenSearchIndexerHasAlwaysIndex(): void
    {
        $newsModel = $this->createMock(NewsModel::class);

        $search = $this->mockAdapter(['removeEntry']);
        $search
            ->expects($this->never())
            ->method($this->anything())
        ;

        $adapters = [
            NewsModel::class => $this->mockConfiguredAdapter(['findById' => $newsModel]),
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

        $listener = new NewsSearchListener(
            $framework,
            $connection,
            $urlGenerator,
        );

        $listener->onSaveRobots('index,follow', $dc);
    }

    public function testPurgesTheSearchIndexOnRobotsChangeToIndexWhenSearchIndexerHasNeverIndex(): void
    {
        $newsModel = $this->createMock(NewsModel::class);

        $search = $this->mockAdapter(['removeEntry']);
        $search
            ->expects($this->once())
            ->method('removeEntry')
            ->with('uri')
        ;

        $adapters = [
            NewsModel::class => $this->mockConfiguredAdapter(['findById' => $newsModel]),
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
            ->with($newsModel, [], UrlGeneratorInterface::ABSOLUTE_URL)
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

        $listener = new NewsSearchListener(
            $framework,
            $connection,
            $urlGenerator,
        );

        $listener->onSaveRobots('index,follow', $dc);
    }

    public function testDoesNotPurgeTheSearchIndexOnRobotsChangeToBlankWhenSearchIndexerHasAlwaysIndex(): void
    {
        $newsModel = $this->createMock(NewsModel::class);

        $search = $this->mockAdapter(['removeEntry']);
        $search
            ->expects($this->never())
            ->method($this->anything())
        ;

        $adapters = [
            NewsModel::class => $this->mockConfiguredAdapter(['findById' => $newsModel]),
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

        $listener = new NewsSearchListener(
            $framework,
            $connection,
            $urlGenerator,
        );

        $listener->onSaveRobots('', $dc);
    }

    public function testPurgesTheSearchIndexOnRobotsChangeToBlankWhenSearchIndexerHasNeverIndex(): void
    {
        $newsModel = $this->createMock(NewsModel::class);

        $search = $this->mockAdapter(['removeEntry']);
        $search
            ->expects($this->once())
            ->method('removeEntry')
            ->with('uri')
        ;

        $adapters = [
            NewsModel::class => $this->mockConfiguredAdapter(['findById' => $newsModel]),
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
            ->with($newsModel, [], UrlGeneratorInterface::ABSOLUTE_URL)
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

        $listener = new NewsSearchListener(
            $framework,
            $connection,
            $urlGenerator,
        );

        $listener->onSaveRobots('', $dc);
    }

    public function testDoesNotPurgeTheSearchIndexOnRobotsChangeToNoindexWhenSearchIndexerHasAlwaysIndex(): void
    {
        $newsModel = $this->createMock(NewsModel::class);

        $search = $this->mockAdapter(['removeEntry']);
        $search
            ->expects($this->never())
            ->method($this->anything())
        ;

        $adapters = [
            NewsModel::class => $this->mockConfiguredAdapter(['findById' => $newsModel]),
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

        $listener = new NewsSearchListener(
            $framework,
            $connection,
            $urlGenerator,
        );

        $listener->onSaveRobots('noindex,follow', $dc);
    }

    public function testPurgesTheSearchIndexOnRobotsChangeToNoindexWhenSearchIndexerHasNeverIndex(): void
    {
        $newsModel = $this->createMock(NewsModel::class);

        $search = $this->mockAdapter(['removeEntry']);
        $search
            ->expects($this->once())
            ->method('removeEntry')
            ->with('uri')
        ;

        $adapters = [
            NewsModel::class => $this->mockConfiguredAdapter(['findById' => $newsModel]),
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
            ->with($newsModel, [], UrlGeneratorInterface::ABSOLUTE_URL)
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

        $listener = new NewsSearchListener(
            $framework,
            $connection,
            $urlGenerator,
        );

        $listener->onSaveRobots('noindex,follow', $dc);
    }

    public function testDoesNotPurgeTheSearchIndexOnRobotsChangeToIndexWhenSearchIndexerIsBlankAndReaderPageHasSearchIndexerBlank(): void
    {
        $newsModel = $this->createMock(NewsModel::class);

        $search = $this->mockAdapter(['removeEntry']);
        $search
            ->expects($this->never())
            ->method($this->anything())
        ;

        $adapters = [
            NewsModel::class => $this->mockConfiguredAdapter(['findById' => $newsModel]),
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

        $listener = new NewsSearchListener(
            $framework,
            $connection,
            $urlGenerator,
        );

        $listener->onSaveRobots('index,follow', $dc);
    }

    public function testDoesNotPurgeTheSearchIndexOnRobotsChangeToIndexWhenSearchIndexerIsBlankAndReaderPageHasSearchIndexerSetToAlwaysIndex(): void
    {
        $newsModel = $this->createMock(NewsModel::class);

        $search = $this->mockAdapter(['removeEntry']);
        $search
            ->expects($this->never())
            ->method($this->anything())
        ;

        $adapters = [
            NewsModel::class => $this->mockConfiguredAdapter(['findById' => $newsModel]),
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

        $listener = new NewsSearchListener(
            $framework,
            $connection,
            $urlGenerator,
        );

        $listener->onSaveRobots('index,follow', $dc);
    }

    public function testPurgesTheSearchIndexOnRobotsChangeToIndexWhenSearchIndexerIsBlankAndReaderPageHasSearchIndexerSetToNeverIndex(): void
    {
        $newsModel = $this->createMock(NewsModel::class);

        $search = $this->mockAdapter(['removeEntry']);
        $search
            ->expects($this->once())
            ->method('removeEntry')
            ->with('uri')
        ;

        $adapters = [
            NewsModel::class => $this->mockConfiguredAdapter(['findById' => $newsModel]),
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
            ->with($newsModel, [], UrlGeneratorInterface::ABSOLUTE_URL)
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

        $listener = new NewsSearchListener(
            $framework,
            $connection,
            $urlGenerator,
        );

        $listener->onSaveRobots('index,follow', $dc);
    }

    public function testPurgesTheSearchIndexOnRobotsChangeToNoindexWhenSearchIndexerIsBlankAndReaderPageHasSearchIndexerSetToBlank(): void
    {
        $newsModel = $this->createMock(NewsModel::class);

        $search = $this->mockAdapter(['removeEntry']);
        $search
            ->expects($this->once())
            ->method('removeEntry')
            ->with('uri')
        ;

        $adapters = [
            NewsModel::class => $this->mockConfiguredAdapter(['findById' => $newsModel]),
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
            ->with($newsModel, [], UrlGeneratorInterface::ABSOLUTE_URL)
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

        $listener = new NewsSearchListener(
            $framework,
            $connection,
            $urlGenerator,
        );

        $listener->onSaveRobots('noindex,follow', $dc);
    }

    public function testDoesNotPurgeTheSearchIndexOnRobotsChangeToNoindexWhenSearchIndexerIsBlankAndReaderPageHasSearchIndexerSetToAlwaysIndex(): void
    {
        $newsModel = $this->createMock(NewsModel::class);

        $search = $this->mockAdapter(['removeEntry']);
        $search
            ->expects($this->never())
            ->method($this->anything())
        ;

        $adapters = [
            NewsModel::class => $this->mockConfiguredAdapter(['findById' => $newsModel]),
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

        $listener = new NewsSearchListener(
            $framework,
            $connection,
            $urlGenerator,
        );

        $listener->onSaveRobots('noindex,follow', $dc);
    }

    public function testPurgesTheSearchIndexOnRobotsChangeToNoindexWhenSearchIndexerIsBlankAndReaderPageHasSearchIndexerSetToNeverIndex(): void
    {
        $newsModel = $this->createMock(NewsModel::class);

        $search = $this->mockAdapter(['removeEntry']);
        $search
            ->expects($this->once())
            ->method('removeEntry')
            ->with('uri')
        ;

        $adapters = [
            NewsModel::class => $this->mockConfiguredAdapter(['findById' => $newsModel]),
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
            ->with($newsModel, [], UrlGeneratorInterface::ABSOLUTE_URL)
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

        $listener = new NewsSearchListener(
            $framework,
            $connection,
            $urlGenerator,
        );

        $listener->onSaveRobots('noindex,follow', $dc);
    }

    public function testDoesNotPurgeTheSearchIndexOnRobotsChangeToBlankWhenSearchIndexerIsBlankAndReaderPageHasSearchIndexerBlankAndRobotsIndex(): void
    {
        $newsModel = $this->createMock(NewsModel::class);

        $search = $this->mockAdapter(['removeEntry']);
        $search
            ->expects($this->never())
            ->method($this->anything())
        ;

        $adapters = [
            NewsModel::class => $this->mockConfiguredAdapter(['findById' => $newsModel]),
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

        $listener = new NewsSearchListener(
            $framework,
            $connection,
            $urlGenerator,
        );

        $listener->onSaveRobots('', $dc);
    }

    public function testDoesNotPurgeTheSearchIndexOnRobotsChangeToBlankWhenSearchIndexerIsBlankAndReaderPageHasSearchIndexerSetToAlwaysIndex(): void
    {
        $newsModel = $this->createMock(NewsModel::class);

        $search = $this->mockAdapter(['removeEntry']);
        $search
            ->expects($this->never())
            ->method($this->anything())
        ;

        $adapters = [
            NewsModel::class => $this->mockConfiguredAdapter(['findById' => $newsModel]),
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

        $listener = new NewsSearchListener(
            $framework,
            $connection,
            $urlGenerator,
        );

        $listener->onSaveRobots('', $dc);
    }

    public function testPurgesTheSearchIndexOnRobotsChangeToBlankWhenSearchIndexerIsBlankAndReaderPageHasSearchIndexerSetToNeverIndex(): void
    {
        $newsModel = $this->createMock(NewsModel::class);

        $search = $this->mockAdapter(['removeEntry']);
        $search
            ->expects($this->once())
            ->method('removeEntry')
            ->with('uri')
        ;

        $adapters = [
            NewsModel::class => $this->mockConfiguredAdapter(['findById' => $newsModel]),
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
            ->with($newsModel, [], UrlGeneratorInterface::ABSOLUTE_URL)
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

        $listener = new NewsSearchListener(
            $framework,
            $connection,
            $urlGenerator,
        );

        $listener->onSaveRobots('', $dc);
    }

    public function testPurgesTheSearchIndexOnRobotsChangeToBlankWhenSearchIndexerIsBlankAndReaderPageHasSearchIndexerBlankAndRobotsNoindex(): void
    {
        $newsModel = $this->createMock(NewsModel::class);

        $search = $this->mockAdapter(['removeEntry']);
        $search
            ->expects($this->once())
            ->method('removeEntry')
            ->with('uri')
        ;

        $adapters = [
            NewsModel::class => $this->mockConfiguredAdapter(['findById' => $newsModel]),
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
            ->with($newsModel, [], UrlGeneratorInterface::ABSOLUTE_URL)
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

        $listener = new NewsSearchListener(
            $framework,
            $connection,
            $urlGenerator,
        );

        $listener->onSaveRobots('', $dc);
    }

    public function testPurgesTheSearchIndexOnDelete(): void
    {
        $newsModel = $this->createMock(NewsModel::class);

        $search = $this->mockAdapter(['removeEntry']);
        $search
            ->expects($this->once())
            ->method('removeEntry')
            ->with('uri')
        ;

        $adapters = [
            NewsModel::class => $this->mockConfiguredAdapter(['findById' => $newsModel]),
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
            ->with($newsModel, [], UrlGeneratorInterface::ABSOLUTE_URL)
            ->willReturn('uri')
        ;

        $dc = $this->mockClassWithProperties(DataContainer::class, ['id' => 17]);

        $listener = new NewsSearchListener(
            $framework,
            $connection,
            $urlGenerator,
        );

        $listener->onDelete($dc);
    }

    public function testDoesNotPurgeTheSearchIndexWithoutId(): void
    {
        $newsModel = $this->createMock(NewsModel::class);

        $search = $this->mockAdapter(['removeEntry']);
        $search
            ->expects($this->never())
            ->method($this->anything())
        ;

        $adapters = [
            NewsModel::class => $this->mockConfiguredAdapter(['findById' => $newsModel]),
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

        $listener = new NewsSearchListener(
            $framework,
            $connection,
            $urlGenerator,
        );

        $listener->onDelete($dc);
    }
}
