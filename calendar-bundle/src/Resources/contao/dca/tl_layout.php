<?php

/**
 * Contao Open Source CMS
 *
 * Copyright (c) 2005-2016 Leo Feyer
 *
 * @license LGPL-3.0+
 */

use Contao\CoreBundle\DataContainer\PaletteManipulator;


/**
 * Extend default palette
 */
PaletteManipulator::create()
    ->addLegend('feed_legend', 'modules_legend', PaletteManipulator::POSITION_BEFORE)
    ->addField('calendarfeeds', 'feed_legend', PaletteManipulator::POSITION_APPEND)
    ->applyToPalette('tl_layout', 'default')
;


/**
 * Extend fields
 */
$GLOBALS['TL_DCA']['tl_layout']['fields']['calendarfeeds'] = array
(
    'label'             => &$GLOBALS['TL_LANG']['tl_layout']['calendarfeeds'],
    'exclude'           => true,
    'inputType'         => 'checkbox',
    'foreignKey'        => 'tl_calendar_feed.title',
    'eval'              => array('multiple'=>true),
    'sql'               => "blob NULL"
);
