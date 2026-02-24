<?php

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

use Contao\Backend;
use Contao\DataContainer;
use Contao\DC_Table;
use Contao\System;
use Doctrine\DBAL\Platforms\MySQLPlatform;

$GLOBALS['TL_DCA']['tl_log'] = array
(
	// Config
	'config' => array
	(
		'dataContainer'               => DC_Table::class,
		'closed'                      => true,
		'notEditable'                 => true,
		'notCopyable'                 => true,
		'backendSearchIgnore'         => true,
		'sql' => array
		(
			'keys' => array
			(
				'id' => 'primary',
				'tstamp' => 'index'
			)
		)
	),

	// List
	'list'  => array
	(
		'sorting' => array
		(
			'mode'                    => DataContainer::MODE_SORTABLE,
			'fields'                  => array('tstamp', 'id'),
			'panelLayout'             => 'search,filter,sort,limit',
			'defaultSearchField'      => 'text'
		),
		'label' => array
		(
			'fields'                  => array('tstamp', 'text'),
			'format'                  => '<span class="label-date">[%s]</span> %s',
			'label_callback'          => array('tl_log', 'colorize')
		)
	),

	// Fields
	'fields' => array
	(
		'id' => array
		(
			'flag'                    => 12,
			'sql'                     => array('type'=>'integer', 'unsigned'=>true, 'autoincrement'=>true)
		),
		'tstamp' => array
		(
			'filter'                  => true,
			'sorting'                 => true,
			'flag'                    => DataContainer::SORT_DAY_DESC,
			'sql'                     => array('type'=>'integer', 'unsigned'=>true, 'default'=>0)
		),
		'source' => array
		(
			'filter'                  => true,
			'sorting'                 => true,
			'reference'               => &$GLOBALS['TL_LANG']['tl_log'],
			'sql'                     => array('type'=>'string', 'length'=>32, 'default'=>'')
		),
		'action' => array
		(
			'filter'                  => true,
			'sorting'                 => true,
			'sql'                     => array('type'=>'string', 'length'=>32, 'default'=>'')
		),
		'username' => array
		(
			'search'                  => true,
			'filter'                  => true,
			'sorting'                 => true,
			'sql'                     => array('type'=>'string', 'length'=>64, 'default'=>'')
		),
		'text' => array
		(
			'search'                  => true,
			'sql'                     => array('type'=>'text', 'length'=>MySQLPlatform::LENGTH_LIMIT_TEXT, 'notnull'=>false)
		),
		'func' => array
		(
			'sorting'                 => true,
			'filter'                  => true,
			'search'                  => true,
			'sql'                     => array('type'=>'string', 'length'=>255, 'default'=>'')
		),
		'browser' => array
		(
			'sorting'                 => true,
			'search'                  => true,
			'sql'                     => array('type'=>'string', 'length'=>255, 'default'=>'')
		),
		'uri' => array
		(
			'sorting'                 => true,
			'search'                  => true,
			'sql'                     => array('type'=>'string', 'length'=>2048, 'default'=>'')
		),
		'page' => array
		(
			'sorting'                 => true,
			'search'                  => true,
			'sql'                     =>array('type' => 'integer', 'unsigned' => true, 'default' => 0)
		)
	)
);

/**
 * Provide miscellaneous methods that are used by the data configuration array.
 *
 * @internal
 */
class tl_log extends Backend
{
	/**
	 * Colorize the log entries depending on their category
	 *
	 * @param array  $row
	 * @param string $label
	 *
	 * @return string
	 */
	public function colorize($row, $label)
	{
		$class = 'ellipsis';

		switch ($row['action'])
		{
			case 'CONFIGURATION':
			case 'REPOSITORY':
				$class .= ' tl_blue';
				break;

			case 'CRON':
				$class .= ' tl_green';
				break;

			case 'ERROR':
				$class .= ' tl_red';
				break;

			default:
				if (isset($GLOBALS['TL_HOOKS']['colorizeLogEntries']) && is_array($GLOBALS['TL_HOOKS']['colorizeLogEntries']))
				{
					foreach ($GLOBALS['TL_HOOKS']['colorizeLogEntries'] as $callback)
					{
						$label = System::importStatic($callback[0])->{$callback[1]}($row, $label, $class);
					}
				}
				break;
		}

		return '<div class="' . $class . '">' . $label . '</div>';
	}
}
