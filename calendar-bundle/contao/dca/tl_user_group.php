<?php

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

use Contao\CoreBundle\DataContainer\PaletteManipulator;

// Extend the default palette
PaletteManipulator::create()
	->addLegend('calendars_legend', 'amg_legend', PaletteManipulator::POSITION_BEFORE)
	->addField(array('calendars', 'calendarp', 'calendarfeeds', 'calendarfeedp'), 'calendars_legend', PaletteManipulator::POSITION_APPEND)
	->applyToPalette('default', 'tl_user_group')
;

// Add fields to tl_user_group
$GLOBALS['TL_DCA']['tl_user_group']['fields']['calendars'] = array
(
	'label'                   => &$GLOBALS['TL_LANG']['tl_user']['calendars'],
	'exclude'                 => true,
	'inputType'               => 'checkbox',
	'foreignKey'              => 'tl_calendar.title',
	'eval'                    => array('multiple'=>true),
	'sql'                     => "blob NULL"
);

$GLOBALS['TL_DCA']['tl_user_group']['fields']['calendarp'] = array
(
	'label'                   => &$GLOBALS['TL_LANG']['tl_user']['calendarp'],
	'exclude'                 => true,
	'inputType'               => 'checkbox',
	'options'                 => array('create', 'delete'),
	'reference'               => &$GLOBALS['TL_LANG']['MSC'],
	'eval'                    => array('multiple'=>true),
	'sql'                     => "blob NULL"
);

$GLOBALS['TL_DCA']['tl_user_group']['fields']['calendarfeeds'] = array
(
	'label'                   => &$GLOBALS['TL_LANG']['tl_user']['calendarfeeds'],
	'exclude'                 => true,
	'inputType'               => 'checkbox',
	'foreignKey'              => 'tl_calendar_feed.title',
	'eval'                    => array('multiple'=>true),
	'sql'                     => "blob NULL"
);

$GLOBALS['TL_DCA']['tl_user_group']['fields']['calendarfeedp'] = array
(
	'label'                   => &$GLOBALS['TL_LANG']['tl_user']['calendarfeedp'],
	'exclude'                 => true,
	'inputType'               => 'checkbox',
	'options'                 => array('create', 'delete'),
	'reference'               => &$GLOBALS['TL_LANG']['MSC'],
	'eval'                    => array('multiple'=>true),
	'sql'                     => "blob NULL"
);
