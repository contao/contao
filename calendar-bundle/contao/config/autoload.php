<?php

/**
 * Contao Open Source CMS
 *
 * Copyright (c) 2005-2014 Leo Feyer
 *
 * @package Calendar
 * @link    https://contao.org
 * @license http://www.gnu.org/licenses/lgpl-3.0.html LGPL
 */

// Classes
ClassLoader::addClasses(array
(
	// Classes
	'Contao\Calendar'            => 'vendor/contao/calendar-bundle/contao/classes/Calendar.php',
	'Contao\Events'              => 'vendor/contao/calendar-bundle/contao/classes/Events.php',

	// Models
	'Contao\CalendarEventsModel' => 'vendor/contao/calendar-bundle/contao/models/CalendarEventsModel.php',
	'Contao\CalendarFeedModel'   => 'vendor/contao/calendar-bundle/contao/models/CalendarFeedModel.php',
	'Contao\CalendarModel'       => 'vendor/contao/calendar-bundle/contao/models/CalendarModel.php',

	// Modules
	'Contao\ModuleCalendar'      => 'vendor/contao/calendar-bundle/contao/modules/ModuleCalendar.php',
	'Contao\ModuleEventlist'     => 'vendor/contao/calendar-bundle/contao/modules/ModuleEventlist.php',
	'Contao\ModuleEventMenu'     => 'vendor/contao/calendar-bundle/contao/modules/ModuleEventMenu.php',
	'Contao\ModuleEventReader'   => 'vendor/contao/calendar-bundle/contao/modules/ModuleEventReader.php',
));

// Templates
TemplateLoader::addFiles(array
(
	'cal_default'        => 'vendor/contao/calendar-bundle/contao/templates/calendar',
	'cal_mini'           => 'vendor/contao/calendar-bundle/contao/templates/calendar',
	'event_full'         => 'vendor/contao/calendar-bundle/contao/templates/events',
	'event_list'         => 'vendor/contao/calendar-bundle/contao/templates/events',
	'event_teaser'       => 'vendor/contao/calendar-bundle/contao/templates/events',
	'event_upcoming'     => 'vendor/contao/calendar-bundle/contao/templates/events',
	'mod_calendar'       => 'vendor/contao/calendar-bundle/contao/templates/modules',
	'mod_event'          => 'vendor/contao/calendar-bundle/contao/templates/modules',
	'mod_eventlist'      => 'vendor/contao/calendar-bundle/contao/templates/modules',
	'mod_eventmenu'      => 'vendor/contao/calendar-bundle/contao/templates/modules',
	'mod_eventmenu_year' => 'vendor/contao/calendar-bundle/contao/templates/modules',
));
