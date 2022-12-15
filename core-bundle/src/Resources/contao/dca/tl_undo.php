<?php

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

$GLOBALS['TL_DCA']['tl_undo'] = array
(
	// Config
	'config' => array
	(
		'dataContainer'               => Contao\DC_Table::class,
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
			'fields'                  => array('tstamp'),
			'panelLayout'             => 'sort,search,limit'
		),
		'label' => array
		(
			'fields'                  => array('tstamp', 'query'),
			'format'                  => '<span style="color:#999;padding-right:3px">[%s]</span>%s',
			'label_callback'          => array('tl_undo', 'ellipsis')
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
		)
	)
);

/**
 * Provide miscellaneous methods that are used by the data configuration array.
 *
 * @author Leo Feyer <https://github.com/leofeyer>
 */
class tl_undo extends Contao\Backend
{
	/**
	 * Import the back end user object
	 */
	public function __construct()
	{
		parent::__construct();
		$this->import('Contao\BackendUser', 'User');
	}

	/**
	 * Check permissions to use table tl_undo
	 *
	 * @throws Contao\CoreBundle\Exception\AccessDeniedException
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
		if (Contao\Input::get('act') && !in_array(Contao\Input::get('id'), $GLOBALS['TL_DCA']['tl_undo']['list']['sorting']['root']))
		{
			throw new Contao\CoreBundle\Exception\AccessDeniedException('Not enough permissions to ' . Contao\Input::get('act') . ' undo step ID ' . Contao\Input::get('id') . '.');
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
		$arrData = Contao\StringUtil::deserialize($row['data']);

		foreach ($arrData as $strTable=>$arrTableData)
		{
			Contao\System::loadLanguageFile($strTable);
			Contao\Controller::loadDataContainer($strTable);

			foreach ($arrTableData as $arrRow)
			{
				$arrBuffer = array();

				foreach ($arrRow as $i=>$v)
				{
					if (is_array($array = Contao\StringUtil::deserialize($v)))
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

					// Get the field label
					if (isset($GLOBALS['TL_DCA'][$strTable]['fields'][$i]['label']))
					{
						$label = is_array($GLOBALS['TL_DCA'][$strTable]['fields'][$i]['label']) ? $GLOBALS['TL_DCA'][$strTable]['fields'][$i]['label'][0] : $GLOBALS['TL_DCA'][$strTable]['fields'][$i]['label'];
					}
					else
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

	/**
	 * Add the surrounding ellipsis layer
	 *
	 * @param array  $row
	 * @param string $label
	 *
	 * @return string
	 */
	public function ellipsis($row, $label)
	{
		return '<div class="ellipsis">' . $label . '</div>';
	}
}
