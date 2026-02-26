<?php

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

use Doctrine\DBAL\Platforms\AbstractMySQLPlatform;

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

$GLOBALS['TL_DCA']['tl_search'] = array
(
	// Config
	'config' => array
	(
		'sql' => array
		(
			'keys' => array
			(
				'id' => 'primary',
				'tstamp' => 'index',
				'url' => 'unique',
				'pid,checksum' => 'unique'
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
		'title' => array
		(
			'sql'                     => array('type'=>'text', 'length'=>AbstractMySQLPlatform::LENGTH_LIMIT_TEXT, 'notnull'=>false)
		),
		'url' => array
		(
			'sql'                     => array('type'=>'string', 'length'=>2048, 'default'=>'', 'customSchemaOptions'=>array('collation'=>'ascii_bin'))
		),
		'text' => array
		(
			'sql'                     => array('type'=>'text', 'length'=>AbstractMySQLPlatform::LENGTH_LIMIT_MEDIUMTEXT, 'notnull'=>false)
		),
		'filesize' => array
		(
			'sql'                     => array('type'=>'float', 'default'=>'0')
		),
		'checksum' => array
		(
			'sql'                     => array('type'=>'string', 'length'=>32, 'default'=>'')
		),
		'protected' => array
		(
			'sql'                     => array('type'=>'boolean', 'default'=>false)
		),
		'groups' => array
		(
			'sql'                     => array('type'=>'blob', 'length'=>AbstractMySQLPlatform::LENGTH_LIMIT_BLOB, 'notnull'=>false)
		),
		'language' => array
		(
			'sql'                     => array('type'=>'string', 'length'=>5, 'default'=>'')
		),
		'vectorLength' => array
		(
			'sql'                     => array('type'=>'float', 'default'=>'0')
		),
		'meta' => array
		(
			'sql'                     => array('type'=>'text', 'length'=>AbstractMySQLPlatform::LENGTH_LIMIT_MEDIUMTEXT, 'notnull'=>false)
		),
	)
);
