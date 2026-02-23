<?php

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

use Doctrine\DBAL\Platforms\MySQLPlatform;

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

$GLOBALS['TL_DCA']['tl_version'] = array
(
	// Config
	'config' => array
	(
		'sql' => array
		(
			'keys' => array
			(
				'id' => 'primary',
				'pid,fromTable,version' => 'unique',
				'tstamp' => 'index',
				'userid' => 'index'
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
		'pid' => array
		(
			'sql'                     => array('type'=>'integer', 'unsigned'=>true, 'default'=>0)
		),
		'tstamp' => array
		(
			'sql'                     => array('type'=>'integer', 'unsigned'=>true, 'default'=>0)
		),
		'version' => array
		(
			'sql'                     => array('type'=>'smallint', 'unsigned'=>true, 'default'=>1)
		),
		'fromTable' => array
		(
			'sql'                     => array('type'=>'string', 'length'=>255, 'default'=>'')
		),
		'userid' => array
		(
			'sql'                     => array('type'=>'integer', 'unsigned'=>true, 'default'=>0)
		),
		'username' => array
		(
			'sql'                     => array('type'=>'string', 'length'=>64, 'notnull'=>false)
		),
		'description' => array
		(
			'sql'                     => array('type'=>'string', 'length'=>255, 'default'=>'')
		),
		'editUrl' => array
		(
			'sql'                     => array('type'=>'text', 'length'=>MySQLPlatform::LENGTH_LIMIT_TEXT, 'notnull'=>false)
		),
		'active' => array
		(
			'sql'                     => array('type'=>'boolean', 'default'=>false)
		),
		'data' => array
		(
			'sql'                     => array('type'=>'blob', 'length'=>MySQLPlatform::LENGTH_LIMIT_MEDIUMBLOB, 'notnull'=>false)
		)
	)
);
