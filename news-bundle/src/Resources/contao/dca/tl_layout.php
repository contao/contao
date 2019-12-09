<?php

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

// Extend default palette
Contao\CoreBundle\DataContainer\PaletteManipulator::create()
	->addLegend('feed_legend', 'modules_legend', Contao\CoreBundle\DataContainer\PaletteManipulator::POSITION_BEFORE)
	->addField(
		'newsfeeds',
		'calendarfeeds',
		Contao\CoreBundle\DataContainer\PaletteManipulator::POSITION_BEFORE,
		'feed_legend',
		Contao\CoreBundle\DataContainer\PaletteManipulator::POSITION_PREPEND
	)
	->applyToPalette('default', 'tl_layout')
;

// Extend fields
$GLOBALS['TL_DCA']['tl_layout']['fields']['newsfeeds'] = array
(
	'exclude'         => true,
	'inputType'       => 'checkbox',
	'foreignKey'      => 'tl_news_feed.title',
	'eval'            => array('multiple'=>true),
	'sql'             => "blob NULL",
);
