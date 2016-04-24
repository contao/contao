<?php

/**
 * Contao Open Source CMS
 *
 * Copyright (c) 2005-2016 Leo Feyer
 *
 * @license LGPL-3.0+
 */


/**
 * Extend default palette
 */
\Contao\CoreBundle\DataContainer\PaletteManipulator::create()
    ->addLegend('feed_legend', 'modules_legend', \Contao\CoreBundle\DataContainer\PaletteManipulator::POSITION_BEFORE)
    ->addField(
        'newsfeeds',
        'calendarfeeds',
        \Contao\CoreBundle\DataContainer\PaletteManipulator::POSITION_BEFORE,
        'feed_legend',
        \Contao\CoreBundle\DataContainer\PaletteManipulator::POSITION_PREPEND
    )
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
