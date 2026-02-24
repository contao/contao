<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao\CalendarBundle\Tests\EventListener;

use Contao\CalendarBundle\EventListener\AddFeedsFromLayoutListener;
use Contao\CoreBundle\Event\LayoutEvent;
use Contao\CoreBundle\Routing\ContentUrlGenerator;
use Contao\CoreBundle\Routing\ResponseContext\HtmlHeadBag\HtmlHeadBag;
use Contao\CoreBundle\Routing\ResponseContext\ResponseContext;
use Contao\CoreBundle\Routing\ResponseContext\ResponseContextAccessor;
use Contao\CoreBundle\Twig\LayoutTemplate;
use Contao\LayoutModel;
use Contao\Model\Collection;
use Contao\PageModel;
use Contao\TestCase\ContaoTestCase;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

class AddFeedsFromLayoutListenerTest extends ContaoTestCase
{
    public function testAddsTheCalendarFeedLink(): void
    {
        $calendarFeedPage = $this->createStub(PageModel::class);
        $calendarFeedPage
            ->method('__get')
            ->willReturnMap([
                ['id', 42],
                ['type', 'calendar_feed'],
                ['alias', 'events'],
                ['title', 'Future events'],
                ['feedFormat', 'rss'],
            ])
        ;

        $urlGenerator = $this->createMock(ContentUrlGenerator::class);
        $urlGenerator
            ->expects($this->exactly(2))
            ->method('generate')
            ->with($calendarFeedPage, [], UrlGeneratorInterface::ABSOLUTE_URL)
            ->willReturn('http://localhost/events.xml')
        ;

        $htmlHeadBag = new HtmlHeadBag();

        $responseContext = new ResponseContext();
        $responseContext->add($htmlHeadBag);

        $responseContextAccessor = $this->createMock(ResponseContextAccessor::class);
        $responseContextAccessor
            ->expects($this->once())
            ->method('getResponseContext')
            ->willReturn($responseContext)
        ;

        $layoutModel = $this->createClassWithPropertiesStub(LayoutModel::class);
        $layoutModel->calendarfeeds = 'a:1:{i:0;i:3;}';

        $currentPage = $this->createStub(PageModel::class);
        $collection = new Collection([$calendarFeedPage], 'tl_page');

        $adapters = [
            PageModel::class => $this->createConfiguredAdapterStub(['findMultipleByIds' => $collection]),
        ];

        $listener = new AddFeedsFromLayoutListener($this->createContaoFrameworkStub($adapters), $urlGenerator, $responseContextAccessor);
        $listener->onLayoutEvent(new LayoutEvent(new LayoutTemplate('<template>', static fn () => new Response('<content>')), $currentPage, $layoutModel, $responseContext));

        $this->assertSame(' type="rss" rel="alternate" href="http://localhost/events.xml" title="Future events"', implode('', $htmlHeadBag->getLinkTags()));

        $htmlHeadBag->setLinkTags([]);
        $listener->onGeneratePage($currentPage, $layoutModel);

        $this->assertSame(' type="rss" rel="alternate" href="http://localhost/events.xml" title="Future events"', implode('', $htmlHeadBag->getLinkTags()));
    }

    public function testDoesNotAddTheCalendarFeedLinkIfThereAreNoFeeds(): void
    {
        $urlGenerator = $this->createMock(ContentUrlGenerator::class);
        $urlGenerator
            ->expects($this->never())
            ->method('generate')
        ;

        $responseContextAccessor = $this->createMock(ResponseContextAccessor::class);
        $responseContextAccessor
            ->expects($this->never())
            ->method('getResponseContext')
        ;

        $htmlHeadBag = new HtmlHeadBag();

        $responseContext = new ResponseContext();
        $responseContext->add($htmlHeadBag);

        $layoutModel = $this->createClassWithPropertiesStub(LayoutModel::class);
        $layoutModel->calendarfeeds = '';

        $currentPage = $this->createStub(PageModel::class);

        $listener = new AddFeedsFromLayoutListener($this->createContaoFrameworkStub(), $urlGenerator, $responseContextAccessor);
        $listener->onLayoutEvent(new LayoutEvent(new LayoutTemplate('<template>', static fn () => new Response('<content>')), $currentPage, $layoutModel, $responseContext));

        $this->assertSame([], $htmlHeadBag->getLinkTags());
    }

    public function testDoesNotAddTheCalendarFeedLinkIfThereAreNoValidModels(): void
    {
        $urlGenerator = $this->createMock(ContentUrlGenerator::class);
        $urlGenerator
            ->expects($this->never())
            ->method('generate')
        ;

        $responseContextAccessor = $this->createMock(ResponseContextAccessor::class);
        $responseContextAccessor
            ->expects($this->never())
            ->method('getResponseContext')
        ;

        $htmlHeadBag = new HtmlHeadBag();

        $responseContext = new ResponseContext();
        $responseContext->add($htmlHeadBag);

        $layoutModel = $this->createClassWithPropertiesStub(LayoutModel::class);
        $layoutModel->calendarfeeds = 'a:1:{i:0;i:3;}';

        $currentPage = $this->createStub(PageModel::class);

        $regularPage = $this->createClassWithPropertiesStub(PageModel::class, ['type' => 'regular']);
        $collection = new Collection([$regularPage], 'tl_page');

        $adapters = [
            PageModel::class => $this->createConfiguredAdapterStub(['findMultipleByIds' => $collection]),
        ];

        $listener = new AddFeedsFromLayoutListener($this->createContaoFrameworkStub($adapters), $urlGenerator, $responseContextAccessor);
        $listener->onLayoutEvent(new LayoutEvent(new LayoutTemplate('<template>', static fn () => new Response('<content>')), $currentPage, $layoutModel, $responseContext));

        $this->assertSame([], $htmlHeadBag->getLinkTags());
    }

    public function testDoesNotAddTheCalendarFeedLinkIfNoResponseContext(): void
    {
        $framework = $this->createContaoFrameworkMock();
        $framework
            ->expects($this->never())
            ->method('initialize')
        ;

        $responseContextAccessor = $this->createMock(ResponseContextAccessor::class);
        $responseContextAccessor
            ->expects($this->once())
            ->method('getResponseContext')
            ->willReturn(null)
        ;

        $listener = new AddFeedsFromLayoutListener(
            $framework,
            $this->createStub(ContentUrlGenerator::class),
            $responseContextAccessor,
        );

        $listener->onLayoutEvent(
            new LayoutEvent(
                new LayoutTemplate('<template>', static fn () => new Response('<content>')),
                $this->createStub(PageModel::class),
                $this->createStub(LayoutModel::class),
                null,
            ),
        );

        $listener
            ->onGeneratePage(
                $this->createStub(PageModel::class),
                $this->createStub(LayoutModel::class),
            )
        ;
    }
}
