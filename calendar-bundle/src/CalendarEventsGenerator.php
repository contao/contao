<?php

declare(strict_types=1);

namespace Contao\CalendarBundle;

use Contao\Calendar;
use Contao\CalendarEventsModel;
use Contao\CalendarModel;
use Contao\ContentModel;
use Contao\Controller;
use Contao\CoreBundle\Routing\PageFinder;
use Contao\Date;
use Contao\StringUtil;
use Contao\System;
use Contao\Template;
use Symfony\Component\Routing\Exception\ExceptionInterface;

class CalendarEventsGenerator
{
    private int|null $todayBegin = null;

    private int|null $todayEnd = null;

    public function __construct(
        private readonly PageFinder $pageFinder,
    ) {
    }

    public function getAllEvents(array $calendars, int $start, int $end, bool|null $featured = null, bool $noSpan = false): array
    {
        if ([] === $calendars) {
            return [];
        }

        $events = [];

        // Include all events of the day, expired events will be filtered out later
        $start = strtotime(date('Y-m-d', $start).' 00:00:00');

        foreach ($calendars as $id) {
            // Get the events of the current period
            $eventModels = CalendarEventsModel::findCurrentByPid($id, $start, $end, ['showFeatured' => $featured]);

            if (null === $eventModels) {
                continue;
            }

            foreach ($eventModels as $eventModel) {
                $eventModel = $eventModels->current();

                $this->addEvent($events, $eventModel, $eventModel->startTime, $eventModel->endTime, $end, $id, $noSpan);

                // Recurring events
                if ($eventModel->recurring) {
                    $repeat = StringUtil::deserialize($eventModel->repeatEach, true);

                    if (!isset($repeat['unit'], $repeat['value']) || $repeat['value'] < 1) {
                        continue;
                    }

                    $count = 0;
                    $startTime = $eventModel->startTime;
                    $endTime = $eventModel->endTime;
                    $strtotime = '+ '.$repeat['value'].' '.$repeat['unit'];

                    while ($endTime < $end) {
                        if ($eventModel->recurrences > 0 && $count++ >= $eventModel->recurrences) {
                            break;
                        }

                        $startTime = strtotime($strtotime, $startTime);
                        $endTime = strtotime($strtotime, $endTime);

                        // Stop if the upper boundary is reached (see #8445)
                        if (false === $startTime || false === $endTime) {
                            break;
                        }

                        // Skip events outside the scope
                        if ($endTime < $start || $startTime > $end) {
                            continue;
                        }

                        $this->addEvent($events, $eventModel, $startTime, $endTime, $end, $id, $noSpan);
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
                $events = System::importStatic($callback[0])->{$callback[1]}($events, $calendars, $start, $end, $this);
            }
        }

        return $events;
    }

    private function addEvent(array &$events, CalendarEventsModel $event, int $start, int $end, int $limit, int $calendar, bool $noSpan): void
    {
        $page = $this->pageFinder->getCurrentPage();

        $date = $start;
        $intKey = date('Ymd', $start);
        $strDate = Date::parse($page->dateFormat, $start);
        $strDay = $GLOBALS['TL_LANG']['DAYS'][date('w', $start)];
        $strMonth = $GLOBALS['TL_LANG']['MONTHS'][date('n', $start) - 1];
        $span = Calendar::calculateSpan($start, $end);

        if ($span > 0) {
            $strDate = Date::parse($page->dateFormat, $start).$GLOBALS['TL_LANG']['MSC']['cal_timeSeparator'].Date::parse($page->dateFormat, $end);
            $strDay = '';
        }

        $strTime = '';

        if ($event->addTime) {
            if ($span > 0) {
                $strDate = Date::parse($page->datimFormat, $start).$GLOBALS['TL_LANG']['MSC']['cal_timeSeparator'].Date::parse($page->datimFormat, $end);
            } elseif ($start === $end) {
                $strTime = Date::parse($page->timeFormat, $start);
            } else {
                $strTime = Date::parse($page->timeFormat, $start).$GLOBALS['TL_LANG']['MSC']['cal_timeSeparator'].Date::parse($page->timeFormat, $end);
            }
        }

        $until = '';
        $recurring = '';

        // Recurring event
        if ($event->recurring) {
            $arrRange = StringUtil::deserialize($event->repeatEach);

            if (isset($arrRange['unit'], $arrRange['value'])) {
                if (1 === $arrRange['value']) {
                    $repeat = $GLOBALS['TL_LANG']['MSC']['cal_single_'.$arrRange['unit']];
                } else {
                    $repeat = \sprintf($GLOBALS['TL_LANG']['MSC']['cal_multiple_'.$arrRange['unit']], $arrRange['value']);
                }

                if ($event->recurrences > 0) {
                    $until = ' '.\sprintf($GLOBALS['TL_LANG']['MSC']['cal_until'], Date::parse($page->dateFormat, $event->repeatEnd));
                }

                if ($event->recurrences > 0 && $end < time()) {
                    $recurring = \sprintf($GLOBALS['TL_LANG']['MSC']['cal_repeat_ended'], $repeat, $until);
                } elseif ($event->addTime) {
                    $recurring = \sprintf($GLOBALS['TL_LANG']['MSC']['cal_repeat'], $repeat, $until, date('Y-m-d\TH:i:sP', $start), $strDate.($strTime ? ' '.$strTime : ''));
                } else {
                    $recurring = \sprintf($GLOBALS['TL_LANG']['MSC']['cal_repeat'], $repeat, $until, date('Y-m-d', $start), $strDate);
                }
            }
        }

        // Tag the event (see #2137)
        if (System::getContainer()->has('fos_http_cache.http.symfony_response_tagger')) {
            $responseTagger = System::getContainer()->get('fos_http_cache.http.symfony_response_tagger');
            $responseTagger->addTags(['contao.db.tl_calendar_events.'.$event->id]);
        }

        // Store raw data
        $arrEvent = $event->row();

        try {
            $url = System::getContainer()->get('contao.routing.content_url_generator')->generate($event);
        } catch (ExceptionInterface) {
            $url = null;
        }

        // Overwrite some settings
        $arrEvent['date'] = $strDate;
        $arrEvent['time'] = $strTime;
        $arrEvent['datetime'] = $event->addTime ? date('Y-m-d\TH:i:sP', $start) : date('Y-m-d', $start);
        $arrEvent['day'] = $strDay;
        $arrEvent['month'] = $strMonth;
        $arrEvent['parent'] = $calendar;
        $arrEvent['model'] = $event;
        $arrEvent['calendar'] = CalendarModel::findById($event->pid);
        $arrEvent['link'] = $event->title;
        $arrEvent['target'] = '';
        $arrEvent['title'] = StringUtil::specialchars($event->title, true);
        $arrEvent['href'] = $url;
        $arrEvent['class'] = $event->cssClass ? ' '.$event->cssClass : '';
        $arrEvent['recurring'] = $recurring;
        $arrEvent['until'] = $until;
        $arrEvent['begin'] = $start;
        $arrEvent['end'] = $end;
        $arrEvent['effectiveEndTime'] = $arrEvent['endTime'];
        $arrEvent['details'] = '';
        $arrEvent['hasTeaser'] = false;

        // Set open-end events to 23:59:59, so they run until the end of the day (see #4476)
        if ($start === $end && $event->addTime) {
            $arrEvent['effectiveEndTime'] = strtotime(date('Y-m-d', $arrEvent['endTime']).' 23:59:59');
        }

        // Override the link target
        if ('external' === $event->source && $event->target) {
            $arrEvent['target'] = ' target="_blank" rel="noreferrer noopener"';
        }

        // Clean the RTE output
        if ($arrEvent['teaser']) {
            $arrEvent['hasTeaser'] = true;
            $arrEvent['teaser'] = StringUtil::encodeEmail($arrEvent['teaser']);
        }

        // Display the "read more" button for external/article links
        if ('default' !== $event->source) {
            $arrEvent['hasDetails'] = null !== $url;
        }

        // Compile the event text
        else {
            $id = $event->id;

            $arrEvent['details'] = Template::once(
                static function () use ($id) {
                    $strDetails = '';
                    $objElement = ContentModel::findPublishedByPidAndTable($id, 'tl_calendar_events');

                    if (null !== $objElement) {
                        while ($objElement->next()) {
                            $strDetails .= Controller::getContentElement($objElement->current());
                        }
                    }

                    return $strDetails;
                }
            );

            $arrEvent['hasDetails'] = null === $url ? false : Template::once(static fn () => ContentModel::countPublishedByPidAndTable($id, 'tl_calendar_events') > 0);
        }

        // Get today's start and end timestamp
        $this->todayBegin ??= strtotime('00:00:00');
        $this->todayEnd ??= strtotime('23:59:59');

        // Mark past and upcoming events (see #3692)
        if ($end < $this->todayBegin) {
            $arrEvent['class'] .= ' bygone';
        } elseif ($start > $this->todayEnd) {
            $arrEvent['class'] .= ' upcoming';
        } else {
            $arrEvent['class'] .= ' current';
        }

        if (1 === $arrEvent['featured']) {
            $arrEvent['class'] .= ' featured';
        }

        $events[$intKey][$start][] = $arrEvent;

        // Multi-day event
        for ($i = 1; $i <= $span; ++$i) {
            // Only show first occurrence
            if ($noSpan) {
                break;
            }

            $date = strtotime('+1 day', $date);

            if ($date > $limit) {
                break;
            }

            $events[date('Ymd', $date)][$date][] = $arrEvent;
        }
    }
}
