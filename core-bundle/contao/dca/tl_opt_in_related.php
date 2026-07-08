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
				'pid' => 'index',
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
			'sql'                     => array('type'=>'integer', 'unsigned'=>true, 'autoincrement'=>true)
		),
		'pid' => array
		(
			'label'                   => &$GLOBALS['TL_LANG']['MSC']['pid'],
			'sql'                     => array('type'=>'integer', 'unsigned'=>true, 'default'=>0)
		),
		'relTable' => array
		(
			'sql'                     => array('type'=>'string', 'length'=>64, 'notnull'=>false)
		),
		'relId' => array
		(
			'sql'                     => array('type'=>'integer', 'unsigned'=>true, 'default'=>0)
		)
	)
);
