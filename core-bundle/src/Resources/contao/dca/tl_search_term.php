<?php

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

$GLOBALS['TL_DCA']['tl_search_term'] = array
(
	// Config
	'config' => array
	(
		'sql' => array
		(
			'keys' => array
			(
				'id' => 'primary',
				'term' => 'unique',
				'documentFrequency' => 'index'
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
		'term' => array
		(
			'sql'                     => "varchar(64) BINARY NOT NULL"
		),
		'documentFrequency' => array
		(
			'sql'                     => "int(10) unsigned NOT NULL"
		)
	)
);
