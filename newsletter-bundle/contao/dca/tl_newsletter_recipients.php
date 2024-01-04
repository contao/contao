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
use Contao\Database;
use Contao\DataContainer;
use Contao\Date;
use Contao\DC_Table;
use Contao\Idna;
use Contao\Image;

$GLOBALS['TL_DCA']['tl_newsletter_recipients'] = array
(
	// Config
	'config' => array
	(
		'dataContainer'               => DC_Table::class,
		'ptable'                      => 'tl_newsletter_channel',
		'enableVersioning'            => true,
		'oncut_callback' => array
		(
			array('tl_newsletter_recipients', 'clearOptInData')
		),
		'sql' => array
		(
			'keys' => array
			(
				'id' => 'primary',
				'pid,email' => 'unique',
				'email' => 'index',
				'active' => 'index'
			)
		)
	),

	// List
	'list' => array
	(
		'sorting' => array
		(
			'mode'                    => DataContainer::MODE_PARENT,
			'fields'                  => array('email'),
			'panelLayout'             => 'filter;sort,search,limit',
			'defaultSearchField'      => 'email',
			'headerFields'            => array('title', 'jumpTo', 'tstamp', 'sender'),
			'child_record_callback'   => array('tl_newsletter_recipients', 'listRecipient')
		),
		'global_operations' => array
		(
			'import' => array
			(
				'href'                => 'key=import',
				'class'               => 'header_css_import',
				'attributes'          => 'data-action="contao--scroll-offset#store"'
			),
			'all'
		)
	),

	// Palettes
	'palettes' => array
	(
		'default'                     => '{email_legend},email,active',
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
			'foreignKey'              => 'tl_newsletter_channel.title',
			'sql'                     => "int(10) unsigned NOT NULL default 0",
			'relation'                => array('type'=>'belongsTo', 'load'=>'lazy')
		),
		'tstamp' => array
		(
			'sql'                     => "int(10) unsigned NOT NULL default 0"
		),
		'email' => array
		(
			'search'                  => true,
			'sorting'                 => true,
			'flag'                    => DataContainer::SORT_INITIAL_LETTER_ASC,
			'inputType'               => 'text',
			'eval'                    => array('mandatory'=>true, 'rgxp'=>'email', 'maxlength'=>255, 'decodeEntities'=>true, 'tl_class'=>'w50'),
			'save_callback' => array
			(
				array('tl_newsletter_recipients', 'checkUniqueRecipient'),
				array('tl_newsletter_recipients', 'checkDenyList')
			),
			'sql'                     => "varchar(255) NOT NULL default ''"
		),
		'active' => array
		(
			'toggle'                  => true,
			'filter'                  => true,
			'inputType'               => 'checkbox',
			'eval'                    => array('tl_class'=>'w50 m12'),
			'sql'                     => array('type' => 'boolean', 'default' => false)
		),
		'addedOn' => array
		(
			'filter'                  => true,
			'sorting'                 => true,
			'flag'                    => DataContainer::SORT_MONTH_DESC,
			'eval'                    => array('rgxp'=>'datim', 'doNotCopy'=>true),
			'sql'                     => "varchar(10) NOT NULL default ''"
		)
	)
);

/**
 * Provide miscellaneous methods that are used by the data configuration array.
 *
 * @internal
 */
class tl_newsletter_recipients extends Backend
{
	/**
	 * Set the recipient status to "added manually" if they are moved to another channel
	 *
	 * @param DataContainer $dc
	 */
	public function clearOptInData(DataContainer $dc)
	{
		Database::getInstance()
			->prepare("UPDATE tl_newsletter_recipients SET addedOn='' WHERE id=?")
			->execute($dc->id);
	}

	/**
	 * Check if recipients are unique per channel
	 *
	 * @param mixed         $varValue
	 * @param DataContainer $dc
	 *
	 * @return mixed
	 *
	 * @throws Exception
	 */
	public function checkUniqueRecipient($varValue, DataContainer $dc)
	{
		$objRecipient = Database::getInstance()
			->prepare("SELECT COUNT(*) AS count FROM tl_newsletter_recipients WHERE email=? AND pid=(SELECT pid FROM tl_newsletter_recipients WHERE id=?) AND id!=?")
			->execute($varValue, $dc->id, $dc->id);

		if ($objRecipient->count > 0)
		{
			throw new Exception(sprintf($GLOBALS['TL_LANG']['ERR']['unique'], $GLOBALS['TL_LANG'][$dc->table][$dc->field][0]));
		}

		return $varValue;
	}

	/**
	 * Check if a recipient was added to the deny list for a channel
	 *
	 * @param mixed         $varValue
	 * @param DataContainer $dc
	 *
	 * @return mixed
	 *
	 * @throws Exception
	 */
	public function checkDenyList($varValue, DataContainer $dc)
	{
		$objDenyList = Database::getInstance()
			->prepare("SELECT COUNT(*) AS count FROM tl_newsletter_deny_list WHERE hash=? AND pid=(SELECT pid FROM tl_newsletter_recipients WHERE id=?) AND id!=?")
			->execute(md5($varValue), $dc->id, $dc->id);

		if ($objDenyList->count > 0)
		{
			throw new Exception($GLOBALS['TL_LANG']['ERR']['onDenyList']);
		}

		return $varValue;
	}

	/**
	 * List a recipient
	 *
	 * @param array $row
	 *
	 * @return string
	 */
	public function listRecipient($row)
	{
		$label = Idna::decodeEmail($row['email']);

		if ($row['addedOn'])
		{
			$label .= ' <span class="label-info">(' . sprintf($GLOBALS['TL_LANG']['tl_newsletter_recipients']['subscribed'], Date::parse(Config::get('datimFormat'), $row['addedOn'])) . ')</span>';
		}
		else
		{
			$label .= ' <span class="label-info">(' . $GLOBALS['TL_LANG']['tl_newsletter_recipients']['manually'] . ')</span>';
		}

		$icon = Image::getPath('member');
		$icond = Image::getPath('member_');

		return sprintf(
			'<div class="tl_content_left"><div class="list_icon" style="background-image:url(\'%s\')" data-icon="%s" data-icon-disabled="%s">%s</div></div>' . "\n",
			$row['active'] ? $icon : $icond,
			$icon,
			$icond,
			$label
		);
	}
}
