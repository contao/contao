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

use Contao\CalendarEventsModel;
use Contao\CalendarModel;
use Contao\Config;
use Contao\CoreBundle\Framework\Adapter;
use Contao\CoreBundle\Framework\ContaoFrameworkInterface;
use Contao\Events;
use Contao\Input;
use Contao\CalendarBundle\EventListener\BreadcrumbListener;
use Contao\PageModel;
use Contao\TestCase\ContaoTestCase;
use PHPUnit\Framework\MockObject\MockObject;

class BreadcrumbListenerTest extends ContaoTestCase
{
    protected function setUp(): void
    {
        $GLOBALS['objPage'] = $this->mockClassWithProperties(
            PageModel::class,
            [
                'id' => 4,
                'pageTitle' => 'Events'
            ]
        );
    }

    /**
     * @dataProvider provideBreadcrumbItems
     */
    public function testDoesNotAddBreadcrumbItemForMissingPage(array $items): void
    {
        unset($GLOBALS['objPage']);

        $framework = $this->mockContaoFramework();
        $listener = new BreadcrumbListener($framework);

        $result = $listener->onGenerateBreadcrumb($items);
        $this->assertSame($items, $result);
    }

    /**
     * @dataProvider provideBreadcrumbItems
     */
    public function testDoesNotAddBreadcrumbItemForEnabledAutoItemButMissingAutoItemParameter(array $items): void
    {
        $inputAdapter = $this->mockAdapter(['get']);
        $inputAdapter
            ->method('get')
            ->with('auto_item')
            ->willReturn(null);

        $framework = $this->mockContaoFramework([Input::class => $inputAdapter]);
        $listener = new BreadcrumbListener($framework);

        $result = $listener->onGenerateBreadcrumb($items);
        $this->assertSame($items, $result);
    }

    /**
     * @dataProvider provideBreadcrumbItems
     */
    public function testDoesNotAddBreadcrumbItemForDisabledAutoItemAndMissingItemsParameter(array $items): void
    {
        $inputAdapter = $this->mockAdapter(['get']);
        $inputAdapter
            ->method('get')
            ->with('items')
            ->willReturn(null);

        $framework = $this->mockContaoFramework(
            [
                Input::class => $inputAdapter,
                Config::class => $this->mockConfigAdapter(false)
            ]
        );
        $listener = new BreadcrumbListener($framework);

        $result = $listener->onGenerateBreadcrumb($items);
        $this->assertSame($items, $result);
    }

    /**
     * @dataProvider provideBreadcrumbItems
     */
    public function testDoesNotAddBreadcrumbItemForNoneCalendarWithJumpToPageSameAsPageId(array $items): void
    {
        $calendarAdapter = $this->mockCalendarAdapter(null);

        $framework = $this->mockContaoFramework([CalendarModel::class => $calendarAdapter]);
        $listener = new BreadcrumbListener($framework);

        $result = $listener->onGenerateBreadcrumb($items);
        $this->assertSame($items, $result);
    }

    /**
     * @dataProvider provideBreadcrumbItems
     */
    public function testDoesNotAddBreadcrumbItemForNotExistingEvents(array $items): void
    {
        $eventsModelAdapter = $this->mockEventsModelAdapter(null);

        $framework = $this->mockContaoFramework([CalendarEventsModel::class => $eventsModelAdapter]);
        $listener = new BreadcrumbListener($framework);

        $result = $listener->onGenerateBreadcrumb($items);
        $this->assertSame($items, $result);
    }

    /**
     * @dataProvider provideBreadcrumbItems
     */
    public function testAddsBreadcrumbItemForEvent(array $items): void
    {
        $framework = $this->mockContaoFramework();
        $listener = new BreadcrumbListener($framework);

        $expectedCount = \count($items) + 1;
        $items = $listener->onGenerateBreadcrumb($items);

        $this->assertCount($expectedCount, $items);
        $this->assertSame(
            [
                'isRoot' => false,
                'isActive' => true,
                'href' => 'events/foo-bar.html',
                'title' => 'Foo bar',
                'link' => 'Foo bar',
                'data' => [
                    'id' => 4,
                    'pageTitle' => 'Events'
                ],
                'class' => '',
            ],
            $items[$expectedCount - 1]
        );
    }

    /**
     * @dataProvider provideBreadcrumbItems
     */
    public function testAddsBreadcrumbItemUsingPageTitleForEvent(array $items): void
    {
        $eventsModelAdapter = $this->mockEventsModelAdapter(
            $this->mockEventModel([
                'title' => 'Foo bar',
                'pageTitle' => 'Page title foo bar'
            ])
        );

        $framework = $this->mockContaoFramework([CalendarEventsModel::class => $eventsModelAdapter]);
        $listener = new BreadcrumbListener($framework);

        $expectedCount = \count($items) + 1;
        $items = $listener->onGenerateBreadcrumb($items);

        $this->assertCount($expectedCount, $items);
        $this->assertSame(
            [
                'isRoot' => false,
                'isActive' => true,
                'href' => 'events/foo-bar.html',
                'title' => 'Page title foo bar',
                'link' => 'Page title foo bar',
                'data' => [
                    'id' => 4,
                    'pageTitle' => 'Events'
                ],
                'class' => '',
            ],
            $items[$expectedCount - 1]
        );
    }

    /**
     * @dataProvider provideBreadcrumbItems
     */
    public function testOverridesCurrentPageItemWithEvent(array $items): void
    {
        $GLOBALS['objPage'] = $this->mockClassWithProperties(
            PageModel::class,
            [
                'id' => 4,
                'pageTitle' => 'News',
                'requireItem' => '1'
            ]
        );

        $calendarModel = $this->mockCalenderModel();
        $calendarAdapter = $this->mockCalendarAdapter($calendarModel);

        $framework = $this->mockContaoFramework([CalendarModel::class => $calendarAdapter]);
        $listener = new BreadcrumbListener($framework);

        $result = $listener->onGenerateBreadcrumb($items);
        $count = count($items);

        if ($count) {
            $items[$count -1]['title'] = 'Foo bar';
            $items[$count -1]['link'] = 'Foo bar';
            $items[$count -1]['href'] = 'events/foo-bar.html';
        }

        $this->assertSame($items, $result);
    }


    /**
     * @dataProvider provideBreadcrumbItems
     */
    public function testOverridesCurrentPageItemWithEventUsingPageTitle(array $items): void
    {
        $GLOBALS['objPage'] = $this->mockClassWithProperties(
            PageModel::class,
            [
                'id' => 4,
                'pageTitle' => 'News',
                'requireItem' => '1'
            ]
        );

        $calendarModel = $this->mockCalenderModel();
        $calendarAdapter = $this->mockCalendarAdapter($calendarModel);

        $eventsModelAdapter = $this->mockEventsModelAdapter(
            $this->mockEventModel([
                'title' => 'Foo bar',
                'pageTitle' => 'Custom page title'
            ])
        );

        $framework = $this->mockContaoFramework(
            [
                CalendarModel::class => $calendarAdapter,
                CalendarEventsModel::class => $eventsModelAdapter
            ]
        );
        $listener = new BreadcrumbListener($framework);

        $result = $listener->onGenerateBreadcrumb($items);
        $count = count($items);

        if ($count) {
            $items[$count -1]['title'] = 'Custom page title';
            $items[$count -1]['link'] = 'Custom page title';
            $items[$count -1]['href'] = 'events/foo-bar.html';
        }

        $this->assertSame($items, $result);
    }

    protected function mockContaoFramework(array $adapters = []): ContaoFrameworkInterface
    {
        if (!isset($adapters[CalendarModel::class])) {
            $calendarModel = $this->mockCalenderModel();
            $adapters[CalendarModel::class] = $this->mockCalendarAdapter($calendarModel);
        }

        if (!isset($adapters[CalendarEventsModel::class])) {
            $eventModel = $this->mockEventModel();
            $adapters[CalendarEventsModel::class] = $this->mockEventsModelAdapter($eventModel);
        }

        if (!isset($adapters[PageModel::class])) {
            $pageModel= $this->mockPageModel();
            $adapters[PageModel::class] = $this->mockPageModelAdapter($pageModel);
        }

        if (!isset($adapters[Config::class])) {
            $adapters[Config::class] = $this->mockConfigAdapter();
        }

        if (!isset($adapters[Input::class])) {
            $adapters[Input::class] = $this->mockInputAdapter();
        }

        if (!isset($adapters[Events::class])) {
            $adapters[Events::class] = $this->mockEventsAdapter();
        }

        return parent::mockContaoFramework($adapters);
    }

    /**
     * @return MockObject|CalendarModel
     */
    private function mockCalenderModel(): CalendarModel
    {
        return $this->mockClassWithProperties(
            CalendarModel::class,
            [
                'id' => 3,
            ]
        );
    }

    /**
     * @return MockObject|CalendarEventsModel
     */
    private function mockEventModel(?array $properties = null): CalendarEventsModel
    {
        $properties = $properties ?: [
            'title' => 'Foo bar',
            'pageTitle' => ''
        ];

        return $this->mockClassWithProperties(CalendarEventsModel::class, $properties);
    }

    /**
     * @return MockObject|PageModel
     */
    private function mockPageModel(): PageModel
    {
        $page = $this->mockClassWithProperties(
            PageModel::class,
            [
                'id' => 4,
                'pageTitle' => 'Events'
            ]
        );
        $page->method('row')->willReturn(
            [
                'id' => 4,
                'pageTitle' => 'Events'
            ]
        );

        return $page;
    }

    /**
     * @return Adapter|MockObject
     */
    private function mockCalendarAdapter(?CalendarModel $calendarModel): Adapter
    {
        $calendarAdapter = $this->mockAdapter(['findOneByJumpTo']);
        $calendarAdapter
            ->method('findOneByJumpTo')
            ->with(4)
            ->willReturn($calendarModel);

        return $calendarAdapter;
    }

    /**
     * @return Adapter|MockObject
     */
    private function mockEventsModelAdapter(?CalendarEventsModel $eventsModel): Adapter
    {
        $eventModelAdapter = $this->mockAdapter(['findPublishedByParentAndIdOrAlias']);
        $eventModelAdapter
            ->method('findPublishedByParentAndIdOrAlias')
            ->with('foo-bar', [3])
            ->willReturn($eventsModel);

        return $eventModelAdapter;
    }

    /**
     * @return Adapter|MockObject
     */
    private function mockConfigAdapter(bool $useAutoItem = true): Adapter
    {
        $configAdapter = $this->mockAdapter(['get']);
        $configAdapter
            ->method('get')
            ->with('useAutoItem')
            ->willReturn($useAutoItem);

        return $configAdapter;
    }

    /**
     * @return Adapter|MockObject
     */
    private function mockInputAdapter(): Adapter
    {
        $inputAdapter = $this->mockAdapter(['get']);
        $inputAdapter
            ->method('get')
            ->with('auto_item')
            ->willReturn('foo-bar');

        return $inputAdapter;
    }

    /**
     * @return Adapter|MockObject
     */
    private function mockPageModelAdapter(PageModel $pageModel): Adapter
    {
        $pageModelAdapter = $this->mockAdapter(['findByPk']);
        $pageModelAdapter
            ->method('findByPk')
            ->with(4)
            ->willReturn($pageModel);

        return $pageModelAdapter;
    }

    /**
     * @return Adapter|MockObject
     */
    private function mockEventsAdapter(): Adapter
    {
        $eventsAdapter = $this->mockAdapter(['generateEventUrl']);
        $eventsAdapter
            ->method('generateEventUrl')
            ->willReturn('events/foo-bar.html');

        return $eventsAdapter;
    }

    public function provideBreadcrumbItems(): array
    {
        return [
            [
                [],
            ],
            [
                [
                    [
                        'isRoot' => true,
                        'isActive' => true,
                        'href' => 'index.html',
                        'title' => 'Home',
                        'link' => 'Home',
                        'data' => [
                            'id' => 4,
                        ],
                        'class' => '',
                    ],
                ],
            ],
            [
                [
                    [
                        'isRoot' => true,
                        'isActive' => false,
                        'href' => 'index.html',
                        'title' => 'Home',
                        'link' => 'Home',
                        'data' => [
                            'id' => 1,
                        ],
                        'class' => '',
                    ],
                    [
                        'isRoot' => false,
                        'isActive' => true,
                        'href' => 'mews.html',
                        'title' => 'Events',
                        'link' => 'Events',
                        'data' => [
                            'id' => 4,
                            'pageTitle' => 'Events'
                        ],
                        'class' => '',
                    ],
                ],
            ],
        ];
    }
}
