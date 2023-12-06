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
use Contao\CoreBundle\Security\ContaoCorePermissions;
use Contao\Database;
use Contao\DataContainer;
use Contao\DC_Table;
use Contao\Image;
use Contao\NewsletterBundle\Security\ContaoNewsletterPermissions;
use Contao\StringUtil;
use Contao\System;
use Symfony\Component\HttpFoundation\Session\Attribute\AttributeBagInterface;

$GLOBALS['TL_DCA']['tl_newsletter_channel'] = array
(
	// Config
	'config' => array
	(
		'dataContainer'               => DC_Table::class,
		'ctable'                      => array('tl_newsletter', 'tl_newsletter_recipients'),
		'switchToEdit'                => true,
		'enableVersioning'            => true,
		'markAsCopy'                  => 'title',
		'onload_callback' => array
		(
			array('tl_newsletter_channel', 'adjustDca')
		),
		'oncreate_callback' => array
		(
			array('tl_newsletter_channel', 'adjustPermissions')
		),
		'oncopy_callback' => array
		(
			array('tl_newsletter_channel', 'adjustPermissions')
		),
		'sql' => array
		(
			'keys' => array
			(
				'id' => 'primary'
			)
		)
	),

	// List
	'list' => array
	(
		'sorting' => array
		(
			'mode'                    => DataContainer::MODE_SORTED,
			'fields'                  => array('title'),
			'flag'                    => DataContainer::SORT_INITIAL_LETTER_ASC,
			'panelLayout'             => 'search,limit',
			'defaultSearchField'      => 'title'
		),
		'label' => array
		(
			'fields'                  => array('title'),
			'format'                  => '%s'
		),
		'operations' => array
		(
			'recipients' => array
			(
				'href'                => 'table=tl_newsletter_recipients',
				'icon'                => 'mgroup.svg'
			)
		)
	),

	// Palettes
	'palettes' => array
	(
		'default'                     => '{title_legend},title,jumpTo;{template_legend:hide},template;{sender_legend},sender,senderName,mailerTransport'
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
		'title' => array
		(
			'search'                  => true,
			'inputType'               => 'text',
			'eval'                    => array('mandatory'=>true, 'decodeEntities'=>true, 'maxlength'=>255, 'tl_class'=>'w50'),
			'sql'                     => "varchar(255) NOT NULL default ''"
		),
		'jumpTo' => array
		(
			'inputType'               => 'pageTree',
			'foreignKey'              => 'tl_page.title',
			'eval'                    => array('fieldType'=>'radio', 'tl_class'=>'clr'),
			'sql'                     => "int(10) unsigned NOT NULL default 0",
			'relation'                => array('type'=>'hasOne', 'load'=>'lazy')
		),
		'template' => array
		(
			'inputType'               => 'select',
			'eval'                    => array('includeBlankOption'=>true, 'chosen'=>true, 'tl_class'=>'w50'),
			'options_callback' => static function () {
				return Controller::getTemplateGroup('mail_');
			},
			'sql'                     => "varchar(32) NOT NULL default ''"
		),
		'mailerTransport' => array
		(
			'inputType'               => 'select',
			'eval'                    => array('includeBlankOption'=>true, 'tl_class'=>'w33'),
			'options_callback'        => array('contao.mailer.available_transports', 'getTransportOptions'),
			'sql'                     => "varchar(255) NOT NULL default ''"
		),
		'sender' => array
		(
			'search'                  => true,
			'filter'                  => true,
			'inputType'               => 'text',
			'eval'                    => array('mandatory'=>true, 'rgxp'=>'email', 'maxlength'=>255, 'decodeEntities'=>true, 'tl_class'=>'w33'),
			'sql'                     => "varchar(255) NOT NULL default ''"
		),
		'senderName' => array
		(
			'search'                  => true,
			'sorting'                 => true,
			'flag'                    => DataContainer::SORT_ASC,
			'inputType'               => 'text',
			'eval'                    => array('decodeEntities'=>true, 'maxlength'=>128, 'tl_class'=>'w33'),
			'sql'                     => "varchar(128) NOT NULL default ''"
		)
	)
);

/**
 * Provide miscellaneous methods that are used by the data configuration array.
 *
 * @internal
 */
class tl_newsletter_channel extends Backend
{
	/**
	 * Set the root IDs.
	 */
	public function adjustDca()
	{
		$user = BackendUser::getInstance();

		if ($user->isAdmin)
		{
			return;
		}

		// Set root IDs
		if (empty($user->newsletters) || !is_array($user->newsletters))
		{
			$root = array(0);
		}
		else
		{
			$root = $user->newsletters;
		}

		$GLOBALS['TL_DCA']['tl_newsletter_channel']['list']['sorting']['root'] = $root;
	}

	/**
	 * Add the new channel to the permissions
	 *
	 * @param string|int $insertId
	 */
	public function adjustPermissions($insertId)
	{
		// The oncreate_callback passes $insertId as second argument
		if (func_num_args() == 4)
		{
			$insertId = func_get_arg(1);
		}

		$user = BackendUser::getInstance();

		if ($user->isAdmin)
		{
			return;
		}

		// Set root IDs
		if (empty($user->newsletters) || !is_array($user->newsletters))
		{
			$root = array(0);
		}
		else
		{
			$root = $user->newsletters;
		}

		// The channel is enabled already
		if (in_array($insertId, $root))
		{
			return;
		}

		$db = Database::getInstance();

		/** @var AttributeBagInterface $objSessionBag */
		$objSessionBag = System::getContainer()->get('request_stack')->getSession()->getBag('contao_backend');
		$arrNew = $objSessionBag->get('new_records');

		if (is_array($arrNew['tl_newsletter_channel']) && in_array($insertId, $arrNew['tl_newsletter_channel']))
		{
			// Add the permissions on group level
			if ($user->inherit != 'custom')
			{
				$objGroup = $db->execute("SELECT id, newsletters, newsletterp FROM tl_user_group WHERE id IN(" . implode(',', array_map('\intval', $user->groups)) . ")");

				while ($objGroup->next())
				{
					$arrNewsletterp = StringUtil::deserialize($objGroup->newsletterp);

					if (is_array($arrNewsletterp) && in_array('create', $arrNewsletterp))
					{
						$arrNewsletters = StringUtil::deserialize($objGroup->newsletters, true);
						$arrNewsletters[] = $insertId;

						$db->prepare("UPDATE tl_user_group SET newsletters=? WHERE id=?")->execute(serialize($arrNewsletters), $objGroup->id);
					}
				}
			}

			// Add the permissions on user level
			if ($user->inherit != 'group')
			{
				$objUser = $db
					->prepare("SELECT newsletters, newsletterp FROM tl_user WHERE id=?")
					->limit(1)
					->execute($user->id);

				$arrNewsletterp = StringUtil::deserialize($objUser->newsletterp);

				if (is_array($arrNewsletterp) && in_array('create', $arrNewsletterp))
				{
					$arrNewsletters = StringUtil::deserialize($objUser->newsletters, true);
					$arrNewsletters[] = $insertId;

					$db->prepare("UPDATE tl_user SET newsletters=? WHERE id=?")->execute(serialize($arrNewsletters), $user->id);
				}
			}

			// Add the new element to the user object
			$root[] = $insertId;
			$user->newsletter = $root;
		}
	}
}
