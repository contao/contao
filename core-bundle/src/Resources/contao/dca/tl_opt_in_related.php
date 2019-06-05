<?php

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

$GLOBALS['TL_DCA']['tl_opt_in_related'] = array
(

	// Config
	'config' => array
	(
		'sql' => array
		(
			'keys' => array
			(
				'id' => 'primary',
				'relTable,relId' => 'index'
			)
		)
	),

	// Fields
	'fields' => array
	(
		'id' => array
		(
			'label'                   => &$GLOBALS['TL_LANG']['MSC']['id'],
			'sql'                     => "int(10) unsigned NOT NULL auto_increment"
		),
		'pid' => array
		(
			'label'                   => &$GLOBALS['TL_LANG']['MSC']['pid'],
			'sql'                     => "int(10) unsigned NOT NULL default 0"
		),
		'relTable' => array
		(
			'label'                   => &$GLOBALS['TL_LANG']['tl_opt_in_related']['relTable'],
			'sql'                     => "varchar(64) NULL"
		),
		'relId' => array
		(
			'label'                   => &$GLOBALS['TL_LANG']['tl_opt_in_related']['relId'],
			'sql'                     => "int(10) unsigned NOT NULL default 0"
		)
	)
);
