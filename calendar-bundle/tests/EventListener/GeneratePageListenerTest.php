<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * Copyright (c) 2005-2017 Leo Feyer
 *
 * @license LGPL-3.0+
 */

namespace Contao\CalendarBundle\Tests\EventListener;

use Contao\CalendarBundle\EventListener\GeneratePageListener;
use Contao\CalendarFeedModel;
use Contao\CoreBundle\Framework\Adapter;
use Contao\CoreBundle\Framework\ContaoFrameworkInterface;
use Contao\LayoutModel;
use Contao\Model\Collection;
use Contao\PageModel;
use Contao\Template;
use PHPUnit\Framework\TestCase;

class GeneratePageListenerTest extends TestCase
{
    public function testCanBeInstantiated(): void
    {
        $listener = new GeneratePageListener($this->mockContaoFramework());

        $this->assertInstanceOf('Contao\CalendarBundle\EventListener\GeneratePageListener', $listener);
    }

    public function testAddsTheCalendarFeedLink(): void
    {
        $pageModel = $this->createMock(PageModel::class);
        $layoutModel = $this->createMock(LayoutModel::class);

        $layoutModel
            ->method('__get')
            ->willReturnCallback(
                function (string $key): ?string {
                    if ('calendarfeeds' === $key) {
                        return 'a:1:{i:0;i:3;}';
                    }

                    return null;
                }
            )
        ;

        $GLOBALS['TL_HEAD'] = [];

        $listener = new GeneratePageListener($this->mockContaoFramework());
        $listener->onGeneratePage($pageModel, $layoutModel);

        $this->assertSame(
            [
                '<link type="application/rss+xml" rel="alternate" href="http://localhost/share/events.xml" title="Upcoming events">',
            ],
            $GLOBALS['TL_HEAD']
        );
    }

    public function testDoesNotAddTheCalendarFeedLinkIfThereAreNoFeeds(): void
    {
        $pageModel = $this->createMock(PageModel::class);
        $layoutModel = $this->createMock(LayoutModel::class);

        $layoutModel
            ->method('__get')
            ->willReturnCallback(
                function (string $key): ?string {
                    if ('calendarfeeds' === $key) {
                        return '';
                    }

                    return null;
                }
            )
        ;

        $GLOBALS['TL_HEAD'] = [];

        $listener = new GeneratePageListener($this->mockContaoFramework());
        $listener->onGeneratePage($pageModel, $layoutModel);

        $this->assertEmpty($GLOBALS['TL_HEAD']);
    }

    public function testDoesNotAddTheCalendarFeedLinkIfThereAreNoModels(): void
    {
        $pageModel = $this->createMock(PageModel::class);
        $layoutModel = $this->createMock(LayoutModel::class);

        $layoutModel
            ->method('__get')
            ->willReturnCallback(
                function (string $key): ?string {
                    if ('calendarfeeds' === $key) {
                        return 'a:1:{i:0;i:3;}';
                    }

                    return null;
                }
            )
        ;

        $GLOBALS['TL_HEAD'] = [];

        $listener = new GeneratePageListener($this->mockContaoFramework(true));
        $listener->onGeneratePage($pageModel, $layoutModel);

        $this->assertEmpty($GLOBALS['TL_HEAD']);
    }

    /**
     * Mocks the Contao framework.
     *
     * @param bool $noModels
     *
     * @return ContaoFrameworkInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    private function mockContaoFramework(bool $noModels = false): ContaoFrameworkInterface
    {
        $framework = $this->createMock(ContaoFrameworkInterface::class);

        $framework
            ->method('isInitialized')
            ->willReturn(true)
        ;

        $feedModel = $this->createMock(CalendarFeedModel::class);

        $feedModel
            ->method('__get')
            ->willReturnCallback(
                function (string $key): ?string {
                    switch ($key) {
                        case 'feedBase':
                            return 'http://localhost/';

                        case 'alias':
                            return 'events';

                        case 'format':
                            return 'rss';

                        case 'title':
                            return 'Upcoming events';
                    }

                    return null;
                }
            )
        ;

        $calendarFeedModelAdapter = $this->createMock(Adapter::class);

        $calendarFeedModelAdapter
            ->method('__call')
            ->willReturn($noModels ? null : new Collection([$feedModel], 'tl_calendar_feeds'))
        ;

        $framework
            ->method('getAdapter')
            ->willReturnCallback(
                function (string $key) use ($calendarFeedModelAdapter): ?Adapter {
                    switch ($key) {
                        case CalendarFeedModel::class:
                            return $calendarFeedModelAdapter;

                        case Template::class:
                            return new Adapter(Template::class);
                    }

                    return null;
                }
            )
        ;

        return $framework;
    }
}
