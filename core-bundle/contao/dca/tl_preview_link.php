<?php

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

use Contao\DataContainer;
use Contao\DC_Table;

$GLOBALS['TL_DCA']['tl_preview_link'] = array
(
	// Config
	'config' => array
	(
		'dataContainer'               => DC_Table::class,
		'enableVersioning'            => true,
		'notCreatable'                => true,
		'notCopyable'                 => true,
		'sql' => array
		(
			'keys' => array
			(
				'id' => 'primary',
				'tstamp' => 'index',
				'id,published,expiresAt' => 'index'
			)
		)
	),

	// List
	'list' => array
	(
		'sorting' => array
		(
			'mode'                    => DataContainer::MODE_SORTABLE,
			'fields'                  => array('createdAt'),
			'panelLayout'             => 'filter;sort,search,limit'
		),
		'label' => array
		(
			'fields'                  => array('url', 'createdAt', 'expiresAt'),
			'showColumns'             => true,
		),
		'operations' => array
		(
			'edit',
			'delete',
			'toggle',
			'show',
			'share' => array
			(
				'icon'                => 'share.svg'
			)
		)
	),

	// Palettes
	'palettes' => array
	(
		'default'                     => '{url_legend},url;{config_legend},showUnpublished,restrictToUrl,expiresAt,createdAt;{publishing_legend},published',
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
			'sql'                     => "int(10) unsigned NOT NULL default 0"
		),
		'url' => array
		(
			'exclude'                 => false,
			'search'                  => true,
			'inputType'               => 'text',
			'eval'                    => array('mandatory'=>true, 'readonly'=>true, 'rgxp'=>'url', 'decodeEntities'=>true, 'maxlength'=>2048),
			'sql'                     => "varchar(2048) NOT NULL default ''",
		),
		'showUnpublished' => array
		(
			'exclude'                 => false,
			'filter'                  => true,
			'inputType'               => 'checkbox',
			'eval'                    => array('tl_class'=>'w50'),
			'sql'                     => array('type' => 'boolean', 'default' => false)
		),
		'restrictToUrl' => array
		(
			'exclude'                 => false,
			'filter'                  => true,
			'inputType'               => 'checkbox',
			'eval'                    => array('tl_class'=>'w50'),
			'sql'                     => array('type' => 'boolean', 'default' => true)
		),
		'createdAt' => array
		(
			'exclude'                 => false,
			'default'                 => time(),
			'flag'                    => DataContainer::SORT_DAY_DESC,
			'sorting'                 => true,
			'inputType'               => 'text',
			'eval'                    => array('rgxp'=>'datim', 'readonly'=>true, 'doNotCopy'=>true, 'tl_class'=>'w50'),
			'sql'                     => "int(10) unsigned NOT NULL default 0"
		),
		'expiresAt' => array
		(
			'exclude'                 => false,
			'flag'                    => DataContainer::SORT_DAY_DESC,
			'sorting'                 => true,
			'inputType'               => 'text',
			'eval'                    => array('rgxp'=>'datim', 'mandatory'=>true, 'doNotCopy'=>true, 'datepicker'=>true, 'tl_class'=>'w50 wizard'),
			'sql'                     => "int(10) unsigned NOT NULL default 0"
		),
		'published' => array
		(
			'toggle'                  => true,
			'filter'                  => true,
			'inputType'               => 'checkbox',
			'sql'                     => array('type' => 'boolean', 'default' => false)
		),
		'createdBy' => array
		(
			'foreignKey'              => 'tl_user.name',
			'sql'                     => "int(10) unsigned NOT NULL default 0",
		)
	)
);
