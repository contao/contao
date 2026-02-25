<?php

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

use Contao\Backend;
use Contao\Config;
use Contao\CoreBundle\EventListener\Widget\HttpUrlListener;
use Contao\Database;
use Contao\DataContainer;
use Contao\DC_Table;
use Contao\FrontendUser;
use Contao\Image;
use Contao\MemberModel;
use Contao\System;
use Doctrine\DBAL\Platforms\AbstractMySQLPlatform;

$GLOBALS['TL_DCA']['tl_member'] = array
(
	// Config
	'config' => array
	(
		'dataContainer'               => DC_Table::class,
		'enableVersioning'            => true,
		'onsubmit_callback' => array
		(
			array('tl_member', 'storeDateAdded')
		),
		'sql' => array
		(
			'keys' => array
			(
				'id' => 'primary',
				'tstamp' => 'index',
				'username' => 'unique',
				'email' => 'index',
				'login,disable,start,stop' => 'index'
			)
		)
	),

	// List
	'list' => array
	(
		'sorting' => array
		(
			'mode'                    => DataContainer::MODE_SORTABLE,
			'fields'                  => array('dateAdded'),
			'panelLayout'             => 'search,filter,sort,limit',
			'defaultSearchField'      => 'lastname'
		),
		'label' => array
		(
			'fields'                  => array('', 'firstname', 'lastname', 'username', 'dateAdded'),
			'showColumns'             => true,
			'label_callback'          => array('tl_member', 'addIcon')
		),
		'operations' => array
		(
			'-',
			'su' => array
			(
				'href'                => 'key=su',
				'icon'                => 'su.svg',
				'primary'             => true
			)
		)
	),

	// Palettes
	'palettes' => array
	(
		'__selector__'                => array('login', 'assignDir'),
		'default'                     => '{personal_legend},firstname,lastname,language,dateOfBirth,gender;{address_legend:hide},company,street,postal,city,state,country;{contact_legend},phone,mobile,email,website,fax;{groups_legend},groups;{login_legend},login;{homedir_legend:hide},assignDir;{account_legend},disable,start,stop',
	),

	// Sub-palettes
	'subpalettes' => array
	(
		'login'                       => 'username,password',
		'assignDir'                   => 'homeDir'
	),

	// Fields
	'fields' => array
	(
		'id' => array
		(
			'sql'                     => array('type'=>'integer', 'unsigned'=>true, 'autoincrement'=>true),
			'search'                  => true
		),
		'tstamp' => array
		(
			'sql'                     => array('type'=>'integer', 'unsigned'=>true, 'default'=>0)
		),
		'firstname' => array
		(
			'search'                  => true,
			'sorting'                 => true,
			'flag'                    => DataContainer::SORT_INITIAL_LETTER_BOTH,
			'inputType'               => 'text',
			'eval'                    => array('mandatory'=>true, 'maxlength'=>255, 'feEditable'=>true, 'feGroup'=>'personal', 'tl_class'=>'w50'),
			'sql'                     => array('type'=>'string', 'length'=>255, 'default'=>'')
		),
		'lastname' => array
		(
			'search'                  => true,
			'sorting'                 => true,
			'flag'                    => DataContainer::SORT_INITIAL_LETTER_BOTH,
			'inputType'               => 'text',
			'eval'                    => array('mandatory'=>true, 'maxlength'=>255, 'feEditable'=>true, 'feGroup'=>'personal', 'tl_class'=>'w50'),
			'sql'                     => array('type'=>'string', 'length'=>255, 'default'=>'')
		),
		'dateOfBirth' => array
		(
			'inputType'               => 'text',
			'eval'                    => array('rgxp'=>'date', 'datepicker'=>true, 'feEditable'=>true, 'feGroup'=>'personal', 'tl_class'=>'w25 wizard'),
			'sql'                     => array('type'=>'string', 'length'=>11, 'default'=>'')
		),
		'gender' => array
		(
			'inputType'               => 'select',
			'options'                 => array('male', 'female', 'other'),
			'reference'               => &$GLOBALS['TL_LANG']['MSC'],
			'eval'                    => array('includeBlankOption'=>true, 'feEditable'=>true, 'feGroup'=>'personal', 'tl_class'=>'w25'),
			'sql'                     => array('type'=>'string', 'length'=>32, 'default'=>'')
		),
		'company' => array
		(
			'search'                  => true,
			'sorting'                 => true,
			'flag'                    => DataContainer::SORT_INITIAL_LETTER_ASC,
			'inputType'               => 'text',
			'eval'                    => array('maxlength'=>255, 'feEditable'=>true, 'feGroup'=>'address', 'tl_class'=>'w50'),
			'sql'                     => array('type'=>'string', 'length'=>255, 'default'=>'')
		),
		'street' => array
		(
			'search'                  => true,
			'inputType'               => 'text',
			'eval'                    => array('maxlength'=>255, 'feEditable'=>true, 'feGroup'=>'address', 'tl_class'=>'w50'),
			'sql'                     => array('type'=>'string', 'length'=>255, 'default'=>'')
		),
		'postal' => array
		(
			'search'                  => true,
			'inputType'               => 'text',
			'eval'                    => array('maxlength'=>32, 'feEditable'=>true, 'feGroup'=>'address', 'tl_class'=>'w50'),
			'sql'                     => array('type'=>'string', 'length'=>32, 'default'=>'')
		),
		'city' => array
		(
			'search'                  => true,
			'sorting'                 => true,
			'inputType'               => 'text',
			'eval'                    => array('maxlength'=>255, 'feEditable'=>true, 'feGroup'=>'address', 'tl_class'=>'w50'),
			'sql'                     => array('type'=>'string', 'length'=>255, 'default'=>'')
		),
		'state' => array
		(
			'sorting'                 => true,
			'inputType'               => 'text',
			'eval'                    => array('maxlength'=>64, 'feEditable'=>true, 'feGroup'=>'address', 'tl_class'=>'w50'),
			'sql'                     => array('type'=>'string', 'length'=>64, 'default'=>'')
		),
		'country' => array
		(
			'filter'                  => true,
			'sorting'                 => true,
			'inputType'               => 'select',
			'eval'                    => array('includeBlankOption'=>true, 'chosen'=>true, 'feEditable'=>true, 'feGroup'=>'address', 'tl_class'=>'w50'),
			'options_callback'        => static fn () => System::getContainer()->get('contao.intl.countries')->getCountries(),
			'sql'                     => array('type'=>'string', 'length'=>6, 'default'=>'')
		),
		'phone' => array
		(
			'search'                  => true,
			'inputType'               => 'text',
			'eval'                    => array('maxlength'=>64, 'rgxp'=>'phone', 'decodeEntities'=>true, 'feEditable'=>true, 'feGroup'=>'contact', 'tl_class'=>'w50'),
			'sql'                     => array('type'=>'string', 'length'=>64, 'default'=>'')
		),
		'mobile' => array
		(
			'search'                  => true,
			'inputType'               => 'text',
			'eval'                    => array('maxlength'=>64, 'rgxp'=>'phone', 'decodeEntities'=>true, 'feEditable'=>true, 'feGroup'=>'contact', 'tl_class'=>'w50'),
			'sql'                     => array('type'=>'string', 'length'=>64, 'default'=>'')
		),
		'fax' => array
		(
			'search'                  => true,
			'inputType'               => 'text',
			'eval'                    => array('maxlength'=>64, 'rgxp'=>'phone', 'decodeEntities'=>true, 'feEditable'=>true, 'feGroup'=>'contact', 'tl_class'=>'w50'),
			'sql'                     => array('type'=>'string', 'length'=>64, 'default'=>'')
		),
		'email' => array
		(
			'search'                  => true,
			'inputType'               => 'text',
			'eval'                    => array('mandatory'=>true, 'maxlength'=>255, 'rgxp'=>'email', 'unique'=>true, 'decodeEntities'=>true, 'feEditable'=>true, 'feGroup'=>'contact', 'tl_class'=>'w50'),
			'sql'                     => array('type'=>'string', 'length'=>255, 'default'=>'')
		),
		'website' => array
		(
			'search'                  => true,
			'inputType'               => 'text',
			'eval'                    => array('rgxp'=>HttpUrlListener::RGXP_NAME, 'maxlength'=>255, 'feEditable'=>true, 'feGroup'=>'contact', 'tl_class'=>'w50'),
			'sql'                     => array('type'=>'string', 'length'=>255, 'default'=>'')
		),
		'language' => array
		(
			'filter'                  => true,
			'inputType'               => 'select',
			'eval'                    => array('includeBlankOption'=>true, 'chosen'=>true, 'feEditable'=>true, 'feGroup'=>'personal', 'tl_class'=>'w50'),
			'options_callback'        => static fn () => System::getContainer()->get('contao.intl.locales')->getLocales(),
			'sql'                     => array('type'=>'string', 'length'=>64, 'default'=>'')
		),
		'groups' => array
		(
			'filter'                  => true,
			'inputType'               => 'checkboxWizard',
			'foreignKey'              => 'tl_member_group.name',
			'eval'                    => array('multiple'=>true, 'feEditable'=>true, 'feGroup'=>'login'),
			'sql'                     => array('type'=>'blob', 'length'=>AbstractMySQLPlatform::LENGTH_LIMIT_BLOB, 'notnull'=>false),
			'relation'                => array('type'=>'belongsToMany', 'load'=>'lazy')
		),
		'login' => array
		(
			'filter'                  => true,
			'inputType'               => 'checkbox',
			'eval'                    => array('submitOnChange'=>true),
			'sql'                     => array('type'=>'boolean', 'default'=>false)
		),
		'username' => array
		(
			'search'                  => true,
			'sorting'                 => true,
			'flag'                    => DataContainer::SORT_INITIAL_LETTER_ASC,
			'inputType'               => 'text',
			'eval'                    => array('mandatory'=>true, 'unique'=>true, 'rgxp'=>'extnd', 'nospace'=>true, 'maxlength'=>64, 'feEditable'=>true, 'feGroup'=>'login', 'tl_class'=>'w50', 'autocapitalize'=>'off', 'autocomplete'=>'username'),
			'sql'                     => array('type'=>'string', 'length'=>64, 'notnull'=>false, 'platformOptions'=>array('collation'=>'utf8mb4_bin'))
		),
		'password' => array
		(
			'label'                   => &$GLOBALS['TL_LANG']['MSC']['password'],
			'inputType'               => 'password',
			'eval'                    => array('mandatory'=>true, 'preserveTags'=>true, 'minlength'=>Config::get('minPasswordLength'), 'feEditable'=>true, 'feGroup'=>'login', 'tl_class'=>'w50'),
			'save_callback' => array
			(
				array('tl_member', 'setNewPassword')
			),
			'sql'                     => array('type'=>'string', 'length'=>255, 'default'=>'')
		),
		'assignDir' => array
		(
			'inputType'               => 'checkbox',
			'eval'                    => array('submitOnChange'=>true),
			'sql'                     => array('type'=>'boolean', 'default'=>false)
		),
		'homeDir' => array
		(
			'inputType'               => 'fileTree',
			'eval'                    => array('fieldType'=>'radio', 'tl_class'=>'clr'),
			'sql'                     => array('type'=>'binary', 'length'=>16, 'fixed'=>true, 'notnull'=>false)
		),
		'disable' => array
		(
			'reverseToggle'           => true,
			'filter'                  => true,
			'inputType'               => 'checkbox',
			'sql'                     => array('type'=>'boolean', 'default'=>false)
		),
		'start' => array
		(
			'inputType'               => 'text',
			'eval'                    => array('rgxp'=>'datim', 'datepicker'=>true, 'tl_class'=>'w50 wizard'),
			'sql'                     => array('type'=>'string', 'length'=>10, 'default'=>'')
		),
		'stop' => array
		(
			'inputType'               => 'text',
			'eval'                    => array('rgxp'=>'datim', 'datepicker'=>true, 'tl_class'=>'w50 wizard'),
			'sql'                     => array('type'=>'string', 'length'=>10, 'default'=>'')
		),
		'dateAdded' => array
		(
			'label'                   => &$GLOBALS['TL_LANG']['MSC']['dateAdded'],
			'default'                 => time(),
			'sorting'                 => true,
			'flag'                    => DataContainer::SORT_DAY_DESC,
			'eval'                    => array('rgxp'=>'datim', 'doNotCopy'=>true),
			'sql'                     => array('type'=>'integer', 'unsigned'=>true, 'default'=>0)
		),
		'lastLogin' => array
		(
			'label'                   => &$GLOBALS['TL_LANG']['MSC']['lastLogin'],
			'eval'                    => array('rgxp'=>'datim', 'doNotCopy'=>true),
			'sql'                     => array('type'=>'integer', 'unsigned'=>true, 'default'=>0)
		),
		'currentLogin' => array
		(
			'label'                   => &$GLOBALS['TL_LANG']['MSC']['currentLogin'],
			'sorting'                 => true,
			'flag'                    => DataContainer::SORT_DAY_DESC,
			'eval'                    => array('rgxp'=>'datim', 'doNotCopy'=>true),
			'sql'                     => array('type'=>'integer', 'unsigned'=>true, 'default'=>0)
		),
		'session' => array
		(
			'eval'                    => array('doNotShow'=>true, 'doNotCopy'=>true),
			'sql'                     => array('type'=>'blob', 'length'=>AbstractMySQLPlatform::LENGTH_LIMIT_BLOB, 'notnull'=>false)
		),
		'secret' => array
		(
			'eval'                    => array('doNotShow'=>true, 'doNotCopy'=>true),
			'sql'                     => array('type'=>'binary', 'length'=>128, 'fixed'=>true, 'notnull'=>false)
		),
		'useTwoFactor' => array
		(
			'eval'                    => array('isBoolean'=>true, 'doNotCopy'=>true, 'tl_class'=>'w50'),
			'sql'                     => array('type'=>'boolean', 'default'=>false)
		),
		'backupCodes' => array
		(
			'eval'                    => array('doNotCopy'=>true, 'doNotShow'=>true),
			'sql'                     => array('type'=>'text', 'length'=>AbstractMySQLPlatform::LENGTH_LIMIT_TEXT, 'notnull'=>false)
		),
		'trustedTokenVersion' => array
		(
			'eval'                    => array('doNotCopy'=>true, 'doNotShow'=>true),
			'sql'                     => array('type'=>'integer', 'unsigned'=>true, 'default'=>0)
		)
	)
);

/**
 * Provide miscellaneous methods that are used by the data configuration array.
 *
 * @internal
 */
class tl_member extends Backend
{
	/**
	 * Add an image to each record
	 *
	 * @param array         $row
	 * @param string        $label
	 * @param DataContainer $dc
	 * @param array         $args
	 *
	 * @return array
	 */
	public function addIcon($row, $label, DataContainer $dc, $args)
	{
		$image = 'member';
		$disabled = ($row['start'] !== '' && $row['start'] > time()) || ($row['stop'] !== '' && $row['stop'] <= time());

		if ($row['useTwoFactor'])
		{
			$image .= '_two_factor';
		}

		$icon = $image;

		if ($disabled || $row['disable'])
		{
			$image .= '--disabled';
		}

		$args[0] = sprintf(
			'<div class="list_icon_new" style="background-image:url(\'%s\')" data-icon="%s" data-icon-disabled="%s">&nbsp;</div>',
			Image::getUrl($image),
			Image::getUrl($icon),
			Image::getUrl($icon . '--disabled')
		);

		return $args;
	}

	/**
	 * Call the "setNewPassword" callback
	 *
	 * @param string                    $strPassword
	 * @param DataContainer|MemberModel $user
	 *
	 * @return string
	 */
	public function setNewPassword($strPassword, $user)
	{
		// Return if there is no user (e.g. upon registration)
		if (!$user)
		{
			return $strPassword;
		}

		$objUser = Database::getInstance()
			->prepare("SELECT * FROM tl_member WHERE id=?")
			->limit(1)
			->execute($user->id);

		// HOOK: set new password callback
		if ($objUser->numRows && isset($GLOBALS['TL_HOOKS']['setNewPassword']) && is_array($GLOBALS['TL_HOOKS']['setNewPassword']))
		{
			foreach ($GLOBALS['TL_HOOKS']['setNewPassword'] as $callback)
			{
				System::importStatic($callback[0])->{$callback[1]}($objUser, $strPassword);
			}
		}

		return $strPassword;
	}

	/**
	 * Store the date when the account has been added
	 *
	 * @param DataContainer|FrontendUser $dc
	 */
	public function storeDateAdded($dc)
	{
		// Front end call
		if (!$dc instanceof DataContainer)
		{
			return;
		}

		// Return if there is no active record (override all)
		if (!$dc->activeRecord || $dc->activeRecord->dateAdded > 0)
		{
			return;
		}

		// Fallback solution for existing accounts
		if ($dc->activeRecord->lastLogin > 0)
		{
			$time = $dc->activeRecord->lastLogin;
		}
		else
		{
			$time = time();
		}

		Database::getInstance()
			->prepare("UPDATE tl_member SET dateAdded=? WHERE id=?")
			->execute($time, $dc->id);
	}
}
