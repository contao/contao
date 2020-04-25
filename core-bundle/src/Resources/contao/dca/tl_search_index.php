<?php

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

$GLOBALS['TL_DCA']['tl_search_index'] = array
(
	// Config
	'config' => array
	(
		'sql' => array
		(
			'keys' => array
			(
				'id' => 'primary',
				'pid' => 'index',
				'wordId' => 'index'
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
		'wordId' => array
		(
			'sql'                     => "int(10) unsigned NOT NULL default 0"
		),
		'relevance' => array
		(
			'sql'                     => "smallint(5) unsigned NOT NULL default 0"
		),
		'language' => array
		(
			'sql'                     => "varchar(5) NOT NULL default ''"
		)
	)
);
