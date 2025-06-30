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

use Contao\CalendarBundle\EventListener\GeneratePageListener;
use Contao\CoreBundle\Framework\Adapter;
use Contao\CoreBundle\Routing\ContentUrlGenerator;
use Contao\Environment;
use Contao\LayoutModel;
use Contao\Model\Collection;
use Contao\PageModel;
use Contao\Template;
use Contao\TestCase\ContaoTestCase;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

class GeneratePageListenerTest extends ContaoTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        $GLOBALS['TL_HEAD'] = [];
    }

    protected function tearDown(): void
    {
        unset($GLOBALS['TL_CONFIG'], $GLOBALS['TL_HEAD']);

        parent::tearDown();
    }

    public function testAddsTheCalendarFeedLink(): void
    {
        $calendarFeedModel = $this->createMock(PageModel::class);
        $calendarFeedModel
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
            ->expects($this->once())
            ->method('generate')
            ->with($calendarFeedModel, [], UrlGeneratorInterface::ABSOLUTE_URL)
            ->willReturn('http://localhost/events.xml')
        ;

        $collection = new Collection([$calendarFeedModel], 'tl_page');

        $adapters = [
            Environment::class => $this->mockAdapter(['get']),
            PageModel::class => $this->mockConfiguredAdapter(['findMultipleByIds' => $collection]),
            Template::class => new Adapter(Template::class),
        ];

        $layoutModel = $this->mockClassWithProperties(LayoutModel::class);
        $layoutModel->calendarfeeds = 'a:1:{i:0;i:3;}';

        $listener = new GeneratePageListener($this->mockContaoFramework($adapters), $urlGenerator);
        $listener($this->createMock(PageModel::class), $layoutModel);

        $this->assertSame(
            ['<link type="application/rss+xml" rel="alternate" href="http://localhost/events.xml" title="Future events">'],
            $GLOBALS['TL_HEAD'],
        );
    }

    public function testDoesNotAddTheCalendarFeedLinkIfThereAreNoFeeds(): void
    {
        $layoutModel = $this->mockClassWithProperties(LayoutModel::class);
        $layoutModel->calendarfeeds = '';

        $listener = new GeneratePageListener($this->mockContaoFramework(), $this->createMock(ContentUrlGenerator::class));
        $listener($this->createMock(PageModel::class), $layoutModel);

        $this->assertEmpty($GLOBALS['TL_HEAD'] ?? null);
    }

    public function testDoesNotAddTheCalendarFeedLinkIfThereAreNoValidModels(): void
    {
        $pageModel = $this->mockClassWithProperties(PageModel::class, ['type' => 'regular']);
        $collection = new Collection([$pageModel], 'tl_page');

        $adapters = [
            PageModel::class => $this->mockConfiguredAdapter(['findMultipleByIds' => $collection]),
        ];

        $layoutModel = $this->mockClassWithProperties(LayoutModel::class);
        $layoutModel->calendarfeeds = 'a:1:{i:0;i:3;}';

        $listener = new GeneratePageListener($this->mockContaoFramework($adapters), $this->createMock(ContentUrlGenerator::class));
        $listener($this->createMock(PageModel::class), $layoutModel);

        $this->assertEmpty($GLOBALS['TL_HEAD'] ?? null);
    }
}
