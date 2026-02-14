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

$GLOBALS['TL_DCA']['tl_favorites'] = array
(
	// Config
	'config' => array
	(
		'dataContainer'               => DC_Table::class,
		'backendSearchIgnore'         => true,
		'permissions'                 => array(),
		'sql' => array
		(
			'keys' => array
			(
				'id' => 'primary',
				'tstamp' => 'index',
				'pid,user' => 'index',
				'url' => 'index'
			)
		)
	),

	// List
	'list' => array
	(
		'sorting' => array
		(
			'mode'                    => DataContainer::MODE_TREE,
			'rootPaste'               => true,
			'showRootTrails'          => true,
		),
		'label' => array
		(
			'fields'                  => array('title'),
			'format'                  => '%s'
		)
	),

	// Palettes
	'palettes' => array
	(
		'default'                     => '{url_legend},title,url'
	),

	// Fields
	'fields' => array
	(
		'id' => array
		(
			'sql'                     => array('type'=>'integer', 'unsigned'=>true, 'autoincrement'=>true)
		),
		'pid' => array
		(
			'sql'                     => array('type'=>'integer', 'unsigned'=>true, 'default'=>0)
		),
		'sorting' => array
		(
			'sql'                     => array('type'=>'integer', 'unsigned'=>true, 'default'=>0)
		),
		'tstamp' => array
		(
			'sql'                     => array('type'=>'integer', 'unsigned'=>true, 'default'=>0)
		),
		'user' => array
		(
			'sql'                     => array('type'=>'integer', 'unsigned'=>true, 'default'=>0)
		),
		'title' => array
		(
			'exclude'                 => false,
			'search'                  => true,
			'inputType'               => 'text',
			'eval'                    => array('mandatory'=>true, 'maxlength'=>255, 'tl_class'=>'w50'),
			'sql'                     => array('type'=>'string', 'length'=>255, 'default'=>'')
		),
		'url' => array
		(
			'exclude'                 => false,
			'search'                  => true,
			'inputType'               => 'text',
			'eval'                    => array('mandatory'=>true, 'rgxp'=>'url', 'decodeEntities'=>true, 'maxlength'=>1022, 'tl_class'=>'w50'),
			'sql'                     => array('type'=>'string', 'length'=>1022, 'default'=>'')
		)
	)
);
