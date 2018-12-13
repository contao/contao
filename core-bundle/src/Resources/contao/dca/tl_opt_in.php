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
		'sql' => array
		(
			'keys' => array
			(
				'id' => 'primary',
				'token' => 'unique'
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
			'fields'                  => array('token', 'email', 'createdOn', 'confirmedOn', 'relatedTable'),
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
		'relatedTable' => array
		(
			'label'                   => &$GLOBALS['TL_LANG']['tl_opt_in']['relatedTable'],
			'filter'                  => true,
			'sorting'                 => true,
			'sql'                     => "varchar(128) NOT NULL default ''"
		),
		'relatedId' => array
		(
			'label'                   => &$GLOBALS['TL_LANG']['tl_opt_in']['relatedId'],
			'search'                  => true,
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
class tl_opt_in extends Backend
{

	/**
	 * Resend the double opt-in token
	 *
	 * @param DataContainer $dc
	 */
	public function resendToken(DataContainer $dc)
	{
		$optIn = System::getContainer()->get('contao.opt-in');
		$token = $optIn->find(OptInModel::findByPk($dc->id)->token);

		$optIn->send($token);

		Message::addConfirmation(sprintf($GLOBALS['TL_LANG']['MSC']['resendToken'], $token->email));
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
		return !$row['confirmedOn'] ? '<a href="'.$this->addToUrl($href.'&amp;id='.$row['id']).'" title="'.StringUtil::specialchars($title).'"'.$attributes.'>'.Image::getHtml($icon, $label).'</a> ' : '';
	}
}
