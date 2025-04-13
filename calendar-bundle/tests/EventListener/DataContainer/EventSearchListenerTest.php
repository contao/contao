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
use Contao\CalendarModel;
use Contao\CoreBundle\Routing\ContentUrlGenerator;
use Contao\CoreBundle\Tests\TestCase;
use Contao\DataContainer;
use Contao\PageModel;
use Contao\Search;
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
            $urlGenerator,
        );

        $listener->onSaveAlias('foo', $dc);
    }

    public function testPurgesTheSearchIndexOnRobotsChangeIfRobotsIsBankAndTheReaderPageHasRobotsNoindex(): void
    {
        $eventModel = $this->createMock(CalendarEventsModel::class);

        $calendar = $this->mockClassWithProperties(CalendarModel::class, ['jumpTo' => 42]);

        $calendarAdapter = $this->mockAdapter(['findById']);
        $calendarAdapter
            ->expects($this->once())
            ->method('findById')
            ->with(5)
            ->willReturn($calendar)
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
            CalendarEventsModel::class => $this->mockConfiguredAdapter(['findById' => $eventModel]),
            CalendarModel::class => $calendarAdapter,
            PageModel::class => $pageAdapter,
            Search::class => $search,
        ];

        $framework = $this->mockContaoFramework($adapters);

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
                'pid' => 5,
            ])
        ;

        $listener = new EventSearchListener(
            $framework,
            $urlGenerator,
        );

        $listener->onSaveRobots('', $dc);
    }

    public function testDoesNotPurgeTheSearchIndexOnRobotsChangeIfRobotsIsBankAndTheReaderPageHasRobotsIndex(): void
    {
        $eventModel = $this->createMock(CalendarEventsModel::class);

        $calendar = $this->mockClassWithProperties(CalendarModel::class, ['jumpTo' => 42]);

        $calendarAdapter = $this->mockAdapter(['findById']);
        $calendarAdapter
            ->expects($this->once())
            ->method('findById')
            ->with(5)
            ->willReturn($calendar)
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
            CalendarEventsModel::class => $this->mockConfiguredAdapter(['findById' => $eventModel]),
            CalendarModel::class => $calendarAdapter,
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

        $listener = new EventSearchListener(
            $framework,
            $urlGenerator,
        );

        $listener->onSaveRobots('', $dc);
    }

    public function testDoesNotPurgeTheSearchIndexOnRobotsChangeIfRobotsIsBankAndTheArchiveDoesNotHaveAJumpToLinkToAReaderPage(): void
    {
        $eventModel = $this->createMock(CalendarEventsModel::class);

        $calendar = $this->mockClassWithProperties(CalendarModel::class);

        $calendarAdapter = $this->mockAdapter(['findById']);
        $calendarAdapter
            ->expects($this->once())
            ->method('findById')
            ->with(5)
            ->willReturn($calendar)
        ;

        $search = $this->mockAdapter(['removeEntry']);
        $search
            ->expects($this->never())
            ->method($this->anything())
        ;

        $adapters = [
            CalendarEventsModel::class => $this->mockConfiguredAdapter(['findById' => $eventModel]),
            CalendarModel::class => $calendarAdapter,
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

        $listener = new EventSearchListener(
            $framework,
            $urlGenerator,
        );

        $listener->onSaveRobots('', $dc);
    }

    public function testPurgesTheSearchIndexOnRobotsChangeIfRobotsIsNoindex(): void
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
                'pid' => 5,
            ])
        ;

        $listener = new EventSearchListener(
            $framework,
            $urlGenerator,
        );

        $listener->onSaveRobots('noindex,follow', $dc);
    }

    public function testDoesNotPurgeTheSearchIndexOnRobotsChangeIfRobotsIsIndex(): void
    {
        $eventModel = $this->createMock(CalendarEventsModel::class);

        $calendar = $this->mockClassWithProperties(CalendarModel::class);

        $calendarAdapter = $this->mockAdapter(['findById']);
        $calendarAdapter
            ->expects($this->never())
            ->method($this->anything())
        ;

        $page = $this->mockClassWithProperties(PageModel::class);
        
        $pageAdapter = $this->mockAdapter(['findById']);
        $pageAdapter
            ->expects($this->never())
            ->method($this->anything())
        ;

        $search = $this->mockAdapter(['removeEntry']);
        $search
            ->expects($this->never())
            ->method($this->anything())
        ;

        $adapters = [
            CalendarEventsModel::class => $this->mockConfiguredAdapter(['findById' => $eventModel]),
            CalendarModel::class => $calendarAdapter,
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
                'robots' => 'noindex,follow',
                'pid' => 5,
            ])
        ;

        $listener = new EventSearchListener(
            $framework,
            $urlGenerator,
        );

        $listener->onSaveRobots('index,follow', $dc);
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
            $urlGenerator,
        );

        $listener->onSaveRobots('index,follow', $dc);
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

        $urlGenerator = $this->createMock(ContentUrlGenerator::class);
        $urlGenerator
            ->expects($this->never())
            ->method($this->anything())
        ;

        $dc = $this->mockClassWithProperties(DataContainer::class, ['id' => null]);

        $listener = new EventSearchListener(
            $framework,
            $urlGenerator,
        );

        $listener->onDelete($dc);
    }
}
