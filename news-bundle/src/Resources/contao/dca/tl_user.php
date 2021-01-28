<?php

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

use Contao\CoreBundle\DataContainer\PaletteManipulator;

// Extend the default palettes
PaletteManipulator::create()
	->addLegend('news_legend', 'amg_legend', PaletteManipulator::POSITION_BEFORE)
	->addField(array('news', 'newp', 'newsfeeds', 'newsfeedp'), 'news_legend', PaletteManipulator::POSITION_APPEND)
	->applyToPalette('extend', 'tl_user')
	->applyToPalette('custom', 'tl_user')
;

// Add fields to tl_user
$GLOBALS['TL_DCA']['tl_user']['fields']['news'] = array
(
	'exclude'                 => true,
	'inputType'               => 'checkbox',
	'foreignKey'              => 'tl_news_archive.title',
	'eval'                    => array('multiple'=>true),
	'sql'                     => "blob NULL"
);

$GLOBALS['TL_DCA']['tl_user']['fields']['newp'] = array
(
	'exclude'                 => true,
	'inputType'               => 'checkbox',
	'options'                 => array('create', 'delete'),
	'reference'               => &$GLOBALS['TL_LANG']['MSC'],
	'eval'                    => array('multiple'=>true),
	'sql'                     => "blob NULL"
);

$GLOBALS['TL_DCA']['tl_user']['fields']['newsfeeds'] = array
(
	'exclude'                 => true,
	'inputType'               => 'checkbox',
	'foreignKey'              => 'tl_news_feed.title',
	'eval'                    => array('multiple'=>true),
	'sql'                     => "blob NULL"
);

$GLOBALS['TL_DCA']['tl_user']['fields']['newsfeedp'] = array
(
	'exclude'                 => true,
	'inputType'               => 'checkbox',
	'options'                 => array('create', 'delete'),
	'reference'               => &$GLOBALS['TL_LANG']['MSC'],
	'eval'                    => array('multiple'=>true),
	'sql'                     => "blob NULL"
);
