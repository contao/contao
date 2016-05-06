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
    ->addField('newsfeeds', 'calendarfeeds', PaletteManipulator::POSITION_BEFORE, 'feed_legend', PaletteManipulator::POSITION_PREPEND)
    ->applyToPalette('tl_layout', 'default')
;


/**
 * Extend fields
 */
$GLOBALS['TL_DCA']['tl_layout']['fields']['newsfeeds'] = array
(
    'label'           => &$GLOBALS['TL_LANG']['tl_layout']['newsfeeds'],
    'exclude'         => true,
    'inputType'       => 'checkbox',
    'foreignKey'      => 'tl_news_feed.title',
    'eval'            => array('multiple'=>true),
    'sql'             => "blob NULL",
);
