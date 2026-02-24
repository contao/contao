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
			'sql'                     => array('type'=>'integer', 'unsigned'=>true)
		),
		'termId' => array
		(
			'sql'                     => array('type'=>'integer', 'unsigned'=>true)
		),
		'relevance' => array
		(
			'sql'                     => array('type'=>'smallint', 'unsigned'=>true)
		)
	)
);
