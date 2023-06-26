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
use Contao\CoreBundle\Security\ContaoCorePermissions;
use Contao\DataContainer;
use Contao\DC_Table;
use Contao\Image;
use Contao\Input;
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
			array('tl_newsletter_channel', 'checkPermission')
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
			'edit' => array
			(
				'href'                => 'act=edit',
				'icon'                => 'edit.svg',
				'button_callback'     => array('tl_newsletter_channel', 'editHeader')
			),
			'children' => array
			(
				'href'                => 'table=tl_newsletter',
				'icon'                => 'children.svg'
			),
			'copy' => array
			(
				'href'                => 'act=copy',
				'icon'                => 'copy.svg',
				'button_callback'     => array('tl_newsletter_channel', 'copyChannel')
			),
			'delete' => array
			(
				'href'                => 'act=delete',
				'icon'                => 'delete.svg',
				'attributes'          => 'onclick="if(!confirm(\'' . ($GLOBALS['TL_LANG']['MSC']['deleteConfirm'] ?? null) . '\'))return false;Backend.getScrollOffset()"',
				'button_callback'     => array('tl_newsletter_channel', 'deleteChannel')
			),
			'show' => array
			(
				'href'                => 'act=show',
				'icon'                => 'show.svg'
			),
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
	 * Import the back end user object
	 */
	public function __construct()
	{
		parent::__construct();
		$this->import(BackendUser::class, 'User');
	}

	/**
	 * Check permissions to edit table tl_newsletter_channel
	 *
	 * @throws AccessDeniedException
	 */
	public function checkPermission()
	{
		if ($this->User->isAdmin)
		{
			return;
		}

		// Set root IDs
		if (empty($this->User->newsletters) || !is_array($this->User->newsletters))
		{
			$root = array(0);
		}
		else
		{
			$root = $this->User->newsletters;
		}

		$GLOBALS['TL_DCA']['tl_newsletter_channel']['list']['sorting']['root'] = $root;
		$security = System::getContainer()->get('security.helper');

		// Check permissions to add channels
		if (!$security->isGranted(ContaoNewsletterPermissions::USER_CAN_CREATE_CHANNELS))
		{
			$GLOBALS['TL_DCA']['tl_newsletter_channel']['config']['closed'] = true;
			$GLOBALS['TL_DCA']['tl_newsletter_channel']['config']['notCreatable'] = true;
			$GLOBALS['TL_DCA']['tl_newsletter_channel']['config']['notCopyable'] = true;
		}

		// Check permissions to delete channels
		if (!$security->isGranted(ContaoNewsletterPermissions::USER_CAN_DELETE_CHANNELS))
		{
			$GLOBALS['TL_DCA']['tl_newsletter_channel']['config']['notDeletable'] = true;
		}

		$objSession = System::getContainer()->get('request_stack')->getSession();

		// Check current action
		switch (Input::get('act'))
		{
			case 'select':
				// Allow
				break;

			case 'create':
				if (!$security->isGranted(ContaoNewsletterPermissions::USER_CAN_CREATE_CHANNELS))
				{
					throw new AccessDeniedException('Not enough permissions to create newsletter channels.');
				}
				break;

			case 'edit':
			case 'copy':
			case 'delete':
			case 'show':
				if (!in_array(Input::get('id'), $root) || (Input::get('act') == 'delete' && !$security->isGranted(ContaoNewsletterPermissions::USER_CAN_DELETE_CHANNELS)))
				{
					throw new AccessDeniedException('Not enough permissions to ' . Input::get('act') . ' newsletter channel ID ' . Input::get('id') . '.');
				}
				break;

			case 'editAll':
			case 'deleteAll':
			case 'overrideAll':
			case 'copyAll':
				$session = $objSession->all();

				if (Input::get('act') == 'deleteAll' && !$security->isGranted(ContaoNewsletterPermissions::USER_CAN_DELETE_CHANNELS))
				{
					$session['CURRENT']['IDS'] = array();
				}
				else
				{
					$session['CURRENT']['IDS'] = array_intersect((array) $session['CURRENT']['IDS'], $root);
				}
				$objSession->replace($session);
				break;

			default:
				if (Input::get('act'))
				{
					throw new AccessDeniedException('Not enough permissions to ' . Input::get('act') . ' newsletter channels.');
				}
				break;
		}
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

		if ($this->User->isAdmin)
		{
			return;
		}

		// Set root IDs
		if (empty($this->User->newsletters) || !is_array($this->User->newsletters))
		{
			$root = array(0);
		}
		else
		{
			$root = $this->User->newsletters;
		}

		// The channel is enabled already
		if (in_array($insertId, $root))
		{
			return;
		}

		/** @var AttributeBagInterface $objSessionBag */
		$objSessionBag = System::getContainer()->get('request_stack')->getSession()->getBag('contao_backend');

		$arrNew = $objSessionBag->get('new_records');

		if (is_array($arrNew['tl_newsletter_channel']) && in_array($insertId, $arrNew['tl_newsletter_channel']))
		{
			// Add the permissions on group level
			if ($this->User->inherit != 'custom')
			{
				$objGroup = $this->Database->execute("SELECT id, newsletters, newsletterp FROM tl_user_group WHERE id IN(" . implode(',', array_map('\intval', $this->User->groups)) . ")");

				while ($objGroup->next())
				{
					$arrNewsletterp = StringUtil::deserialize($objGroup->newsletterp);

					if (is_array($arrNewsletterp) && in_array('create', $arrNewsletterp))
					{
						$arrNewsletters = StringUtil::deserialize($objGroup->newsletters, true);
						$arrNewsletters[] = $insertId;

						$this->Database->prepare("UPDATE tl_user_group SET newsletters=? WHERE id=?")
									   ->execute(serialize($arrNewsletters), $objGroup->id);
					}
				}
			}

			// Add the permissions on user level
			if ($this->User->inherit != 'group')
			{
				$objUser = $this->Database->prepare("SELECT newsletters, newsletterp FROM tl_user WHERE id=?")
										   ->limit(1)
										   ->execute($this->User->id);

				$arrNewsletterp = StringUtil::deserialize($objUser->newsletterp);

				if (is_array($arrNewsletterp) && in_array('create', $arrNewsletterp))
				{
					$arrNewsletters = StringUtil::deserialize($objUser->newsletters, true);
					$arrNewsletters[] = $insertId;

					$this->Database->prepare("UPDATE tl_user SET newsletters=? WHERE id=?")
								   ->execute(serialize($arrNewsletters), $this->User->id);
				}
			}

			// Add the new element to the user object
			$root[] = $insertId;
			$this->User->newsletter = $root;
		}
	}

	/**
	 * Return the edit header button
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
	public function editHeader($row, $href, $label, $title, $icon, $attributes)
	{
		return System::getContainer()->get('security.helper')->isGranted(ContaoCorePermissions::USER_CAN_EDIT_FIELDS_OF_TABLE, 'tl_newsletter_channel') ? '<a href="' . $this->addToUrl($href . '&amp;id=' . $row['id']) . '" title="' . StringUtil::specialchars($title) . '"' . $attributes . '>' . Image::getHtml($icon, $label) . '</a> ' : Image::getHtml(str_replace('.svg', '--disabled.svg', $icon)) . ' ';
	}

	/**
	 * Return the copy channel button
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
	public function copyChannel($row, $href, $label, $title, $icon, $attributes)
	{
		return System::getContainer()->get('security.helper')->isGranted(ContaoNewsletterPermissions::USER_CAN_CREATE_CHANNELS) ? '<a href="' . $this->addToUrl($href . '&amp;id=' . $row['id']) . '" title="' . StringUtil::specialchars($title) . '"' . $attributes . '>' . Image::getHtml($icon, $label) . '</a> ' : Image::getHtml(str_replace('.svg', '--disabled.svg', $icon)) . ' ';
	}

	/**
	 * Return the delete channel button
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
	public function deleteChannel($row, $href, $label, $title, $icon, $attributes)
	{
		return System::getContainer()->get('security.helper')->isGranted(ContaoNewsletterPermissions::USER_CAN_DELETE_CHANNELS) ? '<a href="' . $this->addToUrl($href . '&amp;id=' . $row['id']) . '" title="' . StringUtil::specialchars($title) . '"' . $attributes . '>' . Image::getHtml($icon, $label) . '</a> ' : Image::getHtml(str_replace('.svg', '--disabled.svg', $icon)) . ' ';
	}
}
