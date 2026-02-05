<?php

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

use Contao\CalendarEventsModel;
use Contao\CalendarFeedModel;
use Contao\CalendarModel;
use Contao\CoreBundle\Controller\Backend\CsvImportController;
use Contao\ModuleCalendar;
use Contao\ModuleEventlist;
use Contao\ModuleEventMenu;
use Contao\ModuleEventReader;
use Contao\System;
use Symfony\Component\HttpFoundation\Request;

// Back end modules
$GLOBALS['BE_MOD']['content']['calendar'] = array
(
	'tables'      => array('tl_calendar', 'tl_calendar_events', 'tl_content'),
	'table'       => array(CsvImportController::class, 'importTableWizardAction'),
	'list'        => array(CsvImportController::class, 'importListWizardAction')
);

// Front end modules
$GLOBALS['FE_MOD']['events'] = array
(
	'calendar'    => ModuleCalendar::class,
	'eventreader' => ModuleEventReader::class,
	'eventlist'   => ModuleEventlist::class,
	'eventmenu'   => ModuleEventMenu::class
);

// Style sheet
if (System::getContainer()->get('contao.routing.scope_matcher')->isBackendRequest(System::getContainer()->get('request_stack')->getCurrentRequest() ?? Request::create('')))
{
	$GLOBALS['TL_CSS'][] = 'bundles/contaocalendar/calendar.min.css|static';
}

// Add permissions
$GLOBALS['TL_PERMISSIONS'][] = 'calendars';

// Models
$GLOBALS['TL_MODELS']['tl_calendar_events'] = CalendarEventsModel::class;
$GLOBALS['TL_MODELS']['tl_calendar_feed'] = CalendarFeedModel::class;
$GLOBALS['TL_MODELS']['tl_calendar'] = CalendarModel::class;
