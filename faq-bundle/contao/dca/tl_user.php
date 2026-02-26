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

// Extend the default palettes
PaletteManipulator::create()
	->addLegend('faq_legend', 'amg_legend', PaletteManipulator::POSITION_BEFORE)
	->addField('faqs', 'faq_legend', PaletteManipulator::POSITION_APPEND)
	->applyToPalette('extend', 'tl_user')
	->applyToPalette('custom', 'tl_user')
;

// Add fields to tl_user
$GLOBALS['TL_DCA']['tl_user']['fields']['faqs'] = array
(
	'inputType'               => 'checkbox',
	'foreignKey'              => 'tl_faq_category.title',
	'eval'                    => array('multiple'=>true),
	'sql'                     => array('type'=>'blob', 'length'=>AbstractMySQLPlatform::LENGTH_LIMIT_BLOB, 'notnull'=>false),
	'relation'                => array('type'=>'hasMany', 'load'=>'lazy')
);
