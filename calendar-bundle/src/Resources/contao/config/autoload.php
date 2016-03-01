<?php

/**
 * Contao Open Source CMS
 *
 * Copyright (c) 2005-2016 Leo Feyer
 *
 * @license LGPL-3.0+
 */

// Classes
ClassLoader::addClasses(array
(
	// Classes
	'Contao\Calendar'            => 'vendor/contao/calendar-bundle/src/Resources/contao/classes/Calendar.php',
	'Contao\Events'              => 'vendor/contao/calendar-bundle/src/Resources/contao/classes/Events.php',

	// Models
	'Contao\CalendarEventsModel' => 'vendor/contao/calendar-bundle/src/Resources/contao/models/CalendarEventsModel.php',
	'Contao\CalendarFeedModel'   => 'vendor/contao/calendar-bundle/src/Resources/contao/models/CalendarFeedModel.php',
	'Contao\CalendarModel'       => 'vendor/contao/calendar-bundle/src/Resources/contao/models/CalendarModel.php',

	// Modules
	'Contao\ModuleCalendar'      => 'vendor/contao/calendar-bundle/src/Resources/contao/modules/ModuleCalendar.php',
	'Contao\ModuleEventlist'     => 'vendor/contao/calendar-bundle/src/Resources/contao/modules/ModuleEventlist.php',
	'Contao\ModuleEventMenu'     => 'vendor/contao/calendar-bundle/src/Resources/contao/modules/ModuleEventMenu.php',
	'Contao\ModuleEventReader'   => 'vendor/contao/calendar-bundle/src/Resources/contao/modules/ModuleEventReader.php',
));

// Templates
TemplateLoader::addFiles(array
(
	'cal_default'     => 'vendor/contao/calendar-bundle/src/Resources/contao/templates/calendar',
	'cal_mini'        => 'vendor/contao/calendar-bundle/src/Resources/contao/templates/calendar',
	'event_full'      => 'vendor/contao/calendar-bundle/src/Resources/contao/templates/events',
	'event_list'      => 'vendor/contao/calendar-bundle/src/Resources/contao/templates/events',
	'event_teaser'    => 'vendor/contao/calendar-bundle/src/Resources/contao/templates/events',
	'event_upcoming'  => 'vendor/contao/calendar-bundle/src/Resources/contao/templates/events',
	'mod_calendar'    => 'vendor/contao/calendar-bundle/src/Resources/contao/templates/modules',
	'mod_eventlist'   => 'vendor/contao/calendar-bundle/src/Resources/contao/templates/modules',
	'mod_eventmenu'   => 'vendor/contao/calendar-bundle/src/Resources/contao/templates/modules',
	'mod_eventreader' => 'vendor/contao/calendar-bundle/src/Resources/contao/templates/modules',
));
