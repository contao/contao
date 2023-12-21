<?php

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

use Contao\CoreBundle\DataContainer\PaletteManipulator;

$GLOBALS['TL_DCA']['tl_news_archive']['palettes']['__selector__'][] = 'allowComments';
$GLOBALS['TL_DCA']['tl_news_archive']['subpalettes']['allowComments'] = 'notify,sortOrder,perPage,moderate,bbcode,requireLogin,disableCaptcha';

$GLOBALS['TL_DCA']['tl_news_archive']['fields']['allowComments'] = array(
	'filter'                  => true,
	'inputType'               => 'checkbox',
	'eval'                    => array('submitOnChange'=>true),
	'sql'                     => array('type' => 'boolean', 'default' => false)
);

$GLOBALS['TL_DCA']['tl_news_archive']['fields']['notify'] = array
(
	'inputType'               => 'select',
	'options'                 => array('notify_admin', 'notify_author', 'notify_both'),
	'eval'                    => array('tl_class'=>'w50'),
	'reference'               => &$GLOBALS['TL_LANG']['tl_news_archive'],
	'sql'                     => "varchar(16) NOT NULL default 'notify_admin'"
);

$GLOBALS['TL_DCA']['tl_news_archive']['fields']['sortOrder'] = array
(
	'inputType'               => 'select',
	'options'                 => array('ascending', 'descending'),
	'reference'               => &$GLOBALS['TL_LANG']['MSC'],
	'eval'                    => array('tl_class'=>'w50 clr'),
	'sql'                     => "varchar(32) NOT NULL default 'ascending'"
);

$GLOBALS['TL_DCA']['tl_news_archive']['fields']['perPage'] = array
(
	'inputType'               => 'text',
	'eval'                    => array('rgxp'=>'natural', 'tl_class'=>'w50'),
	'sql'                     => "smallint(5) unsigned NOT NULL default 0"
);

$GLOBALS['TL_DCA']['tl_news_archive']['fields']['moderate'] = array
(
	'inputType'               => 'checkbox',
	'eval'                    => array('tl_class'=>'w50'),
	'sql'                     => array('type' => 'boolean', 'default' => false)
);

$GLOBALS['TL_DCA']['tl_news_archive']['fields']['bbcode'] = array
(
	'inputType'               => 'checkbox',
	'eval'                    => array('tl_class'=>'w50'),
	'sql'                     => array('type' => 'boolean', 'default' => false)
);

$GLOBALS['TL_DCA']['tl_news_archive']['fields']['requireLogin'] = array
(
	'inputType'               => 'checkbox',
	'eval'                    => array('tl_class'=>'w50'),
	'sql'                     => array('type' => 'boolean', 'default' => false)
);

$GLOBALS['TL_DCA']['tl_news_archive']['fields']['disableCaptcha'] = array
(
	'inputType'               => 'checkbox',
	'eval'                    => array('tl_class'=>'w50'),
	'sql'                     => array('type' => 'boolean', 'default' => false)
);

// Extend the default palettes
PaletteManipulator::create()
	->addLegend('comments_legend', 'protected_legend', PaletteManipulator::POSITION_AFTER, true)
	->addField(array('allowComments'), 'comments_legend', PaletteManipulator::POSITION_APPEND)
	->applyToPalette('default', 'tl_news_archive')
;
