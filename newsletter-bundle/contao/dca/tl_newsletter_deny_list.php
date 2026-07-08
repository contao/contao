<?php

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

$GLOBALS['TL_DCA']['tl_newsletter_deny_list'] = array
(
	// Config
	'config' => array
	(
		'sql' => array
		(
			'keys' => array
			(
				'id' => 'primary',
				'pid,hash' => 'unique'
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
		'pid' => array
		(
			'foreignKey'              => 'tl_newsletter_channel.email',
			'sql'                     => array('type'=>'integer', 'unsigned'=>true, 'default'=>0),
			'relation'                => array('type'=>'belongsTo', 'load'=>'lazy')
		),
		'hash' => array
		(
			'sql'                     => array('type'=>'string', 'length'=>32, 'notnull'=>false)
		)
	)
);
