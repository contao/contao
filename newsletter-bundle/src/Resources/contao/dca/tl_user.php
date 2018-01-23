<?php

/**
 * Contao Open Source CMS
 *
 * Copyright (c) 2005-2018 Leo Feyer
 *
 * @license LGPL-3.0+
 */


/**
 * Extend the default palettes
 */
Contao\CoreBundle\DataContainer\PaletteManipulator::create()
    ->addLegend('newsletter_legend', 'amg_legend', Contao\CoreBundle\DataContainer\PaletteManipulator::POSITION_BEFORE)
    ->addField(array('newsletters', 'newsletterp'), 'newsletter_legend', Contao\CoreBundle\DataContainer\PaletteManipulator::POSITION_APPEND)
    ->applyToPalette('extend', 'tl_user')
    ->applyToPalette('custom', 'tl_user')
;


/**
 * Add fields to tl_user_group
 */
$GLOBALS['TL_DCA']['tl_user']['fields']['newsletters'] = array
(
	'label'                   => &$GLOBALS['TL_LANG']['tl_user']['newsletters'],
	'exclude'                 => true,
	'inputType'               => 'checkbox',
	'foreignKey'              => 'tl_newsletter_channel.title',
	'eval'                    => array('multiple'=>true),
	'sql'                     => "blob NULL"
);

$GLOBALS['TL_DCA']['tl_user']['fields']['newsletterp'] = array
(
	'label'                   => &$GLOBALS['TL_LANG']['tl_user']['newsletterp'],
	'exclude'                 => true,
	'inputType'               => 'checkbox',
	'options'                 => array('create', 'delete'),
	'reference'               => &$GLOBALS['TL_LANG']['MSC'],
	'eval'                    => array('multiple'=>true),
	'sql'                     => "blob NULL"
);
