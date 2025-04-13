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
use Contao\NewsArchiveModel;
use Contao\NewsBundle\EventListener\DataContainer\NewsSearchListener;
use Contao\NewsModel;
use Contao\PageModel;
use Contao\Search;
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
            $urlGenerator,
        );

        $listener->onSaveAlias('foo', $dc);
    }

    public function testPurgesTheSearchIndexOnRobotsChangeIfRobotsIsBankAndTheReaderPageHasRobotsNoindex(): void
    {
        $newsModel = $this->createMock(NewsModel::class);

        $newsArchive = $this->mockClassWithProperties(NewsArchiveModel::class, ['jumpTo' => 42]);

        $newsArchiveAdapter = $this->mockAdapter(['findById']);
        $newsArchiveAdapter
            ->expects($this->once())
            ->method('findById')
            ->with(5)
            ->willReturn($newsArchive)
        ;

        $page = $this->mockClassWithProperties(PageModel::class, ['robots' => 'noindex,follow']);
        
        $pageAdapter = $this->mockAdapter(['findById']);
        $pageAdapter
            ->expects($this->once())
            ->method('findById')
            ->with(42)
            ->willReturn($page)
        ;

        $search = $this->mockAdapter(['removeEntry']);
        $search
            ->expects($this->once())
            ->method('removeEntry')
            ->with('uri')
        ;

        $adapters = [
            NewsModel::class => $this->mockConfiguredAdapter(['findById' => $newsModel]),
            NewsArchiveModel::class => $newsArchiveAdapter,
            PageModel::class => $pageAdapter,
            Search::class => $search,
        ];

        $framework = $this->mockContaoFramework($adapters);

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
            $urlGenerator,
        );

        $listener->onSaveRobots('', $dc);
    }

    public function testDoesNotPurgeTheSearchIndexOnRobotsChangeIfRobotsIsBankAndTheReaderPageHasRobotsIndex(): void
    {
        $newsModel = $this->createMock(NewsModel::class);

        $newsArchive = $this->mockClassWithProperties(NewsArchiveModel::class, ['jumpTo' => 42]);

        $newsArchiveAdapter = $this->mockAdapter(['findById']);
        $newsArchiveAdapter
            ->expects($this->once())
            ->method('findById')
            ->with(5)
            ->willReturn($newsArchive)
        ;

        $page = $this->mockClassWithProperties(PageModel::class, ['robots' => 'index,follow']);
        
        $pageAdapter = $this->mockAdapter(['findById']);
        $pageAdapter
            ->expects($this->once())
            ->method('findById')
            ->with(42)
            ->willReturn($page)
        ;

        $search = $this->mockAdapter(['removeEntry']);
        $search
            ->expects($this->never())
            ->method($this->anything())
        ;

        $adapters = [
            NewsModel::class => $this->mockConfiguredAdapter(['findById' => $newsModel]),
            NewsArchiveModel::class => $newsArchiveAdapter,
            PageModel::class => $pageAdapter,
            Search::class => $search,
        ];

        $framework = $this->mockContaoFramework($adapters);

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
            $urlGenerator,
        );

        $listener->onSaveRobots('', $dc);
    }

    public function testDoesNotPurgeTheSearchIndexOnRobotsChangeIfRobotsIsBankAndTheArchiveDoesNotHaveAJumpToLinkToAReaderPage(): void
    {
        $newsModel = $this->createMock(NewsModel::class);

        $newsArchive = $this->mockClassWithProperties(NewsArchiveModel::class);

        $newsArchiveAdapter = $this->mockAdapter(['findById']);
        $newsArchiveAdapter
            ->expects($this->once())
            ->method('findById')
            ->with(5)
            ->willReturn($newsArchive)
        ;

        $search = $this->mockAdapter(['removeEntry']);
        $search
            ->expects($this->never())
            ->method($this->anything())
        ;

        $adapters = [
            NewsModel::class => $this->mockConfiguredAdapter(['findById' => $newsModel]),
            NewsArchiveModel::class => $newsArchiveAdapter,
            Search::class => $search,
        ];

        $framework = $this->mockContaoFramework($adapters);

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
            $urlGenerator,
        );

        $listener->onSaveRobots('', $dc);
    }

    public function testPurgesTheSearchIndexOnRobotsChangeIfRobotsIsNoindex(): void
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
            $urlGenerator,
        );

        $listener->onSaveRobots('noindex,follow', $dc);
    }

    public function testDoesNotPurgeTheSearchIndexOnRobotsChangeIfRobotsIsIndex(): void
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
            $urlGenerator,
        );

        $listener->onSaveRobots('index,follow', $dc);
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
            $urlGenerator,
        );

        $listener->onSaveRobots('index,follow', $dc);
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

        $urlGenerator = $this->createMock(ContentUrlGenerator::class);
        $urlGenerator
            ->expects($this->never())
            ->method($this->anything())
        ;

        $dc = $this->mockClassWithProperties(DataContainer::class, ['id' => null]);

        $listener = new NewsSearchListener(
            $framework,
            $urlGenerator,
        );

        $listener->onDelete($dc);
    }
}
