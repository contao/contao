<?php

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

use Contao\CoreBundle\DataContainer\PaletteManipulator;

$GLOBALS['TL_DCA']['tl_faq']['list']['sorting']['headerFields'][] = 'allowComments';

$GLOBALS['TL_DCA']['tl_faq']['fields']['noComments'] = array
(
	'filter'                  => true,
	'inputType'               => 'checkbox',
	'sql'                     => array('type' => 'boolean', 'default' => false)
);

PaletteManipulator::create()
	->addLegend('expert_legend', 'publish_legend', PaletteManipulator::POSITION_BEFORE, true)
	->addField(array('noComments'), 'expert_legend', PaletteManipulator::POSITION_APPEND)
	->applyToPalette('default', 'tl_faq')
;
