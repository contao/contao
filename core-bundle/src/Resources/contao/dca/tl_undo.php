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
use Contao\CoreBundle\Exception\AccessDeniedException;
use Contao\Input;
use Contao\StringUtil;
use Contao\System;

$GLOBALS['TL_DCA']['tl_undo'] = array
(
	// Config
	'config' => array
	(
		'dataContainer'               => 'Table',
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
			array('tl_undo', 'checkPermission')
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
			'mode'                    => 2,
			'fields'                  => array('tstamp DESC'),
			'panelLayout'             => 'filter;sort,search,limit'
		),
		'label' => array
		(
			'fields'                  => array('tstamp', 'pid', 'fromTable', 'query'),
			'showColumns'             => true,
			'label_callback'          => array('tl_undo', 'labelCallback')
		),
		'operations' => array
		(
			'undo' => array
			(
				'href'                => '&amp;act=undo',
				'icon'                => 'undo.svg'
			),
			'show' => array
			(
				'href'                => '&amp;act=show',
				'icon'                => 'show.svg'
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
		'pid' => array
		(
			'sorting'                 => true,
			'filter'                  => true,
			'foreignKey'              => 'tl_user.name',
			'sql'                     => "int(10) unsigned NOT NULL default 0",
			'relation'                => array('type'=>'belongsTo', 'load'=>'lazy')
		),
		'tstamp' => array
		(
			'sorting'                 => true,
			'flag'                    => 6,
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
			'sql'                     => "mediumblob NULL"
		)
	)
);

/**
 * Provide miscellaneous methods that are used by the data configuration array.
 *
 * @author Leo Feyer <https://github.com/leofeyer>
 */
class tl_undo extends Backend
{
	/**
	 * Import the back end user object
	 */
	public function __construct()
	{
		parent::__construct();
		$this->import(BackendUser::class, 'User');
	}

	/**
	 * Check permissions to use table tl_undo
	 *
	 * @throws AccessDeniedException
	 */
	public function checkPermission()
	{
		if ($this->User->isAdmin)
		{
			return;
		}

		// Show only own undo steps
		$objSteps = $this->Database->prepare("SELECT id FROM tl_undo WHERE pid=?")
								   ->execute($this->User->id);

		// Restrict the list
		$GLOBALS['TL_DCA']['tl_undo']['list']['sorting']['root'] = $objSteps->numRows ? $objSteps->fetchEach('id') : array(0);

		// Redirect if there is an error
		if (Input::get('act') && !in_array(Input::get('id'), $GLOBALS['TL_DCA']['tl_undo']['list']['sorting']['root'] ?? array()))
		{
			throw new AccessDeniedException('Not enough permissions to ' . Input::get('act') . ' undo step ID ' . Input::get('id') . '.');
		}
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

	public function labelCallback($row, $label, \DataContainer $dc, $args)
	{
        $table = $args[2];
	    \Contao\System::loadLanguageFile($table);

	    // Date
        $args[0] = sprintf('<span style="color:#999;padding-right:3px">%s</span>', $args[0]);

        // Username
        $user = \Contao\UserModel::findById($args[1]);

        if ($user !== null) {
            $args[1] = $user->username;
        }

        // fromTable
        $args[2] = '<strong>' . ($GLOBALS['TL_LANG'][$table][$table]) ?: $args[2] . '</strong>';

        // Description
        $args[3] = sprintf('<span title="%s">%s</span>', $args[3], \Contao\StringUtil::substr($args[3], 90));

        return $args;
	}
}
