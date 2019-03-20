<?php

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

$GLOBALS['TL_DCA']['tl_opt_in'] = array
(

	// Config
	'config' => array
	(
		'dataContainer'               => 'Table',
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
			'mode'                    => 2,
			'fields'                  => array('createdOn DESC'),
			'panelLayout'             => 'filter;sort,search,limit'
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
				'label'               => &$GLOBALS['TL_LANG']['tl_opt_in']['resend'],
				'href'                => 'key=resend',
				'icon'                => 'resend.svg',
				'button_callback'     => array('tl_opt_in', 'resendButton')
			),
			'show' => array
			(
				'label'               => &$GLOBALS['TL_LANG']['tl_opt_in']['show'],
				'href'                => 'act=show',
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
		'tstamp' => array
		(
			'sql'                     => "int(10) unsigned NOT NULL default '0'"
		),
		'token' => array
		(
			'label'                   => &$GLOBALS['TL_LANG']['tl_opt_in']['token'],
			'search'                  => true,
			'sql'                     => "varchar(24) NOT NULL default ''"
		),
		'createdOn' => array
		(
			'label'                   => &$GLOBALS['TL_LANG']['MSC']['createdOn'],
			'filter'                  => true,
			'sorting'                 => true,
			'flag'                    => 6,
			'eval'                    => array('rgxp'=>'datim'),
			'sql'                     => "int(10) unsigned NOT NULL default '0'"
		),
		'confirmedOn' => array
		(
			'label'                   => &$GLOBALS['TL_LANG']['tl_opt_in']['confirmedOn'],
			'filter'                  => true,
			'sorting'                 => true,
			'flag'                    => 6,
			'eval'                    => array('rgxp'=>'datim'),
			'sql'                     => "int(10) unsigned NOT NULL default '0'"
		),
		'removeOn' => array
		(
			'label'                   => &$GLOBALS['TL_LANG']['tl_opt_in']['removeOn'],
			'filter'                  => true,
			'sorting'                 => true,
			'flag'                    => 6,
			'eval'                    => array('rgxp'=>'datim'),
			'sql'                     => "int(10) unsigned NOT NULL default '0'"
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
			'label'                   => &$GLOBALS['TL_LANG']['tl_opt_in']['emailSubject'],
			'search'                  => true,
			'sql'                     => "varchar(255) NOT NULL default ''"
		),
		'emailText' => array
		(
			'label'                   => &$GLOBALS['TL_LANG']['tl_opt_in']['emailText'],
			'search'                  => true,
			'sql'                     => "text NULL"
		)
	)
);

/**
 * Provide miscellaneous methods that are used by the data configuration array.
 *
 * @author Leo Feyer <https://github.com/leofeyer>
 */
class tl_opt_in extends Contao\Backend
{

	/**
	 * Show the related records
	 *
	 * @param array $data
	 * @param array $arrRow
	 */
	public function showRelatedRecords($data, $row)
	{
		Contao\System::loadLanguageFile('tl_opt_in_related');
		Contao\Controller::loadDataContainer('tl_opt_in_related');

		$objRelated = $this->Database->prepare("SELECT * FROM tl_opt_in_related WHERE pid=?")
									 ->execute($row['id']);

		while ($objRelated->next())
		{
			$arrAdd = array();
			$arrRow = $objRelated->row();

			foreach ($arrRow as $k=>$v)
			{
				$label = \is_array($GLOBALS['TL_DCA']['tl_opt_in_related']['fields'][$k]['label']) ? $GLOBALS['TL_DCA']['tl_opt_in_related']['fields'][$k]['label'][0] : $GLOBALS['TL_DCA']['tl_opt_in_related']['fields'][$k]['label'];

				$arrAdd[$label] = $v;
			}

			$data['tl_opt_in_related'][] = $arrAdd;
		}

		return $data;
	}

	/**
	 * Resend the double opt-in token
	 *
	 * @param Contao\DataContainer $dc
	 */
	public function resendToken(Contao\DataContainer $dc)
	{
		$model = Contao\OptInModel::findByPk($dc->id);

		Contao\System::getContainer()->get('contao.opt-in')->find($model->token)->send();
		Contao\Message::addConfirmation(sprintf($GLOBALS['TL_LANG']['MSC']['resendToken'], $model->email));
		Contao\Controller::redirect($this->getReferer());
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
		return (!$row['confirmedOn'] && $row['emailSubject'] && $row['emailText'] && $row['createdOn'] > strtotime('-24 hours')) ? '<a href="'.$this->addToUrl($href.'&amp;id='.$row['id']).'" title="'.Contao\StringUtil::specialchars($title).'"'.$attributes.'>'.Contao\Image::getHtml($icon, $label).'</a> ' : '';
	}
}
