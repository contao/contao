<?php

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

$GLOBALS['TL_DCA']['tl_search_index_queue'] = array
(
	// Config
	'config' => array
	(
		'sql' => array
		(
			'keys' => array
			(
				'id' => 'primary',
				'job_id' => 'index',
				'uri' => 'index',
				'processed' => 'index',
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
		'job_id' => array
		(
			'sql'                     => "varchar(128) NOT NULL"
		),
		'uri' => array
		(
			'sql'                     => "varchar(255) NOT NULL"
		),
		'found_on' => array
		(
			'sql'                     => "varchar(255) NULL"
		),
		'level' => array
		(
			'sql'                     => "smallint(5) unsigned NOT NULL"
		),
		'processed' => array
		(
			'sql'                     => "tinyint(1) NOT NULL"
		),
	)
);
