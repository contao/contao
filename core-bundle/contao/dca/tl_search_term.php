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
			'sql'                     => array('type'=>'integer', 'unsigned'=>true, 'autoincrement'=>true)
		),
		'term' => array
		(
			'sql'                     => array('type'=>'string', 'length'=>64, 'default'=>'', 'platformOptions'=>array('collation'=>'utf8mb4_bin'))
		),
		'documentFrequency' => array
		(
			'sql'                     => array('type'=>'integer', 'unsigned'=>true)
		)
	)
);
