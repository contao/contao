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
				'termId,pid' => 'primary',
				'pid' => 'index'
			)
		)
	),

	// Fields
	'fields' => array
	(
		'pid' => array
		(
			'sql'                     => "int(10) unsigned NOT NULL"
		),
		'termId' => array
		(
			'sql'                     => "int(10) unsigned NOT NULL"
		),
		'relevance' => array
		(
			'sql'                     => "smallint(5) unsigned NOT NULL"
		)
	)
);
