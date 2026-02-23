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

// Extend default palette
PaletteManipulator::create()
	->addLegend('feed_legend', 'modules_legend', PaletteManipulator::POSITION_BEFORE)
	->addField('newsfeeds', 'feed_legend', PaletteManipulator::POSITION_APPEND)
	->applyToPalette('default', 'tl_layout')
;

// Extend fields
$GLOBALS['TL_DCA']['tl_layout']['fields']['newsfeeds'] = array
(
	'inputType'       => 'checkbox',
	'foreignKey'      => 'tl_page.title',
	'eval'            => array('multiple'=>true),
	'sql'             => array('type'=>'blob', 'length'=>AbstractMySQLPlatform::LENGTH_LIMIT_BLOB, 'notnull'=>false),
	'relation'        => array('type'=>'hasMany', 'load'=>'lazy')
);
