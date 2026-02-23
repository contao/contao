<?php

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

use Contao\DC_Table;

$GLOBALS['TL_DCA']['tl_comments_notify'] = array
(
	// Config
	'config' => array
	(
		'dataContainer'               => DC_Table::class,
		'closed'                      => true,
		'notEditable'                 => true,
		'sql' => array
		(
			'keys' => array
			(
				'id' => 'primary',
				'tstamp' => 'index',
				'source,parent,active,email' => 'index',
				'tokenRemove' => 'index'
			)
		)
	),

	// Fields
	'fields' => array
	(
		'id' => array
		(
			'sql'                     => array('type'=>'integer', 'unsigned'=>true, 'autoincrement'=>true)
		),
		'tstamp' => array
		(
			'sql'                     => array('type'=>'integer', 'unsigned'=>true, 'default'=>0)
		),
		'source' => array
		(
			'sql'                     => array('type'=>'string', 'length'=>32, 'default'=>'')
		),
		'parent' => array
		(
			'sql'                     => array('type'=>'integer', 'unsigned'=>true, 'default'=>0)
		),
		'name' => array
		(
			'sql'                     => array('type'=>'string', 'length'=>128, 'default'=>'')
		),
		'email' => array
		(
			'sql'                     => array('type'=>'string', 'length'=>255, 'default'=>'')
		),
		'url' => array
		(
			'sql'                     => array('type'=>'string', 'length'=>2048, 'default'=>'', 'customSchemaOptions'=>array('collation'=>'ascii_bin'))
		),
		'addedOn' => array
		(
			'sql'                     => array('type'=>'string', 'length'=>10, 'default'=>'')
		),
		'active' => array
		(
			'sql'                     => array('type'=>'boolean', 'default'=>false)
		),
		'tokenRemove' => array
		(
			'sql'                     => array('type'=>'string', 'length'=>32, 'default'=>'')
		)
	)
);
