<?php

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

use Contao\Backend;
use Contao\Controller;
use Contao\Database;
use Contao\DataContainer;
use Contao\DC_Table;
use Contao\Image;
use Contao\Message;
use Contao\OptInModel;
use Contao\StringUtil;
use Contao\System;

$GLOBALS['TL_DCA']['tl_opt_in'] = array
(
	// Config
	'config' => array
	(
		'dataContainer'               => DC_Table::class,
		'closed'                      => true,
		'notEditable'                 => true,
		'notCopyable'                 => true,
		'notDeletable'                => true,
		'onshow_callback' => array
		(
			array('tl_opt_in', 'showRelatedRecords')
		),
		'sql' => array
		(
			'keys' => array
			(
				'id' => 'primary',
				'tstamp' => 'index',
				'token' => 'unique',
				'removeOn' => 'index'
			)
		)
	),

	// List
	'list'  => array
	(
		'sorting' => array
		(
			'mode'                    => DataContainer::MODE_SORTABLE,
			'fields'                  => array('createdOn DESC'),
			'panelLayout'             => 'filter;sort,search,limit',
			'defaultSearchField'      => 'email'
		),
		'label' => array
		(
			'fields'                  => array('token', 'email', 'createdOn', 'confirmedOn', 'removeOn'),
			'showColumns'             => true,
		),
		'operations' => array
		(
			'resend' => array
			(
				'href'                => 'key=resend',
				'icon'                => 'resend.svg',
				'button_callback'     => array('tl_opt_in', 'resendButton')
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
		'tstamp' => array
		(
			'sql'                     => "int(10) unsigned NOT NULL default 0"
		),
		'token' => array
		(
			'search'                  => true,
			'sql'                     => "varchar(24) NOT NULL default ''"
		),
		'createdOn' => array
		(
			'label'                   => &$GLOBALS['TL_LANG']['MSC']['createdOn'],
			'filter'                  => true,
			'sorting'                 => true,
			'flag'                    => DataContainer::SORT_DAY_DESC,
			'eval'                    => array('rgxp'=>'datim'),
			'sql'                     => "int(10) unsigned NOT NULL default 0"
		),
		'confirmedOn' => array
		(
			'filter'                  => true,
			'sorting'                 => true,
			'flag'                    => DataContainer::SORT_DAY_DESC,
			'eval'                    => array('rgxp'=>'datim'),
			'sql'                     => "int(10) unsigned NOT NULL default 0"
		),
		'removeOn' => array
		(
			'filter'                  => true,
			'sorting'                 => true,
			'flag'                    => DataContainer::SORT_DAY_DESC,
			'eval'                    => array('rgxp'=>'datim'),
			'sql'                     => "int(10) unsigned NOT NULL default 0"
		),
		'invalidatedThrough' => array
		(
			'search'                  => true,
			'sql'                     => "varchar(24) NOT NULL default ''"
		),
		'email' => array
		(
			'label'                   => &$GLOBALS['TL_LANG']['MSC']['emailAddress'],
			'search'                  => true,
			'sorting'                 => true,
			'eval'                    => array('rgxp'=>'email'),
			'sql'                     => "varchar(255) NOT NULL default ''"
		),
		'emailSubject' => array
		(
			'search'                  => true,
			'sql'                     => "varchar(255) NOT NULL default ''"
		),
		'emailText' => array
		(
			'search'                  => true,
			'sql'                     => "text NULL"
		)
	)
);

/**
 * Provide miscellaneous methods that are used by the data configuration array.
 *
 * @internal
 */
class tl_opt_in extends Backend
{
	/**
	 * Show the related records
	 *
	 * @param array $data
	 * @param array $row
	 */
	public function showRelatedRecords($data, $row)
	{
		System::loadLanguageFile('tl_opt_in_related');
		Controller::loadDataContainer('tl_opt_in_related');

		$objRelated = Database::getInstance()
			->prepare("SELECT * FROM tl_opt_in_related WHERE pid=?")
			->execute($row['id']);

		while ($objRelated->next())
		{
			$arrAdd = array();
			$arrRow = $objRelated->row();

			foreach ($arrRow as $k=>$v)
			{
				$label = is_array($GLOBALS['TL_DCA']['tl_opt_in_related']['fields'][$k]['label'] ?? null) ? $GLOBALS['TL_DCA']['tl_opt_in_related']['fields'][$k]['label'][0] : ($GLOBALS['TL_DCA']['tl_opt_in_related']['fields'][$k]['label'] ?? null);

				$arrAdd[$label] = $v;
			}

			$data['tl_opt_in_related'][] = $arrAdd;
		}

		return $data;
	}

	/**
	 * Resend the double opt-in token
	 *
	 * @param DataContainer $dc
	 */
	public function resendToken(DataContainer $dc)
	{
		$model = OptInModel::findById($dc->id);

		System::getContainer()->get('contao.opt_in')->find($model->token)->send();
		Message::addConfirmation(sprintf($GLOBALS['TL_LANG']['MSC']['resendToken'], $model->email));
		Controller::redirect($this->getReferer());
	}

	/**
	 * Return the resend token button
	 *
	 * @param array  $row
	 * @param string $href
	 * @param string $label
	 * @param string $title
	 * @param string $icon
	 * @param string $attributes
	 *
	 * @return string
	 */
	public function resendButton($row, $href, $label, $title, $icon, $attributes)
	{
		return (!$row['confirmedOn'] &&!$row['invalidatedThrough'] && $row['emailSubject'] && $row['emailText'] && $row['createdOn'] > strtotime('-24 hours')) ? '<a href="' . $this->addToUrl($href . '&amp;id=' . $row['id']) . '" title="' . StringUtil::specialchars($title) . '"' . $attributes . '>' . Image::getHtml($icon, $label) . '</a> ' : '';
	}
}
