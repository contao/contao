<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao\CalendarBundle\Tests\Migration;

use Contao\CalendarBundle\CalendarEventsGenerator;
use Contao\CalendarEventsModel;
use Contao\CalendarModel;
use Contao\ContentModel;
use Contao\Controller;
use Contao\CoreBundle\Framework\ContaoFramework;
use Contao\CoreBundle\Routing\ContentUrlGenerator;
use Contao\CoreBundle\Routing\PageFinder;
use Contao\Model\Collection;
use Contao\PageModel;
use Contao\Template;
use Contao\TestCase\ContaoTestCase;
use PHPUnit\Framework\Attributes\DataProvider;
use Symfony\Contracts\Translation\TranslatorInterface;

class CalendarEventsGeneratorTest extends ContaoTestCase
{
    public function testReturnsEmptyIfNoCalendars(): void
    {
        $generator = new CalendarEventsGenerator(
            $this->createMock(ContaoFramework::class),
            $this->createMock(PageFinder::class),
            $this->createMock(ContentUrlGenerator::class),
            $this->createMock(TranslatorInterface::class),
        );

        $events = $generator->getAllEvents([], new \DateTime(), new \DateTime('9999-12-31 23:59:59'));

        $this->assertSame([], $events);
    }

    public function testReturnsEmptyIfNoCalendarFound(): void
    {
        $rangeStart = new \DateTimeImmutable();
        $rangeEnd = new \DateTimeImmutable('9999-12-31 23:59:59');

        $calendarEventsAdapter = $this->mockAdapter(['findCurrentByPid']);
        $calendarEventsAdapter
            ->expects($this->once())
            ->method('findCurrentByPid')
            ->with(1864, $rangeStart->setTime(0, 0)->getTimestamp(), $rangeEnd->getTimestamp(), ['showFeatured' => null])
            ->willReturn(null)
        ;

        $generator = new CalendarEventsGenerator(
            $this->mockContaoFramework([CalendarEventsModel::class => $calendarEventsAdapter]),
            $this->createMock(PageFinder::class),
            $this->createMock(ContentUrlGenerator::class),
            $this->createMock(TranslatorInterface::class),
        );

        $events = $generator->getAllEvents([1864], $rangeStart, $rangeEnd);

        $this->assertSame([], $events);
    }

    #[DataProvider('getEvent')]
    public function testProcessesEvent(array $record, array $expected): void
    {
        $rangeStart = new \DateTimeImmutable('2025-06-30 12:00:00');
        $rangeEnd = new \DateTimeImmutable('9999-12-31 23:59:59');

        $eventModel = $this->mockClassWithProperties(CalendarEventsModel::class, $record);

        $collection = new Collection([$eventModel], CalendarEventsModel::getTable());

        $calendarEventsAdapter = $this->mockAdapter(['findCurrentByPid']);
        $calendarEventsAdapter
            ->expects($this->once())
            ->method('findCurrentByPid')
            ->with(1864, $rangeStart->setTime(0, 0)->getTimestamp(), $rangeEnd->getTimestamp(), ['showFeatured' => null])
            ->willReturn($collection)
        ;

        $calendarModel = $this->createMock(CalendarModel::class);

        $calendarAdapter = $this->mockAdapter(['findById']);
        $calendarAdapter
            ->expects($this->atLeastOnce())
            ->method('findById')
            ->willReturn($calendarModel)
        ;

        $templateAdapter = $this->mockAdapter(['once']);
        $templateAdapter
            ->expects('default' !== ($record['source'] ?? null) ? $this->never() : $this->atLeast(2))
            ->method('once')
            ->willReturn(true)
        ;

        $contaoFramework = $this->mockContaoFramework(
            [
                CalendarEventsModel::class => $calendarEventsAdapter,
                CalendarModel::class => $calendarAdapter,
                Controller::class => $this->mockAdapter([]),
                ContentModel::class => $this->mockAdapter([]),
                Template::class => $templateAdapter,
            ],
        );

        $page = $this->mockClassWithProperties(PageModel::class, ['dateFormat']);
        $page->dateFormat = 'Y-m-d';
        $page->timeFormat = 'H:i';
        $page->datimFormat = 'Y-m-d H:i';

        $pageFinder = $this->createMock(PageFinder::class);
        $pageFinder
            ->expects($this->atLeastOnce())
            ->method('getCurrentPage')
            ->willReturn($page)
        ;

        $translator = $this->createMock(TranslatorInterface::class);
        $translator
            ->method('trans')
            ->willReturnCallback(
                static fn (string $id, array $parameters = [], string|null $domain = null, string|null $locale = null): string => \sprintf(
                    'translated(%s%s%s)',
                    null !== $domain ? "$domain:" : '',
                    $id,
                    $parameters ? '['.implode(', ', $parameters).']' : '',
                ),
            )
        ;

        $generator = new CalendarEventsGenerator(
            $contaoFramework,
            $pageFinder,
            $this->createMock(ContentUrlGenerator::class),
            $translator,
        );

        $allEvents = $generator->getAllEvents([1864], $rangeStart, $rangeEnd);
        $events = [];

        foreach ($allEvents as $v) {
            foreach ($v as $vv) {
                foreach ($vv as $event) {
                    $events[] = $event;
                }
            }
        }

        $events = array_map(
            static function (array $event): array {
                ksort($event);

                return $event;
            },
            $events,
        );

        $expected = array_map(
            static function (array $event) use ($record, $eventModel, $calendarModel): array {
                $event = ['time' => '', 'day' => '', 'month' => '', 'target' => '', 'href' => '', 'recurring' => '', 'until' => '', 'link' => $record['title'] ?? '', 'parent' => 1864, 'model' => $eventModel, 'calendar' => $calendarModel, ...$record, ...$event];

                ksort($event);

                return $event;
            },
            $expected,
        );

        $this->assertSame($expected, $events);
    }

    public static function getEvent(): iterable
    {
        $time = strtotime('2025-12-01 00:00:00');

        yield 'Basic event' => [
            [
                'title' => 'Lorem Ipsum',
                'startTime' => $time,
                'endTime' => $time,
                'teaser' => '',
                'featured' => false,
                'source' => 'default',
            ],
            [
                [
                    'date' => '2025-12-01',
                    'datetime' => '2025-12-01',
                    'class' => ' upcoming',
                    'begin' => $time,
                    'end' => $time,
                    'effectiveEndTime' => $time,
                    'hasTeaser' => false,
                    'details' => true,
                    'hasDetails' => true,
                    'time' => '',
                    'day' => 'translated(contao_default:DAYS.1)',
                    'month' => 'translated(contao_default:MONTHS.11)',
                ],
            ],
        ];

        $time = strtotime('2025-12-01 12:00:00');

        yield 'Event with open ended start time' => [
            [
                'title' => 'Lorem Ipsum',
                'startTime' => $time,
                'endTime' => $time,
                'teaser' => '',
                'featured' => false,
                'source' => 'default',
                'addTime' => true,
            ],
            [
                [
                    'date' => '2025-12-01',
                    'datetime' => '2025-12-01T12:00:00+00:00',
                    'class' => ' upcoming',
                    'begin' => $time,
                    'end' => $time,
                    'effectiveEndTime' => strtotime('2025-12-01 23:59:59'),
                    'hasTeaser' => false,
                    'details' => true,
                    'hasDetails' => true,
                    'time' => '12:00',
                    'day' => 'translated(contao_default:DAYS.1)',
                    'month' => 'translated(contao_default:MONTHS.11)',
                ],
            ],
        ];

        $time = strtotime('2025-06-30 00:00:00');

        yield 'Ongoing event' => [
            [
                'title' => 'Lorem Ipsum',
                'startTime' => $time,
                'endTime' => $time,
                'teaser' => '',
                'featured' => false,
                'source' => 'default',
            ],
            [
                [
                    'date' => date('Y-m-d', $time),
                    'datetime' => date('Y-m-d', $time),
                    'class' => ' current',
                    'begin' => $time,
                    'end' => $time,
                    'effectiveEndTime' => $time,
                    'hasTeaser' => false,
                    'details' => true,
                    'hasDetails' => true,
                    'time' => '',
                    'day' => 'translated(contao_default:DAYS.1)',
                    'month' => 'translated(contao_default:MONTHS.5)',
                ],
            ],
        ];

        $time = strtotime('2025-06-28 00:00:00');

        yield 'Past event' => [
            [
                'title' => 'Lorem Ipsum',
                'startTime' => $time,
                'endTime' => $time,
                'teaser' => '',
                'featured' => false,
                'source' => 'default',
            ],
            [
                [
                    'date' => date('Y-m-d', $time),
                    'datetime' => date('Y-m-d', $time),
                    'class' => ' bygone',
                    'begin' => $time,
                    'end' => $time,
                    'effectiveEndTime' => $time,
                    'hasTeaser' => false,
                    'details' => true,
                    'hasDetails' => true,
                    'time' => '',
                    'day' => 'translated(contao_default:DAYS.6)',
                    'month' => 'translated(contao_default:MONTHS.5)',
                ],
            ],
        ];

        $time1 = strtotime('2025-06-30 20:00:00');
        $time2 = strtotime('2025-07-01 20:00:00');
        $time3 = strtotime('2025-07-02 20:00:00');

        yield 'Recurring event' => [
            [
                'title' => 'Lorem Ipsum',
                'startTime' => $time1,
                'endTime' => $time1,
                'repeatEnd' => $time3,
                'teaser' => '',
                'featured' => false,
                'source' => 'default',
                'recurring' => true,
                'repeatEach' => serialize(['unit' => 'day', 'value' => 1]),
                'recurrences' => 2,
            ],
            [
                [
                    'date' => date('Y-m-d', $time1),
                    'datetime' => date('Y-m-d', $time1),
                    'class' => ' current',
                    'begin' => $time1,
                    'end' => $time1,
                    'effectiveEndTime' => $time1,
                    'hasTeaser' => false,
                    'details' => true,
                    'hasDetails' => true,
                    'time' => '',
                    'recurring' => 'translated(contao_default:MSC.cal_repeat_ended[translated(contao_default:MSC.cal_single_day),  translated(contao_default:MSC.cal_until[2025-07-02])])',
                    'day' => 'translated(contao_default:DAYS.1)',
                    'month' => 'translated(contao_default:MONTHS.5)',
                    'until' => ' translated(contao_default:MSC.cal_until[2025-07-02])',
                ],
                [
                    'date' => date('Y-m-d', $time2),
                    'datetime' => date('Y-m-d', $time2),
                    'class' => ' upcoming',
                    'begin' => $time2,
                    'end' => $time2,
                    'effectiveEndTime' => $time2,
                    'hasTeaser' => false,
                    'details' => true,
                    'hasDetails' => true,
                    'time' => '',
                    'recurring' => 'translated(contao_default:MSC.cal_repeat[translated(contao_default:MSC.cal_single_day),  translated(contao_default:MSC.cal_until[2025-07-02]), 2025-07-01, 2025-07-01])',
                    'day' => 'translated(contao_default:DAYS.2)',
                    'month' => 'translated(contao_default:MONTHS.6)',
                    'until' => ' translated(contao_default:MSC.cal_until[2025-07-02])',
                ],
                [
                    'date' => date('Y-m-d', $time3),
                    'datetime' => date('Y-m-d', $time3),
                    'class' => ' upcoming',
                    'begin' => $time3,
                    'end' => $time3,
                    'effectiveEndTime' => $time3,
                    'hasTeaser' => false,
                    'details' => true,
                    'hasDetails' => true,
                    'time' => '',
                    'recurring' => 'translated(contao_default:MSC.cal_repeat[translated(contao_default:MSC.cal_single_day),  translated(contao_default:MSC.cal_until[2025-07-02]), 2025-07-02, 2025-07-02])',
                    'day' => 'translated(contao_default:DAYS.3)',
                    'month' => 'translated(contao_default:MONTHS.6)',
                    'until' => ' translated(contao_default:MSC.cal_until[2025-07-02])',
                ],
            ],
        ];
    }
}
