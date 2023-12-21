<?php

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

use Contao\CoreBundle\DataContainer\PaletteManipulator;

$GLOBALS['TL_DCA']['tl_calendar_events']['list']['sorting']['headerFields'][] = 'allowComments';

$GLOBALS['TL_DCA']['tl_calendar_events']['fields']['noComments'] = array
(
	'inputType'               => 'checkbox',
	'eval'                    => array('tl_class'=>'w50 m12'),
	'sql'                     => array('type' => 'boolean', 'default' => false)
);

PaletteManipulator::create()
	->addField(array('noComments'), 'expert_legend', PaletteManipulator::POSITION_APPEND)
	->applyToPalette('default', 'tl_calendar_events')
	->applyToPalette('internal', 'tl_calendar_events')
	->applyToPalette('article', 'tl_calendar_events')
	->applyToPalette('external', 'tl_calendar_events')
;
