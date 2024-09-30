<?php

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

use Contao\CoreBundle\DataContainer\PaletteManipulator;

// Extend default palette
PaletteManipulator::create()
	->addLegend('feed_legend', 'modules_legend', PaletteManipulator::POSITION_BEFORE)
	->addField('calendarfeeds', 'feed_legend', PaletteManipulator::POSITION_APPEND)
	->applyToPalette('default', 'tl_layout')
;

// Extend fields
$GLOBALS['TL_DCA']['tl_layout']['fields']['calendarfeeds'] = array
(
	'exclude'           => true,
	'inputType'         => 'checkbox',
	'foreignKey'        => 'tl_calendar_feed.title',
	'eval'              => array('multiple'=>true),
	'sql'               => "blob NULL",
	'relation'          => array('type'=>'hasMany', 'load'=>'lazy')
);
