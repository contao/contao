<?php

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

$GLOBALS['TL_DCA']['tl_opt_in'] = array
(

	// Config
	'config' => array
	(
		'dataContainer'               => 'Table',
		'closed'                      => true,
		'notEditable'                 => true,
		'notCopyable'                 => true,
		'notDeletable'                => true,
		'sql' => array
		(
			'keys' => array
			(
				'id' => 'primary',
				'token' => 'unique'
			)
		)
	),

	// List
	'list'  => array
	(
		'sorting' => array
		(
			'mode'                    => 2,
			'fields'                  => array('createdOn DESC'),
			'panelLayout'             => 'filter;sort,search,limit'
		),
		'label' => array
		(
			'fields'                  => array('token', 'email', 'createdOn', 'relatedTable', 'relatedId'),
			'showColumns'             => true,
		),
		'operations' => array
		(
			'show' => array
			(
				'label'               => &$GLOBALS['TL_LANG']['tl_opt_in']['show'],
				'href'                => 'act=show',
				'icon'                => 'show.svg'
			),
			'resend' => array
			(
				'label'               => &$GLOBALS['TL_LANG']['tl_opt_in']['resend'],
				'href'                => 'act=resend',
				'icon'                => 'resend.svg'
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
		'tstamp' => array
		(
			'sql'                     => "int(10) unsigned NOT NULL default '0'"
		),
		'token' => array
		(
			'label'                   => &$GLOBALS['TL_LANG']['tl_opt_in']['token'],
			'search'                  => true,
			'sql'                     => "varchar(40) NOT NULL default ''"
		),
		'createdOn' => array
		(
			'label'                   => &$GLOBALS['TL_LANG']['MSC']['createdOn'],
			'filter'                  => true,
			'sorting'                 => true,
			'flag'                    => 6,
			'eval'                    => array('rgxp'=>'datim'),
			'sql'                     => "int(10) unsigned NOT NULL default '0'"
		),
		'confirmedOn' => array
		(
			'label'                   => &$GLOBALS['TL_LANG']['tl_opt_in']['confirmedOn'],
			'filter'                  => true,
			'sorting'                 => true,
			'flag'                    => 6,
			'eval'                    => array('rgxp'=>'datim'),
			'sql'                     => "int(10) unsigned NOT NULL default '0'"
		),
		'removeOn' => array
		(
			'label'                   => &$GLOBALS['TL_LANG']['tl_opt_in']['removeOn'],
			'filter'                  => true,
			'sorting'                 => true,
			'flag'                    => 6,
			'eval'                    => array('rgxp'=>'datim'),
			'sql'                     => "int(10) unsigned NOT NULL default '0'"
		),
		'relatedTable' => array
		(
			'label'                   => &$GLOBALS['TL_LANG']['tl_opt_in']['relatedTable'],
			'filter'                  => true,
			'sorting'                 => true,
			'sql'                     => "varchar(128) NOT NULL default ''"
		),
		'relatedId' => array
		(
			'label'                   => &$GLOBALS['TL_LANG']['tl_opt_in']['relatedId'],
			'search'                  => true,
			'sql'                     => "int(10) unsigned NOT NULL default '0'"
		),
		'email' => array
		(
			'label'                   => &$GLOBALS['TL_LANG']['MSC']['emailAddress'],
			'search'                  => true,
			'sorting'                 => true,
			'eval'                    => array('rgxp'=>'email'),
			'sql'                     => "varchar(255) NOT NULL default ''"
		),
		'emailSubject' => array
		(
			'label'                   => &$GLOBALS['TL_LANG']['tl_opt_in']['emailSubject'],
			'search'                  => true,
			'sql'                     => "varchar(255) NOT NULL default ''"
		),
		'emailText' => array
		(
			'label'                   => &$GLOBALS['TL_LANG']['tl_opt_in']['emailText'],
			'search'                  => true,
			'sql'                     => "text NULL"
		)
	)
);
