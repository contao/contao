<?php

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

use Contao\CoreBundle\DataContainer\PaletteManipulator;
use Doctrine\DBAL\Platforms\AbstractMySQLPlatform;

// Extend the default palette
PaletteManipulator::create()
	->addLegend('newsletter_legend', 'amg_legend', PaletteManipulator::POSITION_BEFORE)
	->addField(array('newsletters'), 'newsletter_legend', PaletteManipulator::POSITION_APPEND)
	->applyToPalette('default', 'tl_user_group')
;

// Add fields to tl_user_group
$GLOBALS['TL_DCA']['tl_user_group']['fields']['newsletters'] = array
(
	'label'                   => &$GLOBALS['TL_LANG']['tl_user']['newsletters'],
	'inputType'               => 'checkbox',
	'foreignKey'              => 'tl_newsletter_channel.title',
	'eval'                    => array('multiple'=>true),
	'sql'                     => array('type'=>'blob', 'length'=>AbstractMySQLPlatform::LENGTH_LIMIT_BLOB, 'notnull'=>false),
	'relation'                => array('type'=>'hasMany', 'load'=>'lazy')
);
