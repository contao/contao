<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao\CalendarBundle\Tests;

use Contao\CalendarBundle\Generator\CalendarEventsGenerator;
use Contao\CalendarEventsModel;
use Contao\CalendarModel;
use Contao\ContentModel;
use Contao\Controller;
use Contao\CoreBundle\Framework\Adapter;
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
            $this->createStub(ContaoFramework::class),
            $this->createStub(PageFinder::class),
            $this->createStub(ContentUrlGenerator::class),
            $this->createStub(TranslatorInterface::class),
        );

        $events = $generator->getAllEvents([], new \DateTimeImmutable(), new \DateTimeImmutable('9999-12-31 23:59:59'));

        $this->assertSame([], $events);
    }

    public function testReturnsEmptyIfNoCalendarFound(): void
    {
        $rangeStart = new \DateTimeImmutable();
        $rangeEnd = new \DateTimeImmutable('9999-12-31 23:59:59');

        $calendarEventsAdapter = $this->createAdapterMock(['findCurrentByPid']);
        $calendarEventsAdapter
            ->expects($this->once())
            ->method('findCurrentByPid')
            ->with(1864, $rangeStart->setTime(0, 0)->getTimestamp(), $rangeEnd->getTimestamp(), ['showFeatured' => null])
            ->willReturn(null)
        ;

        $generator = new CalendarEventsGenerator(
            $this->createContaoFrameworkStub([CalendarEventsModel::class => $calendarEventsAdapter]),
            $this->createStub(PageFinder::class),
            $this->createStub(ContentUrlGenerator::class),
            $this->createStub(TranslatorInterface::class),
        );

        $events = $generator->getAllEvents([1864], $rangeStart, $rangeEnd);

        $this->assertSame([], $events);
    }

    #[DataProvider('getEvent')]
    public function testProcessesEvent(array $record, array $expected): void
    {
        $rangeStart = new \DateTimeImmutable();
        $rangeEnd = new \DateTimeImmutable('9999-12-31 23:59:59');

        $eventModel = $this->mockClassWithProperties(CalendarEventsModel::class, $record);

        $collection = new Collection([$eventModel], CalendarEventsModel::getTable());

        $calendarEventsAdapter = $this->createAdapterMock(['findCurrentByPid']);
        $calendarEventsAdapter
            ->expects($this->once())
            ->method('findCurrentByPid')
            ->with(1864, $rangeStart->setTime(0, 0)->getTimestamp(), $rangeEnd->getTimestamp(), ['showFeatured' => null])
            ->willReturn($collection)
        ;

        $calendarModel = $this->createStub(CalendarModel::class);

        $calendarAdapter = $this->createAdapterMock(['findById']);
        $calendarAdapter
            ->expects($this->atLeastOnce())
            ->method('findById')
            ->willReturn($calendarModel)
        ;

        $templateAdapter = $this->createAdapterMock(['once']);
        $templateAdapter
            ->expects('default' !== ($record['source'] ?? null) ? $this->never() : $this->atLeast(2))
            ->method('once')
            ->willReturn(static fn () => true)
        ;

        $contaoFramework = $this->createContaoFrameworkStub(
            [
                CalendarEventsModel::class => $calendarEventsAdapter,
                CalendarModel::class => $calendarAdapter,
                Controller::class => $this->createStub(Adapter::class),
                ContentModel::class => $this->createStub(Adapter::class),
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

        $translator = $this->createStub(TranslatorInterface::class);
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
            $this->createStub(ContentUrlGenerator::class),
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

                $event['details'] = $event['details']();
                $event['hasDetails'] = $event['hasDetails']();

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
        $time = (new \DateTimeImmutable())->modify('+7 days');

        yield 'Basic event' => [
            [
                'title' => 'Lorem Ipsum',
                'startTime' => $time->getTimestamp(),
                'endTime' => $time->getTimestamp(),
                'teaser' => '',
                'featured' => 0,
                'source' => 'default',
            ],
            [
                [
                    'date' => $time->format('Y-m-d'),
                    'datetime' => $time->format('Y-m-d'),
                    'class' => ' upcoming',
                    'begin' => $time->getTimestamp(),
                    'end' => $time->getTimestamp(),
                    'effectiveEndTime' => $time->getTimestamp(),
                    'hasTeaser' => false,
                    'details' => true,
                    'hasDetails' => true,
                    'day' => \sprintf('translated(contao_default:DAYS.%s)', $time->format('w')),
                    'month' => \sprintf('translated(contao_default:MONTHS.%s)', $time->format('n') - 1),
                ],
            ],
        ];

        yield 'Featured event' => [
            [
                'title' => 'Lorem Ipsum',
                'startTime' => $time->getTimestamp(),
                'endTime' => $time->getTimestamp(),
                'teaser' => '',
                'featured' => 1,
                'source' => 'default',
            ],
            [
                [
                    'date' => $time->format('Y-m-d'),
                    'datetime' => $time->format('Y-m-d'),
                    'class' => ' upcoming featured',
                    'begin' => $time->getTimestamp(),
                    'end' => $time->getTimestamp(),
                    'effectiveEndTime' => $time->getTimestamp(),
                    'hasTeaser' => false,
                    'details' => true,
                    'hasDetails' => true,
                    'day' => \sprintf('translated(contao_default:DAYS.%s)', $time->format('w')),
                    'month' => \sprintf('translated(contao_default:MONTHS.%s)', $time->format('n') - 1),
                ],
            ],
        ];

        $time = (new \DateTimeImmutable())->modify('+7 days')->setTime(12, 0);

        yield 'Event with open ended start time' => [
            [
                'title' => 'Lorem Ipsum',
                'startTime' => $time->getTimestamp(),
                'endTime' => $time->getTimestamp(),
                'teaser' => '',
                'featured' => 0,
                'source' => 'default',
                'addTime' => true,
            ],
            [
                [
                    'date' => $time->format('Y-m-d'),
                    'datetime' => $time->format('Y-m-d\TH:i:sP'),
                    'class' => ' upcoming',
                    'begin' => $time->getTimestamp(),
                    'end' => $time->getTimestamp(),
                    'effectiveEndTime' => $time->setTime(23, 59, 59)->getTimestamp(),
                    'hasTeaser' => false,
                    'details' => true,
                    'hasDetails' => true,
                    'time' => $time->format('H:i'),
                    'day' => \sprintf('translated(contao_default:DAYS.%s)', $time->format('w')),
                    'month' => \sprintf('translated(contao_default:MONTHS.%s)', $time->format('n') - 1),
                ],
            ],
        ];

        $time = (new \DateTimeImmutable())->setTime(0, 0);

        yield 'Ongoing event' => [
            [
                'title' => 'Lorem Ipsum',
                'startTime' => $time->getTimestamp(),
                'endTime' => $time->getTimestamp(),
                'teaser' => '',
                'featured' => 0,
                'source' => 'default',
            ],
            [
                [
                    'date' => $time->format('Y-m-d'),
                    'datetime' => $time->format('Y-m-d'),
                    'class' => ' current',
                    'begin' => $time->getTimestamp(),
                    'end' => $time->getTimestamp(),
                    'effectiveEndTime' => $time->getTimestamp(),
                    'hasTeaser' => false,
                    'details' => true,
                    'hasDetails' => true,
                    'day' => \sprintf('translated(contao_default:DAYS.%s)', $time->format('w')),
                    'month' => \sprintf('translated(contao_default:MONTHS.%s)', $time->format('n') - 1),
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
                'featured' => 0,
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
                    'day' => 'translated(contao_default:DAYS.6)',
                    'month' => 'translated(contao_default:MONTHS.5)',
                ],
            ],
        ];

        $time1 = (new \DateTimeImmutable())->modify('- 1 minute');
        $time2 = $time1->modify('+ 1 day');
        $time3 = $time2->modify('+ 1 day');

        yield 'Recurring event' => [
            [
                'title' => 'Lorem Ipsum',
                'startTime' => $time1->getTimestamp(),
                'endTime' => $time1->getTimestamp(),
                'repeatEnd' => $time3->getTimestamp(),
                'teaser' => '',
                'featured' => 0,
                'source' => 'default',
                'recurring' => true,
                'repeatEach' => serialize(['unit' => 'day', 'value' => 1]),
                'recurrences' => 2,
            ],
            [
                [
                    'date' => $time1->format('Y-m-d'),
                    'datetime' => $time1->format('Y-m-d'),
                    'class' => ' current',
                    'begin' => $time1->getTimestamp(),
                    'end' => $time1->getTimestamp(),
                    'effectiveEndTime' => $time1->getTimestamp(),
                    'hasTeaser' => false,
                    'details' => true,
                    'hasDetails' => true,
                    'recurring' => \sprintf('translated(contao_default:MSC.cal_repeat_ended[translated(contao_default:MSC.cal_single_day),  translated(contao_default:MSC.cal_until[%s])])', $time3->format('Y-m-d')),
                    'day' => \sprintf('translated(contao_default:DAYS.%s)', $time1->format('w')),
                    'month' => \sprintf('translated(contao_default:MONTHS.%s)', $time1->format('n') - 1),
                    'until' => \sprintf(' translated(contao_default:MSC.cal_until[%s])', $time3->format('Y-m-d')),
                ],
                [
                    'date' => $time2->format('Y-m-d'),
                    'datetime' => $time2->format('Y-m-d'),
                    'class' => ' upcoming',
                    'begin' => $time2->getTimestamp(),
                    'end' => $time2->getTimestamp(),
                    'effectiveEndTime' => $time2->getTimestamp(),
                    'hasTeaser' => false,
                    'details' => true,
                    'hasDetails' => true,
                    'recurring' => \sprintf('translated(contao_default:MSC.cal_repeat[translated(contao_default:MSC.cal_single_day),  translated(contao_default:MSC.cal_until[%s]), %s, %s])', $time3->format('Y-m-d'), $time2->format('Y-m-d'), $time2->format('Y-m-d')),
                    'day' => \sprintf('translated(contao_default:DAYS.%s)', $time2->format('w')),
                    'month' => \sprintf('translated(contao_default:MONTHS.%s)', $time2->format('n') - 1),
                    'until' => \sprintf(' translated(contao_default:MSC.cal_until[%s])', $time3->format('Y-m-d')),
                ],
                [
                    'date' => $time3->format('Y-m-d'),
                    'datetime' => $time3->format('Y-m-d'),
                    'class' => ' upcoming',
                    'begin' => $time3->getTimestamp(),
                    'end' => $time3->getTimestamp(),
                    'effectiveEndTime' => $time3->getTimestamp(),
                    'hasTeaser' => false,
                    'details' => true,
                    'hasDetails' => true,
                    'recurring' => \sprintf('translated(contao_default:MSC.cal_repeat[translated(contao_default:MSC.cal_single_day),  translated(contao_default:MSC.cal_until[%s]), %s, %s])', $time3->format('Y-m-d'), $time3->format('Y-m-d'), $time3->format('Y-m-d')),
                    'day' => \sprintf('translated(contao_default:DAYS.%s)', $time3->format('w')),
                    'month' => \sprintf('translated(contao_default:MONTHS.%s)', $time3->format('n') - 1),
                    'until' => \sprintf(' translated(contao_default:MSC.cal_until[%s])', $time3->format('Y-m-d')),
                ],
            ],
        ];

        yield 'Recurring event with added time' => [
            [
                'title' => 'Lorem Ipsum',
                'startTime' => $time1->getTimestamp(),
                'endTime' => $time1->getTimestamp(),
                'repeatEnd' => $time3->getTimestamp(),
                'teaser' => '',
                'featured' => 0,
                'source' => 'default',
                'recurring' => true,
                'repeatEach' => serialize(['unit' => 'day', 'value' => 1]),
                'recurrences' => 1,
                'addTime' => true,
            ],
            [
                [
                    'date' => $time1->format('Y-m-d'),
                    'datetime' => $time1->format('c'),
                    'class' => ' current',
                    'begin' => $time1->getTimestamp(),
                    'end' => $time1->getTimestamp(),
                    'effectiveEndTime' => $time1->setTime(23, 59, 59)->getTimestamp(),
                    'hasTeaser' => false,
                    'details' => true,
                    'hasDetails' => true,
                    'recurring' => \sprintf('translated(contao_default:MSC.cal_repeat_ended[translated(contao_default:MSC.cal_single_day),  translated(contao_default:MSC.cal_until[%s])])', $time3->format('Y-m-d')),
                    'day' => \sprintf('translated(contao_default:DAYS.%s)', $time1->format('w')),
                    'month' => \sprintf('translated(contao_default:MONTHS.%s)', $time1->format('n') - 1),
                    'until' => \sprintf(' translated(contao_default:MSC.cal_until[%s])', $time3->format('Y-m-d')),
                    'time' => $time1->format('H:i'),
                ],
                [
                    'date' => $time2->format('Y-m-d'),
                    'datetime' => $time2->format('c'),
                    'class' => ' upcoming',
                    'begin' => $time2->getTimestamp(),
                    'end' => $time2->getTimestamp(),
                    'effectiveEndTime' => $time2->setTime(23, 59, 59)->getTimestamp(),
                    'hasTeaser' => false,
                    'details' => true,
                    'hasDetails' => true,
                    'recurring' => \sprintf('translated(contao_default:MSC.cal_repeat[translated(contao_default:MSC.cal_single_day),  translated(contao_default:MSC.cal_until[%s]), %s, %s])', $time3->format('Y-m-d'), $time2->format('Y-m-d\TH:i:sP'), $time2->format('Y-m-d H:i')),
                    'day' => \sprintf('translated(contao_default:DAYS.%s)', $time2->format('w')),
                    'month' => \sprintf('translated(contao_default:MONTHS.%s)', $time2->format('n') - 1),
                    'until' => \sprintf(' translated(contao_default:MSC.cal_until[%s])', $time3->format('Y-m-d')),
                    'time' => $time2->format('H:i'),
                ],
            ],
        ];
    }
}
