<?php

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

use Contao\Backend;
use Contao\BackendUser;
use Contao\Controller;
use Contao\DataContainer;
use Contao\DC_Table;
use Contao\StringUtil;
use Contao\System;

$GLOBALS['TL_DCA']['tl_undo'] = array
(
	// Config
	'config' => array
	(
		'dataContainer'               => DC_Table::class,
		'closed'                      => true,
		'notEditable'                 => true,
		'sql' => array
		(
			'keys' => array
			(
				'id' => 'primary'
			)
		),
		'onload_callback' => array
		(
			array('tl_undo', 'adjustDca')
		),
		'onshow_callback' => array
		(
			array('tl_undo', 'showDeletedRecords')
		)
	),

	// List
	'list'  => array
	(
		'sorting' => array
		(
			'mode'                    => DataContainer::MODE_SORTABLE,
			'fields'                  => array('tstamp DESC'),
			'panelLayout'             => 'filter;sort,search,limit',
			'defaultSearchField'      => 'data'
		),
		'label' => array
		(
			'fields'                  => array('tstamp', 'pid', 'fromTable', 'query'),
		),
		'operations' => array
		(
			'undo' => array
			(
				'href'                => '&amp;act=undo',
				'icon'                => 'undo.svg'
			),
			'jumpToParent' => array
			(
				'icon'                => 'parent.svg',
			),
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
			'sorting'                 => true,
			'filter'                  => true,
			'foreignKey'              => 'tl_user.username',
			'sql'                     => "int(10) unsigned NOT NULL default 0",
			'relation'                => array('type'=>'belongsTo', 'load'=>'lazy')
		),
		'tstamp' => array
		(
			'sorting'                 => true,
			'flag'                    => DataContainer::SORT_DAY_DESC,
			'sql'                     => "int(10) unsigned NOT NULL default 0"
		),
		'fromTable' => array
		(
			'sorting'                 => true,
			'filter'                  => true,
			'sql'                     => "varchar(255) NOT NULL default ''"
		),
		'query' => array
		(
			'sql'                     => "text NULL"
		),
		'affectedRows' => array
		(
			'sql'                     => "smallint(5) unsigned NOT NULL default 0"
		),
		'data' => array
		(
			'search'                  => true,
			'eval'                    => array('doNotShow'=>true),
			'sql'                     => "mediumblob NULL"
		),
		'preview' => array
		(
			'eval'                    => array('doNotShow'=>true),
			'sql'                     => "mediumblob NULL"
		)
	)
);

/**
 * Provide miscellaneous methods that are used by the data configuration array.
 *
 * @internal
 */
class tl_undo extends Backend
{
	/**
	 * Set the user filter.
	 */
	public function adjustDca()
	{
		$user = BackendUser::getInstance();

		if ($user->isAdmin)
		{
			return;
		}

		$GLOBALS['TL_DCA']['tl_undo']['list']['sorting']['filter'][] = array('pid=?', $user->id);
	}

	/**
	 * Show the deleted records
	 *
	 * @param array $data
	 * @param array $row
	 */
	public function showDeletedRecords($data, $row)
	{
		$arrData = StringUtil::deserialize($row['data']);

		foreach ($arrData as $strTable=>$arrTableData)
		{
			System::loadLanguageFile($strTable);
			Controller::loadDataContainer($strTable);

			foreach ($arrTableData as $arrRow)
			{
				// Unset fields that are not to be displayed
				foreach (($GLOBALS['TL_DCA'][$strTable]['fields'] ?? array()) as $key=>$config)
				{
					if ($config['eval']['doNotShow'] ?? false)
					{
						unset($arrRow[$key]);
					}
				}

				$arrBuffer = array();

				foreach ($arrRow as $i=>$v)
				{
					if (is_array($array = StringUtil::deserialize($v)))
					{
						if (isset($array['value'], $array['unit']))
						{
							$v = trim($array['value'] . ', ' . $array['unit']);
						}
						else
						{
							$v = implode(', ', $array);
						}
					}

					$label = null;

					// Get the field label
					if (isset($GLOBALS['TL_DCA'][$strTable]['fields'][$i]['label']))
					{
						$label = is_array($GLOBALS['TL_DCA'][$strTable]['fields'][$i]['label']) ? $GLOBALS['TL_DCA'][$strTable]['fields'][$i]['label'][0] : $GLOBALS['TL_DCA'][$strTable]['fields'][$i]['label'];
					}
					elseif (isset($GLOBALS['TL_LANG']['MSC'][$i]))
					{
						$label = is_array($GLOBALS['TL_LANG']['MSC'][$i]) ? $GLOBALS['TL_LANG']['MSC'][$i][0] : $GLOBALS['TL_LANG']['MSC'][$i];
					}

					if (!$label)
					{
						$label = '-';
					}

					$label .= ' <small>' . $i . '</small>';

					$arrBuffer[$label] = $v;
				}

				$data[$strTable][] = $arrBuffer;
			}
		}

		return $data;
	}
}
