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

$GLOBALS['TL_DCA']['tl_log'] = array
(
	// Config
	'config' => array
	(
		'dataContainer'               => DC_Table::class,
		'closed'                      => true,
		'notEditable'                 => true,
		'notCopyable'                 => true,
		'sql' => array
		(
			'keys' => array
			(
				'id' => 'primary'
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
			'panelLayout'             => 'filter;sort,search,limit',
			'defaultSearchField'      => 'text'
		),
		'label' => array
		(
			'fields'                  => array('tstamp', 'text'),
			'format'                  => '<span class="label-date">[%s]</span> %s',
			'label_callback'          => array('tl_log', 'colorize')
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
			'delete',
			'show'
		)
	),

	// Fields
	'fields' => array
	(
		'id' => array
		(
			'flag'                    => 12,
			'sql'                     => "int(10) unsigned NOT NULL auto_increment"
		),
		'tstamp' => array
		(
			'filter'                  => true,
			'sorting'                 => true,
			'flag'                    => DataContainer::SORT_DAY_DESC,
			'sql'                     => "int(10) unsigned NOT NULL default 0"
		),
		'source' => array
		(
			'filter'                  => true,
			'sorting'                 => true,
			'reference'               => &$GLOBALS['TL_LANG']['tl_log'],
			'sql'                     => "varchar(32) NOT NULL default ''"
		),
		'action' => array
		(
			'filter'                  => true,
			'sorting'                 => true,
			'sql'                     => "varchar(32) NOT NULL default ''"
		),
		'username' => array
		(
			'search'                  => true,
			'filter'                  => true,
			'sorting'                 => true,
			'sql'                     => "varchar(64) NOT NULL default ''"
		),
		'text' => array
		(
			'search'                  => true,
			'sql'                     => "text NULL"
		),
		'func' => array
		(
			'sorting'                 => true,
			'filter'                  => true,
			'search'                  => true,
			'sql'                     => "varchar(255) NOT NULL default ''"
		),
		'browser' => array
		(
			'sorting'                 => true,
			'search'                  => true,
			'sql'                     => "varchar(255) NOT NULL default ''"
		),
		'uri' => array
		(
			'sorting'                 => true,
			'search'                  => true,
			'sql'                     => "varchar(2048) NOT NULL default ''"
		),
		'page' => array
		(
			'sorting'                 => true,
			'search'                  => true,
			'relation'                => array('type' => 'hasOne', 'load' => 'lazy', 'table' => 'tl_page'),
			'sql'                     => "int(10) unsigned NOT NULL default '0'"
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
		switch ($row['action'])
		{
			case 'CONFIGURATION':
			case 'REPOSITORY':
				$label = preg_replace('@^(.*</span> )(.*)$@U', '$1 <span class="tl_blue">$2</span>', $label);
				break;

			case 'CRON':
				$label = preg_replace('@^(.*</span> )(.*)$@U', '$1 <span class="tl_green">$2</span>', $label);
				break;

			case 'ERROR':
				$label = preg_replace('@^(.*</span> )(.*)$@U', '$1 <span class="tl_red">$2</span>', $label);
				break;

			default:
				if (isset($GLOBALS['TL_HOOKS']['colorizeLogEntries']) && is_array($GLOBALS['TL_HOOKS']['colorizeLogEntries']))
				{
					foreach ($GLOBALS['TL_HOOKS']['colorizeLogEntries'] as $callback)
					{
						$this->import($callback[0]);
						$label = $this->{$callback[0]}->{$callback[1]}($row, $label);
					}
				}
				break;
		}

		return '<div class="ellipsis">' . $label . '</div>';
	}
}
