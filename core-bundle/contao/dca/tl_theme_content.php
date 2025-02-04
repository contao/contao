<?php

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

use Contao\DataContainer;
use Contao\DC_Table;

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

$GLOBALS['TL_DCA']['tl_theme_content'] = array(
	// Config
	'config' => array
	(
		'dataContainer'               => DC_Table::class,
		'ptable'                      => 'tl_theme',
		'ctable'                      => array('tl_content'),
		'switchToEdit'                => true,
		'enableVersioning'            => true,
		'markAsCopy'                  => 'title',
		'sql' => array
		(
			'keys' => array
			(
				'id' => 'primary',
				'tstamp' => 'index',
				'pid' => 'index'
			)
		)
	),
	// List
	'list' => array
	(
		'sorting' => array
		(
			'mode'                    => DataContainer::MODE_PARENT,
			'fields'                  => array('title'),
			'panelLayout'             => 'filter;search,limit',
			'defaultSearchField'      => 'title',
			'headerFields'            => array('name', 'author', 'tstamp'),
		),
		'label' => array
		(
			'fields'                  => array('title'),
			'format'                  => '%s',
		)
	),

	// Palettes
	'palettes' => array(
		'default' => '{title_legend},title',
	),

	// Fields
	'fields' => array(
		'id' => array(
			'sql' => array('type' => 'integer', 'unsigned' => true, 'autoincrement' => true),
		),
		'pid' => array(
			'foreignKey' => 'tl_theme.name',
			'sql' => array('type' => 'integer', 'unsigned' => true, 'default' => 0),
		),
		'tstamp' => array(
			'sql' => array('type' => 'integer', 'unsigned' => true, 'default' => 0),
		),
		'title' => array(
			'search' => true,
			'sorting' => true,
			'flag'                    => DataContainer::SORT_INITIAL_LETTER_ASC,
			'inputType' => 'text',
			'eval' => array('mandatory' => true, 'maxlength' => 255, 'tl_class' => 'w50'),
			'sql' => array('type' => 'string', 'length' => 255, 'default' => ''),
		),
	),
);
