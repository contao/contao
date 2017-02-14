<?php

/*
 * This file is part of Contao.
 *
 * Copyright (c) 2005-2017 Leo Feyer
 *
 * @license LGPL-3.0+
 */

namespace Contao\CalendarBundle\EventListener;

use Contao\CalendarEventsModel;
use Contao\CalendarModel;
use Contao\CoreBundle\Framework\ContaoFrameworkInterface;
use Contao\PageModel;

/**
 * Provides file meta information for the request.
 *
 * @author Andreas Schempp <https://github.com/aschempp>
 * @author Leo Feyer <https://github.com/leofeyer>
 */
class FileMetaInformationListener
{
    /**
     * @var ContaoFrameworkInterface
     */
    private $framework;

    /**
     * Constructor.
     *
     * @param ContaoFrameworkInterface $framework
     */
    public function __construct(ContaoFrameworkInterface $framework)
    {
        $this->framework = $framework;
    }

    /**
     * Returns the page model related to the given table and ID.
     *
     * @param string $table
     * @param int    $id
     *
     * @return PageModel|false|null
     */
    public function onAddFileMetaInformationToRequest($table, $id)
    {
        switch ($table) {
            case 'tl_calendar':
                return $this->getPageForCalendar($id);

            case 'tl_calendar_events':
                return $this->getPageForEvent($id);
        }

        return false;
    }

    /**
     * Returns the page model for a calendar.
     *
     * @param int $id
     *
     * @return PageModel|false|null
     */
    private function getPageForCalendar($id)
    {
        $this->framework->initialize();

        /** @var CalendarModel $calendarAdapter */
        $calendarAdapter = $this->framework->getAdapter(CalendarModel::class);

        if (null === ($calendarModel = $calendarAdapter->findByPk($id))) {
            return false;
        }

        /** @var PageModel $pageModel */
        $pageModel = $calendarModel->getRelated('jumpTo');

        return $pageModel;
    }

    /**
     * Returns the page model for an event.
     *
     * @param int $id
     *
     * @return PageModel|false|null
     */
    private function getPageForEvent($id)
    {
        $this->framework->initialize();

        /** @var CalendarEventsModel $eventsAdapter */
        $eventsAdapter = $this->framework->getAdapter(CalendarEventsModel::class);

        if (null === ($eventsModel = $eventsAdapter->findByPk($id))) {
            return false;
        }

        /** @var CalendarModel $calendarModel */
        if (null === ($calendarModel = $eventsModel->getRelated('pid'))) {
            return false;
        }

        /** @var PageModel $pageModel */
        $pageModel = $calendarModel->getRelated('jumpTo');

        return $pageModel;
    }
}
