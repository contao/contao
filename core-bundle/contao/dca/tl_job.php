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
		'closed'                      => true,
		'notEditable'                 => true,
		'notCopyable'                 => true,
		'notDeletable'                => true,
		'backendSearchIgnore'         => true,
		'sql' => array
		(
			'keys' => array
			(
				'id' => 'primary',
				'uuid' => 'index',
				'pid' => 'index',
				'tstamp' => 'index',
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
			// TODO: Filter just for my jobs and system jobs
		),
		'label' => array
		(
			'fields'                  => array('tstamp', 'status', 'owner'),
			'showColumns'             => true,
		),
		'operations' => array
		(
			'show' => false,
		)
	),

	// Fields
	'fields' => array
	(
		'id' => array
		(
			'sql' => array('type' => 'integer', 'unsigned' => true, 'autoincrement' => true),
		),
		'uuid' => array
		(
			'sql' => array('type' => 'string', 'length' => 36, 'default' => ''),
		),
		'pid' => array
		(
			'sql' => array('type' => 'integer', 'unsigned' => true, 'default' => 0),
		),
		'tstamp' => array
		(
			'flag' => DataContainer::SORT_DAY_DESC,
			'sql' => array('type' => 'integer', 'unsigned' => true, 'default' => 0),
		),
		'owner' => array
		(
			'sql' => array('type' => 'string', 'length' => 255, 'notnull' => true),
		),
		'status' => array
		(
			'inputType' => 'select',
			'enum' => Status::class,
			'sql' => array('type' => 'string', 'length' => 255, 'notnull' => true),
		),
		'public' => array
		(
			'sql' => array('type' => 'boolean', 'default' => false),
		),
		'jobData' => array
		(
			'sql' => array('type' => 'text', 'notnull' => false),
		),
	)
);
