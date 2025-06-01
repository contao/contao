<?php

declare(strict_types=1);

namespace Contao\CalendarBundle;

use Contao\CalendarEventsModel;
use Contao\StringUtil;
use Contao\System;

class CalendarEventsGenerator
{
    public function getAllEvents(array $calendars, int $start, int $end, bool|null $featured = null): array
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

                $this->addEvent($events, $eventModel, $eventModel->startTime, $eventModel->endTime, $start, $end, $id);

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

                        $this->addEvent($events, $eventModel, $startTime, $endTime, $start, $end, $id);
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

    private function addEvent(array &$events, CalendarEventsModel $event, int $start, int $end, int $begin, int $limit, int $calendar): void
    {
        // TODO
    }
}
