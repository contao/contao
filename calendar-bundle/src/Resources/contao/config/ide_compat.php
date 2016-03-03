<?php

/**
 * This file is part of Contao.
 *
 * Copyright (c) 2005-2016 Leo Feyer
 *
 * @license LGPL-3.0+
 */

// This file is not used in Contao. Its only purpose is to make PHP IDEs like
// Eclipse, Zend Studio or PHPStorm realize the class origins, since the dynamic
// class aliasing we are using is a bit too complex for them to understand.
namespace  {
	class Calendar extends \Contao\Calendar {}
	abstract class Events extends \Contao\Events {}
	class CalendarEventsModel extends \Contao\CalendarEventsModel {}
	class CalendarFeedModel extends \Contao\CalendarFeedModel {}
	class CalendarModel extends \Contao\CalendarModel {}
	class ModuleCalendar extends \Contao\ModuleCalendar {}
	class ModuleEventlist extends \Contao\ModuleEventlist {}
	class ModuleEventMenu extends \Contao\ModuleEventMenu {}
	class ModuleEventReader extends \Contao\ModuleEventReader {}
}
