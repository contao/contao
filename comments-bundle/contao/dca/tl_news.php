<?php

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

use Contao\CoreBundle\DataContainer\PaletteManipulator;

$GLOBALS['TL_DCA']['tl_news']['list']['sorting']['headerFields'][] = 'allowComments';

$GLOBALS['TL_DCA']['tl_news']['fields']['noComments'] = array
(
	'filter'                  => true,
	'inputType'               => 'checkbox',
	'eval'                    => array('tl_class'=>'w50 m12'),
	'sql'                     => array('type' => 'boolean', 'default' => false)
);

PaletteManipulator::create()
	->addField(array('noComments'), 'expert_legend', PaletteManipulator::POSITION_APPEND)
	->applyToPalette('default', 'tl_news')
	->applyToPalette('internal', 'tl_news')
	->applyToPalette('article', 'tl_news')
	->applyToPalette('external', 'tl_news')
;
