<?php

declare(strict_types=1);

namespace Contao\CalendarBundle;

use Contao\Calendar;
use Contao\CalendarEventsModel;
use Contao\CalendarModel;
use Contao\ContentModel;
use Contao\Controller;
use Contao\CoreBundle\Framework\ContaoFramework;
use Contao\CoreBundle\Routing\ContentUrlGenerator;
use Contao\CoreBundle\Routing\PageFinder;
use Contao\Date;
use Contao\StringUtil;
use Contao\System;
use Contao\Template;
use FOS\HttpCache\ResponseTagger;
use Symfony\Component\Routing\Exception\ExceptionInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

class CalendarEventsGenerator
{
    private int|null $todayBegin = null;

    private int|null $todayEnd = null;

    public function __construct(
        private readonly ContaoFramework $contaoFramework,
        private readonly PageFinder $pageFinder,
        private readonly ContentUrlGenerator $contentUrlGenerator,
        private readonly TranslatorInterface $translator,
        private readonly ResponseTagger|null $responseTagger = null,
    ) {
    }

    /**
     * Returns the generated events including recurrences as a multidimensional array,
     * grouped by day and timestamp.
     */
    public function getAllEvents(array $calendars, \DateTimeInterface $rangeStart, \DateTimeInterface $rangeEnd, bool|null $featured = null, bool $noSpan = false, int|null $recurrenceLimit = null): array
    {
        if ([] === $calendars) {
            return [];
        }

        $events = [];

        // Include all events of the day, expired events will be filtered out later
        $rangeStart = \DateTime::createFromInterface($rangeStart)->setTime(0, 0);

        $calendarEventsModel = $this->contaoFramework->getAdapter(CalendarEventsModel::class);

        foreach ($calendars as $id) {
            // Get the events of the current period
            $eventModels = $calendarEventsModel->findCurrentByPid($id, $rangeStart->getTimestamp(), $rangeEnd->getTimestamp(), ['showFeatured' => $featured]);

            if (!$eventModels) {
                continue;
            }

            foreach ($eventModels as $eventModel) {
                $this->addEvent($events, $eventModel, $eventModel->startTime, $eventModel->endTime, $rangeEnd->getTimestamp(), $id, $noSpan);

                // Recurring events
                if ($eventModel->recurring) {
                    $repeat = StringUtil::deserialize($eventModel->repeatEach, true);

                    if (!isset($repeat['unit'], $repeat['value']) || $repeat['value'] < 1) {
                        continue;
                    }

                    $count = 0;
                    $eventStartTime = (new \DateTime())->setTimestamp($eventModel->startTime);
                    $eventEndTime = (new \DateTime())->setTimestamp($eventModel->endTime);
                    $modifier = '+ '.$repeat['value'].' '.$repeat['unit'];

                    while ($eventEndTime < $rangeEnd) {
                        ++$count;

                        if (($eventModel->recurrences > 0 && $count > $eventModel->recurrences) || (null !== $recurrenceLimit && $count > $recurrenceLimit)) {
                            break;
                        }

                        $eventStartTime->modify($modifier);
                        $eventEndTime->modify($modifier);

                        // Skip events outside the scope
                        if ($eventEndTime < $rangeStart || $eventStartTime > $rangeEnd) {
                            continue;
                        }

                        $this->addEvent($events, $eventModel, $eventStartTime->getTimestamp(), $eventEndTime->getTimestamp(), $rangeEnd->getTimestamp(), $id, $noSpan);
                    }
                }
            }
        }

        // Sort the array
        foreach (array_keys($events) as $key) {
            ksort($events[$key]);
        }

        // HOOK: modify the result set
        if (isset($GLOBALS['TL_HOOKS']['getAllEvents']) && \is_array($GLOBALS['TL_HOOKS']['getAllEvents'])) {
            foreach ($GLOBALS['TL_HOOKS']['getAllEvents'] as $callback) {
                $events = System::importStatic($callback[0])->{$callback[1]}($events, $calendars, $rangeStart->getTimestamp(), $rangeEnd->getTimestamp(), $this);
            }
        }

        return $events;
    }

    private function addEvent(array &$events, CalendarEventsModel $eventModel, int $start, int $end, int $rangeEnd, int $calendar, bool $noSpan): void
    {
        $page = $this->pageFinder->getCurrentPage();

        $timestamp = $start;
        $key = date('Ymd', $start);
        $formattedDate = Date::parse($page->dateFormat, $start);
        $day = $this->translator->trans('DAYS.'.date('w', $start), [], 'contao_default');
        $month = $this->translator->trans('MONTHS.'.(date('n', $start) - 1), [], 'contao_default');
        $span = Calendar::calculateSpan($start, $end);
        $timeSeparator = $this->translator->trans('MSC.cal_timeSeparator', [], 'contao_default');

        if ($span > 0) {
            $formattedDate = Date::parse($page->dateFormat, $start).$timeSeparator.Date::parse($page->dateFormat, $end);
            $day = '';
        }

        $formattedTime = '';

        if ($eventModel->addTime) {
            if ($span > 0) {
                $formattedDate = Date::parse($page->datimFormat, $start).$timeSeparator.Date::parse($page->datimFormat, $end);
            } elseif ($start === $end) {
                $formattedTime = Date::parse($page->timeFormat, $start);
            } else {
                $formattedTime = Date::parse($page->timeFormat, $start).$timeSeparator.Date::parse($page->timeFormat, $end);
            }
        }

        $until = '';
        $recurring = '';

        // Recurring event
        if ($eventModel->recurring) {
            $range = StringUtil::deserialize($eventModel->repeatEach);

            if (isset($range['unit'], $range['value'])) {
                if (1 === $range['value']) {
                    $repeat = $this->translator->trans('MSC.cal_single_'.$range['unit'], [], 'contao_default');
                } else {
                    $repeat = $this->translator->trans('MSC.cal_multiple_'.$range['unit'], [$range['value']], 'contao_default');
                }

                if ($eventModel->recurrences > 0) {
                    $until = ' '.$this->translator->trans('MSC.cal_until', [Date::parse($page->dateFormat, $eventModel->repeatEnd)], 'contao_default');
                }

                if ($eventModel->recurrences > 0 && $end < time()) {
                    $recurring = $this->translator->trans('MSC.cal_repeat_ended', [$repeat, $until], 'contao_default');
                } elseif ($eventModel->addTime) {
                    $recurring = $this->translator->trans('MSC.cal_repeat', [$repeat, $until, date('Y-m-d\TH:i:sP', $start), $formattedDate.($formattedTime ? ' '.$formattedTime : '')], 'contao_default');
                } else {
                    $recurring = $this->translator->trans('MSC.cal_repeat', [$repeat, $until, date('Y-m-d', $start), $formattedDate], 'contao_default');
                }
            }
        }

        // Tag the event (see #2137)
        $this->responseTagger?->addTags(['contao.db.tl_calendar_events.'.$eventModel->id]);

        // Store raw data
        $event = $eventModel->row();

        try {
            $url = $this->contentUrlGenerator->generate($eventModel);
        } catch (ExceptionInterface) {
            $url = null;
        }

        $calendarModel = $this->contaoFramework->getAdapter(CalendarModel::class);

        // Overwrite some settings
        $event['date'] = $formattedDate;
        $event['time'] = $formattedTime;
        $event['datetime'] = $eventModel->addTime ? date('Y-m-d\TH:i:sP', $start) : date('Y-m-d', $start);
        $event['day'] = $day;
        $event['month'] = $month;
        $event['parent'] = $calendar;
        $event['model'] = $eventModel;
        $event['calendar'] = $calendarModel->findById($eventModel->pid);
        $event['link'] = $eventModel->title;
        $event['target'] = '';
        $event['title'] = StringUtil::specialchars($eventModel->title, true);
        $event['href'] = $url;
        $event['class'] = $eventModel->cssClass ? ' '.$eventModel->cssClass : '';
        $event['recurring'] = $recurring;
        $event['until'] = $until;
        $event['begin'] = $start;
        $event['end'] = $end;
        $event['effectiveEndTime'] = $end;
        $event['details'] = '';
        $event['hasTeaser'] = false;

        // Set open-end events to 23:59:59, so they run until the end of the day (see #4476)
        if ($start === $end && $eventModel->addTime) {
            $event['effectiveEndTime'] = strtotime(date('Y-m-d', $end).' 23:59:59');
        }

        // Override the link target
        if ('external' === $eventModel->source && $eventModel->target) {
            $event['target'] = ' target="_blank" rel="noreferrer noopener"';
        }

        // Clean the RTE output
        if ($event['teaser']) {
            $event['hasTeaser'] = true;
            $event['teaser'] = StringUtil::encodeEmail($event['teaser']);
        }

        // Display the "read more" button for external/article links
        if ('default' !== $eventModel->source) {
            $event['hasDetails'] = null !== $url;
        }

        // Compile the event text
        else {
            $id = $eventModel->id;
            $controller = $this->contaoFramework->getAdapter(Controller::class);
            $contentModel = $this->contaoFramework->getAdapter(ContentModel::class);
            $template = $this->contaoFramework->getAdapter(Template::class);

            $event['details'] = $template->once(
                static function () use ($contentModel, $controller, $id): string {
                    $details = '';
                    $elements = $contentModel->findPublishedByPidAndTable($id, 'tl_calendar_events');

                    foreach ($elements ?? [] as $element) {
                        $details .= $controller->getContentElement($element);
                    }

                    return $details;
                },
            );

            $event['hasDetails'] = null === $url ? false : $template->once(static fn (): bool => $contentModel->countPublishedByPidAndTable($id, 'tl_calendar_events') > 0);
        }

        // Get today's start and end timestamp
        $this->todayBegin ??= strtotime('00:00:00');
        $this->todayEnd ??= strtotime('23:59:59');

        // Mark past and upcoming events (see #3692)
        if ($end < $this->todayBegin) {
            $event['class'] .= ' bygone';
        } elseif ($start > $this->todayEnd) {
            $event['class'] .= ' upcoming';
        } else {
            $event['class'] .= ' current';
        }

        if (1 === $event['featured']) {
            $event['class'] .= ' featured';
        }

        $events[$key][$start][] = $event;

        // Multi-day event
        for ($i = 1; $i <= $span; ++$i) {
            // Only show first occurrence
            if ($noSpan) {
                break;
            }

            $timestamp = strtotime('+1 day', $timestamp);

            if ($timestamp > $rangeEnd) {
                break;
            }

            $events[date('Ymd', $timestamp)][$timestamp][] = $event;
        }
    }
}
