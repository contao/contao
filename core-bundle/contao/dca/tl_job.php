<?php

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

use Contao\CoreBundle\Job\Status;
use Contao\DataContainer;
use Contao\DC_Table;

$GLOBALS['TL_DCA']['tl_job'] = array
(
	// Config
	'config' => array
	(
		'dataContainer'               => DC_Table::class,
		'ptable'                      => 'tl_job',
		'ctable'                      => array('tl_job'),
		'doNotDeleteRecords'          => true,
		'closed'                      => true,
		'notEditable'                 => true,
		'notCopyable'                 => true,
		'notDeletable'                => true,
		'permissions'                 => array(),
		'backendSearchIgnore'         => true,
		'sql' => array
		(
			'keys' => array
			(
				'id' => 'primary',
				'uuid' => 'index',
				'pid' => 'index',
				'tstamp' => 'index',
				'type' => 'index',
				'owner' => 'index',
				'status' => 'index',
				'public' => 'index',
			)
		)
	),

	// List
	'list'  => array
	(
		'sorting' => array
		(
			'mode'                    => DataContainer::MODE_SORTED,
			'fields'                  => array('tstamp'),
			'panelLayout'             => 'filter;limit',
			'headerFields'            => array('tstamp', 'type', 'uuid', 'status', 'owner'),
		),
		'label' => array
		(
			'fields'                  => array('tstamp', 'type', 'progress', 'status', 'owner'),
			'showColumns'             => true,
		),
		'operations' => array
		(
			'children',
			'show' => false,
		)
	),

	// Fields
	'fields' => array
	(
		'id' => array
		(
			'sql'                     => array('type' => 'integer', 'unsigned' => true, 'autoincrement' => true),
		),
		'uuid' => array
		(
			'sql'                     => array('type' => 'string', 'length' => 36, 'default' => ''),
		),
		'pid' => array
		(
			'sql'                     => array('type' => 'integer', 'unsigned' => true, 'default' => 0),
		),
		'tstamp' => array
		(
			'label'                   => &$GLOBALS['TL_LANG']['jobs']['tstamp'],
			'flag'                    => DataContainer::SORT_DAY_DESC,
			'sql'                     => array('type' => 'integer', 'unsigned' => true, 'default' => 0),
		),
		'type' => array
		(
			'label'                   => &$GLOBALS['TL_LANG']['jobs']['type'],
			'inputType'               => 'select',
			'reference'               => &$GLOBALS['TL_LANG']['jobs']['typeLabel'],
			'sql'                     => array('type' => 'string', 'length' => 255, 'notnull' => true),
		),
		'owner' => array
		(
			'label'                   => &$GLOBALS['TL_LANG']['jobs']['owner'],
			'foreignKey'              => 'tl_user.name',
			'sql'                     => array('type' => 'integer', 'unsigned' => true, 'default' => 0),
		),
		'progress' => array
		(
			'label'                   => &$GLOBALS['TL_LANG']['jobs']['progress'],
		),
		'status' => array
		(
			'label'                   => &$GLOBALS['TL_LANG']['jobs']['status'],
			'inputType'               => 'select',
			'enum'                    => Status::class,
			'sql'                     => array('type' => 'string', 'length' => 255, 'notnull' => true),
		),
		'public' => array
		(
			'sql'                     => array('type' => 'boolean', 'default' => false),
		),
		'jobData' => array
		(
			'sql'                     => array('type' => 'text', 'notnull' => false),
		),
	)
);
