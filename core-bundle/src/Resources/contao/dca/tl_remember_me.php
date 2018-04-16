<?php

/**
 * Contao Open Source CMS
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

$GLOBALS['TL_DCA']['tl_remember_me'] = array
(

	// Config
	'config' => array
	(
		'sql' => array
		(
			'keys' => array
			(
				'series' => 'primary'
			)
		)
	),

	// Fields
	'fields' => array
	(
		'series' => array
		(
			'sql'                     => "char(88) NOT NULL"
		),
		'value' => array
		(
			'sql'                     => "char(88) NOT NULL"
		),
		'lastUsed' => array
		(
			'sql'                     => "datetime NULL"
		),
		'class' => array
		(
			'sql'                     => "varchar(100) NOT NULL"
		),
		'username' => array
		(
			'sql'                     => "varchar(200) NOT NULL"
		)
	)
);
