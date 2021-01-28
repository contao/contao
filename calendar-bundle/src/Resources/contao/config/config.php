<?php

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

use Contao\Calendar;
use Contao\CalendarEventsModel;
use Contao\CalendarFeedModel;
use Contao\CalendarModel;
use Contao\ListWizard;
use Contao\ModuleCalendar;
use Contao\ModuleEventlist;
use Contao\ModuleEventMenu;
use Contao\ModuleEventReader;
use Contao\TableWizard;

// Back end modules
$GLOBALS['BE_MOD']['content']['calendar'] = array
(
	'tables'      => array('tl_calendar', 'tl_calendar_events', 'tl_calendar_feed', 'tl_content'),
	'table'       => array(TableWizard::class, 'importTable'),
	'list'        => array(ListWizard::class, 'importList')
);

// Front end modules
$GLOBALS['FE_MOD']['events'] = array
(
	'calendar'    => ModuleCalendar::class,
	'eventreader' => ModuleEventReader::class,
	'eventlist'   => ModuleEventlist::class,
	'eventmenu'   => ModuleEventMenu::class
);

// Cron jobs
$GLOBALS['TL_CRON']['daily']['generateCalendarFeeds'] = array(Calendar::class, 'generateFeeds');

// Style sheet
if (defined('TL_MODE') && TL_MODE == 'BE')
{
	$GLOBALS['TL_CSS'][] = 'bundles/contaocalendar/calendar.min.css|static';
}

// Register hooks
$GLOBALS['TL_HOOKS']['removeOldFeeds'][] = array(Calendar::class, 'purgeOldFeeds');
$GLOBALS['TL_HOOKS']['getSearchablePages'][] = array(Calendar::class, 'getSearchablePages');
$GLOBALS['TL_HOOKS']['generateXmlFiles'][] = array(Calendar::class, 'generateFeeds');

// Add permissions
$GLOBALS['TL_PERMISSIONS'][] = 'calendars';
$GLOBALS['TL_PERMISSIONS'][] = 'calendarp';
$GLOBALS['TL_PERMISSIONS'][] = 'calendarfeeds';
$GLOBALS['TL_PERMISSIONS'][] = 'calendarfeedp';

// Models
$GLOBALS['TL_MODELS']['tl_calendar_events'] = CalendarEventsModel::class;
$GLOBALS['TL_MODELS']['tl_calendar_feed'] = CalendarFeedModel::class;
$GLOBALS['TL_MODELS']['tl_calendar'] = CalendarModel::class;
