<?php

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
				'url' => 'unique',
				'checksum,pid' => 'unique'
			)
		)
	),

	// Fields
	'fields' => array
	(
		'id' => array
		(
			'sql'                     => "int(10) unsigned NOT NULL auto_increment"
		),
		'pid' => array
		(
			'sql'                     => "int(10) unsigned NOT NULL default 0"
		),
		'tstamp' => array
		(
			'sql'                     => "int(10) unsigned NOT NULL default 0"
		),
		'title' => array
		(
			'sql'                     => "text NULL"
		),
		'url' => array
		(
			'sql'                     => "varchar(2048) COLLATE ascii_bin NOT NULL default ''"
		),
		'text' => array
		(
			'sql'                     => "mediumtext NULL"
		),
		'filesize' => array
		(
			'sql'                     => "double NOT NULL default 0" // see doctrine/dbal#1018
		),
		'checksum' => array
		(
			'sql'                     => "varchar(32) NOT NULL default ''"
		),
		'protected' => array
		(
			'sql'                     => "char(1) NOT NULL default ''"
		),
		'groups' => array
		(
			'sql'                     => "blob NULL"
		),
		'language' => array
		(
			'sql'                     => "varchar(5) NOT NULL default ''"
		),
		'vectorLength' => array
		(
			'sql'                     => "double NOT NULL default 0"
		),
		'meta' => array
		(
			'sql'                     => "mediumtext NULL"
		),
	)
);
