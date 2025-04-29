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

    public function testDoesNotPurgeTheSearchIndexOnRobotsChangeToIndex(): void
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
                'robots' => 'noindex,follow',
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

    public function testPurgesTheSearchIndexOnRobotsChangeToNoindex(): void
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

    public function testDoesNotPurgeTheSearchIndexOnRobotsChangeFromIndexToBlankAndTheReaderPageHasRobotsIndex(): void
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
            ->method('fetchOne')
            ->willReturn('index,follow')
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

        $listener->onSaveRobots('', $dc);
    }

    public function testPurgesTheSearchIndexOnRobotsChangeFromIndexToBlankAndTheReaderPageHasRobotsNoindex(): void
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
            ->method('fetchOne')
            ->willReturn('noindex,follow')
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
