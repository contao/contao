<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao\FaqBundle\Tests\EventListener\DataContainer;

use Contao\CoreBundle\Routing\ContentUrlGenerator;
use Contao\CoreBundle\Tests\TestCase;
use Contao\DataContainer;
use Contao\FaqBundle\EventListener\DataContainer\FaqSearchListener;
use Contao\FaqModel;
use Contao\Search;
use Doctrine\DBAL\Connection;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

class FaqSearchListenerTest extends TestCase
{
    public function testPurgesTheSearchIndexOnAliasChange(): void
    {
        $faqModel = $this->createMock(FaqModel::class);

        $search = $this->mockAdapter(['removeEntry']);
        $search
            ->expects($this->once())
            ->method('removeEntry')
            ->with('uri')
        ;

        $adapters = [
            FaqModel::class => $this->mockConfiguredAdapter(['findById' => $faqModel]),
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
            ->with($faqModel, [], UrlGeneratorInterface::ABSOLUTE_URL)
            ->willReturn('uri')
        ;

        $dc = $this->mockClassWithProperties(DataContainer::class, ['id' => 17]);
        $dc
            ->method('getCurrentRecord')
            ->willReturn(['alias' => 'foo'])
        ;

        $listener = new FaqSearchListener(
            $framework,
            $connection,
            $urlGenerator,
        );

        $listener->onSaveAlias('bar', $dc);
    }

    public function testDoesNotPurgeTheSearchIndexWithUnchangedAlias(): void
    {
        $faqModel = $this->createMock(FaqModel::class);

        $search = $this->mockAdapter(['removeEntry']);
        $search
            ->expects($this->never())
            ->method($this->anything())
        ;

        $adapters = [
            FaqModel::class => $this->mockConfiguredAdapter(['findById' => $faqModel]),
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

        $listener = new FaqSearchListener(
            $framework,
            $connection,
            $urlGenerator,
        );

        $listener->onSaveAlias('foo', $dc);
    }

    public function testDoesNotPurgeTheSearchIndexWithUnchangedSearchIndexer(): void
    {
        $faqModel = $this->createMock(FaqModel::class);

        $search = $this->mockAdapter(['removeEntry']);
        $search
            ->expects($this->never())
            ->method($this->anything())
        ;

        $adapters = [
            FaqModel::class => $this->mockConfiguredAdapter(['findById' => $faqModel]),
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

        $listener = new FaqSearchListener(
            $framework,
            $connection,
            $urlGenerator,
        );

        $listener->onSaveSearchIndexer('always_index', $dc);
    }

    public function testDoesNotPurgeTheSearchIndexOnSearchIndexerChangeToAlwaysIndex(): void
    {
        $faqModel = $this->createMock(FaqModel::class);

        $search = $this->mockAdapter(['removeEntry']);
        $search
            ->expects($this->never())
            ->method($this->anything())
        ;

        $adapters = [
            FaqModel::class => $this->mockConfiguredAdapter(['findById' => $faqModel]),
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

        $listener = new FaqSearchListener(
            $framework,
            $connection,
            $urlGenerator,
        );

        $listener->onSaveSearchIndexer('always_index', $dc);
    }

    public function testPurgesTheSearchIndexOnSearchIndexerChangeToNeverIndex(): void
    {
        $faqModel = $this->createMock(FaqModel::class);

        $search = $this->mockAdapter(['removeEntry']);
        $search
            ->expects($this->once())
            ->method('removeEntry')
            ->with('uri')
        ;

        $adapters = [
            FaqModel::class => $this->mockConfiguredAdapter(['findById' => $faqModel]),
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
            ->with($faqModel, [], UrlGeneratorInterface::ABSOLUTE_URL)
            ->willReturn('uri')
        ;

        $dc = $this->mockClassWithProperties(DataContainer::class, ['id' => 17]);
        $dc
            ->method('getCurrentRecord')
            ->willReturn(['searchIndexer' => ''])
        ;

        $listener = new FaqSearchListener(
            $framework,
            $connection,
            $urlGenerator,
        );

        $listener->onSaveSearchIndexer('never_index', $dc);
    }

    public function testDoesNotPurgeTheSearchIndexOnSearchIndexerChangeToBlankAndReaderPageHasSearchIndexerAlways(): void
    {
        $faqModel = $this->createMock(FaqModel::class);

        $search = $this->mockAdapter(['removeEntry']);
        $search
            ->expects($this->never())
            ->method($this->anything())
        ;

        $adapters = [
            FaqModel::class => $this->mockConfiguredAdapter(['findById' => $faqModel]),
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

        $listener = new FaqSearchListener(
            $framework,
            $connection,
            $urlGenerator,
        );

        $listener->onSaveSearchIndexer('', $dc);
    }

    public function testPurgesTheSearchIndexOnSearchIndexerChangeToBlankAndReaderPageHasSearchIndexerNever(): void
    {
        $faqModel = $this->createMock(FaqModel::class);

        $search = $this->mockAdapter(['removeEntry']);
        $search
            ->expects($this->once())
            ->method('removeEntry')
            ->with('uri')
        ;

        $adapters = [
            FaqModel::class => $this->mockConfiguredAdapter(['findById' => $faqModel]),
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
            ->with($faqModel, [], UrlGeneratorInterface::ABSOLUTE_URL)
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

        $listener = new FaqSearchListener(
            $framework,
            $connection,
            $urlGenerator,
        );

        $listener->onSaveSearchIndexer('', $dc);
    }

    public function testDoesNotPurgeTheSearchIndexOnSearchIndexerChangeToBlankWithRobotsIndexAndReaderPageHasSearchIndexerBlank(): void
    {
        $faqModel = $this->createMock(FaqModel::class);

        $search = $this->mockAdapter(['removeEntry']);
        $search
            ->expects($this->never())
            ->method($this->anything())
        ;

        $adapters = [
            FaqModel::class => $this->mockConfiguredAdapter(['findById' => $faqModel]),
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

        $listener = new FaqSearchListener(
            $framework,
            $connection,
            $urlGenerator,
        );

        $listener->onSaveSearchIndexer('', $dc);
    }

    public function testPurgesTheSearchIndexOnSearchIndexerChangeToBlankWithRobotsNoindexAndReaderPageHasSearchIndexerBlank(): void
    {
        $faqModel = $this->createMock(FaqModel::class);

        $search = $this->mockAdapter(['removeEntry']);
        $search
            ->expects($this->once())
            ->method('removeEntry')
            ->with('uri')
        ;

        $adapters = [
            FaqModel::class => $this->mockConfiguredAdapter(['findById' => $faqModel]),
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
            ->with($faqModel, [], UrlGeneratorInterface::ABSOLUTE_URL)
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

        $listener = new FaqSearchListener(
            $framework,
            $connection,
            $urlGenerator,
        );

        $listener->onSaveSearchIndexer('', $dc);
    }

    public function testDoesNotPurgeTheSearchIndexOnSearchIndexerChangeToBlankWithRobotsBlankAndReaderPageHasSearchIndexerBlankAndRobotsIndex(): void
    {
        $faqModel = $this->createMock(FaqModel::class);

        $search = $this->mockAdapter(['removeEntry']);
        $search
            ->expects($this->never())
            ->method($this->anything())
        ;

        $adapters = [
            FaqModel::class => $this->mockConfiguredAdapter(['findById' => $faqModel]),
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

        $listener = new FaqSearchListener(
            $framework,
            $connection,
            $urlGenerator,
        );

        $listener->onSaveSearchIndexer('', $dc);
    }

    public function testPurgesTheSearchIndexOnSearchIndexerChangeToBlankWithRobotsBlankAndReaderPageHasSearchIndexerBlankAndRobotsNoindex(): void
    {
        $faqModel = $this->createMock(FaqModel::class);

        $search = $this->mockAdapter(['removeEntry']);
        $search
            ->expects($this->once())
            ->method('removeEntry')
            ->with('uri')
        ;

        $adapters = [
            FaqModel::class => $this->mockConfiguredAdapter(['findById' => $faqModel]),
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
            ->with($faqModel, [], UrlGeneratorInterface::ABSOLUTE_URL)
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

        $listener = new FaqSearchListener(
            $framework,
            $connection,
            $urlGenerator,
        );

        $listener->onSaveSearchIndexer('', $dc);
    }

    public function testDoesNotPurgeTheSearchIndexWithUnchangedRobots(): void
    {
        $faqModel = $this->createMock(FaqModel::class);

        $search = $this->mockAdapter(['removeEntry']);
        $search
            ->expects($this->never())
            ->method($this->anything())
        ;

        $adapters = [
            FaqModel::class => $this->mockConfiguredAdapter(['findById' => $faqModel]),
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

        $listener = new FaqSearchListener(
            $framework,
            $connection,
            $urlGenerator,
        );

        $listener->onSaveRobots('index,follow', $dc);
    }

    public function testDoesNotPurgeTheSearchIndexOnRobotsChangeToIndexWhenSearchIndexerHasAlwaysIndex(): void
    {
        $faqModel = $this->createMock(FaqModel::class);

        $search = $this->mockAdapter(['removeEntry']);
        $search
            ->expects($this->never())
            ->method($this->anything())
        ;

        $adapters = [
            FaqModel::class => $this->mockConfiguredAdapter(['findById' => $faqModel]),
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

        $listener = new FaqSearchListener(
            $framework,
            $connection,
            $urlGenerator,
        );

        $listener->onSaveRobots('index,follow', $dc);
    }

    public function testPurgesTheSearchIndexOnRobotsChangeToIndexWhenSearchIndexerHasNeverIndex(): void
    {
        $faqModel = $this->createMock(FaqModel::class);

        $search = $this->mockAdapter(['removeEntry']);
        $search
            ->expects($this->once())
            ->method('removeEntry')
            ->with('uri')
        ;

        $adapters = [
            FaqModel::class => $this->mockConfiguredAdapter(['findById' => $faqModel]),
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
            ->with($faqModel, [], UrlGeneratorInterface::ABSOLUTE_URL)
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

        $listener = new FaqSearchListener(
            $framework,
            $connection,
            $urlGenerator,
        );

        $listener->onSaveRobots('index,follow', $dc);
    }

    public function testDoesNotPurgeTheSearchIndexOnRobotsChangeToBlankWhenSearchIndexerHasAlwaysIndex(): void
    {
        $faqModel = $this->createMock(FaqModel::class);

        $search = $this->mockAdapter(['removeEntry']);
        $search
            ->expects($this->never())
            ->method($this->anything())
        ;

        $adapters = [
            FaqModel::class => $this->mockConfiguredAdapter(['findById' => $faqModel]),
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

        $listener = new FaqSearchListener(
            $framework,
            $connection,
            $urlGenerator,
        );

        $listener->onSaveRobots('', $dc);
    }

    public function testPurgesTheSearchIndexOnRobotsChangeToBlankWhenSearchIndexerHasNeverIndex(): void
    {
        $faqModel = $this->createMock(FaqModel::class);

        $search = $this->mockAdapter(['removeEntry']);
        $search
            ->expects($this->once())
            ->method('removeEntry')
            ->with('uri')
        ;

        $adapters = [
            FaqModel::class => $this->mockConfiguredAdapter(['findById' => $faqModel]),
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
            ->with($faqModel, [], UrlGeneratorInterface::ABSOLUTE_URL)
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

        $listener = new FaqSearchListener(
            $framework,
            $connection,
            $urlGenerator,
        );

        $listener->onSaveRobots('', $dc);
    }

    public function testDoesNotPurgeTheSearchIndexOnRobotsChangeToNoindexWhenSearchIndexerHasAlwaysIndex(): void
    {
        $faqModel = $this->createMock(FaqModel::class);

        $search = $this->mockAdapter(['removeEntry']);
        $search
            ->expects($this->never())
            ->method($this->anything())
        ;

        $adapters = [
            FaqModel::class => $this->mockConfiguredAdapter(['findById' => $faqModel]),
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

        $listener = new FaqSearchListener(
            $framework,
            $connection,
            $urlGenerator,
        );

        $listener->onSaveRobots('noindex,follow', $dc);
    }

    public function testPurgesTheSearchIndexOnRobotsChangeToNoindexWhenSearchIndexerHasNeverIndex(): void
    {
        $faqModel = $this->createMock(FaqModel::class);

        $search = $this->mockAdapter(['removeEntry']);
        $search
            ->expects($this->once())
            ->method('removeEntry')
            ->with('uri')
        ;

        $adapters = [
            FaqModel::class => $this->mockConfiguredAdapter(['findById' => $faqModel]),
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
            ->with($faqModel, [], UrlGeneratorInterface::ABSOLUTE_URL)
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

        $listener = new FaqSearchListener(
            $framework,
            $connection,
            $urlGenerator,
        );

        $listener->onSaveRobots('noindex,follow', $dc);
    }

    public function testDoesNotPurgeTheSearchIndexOnRobotsChangeToIndexWhenSearchIndexerIsBlankAndReaderPageHasSearchIndexerBlank(): void
    {
        $faqModel = $this->createMock(FaqModel::class);

        $search = $this->mockAdapter(['removeEntry']);
        $search
            ->expects($this->never())
            ->method($this->anything())
        ;

        $adapters = [
            FaqModel::class => $this->mockConfiguredAdapter(['findById' => $faqModel]),
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

        $listener = new FaqSearchListener(
            $framework,
            $connection,
            $urlGenerator,
        );

        $listener->onSaveRobots('index,follow', $dc);
    }

    public function testDoesNotPurgeTheSearchIndexOnRobotsChangeToIndexWhenSearchIndexerIsBlankAndReaderPageHasSearchIndexerSetToAlwaysIndex(): void
    {
        $faqModel = $this->createMock(FaqModel::class);

        $search = $this->mockAdapter(['removeEntry']);
        $search
            ->expects($this->never())
            ->method($this->anything())
        ;

        $adapters = [
            FaqModel::class => $this->mockConfiguredAdapter(['findById' => $faqModel]),
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

        $listener = new FaqSearchListener(
            $framework,
            $connection,
            $urlGenerator,
        );

        $listener->onSaveRobots('index,follow', $dc);
    }

    public function testPurgesTheSearchIndexOnRobotsChangeToIndexWhenSearchIndexerIsBlankAndReaderPageHasSearchIndexerSetToNeverIndex(): void
    {
        $faqModel = $this->createMock(FaqModel::class);

        $search = $this->mockAdapter(['removeEntry']);
        $search
            ->expects($this->once())
            ->method('removeEntry')
            ->with('uri')
        ;

        $adapters = [
            FaqModel::class => $this->mockConfiguredAdapter(['findById' => $faqModel]),
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
            ->with($faqModel, [], UrlGeneratorInterface::ABSOLUTE_URL)
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

        $listener = new FaqSearchListener(
            $framework,
            $connection,
            $urlGenerator,
        );

        $listener->onSaveRobots('index,follow', $dc);
    }

    public function testPurgesTheSearchIndexOnRobotsChangeToNoindexWhenSearchIndexerIsBlankAndReaderPageHasSearchIndexerSetToBlank(): void
    {
        $faqModel = $this->createMock(FaqModel::class);

        $search = $this->mockAdapter(['removeEntry']);
        $search
            ->expects($this->once())
            ->method('removeEntry')
            ->with('uri')
        ;

        $adapters = [
            FaqModel::class => $this->mockConfiguredAdapter(['findById' => $faqModel]),
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
            ->with($faqModel, [], UrlGeneratorInterface::ABSOLUTE_URL)
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

        $listener = new FaqSearchListener(
            $framework,
            $connection,
            $urlGenerator,
        );

        $listener->onSaveRobots('noindex,follow', $dc);
    }

    public function testDoesNotPurgeTheSearchIndexOnRobotsChangeToNoindexWhenSearchIndexerIsBlankAndReaderPageHasSearchIndexerSetToAlwaysIndex(): void
    {
        $faqModel = $this->createMock(FaqModel::class);

        $search = $this->mockAdapter(['removeEntry']);
        $search
            ->expects($this->never())
            ->method($this->anything())
        ;

        $adapters = [
            FaqModel::class => $this->mockConfiguredAdapter(['findById' => $faqModel]),
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

        $listener = new FaqSearchListener(
            $framework,
            $connection,
            $urlGenerator,
        );

        $listener->onSaveRobots('noindex,follow', $dc);
    }

    public function testPurgesTheSearchIndexOnRobotsChangeToNoindexWhenSearchIndexerIsBlankAndReaderPageHasSearchIndexerSetToNeverIndex(): void
    {
        $faqModel = $this->createMock(FaqModel::class);

        $search = $this->mockAdapter(['removeEntry']);
        $search
            ->expects($this->once())
            ->method('removeEntry')
            ->with('uri')
        ;

        $adapters = [
            FaqModel::class => $this->mockConfiguredAdapter(['findById' => $faqModel]),
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
            ->with($faqModel, [], UrlGeneratorInterface::ABSOLUTE_URL)
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

        $listener = new FaqSearchListener(
            $framework,
            $connection,
            $urlGenerator,
        );

        $listener->onSaveRobots('noindex,follow', $dc);
    }

    public function testDoesNotPurgeTheSearchIndexOnRobotsChangeToBlankWhenSearchIndexerIsBlankAndReaderPageHasSearchIndexerBlankAndRobotsIndex(): void
    {
        $faqModel = $this->createMock(FaqModel::class);

        $search = $this->mockAdapter(['removeEntry']);
        $search
            ->expects($this->never())
            ->method($this->anything())
        ;

        $adapters = [
            FaqModel::class => $this->mockConfiguredAdapter(['findById' => $faqModel]),
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

        $listener = new FaqSearchListener(
            $framework,
            $connection,
            $urlGenerator,
        );

        $listener->onSaveRobots('', $dc);
    }

    public function testDoesNotPurgeTheSearchIndexOnRobotsChangeToBlankWhenSearchIndexerIsBlankAndReaderPageHasSearchIndexerSetToAlwaysIndex(): void
    {
        $faqModel = $this->createMock(FaqModel::class);

        $search = $this->mockAdapter(['removeEntry']);
        $search
            ->expects($this->never())
            ->method($this->anything())
        ;

        $adapters = [
            FaqModel::class => $this->mockConfiguredAdapter(['findById' => $faqModel]),
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

        $listener = new FaqSearchListener(
            $framework,
            $connection,
            $urlGenerator,
        );

        $listener->onSaveRobots('', $dc);
    }

    public function testPurgesTheSearchIndexOnRobotsChangeToBlankWhenSearchIndexerIsBlankAndReaderPageHasSearchIndexerSetToNeverIndex(): void
    {
        $faqModel = $this->createMock(FaqModel::class);

        $search = $this->mockAdapter(['removeEntry']);
        $search
            ->expects($this->once())
            ->method('removeEntry')
            ->with('uri')
        ;

        $adapters = [
            FaqModel::class => $this->mockConfiguredAdapter(['findById' => $faqModel]),
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
            ->with($faqModel, [], UrlGeneratorInterface::ABSOLUTE_URL)
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

        $listener = new FaqSearchListener(
            $framework,
            $connection,
            $urlGenerator,
        );

        $listener->onSaveRobots('', $dc);
    }

    public function testPurgesTheSearchIndexOnRobotsChangeToBlankWhenSearchIndexerIsBlankAndReaderPageHasSearchIndexerBlankAndRobotsNoindex(): void
    {
        $faqModel = $this->createMock(FaqModel::class);

        $search = $this->mockAdapter(['removeEntry']);
        $search
            ->expects($this->once())
            ->method('removeEntry')
            ->with('uri')
        ;

        $adapters = [
            FaqModel::class => $this->mockConfiguredAdapter(['findById' => $faqModel]),
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
            ->with($faqModel, [], UrlGeneratorInterface::ABSOLUTE_URL)
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

        $listener = new FaqSearchListener(
            $framework,
            $connection,
            $urlGenerator,
        );

        $listener->onSaveRobots('', $dc);
    }

    public function testPurgesTheSearchIndexOnDelete(): void
    {
        $faqModel = $this->createMock(FaqModel::class);

        $search = $this->mockAdapter(['removeEntry']);
        $search
            ->expects($this->once())
            ->method('removeEntry')
            ->with('uri')
        ;

        $adapters = [
            FaqModel::class => $this->mockConfiguredAdapter(['findById' => $faqModel]),
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
            ->with($faqModel, [], UrlGeneratorInterface::ABSOLUTE_URL)
            ->willReturn('uri')
        ;

        $dc = $this->mockClassWithProperties(DataContainer::class, ['id' => 17]);

        $listener = new FaqSearchListener(
            $framework,
            $connection,
            $urlGenerator,
        );

        $listener->onDelete($dc);
    }

    public function testDoesNotPurgeTheSearchIndexWithoutId(): void
    {
        $faqModel = $this->createMock(FaqModel::class);

        $search = $this->mockAdapter(['removeEntry']);
        $search
            ->expects($this->never())
            ->method($this->anything())
        ;

        $adapters = [
            FaqModel::class => $this->mockConfiguredAdapter(['findById' => $faqModel]),
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

        $listener = new FaqSearchListener(
            $framework,
            $connection,
            $urlGenerator,
        );

        $listener->onDelete($dc);
    }
}
