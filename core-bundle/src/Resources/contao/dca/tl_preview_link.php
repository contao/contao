<?php

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

use Contao\DataContainer;

$GLOBALS['TL_DCA']['tl_preview_link'] = array
(
	// Config
	'config' => array
	(
		'dataContainer'               => 'Table',
		'enableVersioning'            => true,
		'notCreatable'                => true,
		'notCopyable'                 => true,
		'sql' => array
		(
			'keys' => array
			(
				'id' => 'primary',
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
			'fields'                  => array('url', 'showUnpublished', 'expiresAt'),
			'showColumns'             => true,
		),
		'global_operations' => array
		(
			'all' => array
			(
				'href'                => 'act=select',
				'class'               => 'header_edit_all',
				'attributes'          => 'onclick="Backend.getScrollOffset()" accesskey="e"'
			)
		),
		'operations' => array
		(
			'edit' => array
			(
				'href'                => 'act=edit',
				'icon'                => 'edit.svg'
			),
			'delete' => array
			(
				'href'                => 'act=delete',
				'icon'                => 'delete.svg',
				'attributes'          => 'onclick="if(!confirm(\'' . ($GLOBALS['TL_LANG']['MSC']['deleteConfirm'] ?? null) . '\'))return false;Backend.getScrollOffset()"'
			),
			'toggle' => array
			(
				'icon'                => 'visible.svg',
				'attributes'          => 'onclick="Backend.getScrollOffset();return AjaxRequest.toggleVisibility(this,%s)"',
			),
			'show' => array
			(
				'href'                => 'act=show',
				'icon'                => 'show.svg'
			),
			'share' => array
			(
				'icon'                => 'share.svg'
			)
		)
	),

	// Palettes
	'palettes' => array
	(
		'default'                     => '{url_legend},url,showUnpublished;{expire_legend},createdAt,createdBy,expiresInDays,expiresAt;{publishing_legend},published',
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
		'createdAt' => array
		(
			'default'                 => time(),
			'flag'                    => DataContainer::SORT_DAY_DESC,
			'sorting'                 => true,
			'inputType'               => 'text',
			'eval'                    => array('rgxp'=>'datim', 'disabled'=>true, 'doNotCopy'=>true, 'tl_class'=>'w50'),
			'sql'                     => "int(10) unsigned NOT NULL default 0"
		),
		'createdBy' => array
		(
			'sorting'                 => true,
			'inputType'               => 'select',
			'foreignKey'              => 'tl_user.name',
			'eval'                    => array('disabled'=>true, 'doNotCopy'=>true, 'tl_class'=>'w50'),
			'sql'                     => "int(10) unsigned NOT NULL default 0",
			'relation'                => array('type'=>'hasOne', 'load'=>'lazy')
		),
		'url' => array
		(
			'search'                  => true,
			'inputType'               => 'text',
			'eval'                    => array('mandatory'=>true, 'disabled'=>true, 'rgxp'=>'url', 'decodeEntities'=>true, 'maxlength'=>2048),
			'sql'                     => "varchar(2048) NOT NULL default ''",
		),
		'showUnpublished' => array
		(
			'filter'                  => true,
			'inputType'               => 'checkbox',
			'eval'                    => array('tl_class'=>'clr'),
			'sql'                     => "char(1) NOT NULL default ''"
		),
		'expiresInDays' => array
		(
			'filter'                  => true,
			'inputType'               => 'select',
			'options'                 => ['1', '7', '30'],
			'reference'               => &$GLOBALS['TL_LANG']['tl_preview_link']['expire_options'],
			'eval'                    => array('mandatory'=>true, 'tl_class'=>'w50'),
			'sql'                     => "int(10) unsigned NOT NULL default 1"
		),
		'expiresAt' => array
		(
			'flag'                    => DataContainer::SORT_DAY_DESC,
			'sorting'                 => true,
			'inputType'               => 'text',
			'eval'                    => array('rgxp'=>'datim', 'disabled'=>true, 'doNotCopy'=>true, 'tl_class'=>'w50'),
			'sql'                     => "int(10) unsigned NOT NULL default 0"
		),
		'published' => array
		(
			'exclude'                 => true,
			'filter'                  => true,
			'inputType'               => 'checkbox',
			'sql'                     => "char(1) NOT NULL default ''"
		)
	)
);
