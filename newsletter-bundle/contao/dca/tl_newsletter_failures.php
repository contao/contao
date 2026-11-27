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
use Contao\StringUtil;

$GLOBALS['TL_DCA']['tl_newsletter_failures'] = array
(
	// Config
	'config' => array
	(
		'dataContainer'               => DC_Table::class,
		'ptable'                      => 'tl_newsletter',
		'closed'                      => true,
		'notEditable'                 => true,
		'notCopyable'                 => true,
		'notDeletable'                => true,
		'notSortable'                 => true,
		'doNotCopyRecords'            => true,
		'onshow_callback' => array
		(
			static function (array $data) {
				foreach ($data['tl_newsletter_failures'] as $key=>$row)
				{
					$data['tl_newsletter_failures'][$key] = array_map(array(StringUtil::class, 'specialchars'), $row);
				}

				return $data;
			}
		),
		'sql' => array
		(
			'keys' => array
			(
				'id' => 'primary',
				'pid,email' => 'unique'
			)
		)
	),

	// List
	'list' => array
	(
		'sorting' => array
		(
			'mode'                    => DataContainer::MODE_SORTABLE,
			'fields'                  => array('tstamp DESC'),
			'panelLayout'             => 'search,limit',
			'defaultSearchField'      => 'email'
		),
		'label' => array
		(
			'fields'                  => array('tstamp', 'email', 'error'),
			'showColumns'             => true,
			'label_callback'          => static function (array $row, string $label, DataContainer $dc, array $args) {
				return array_map(array(StringUtil::class, 'specialchars'), $args);
			}
		),
		'operations' => array
		(
			'show'
		)
	),

	// Fields
	'fields' => array
	(
		'id' => array
		(
			'sql'                     => "int(10) unsigned NOT NULL auto_increment"
		),
		'pid' => array
		(
			'foreignKey'              => 'tl_newsletter.subject',
			'sql'                     => "int(10) unsigned NOT NULL default 0",
			'relation'                => array('type'=>'belongsTo', 'load'=>'lazy')
		),
		'tstamp' => array
		(
			'flag'                    => DataContainer::SORT_DAY_DESC,
			'eval'                    => array('rgxp'=>'datim'),
			'sql'                     => "int(10) unsigned NOT NULL default 0"
		),
		'email' => array
		(
			'search'                  => true,
			'sql'                     => "varchar(255) NOT NULL default ''"
		),
		'error' => array
		(
			'search'                  => true,
			'sql'                     => "text NULL"
		)
	)
);
