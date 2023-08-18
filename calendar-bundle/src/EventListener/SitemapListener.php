<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao\CalendarBundle\EventListener;

use Contao\CalendarEventsModel;
use Contao\CalendarModel;
use Contao\CoreBundle\Event\SitemapEvent;
use Contao\CoreBundle\Framework\ContaoFramework;
use Contao\Database;
use Contao\PageModel;

class SitemapListener
{
    public function __construct(private readonly ContaoFramework $framework)
    {
    }

    public function __invoke(SitemapEvent $event): void
    {
        $arrRoot = $this->framework->createInstance(Database::class)->getChildRecords($event->getRootPageIds(), 'tl_page');

        // Early return here in the unlikely case that there are no pages
        if (empty($arrRoot)) {
            return;
        }

        $arrPages = [];
        $time = time();

        // Get all calendars
        $objCalendars = $this->framework->getAdapter(CalendarModel::class)->findByProtected('');

        if (null === $objCalendars) {
            return;
        }

        // Walk through each calendar
        foreach ($objCalendars as $objCalendar) {
            // Skip calendars without target page
            if (!$objCalendar->jumpTo) {
                continue;
            }

            // Skip calendars outside the root nodes
            if (!\in_array($objCalendar->jumpTo, $arrRoot, true)) {
                continue;
            }

            $objParent = $this->framework->getAdapter(PageModel::class)->findWithDetails($objCalendar->jumpTo);

            // The target page does not exist
            if (!$objParent instanceof PageModel) {
                continue;
            }

            // The target page has not been published (see #5520)
            if (!$objParent->published || ($objParent->start && $objParent->start > $time) || ($objParent->stop && $objParent->stop <= $time)) {
                continue;
            }

            // The target page is protected (see #8416)
            if ($objParent->protected) {
                continue;
            }

            // The target page is exempt from the sitemap (see #6418)
            if ('noindex,nofollow' === $objParent->robots) {
                continue;
            }

            // Get the items
            $objEvents = $this->framework->getAdapter(CalendarEventsModel::class)->findPublishedDefaultByPid($objCalendar->id);

            if (null === $objEvents) {
                continue;
            }

            foreach ($objEvents as $objEvent) {
                if ('noindex,nofollow' === $objEvent->robots) {
                    continue;
                }

                $arrPages[] = $objParent->getAbsoluteUrl('/'.($objEvent->alias ?: $objEvent->id));
            }
        }

        foreach ($arrPages as $strUrl) {
            $event->addUrlToDefaultUrlSet($strUrl);
        }
    }
}
