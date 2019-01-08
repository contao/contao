<?php

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

// Back end modules
array_insert($GLOBALS['BE_MOD']['content'], 1, array
(
	'calendar' => array
	(
		'tables'      => array('tl_calendar', 'tl_calendar_events', 'tl_calendar_feed', 'tl_content'),
		'table'       => array('Contao\TableWizard', 'importTable'),
		'list'        => array('Contao\ListWizard', 'importList')
	)
));

// Front end modules
array_insert($GLOBALS['FE_MOD'], 2, array
(
	'events' => array
	(
		'calendar'    => 'Contao\ModuleCalendar',
		'eventreader' => 'Contao\ModuleEventReader',
		'eventlist'   => 'Contao\ModuleEventlist',
		'eventmenu'   => 'Contao\ModuleEventMenu'
	)
));

// Cron jobs
$GLOBALS['TL_CRON']['daily']['generateCalendarFeeds'] = array('Contao\Calendar', 'generateFeeds');

// Style sheet
if (\defined('TL_MODE') && TL_MODE == 'BE')
{
	$GLOBALS['TL_CSS'][] = 'bundles/contaocalendar/calendar.min.css|static';
}

// Register hooks
$GLOBALS['TL_HOOKS']['removeOldFeeds'][] = array('Contao\Calendar', 'purgeOldFeeds');
$GLOBALS['TL_HOOKS']['getSearchablePages'][] = array('Contao\Calendar', 'getSearchablePages');
$GLOBALS['TL_HOOKS']['generatePage'][] = array('contao_calendar.listener.generate_page', 'onGeneratePage');
$GLOBALS['TL_HOOKS']['generateXmlFiles'][] = array('Contao\Calendar', 'generateFeeds');
$GLOBALS['TL_HOOKS']['replaceInsertTags'][] = array('contao_calendar.listener.insert_tags', 'onReplaceInsertTags');

// Add permissions
$GLOBALS['TL_PERMISSIONS'][] = 'calendars';
$GLOBALS['TL_PERMISSIONS'][] = 'calendarp';
$GLOBALS['TL_PERMISSIONS'][] = 'calendarfeeds';
$GLOBALS['TL_PERMISSIONS'][] = 'calendarfeedp';
