<?php

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

use Contao\Automator;
use Contao\Backend;
use Contao\BackendUser;
use Contao\CoreBundle\EventListener\Widget\HttpUrlListener;
use Contao\CoreBundle\Exception\AccessDeniedException;
use Contao\DataContainer;
use Contao\DC_Table;
use Contao\Environment;
use Contao\Image;
use Contao\Input;
use Contao\News;
use Contao\NewsArchiveModel;
use Contao\NewsBundle\Security\ContaoNewsPermissions;
use Contao\StringUtil;
use Contao\System;
use Symfony\Component\HttpFoundation\Session\Attribute\AttributeBagInterface;

$GLOBALS['TL_DCA']['tl_news_feed'] = array
(
	// Config
	'config' => array
	(
		'dataContainer'               => DC_Table::class,
		'enableVersioning'            => true,
		'markAsCopy'                  => 'title',
		'onload_callback' => array
		(
			array('tl_news_feed', 'checkPermission'),
			array('tl_news_feed', 'generateFeed')
		),
		'oncreate_callback' => array
		(
			array('tl_news_feed', 'adjustPermissions')
		),
		'oncopy_callback' => array
		(
			array('tl_news_feed', 'adjustPermissions')
		),
		'onsubmit_callback' => array
		(
			array('tl_news_feed', 'scheduleUpdate')
		),
		'sql' => array
		(
			'keys' => array
			(
				'id' => 'primary',
				'alias' => 'index'
			)
		),
		'backlink'                    => 'do=news'
	),

	// List
	'list' => array
	(
		'sorting' => array
		(
			'mode'                    => DataContainer::MODE_SORTED,
			'fields'                  => array('title'),
			'flag'                    => DataContainer::SORT_INITIAL_LETTER_ASC,
			'panelLayout'             => 'filter;search,limit'
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
			),
		),
		'operations' => array
		(
			'edit',
			'copy' => array
			(
				'href'                => 'act=copy',
				'icon'                => 'copy.svg',
				'button_callback'     => array('tl_news_feed', 'copyFeed')
			),
			'delete' => array
			(
				'href'                => 'act=delete',
				'icon'                => 'delete.svg',
				'attributes'          => 'onclick="if(!confirm(\'' . ($GLOBALS['TL_LANG']['MSC']['deleteConfirm'] ?? null) . '\'))return false;Backend.getScrollOffset()"',
				'button_callback'     => array('tl_news_feed', 'deleteFeed')
			),
			'show'
		)
	),

	// Palettes
	'palettes' => array
	(
		'default'                     => '{title_legend},title,alias,language;{archives_legend},archives;{config_legend},format,source,maxItems,feedBase,description;{image_legend:hide},imgSize'
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
			'eval'                    => array('mandatory'=>true, 'maxlength'=>255, 'tl_class'=>'w50'),
			'sql'                     => "varchar(255) NOT NULL default ''"
		),
		'alias' => array
		(
			'search'                  => true,
			'inputType'               => 'text',
			'eval'                    => array('mandatory'=>true, 'rgxp'=>'alias', 'unique'=>true, 'maxlength'=>255, 'tl_class'=>'w50 clr'),
			'save_callback' => array
			(
				array('tl_news_feed', 'checkFeedAlias')
			),
			'sql'                     => "varchar(255) BINARY NOT NULL default ''"
		),
		'language' => array
		(
			'search'                  => true,
			'filter'                  => true,
			'inputType'               => 'text',
			'eval'                    => array('mandatory'=>true, 'maxlength'=>32, 'tl_class'=>'w50'),
			'sql'                     => "varchar(32) NOT NULL default ''"
		),
		'archives' => array
		(
			'search'                  => true,
			'inputType'               => 'checkbox',
			'options_callback'        => array('tl_news_feed', 'getAllowedArchives'),
			'eval'                    => array('multiple'=>true, 'mandatory'=>true),
			'sql'                     => "blob NULL"
		),
		'format' => array
		(
			'filter'                  => true,
			'inputType'               => 'select',
			'options'                 => array('rss'=>'RSS 2.0', 'atom'=>'Atom'),
			'eval'                    => array('tl_class'=>'w50'),
			'sql'                     => "varchar(32) NOT NULL default 'rss'"
		),
		'source' => array
		(
			'inputType'               => 'select',
			'options'                 => array('source_teaser', 'source_text'),
			'reference'               => &$GLOBALS['TL_LANG']['tl_news_feed'],
			'eval'                    => array('tl_class'=>'w50'),
			'sql'                     => "varchar(32) NOT NULL default 'source_teaser'"
		),
		'maxItems' => array
		(
			'inputType'               => 'text',
			'eval'                    => array('mandatory'=>true, 'rgxp'=>'natural', 'tl_class'=>'w50'),
			'sql'                     => "smallint(5) unsigned NOT NULL default 25"
		),
		'feedBase' => array
		(
			'search'                  => true,
			'inputType'               => 'text',
			'eval'                    => array('trailingSlash'=>true, 'rgxp'=>HttpUrlListener::RGXP_NAME, 'decodeEntities'=>true, 'maxlength'=>255, 'tl_class'=>'w50'),
			'load_callback' => array
			(
				array('tl_news_feed', 'addFeedBase')
			),
			'sql'                     => "varchar(255) NOT NULL default ''"
		),
		'description' => array
		(
			'search'                  => true,
			'inputType'               => 'textarea',
			'eval'                    => array('style'=>'height:60px', 'tl_class'=>'clr'),
			'sql'                     => "text NULL"
		),
		'imgSize' => array
		(
			'label'                   => &$GLOBALS['TL_LANG']['MSC']['imgSize'],
			'inputType'               => 'imageSize',
			'reference'               => &$GLOBALS['TL_LANG']['MSC'],
			'eval'                    => array('rgxp'=>'natural', 'includeBlankOption'=>true, 'nospace'=>true, 'helpwizard'=>true, 'tl_class'=>'w50'),
			'options_callback'	      => array('contao.listener.image_size_options', '__invoke'),
			'sql'                     => "varchar(255) NOT NULL default ''"
		),
	)
);

/**
 * Provide miscellaneous methods that are used by the data configuration array.
 *
 * @property News $News
 *
 * @internal
 */
class tl_news_feed extends Backend
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
	 * Check permissions to edit table tl_news_archive
	 *
	 * @throws AccessDeniedException
	 */
	public function checkPermission()
	{
		if ($this->User->isAdmin)
		{
			return;
		}

		// Set the root IDs
		if (empty($this->User->newsfeeds) || !is_array($this->User->newsfeeds))
		{
			$root = array(0);
		}
		else
		{
			$root = $this->User->newsfeeds;
		}

		$GLOBALS['TL_DCA']['tl_news_feed']['list']['sorting']['root'] = $root;
		$security = System::getContainer()->get('security.helper');

		// Check permissions to add feeds
		if (!$security->isGranted(ContaoNewsPermissions::USER_CAN_CREATE_FEEDS))
		{
			$GLOBALS['TL_DCA']['tl_news_feed']['config']['closed'] = true;
			$GLOBALS['TL_DCA']['tl_news_feed']['config']['notCreatable'] = true;
			$GLOBALS['TL_DCA']['tl_news_feed']['config']['notCopyable'] = true;
		}

		// Check permissions to delete feeds
		if (!$security->isGranted(ContaoNewsPermissions::USER_CAN_DELETE_FEEDS))
		{
			$GLOBALS['TL_DCA']['tl_news_feed']['config']['notDeletable'] = true;
		}

		$objSession = System::getContainer()->get('session');

		// Check current action
		switch (Input::get('act'))
		{
			case 'select':
				// Allow
				break;

			case 'create':
				if (!$security->isGranted(ContaoNewsPermissions::USER_CAN_CREATE_FEEDS))
				{
					throw new AccessDeniedException('Not enough permissions to create news feeds.');
				}
				break;

			case 'edit':
			case 'copy':
			case 'delete':
			case 'show':
				if (!in_array(Input::get('id'), $root) || (Input::get('act') == 'delete' && !$security->isGranted(ContaoNewsPermissions::USER_CAN_DELETE_FEEDS)))
				{
					throw new AccessDeniedException('Not enough permissions to ' . Input::get('act') . ' news feed ID ' . Input::get('id') . '.');
				}
				break;

			case 'editAll':
			case 'deleteAll':
			case 'overrideAll':
			case 'copyAll':
				$session = $objSession->all();

				if (Input::get('act') == 'deleteAll' && !$security->isGranted(ContaoNewsPermissions::USER_CAN_DELETE_FEEDS))
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
					throw new AccessDeniedException('Not enough permissions to ' . Input::get('act') . ' news feeds.');
				}
				break;
		}
	}

	/**
	 * Add the new feed to the permissions
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
		if (empty($this->User->newsfeeds) || !is_array($this->User->newsfeeds))
		{
			$root = array(0);
		}
		else
		{
			$root = $this->User->newsfeeds;
		}

		// The feed is enabled already
		if (in_array($insertId, $root))
		{
			return;
		}

		/** @var AttributeBagInterface $objSessionBag */
		$objSessionBag = System::getContainer()->get('session')->getBag('contao_backend');

		$arrNew = $objSessionBag->get('new_records');

		if (is_array($arrNew['tl_news_feed']) && in_array($insertId, $arrNew['tl_news_feed']))
		{
			// Add the permissions on group level
			if ($this->User->inherit != 'custom')
			{
				$objGroup = $this->Database->execute("SELECT id, newsfeeds, newsfeedp FROM tl_user_group WHERE id IN(" . implode(',', array_map('\intval', $this->User->groups)) . ")");

				while ($objGroup->next())
				{
					$arrNewsfeedp = StringUtil::deserialize($objGroup->newsfeedp);

					if (is_array($arrNewsfeedp) && in_array('create', $arrNewsfeedp))
					{
						$arrNewsfeeds = StringUtil::deserialize($objGroup->newsfeeds, true);
						$arrNewsfeeds[] = $insertId;

						$this->Database->prepare("UPDATE tl_user_group SET newsfeeds=? WHERE id=?")
									   ->execute(serialize($arrNewsfeeds), $objGroup->id);
					}
				}
			}

			// Add the permissions on user level
			if ($this->User->inherit != 'group')
			{
				$objUser = $this->Database->prepare("SELECT newsfeeds, newsfeedp FROM tl_user WHERE id=?")
										   ->limit(1)
										   ->execute($this->User->id);

				$arrNewsfeedp = StringUtil::deserialize($objUser->newsfeedp);

				if (is_array($arrNewsfeedp) && in_array('create', $arrNewsfeedp))
				{
					$arrNewsfeeds = StringUtil::deserialize($objUser->newsfeeds, true);
					$arrNewsfeeds[] = $insertId;

					$this->Database->prepare("UPDATE tl_user SET newsfeeds=? WHERE id=?")
								   ->execute(serialize($arrNewsfeeds), $this->User->id);
				}
			}

			// Add the new element to the user object
			$root[] = $insertId;
			$this->User->newsfeeds = $root;
		}
	}

	/**
	 * Return the copy news feed button
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
	public function copyFeed($row, $href, $label, $title, $icon, $attributes)
	{
		return System::getContainer()->get('security.helper')->isGranted(ContaoNewsPermissions::USER_CAN_CREATE_FEEDS) ? '<a href="' . $this->addToUrl($href . '&amp;id=' . $row['id']) . '" title="' . StringUtil::specialchars($title) . '"' . $attributes . '>' . Image::getHtml($icon, $label) . '</a> ' : Image::getHtml(preg_replace('/\.svg/i', '_.svg', $icon)) . ' ';
	}

	/**
	 * Return the delete news feed button
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
	public function deleteFeed($row, $href, $label, $title, $icon, $attributes)
	{
		return System::getContainer()->get('security.helper')->isGranted(ContaoNewsPermissions::USER_CAN_DELETE_FEEDS) ? '<a href="' . $this->addToUrl($href . '&amp;id=' . $row['id']) . '" title="' . StringUtil::specialchars($title) . '"' . $attributes . '>' . Image::getHtml($icon, $label) . '</a> ' : Image::getHtml(preg_replace('/\.svg/i', '_.svg', $icon)) . ' ';
	}

	/**
	 * Check for modified news feeds and update the XML files if necessary
	 */
	public function generateFeed()
	{
		$objSession = System::getContainer()->get('session');
		$session = $objSession->get('news_feed_updater');

		if (empty($session) || !is_array($session))
		{
			return;
		}

		$request = System::getContainer()->get('request_stack')->getCurrentRequest();

		if ($request)
		{
			$origScope = $request->attributes->get('_scope');
			$request->attributes->set('_scope', 'frontend');
		}

		$this->import(News::class, 'News');

		foreach ($session as $id)
		{
			$this->News->generateFeed($id);
		}

		if ($request)
		{
			$request->attributes->set('_scope', $origScope);
		}

		$objSession->set('news_feed_updater', null);
	}

	/**
	 * Schedule a news feed update
	 *
	 * This method is triggered when a single news archive or multiple news
	 * archives are modified (edit/editAll).
	 *
	 * @param DataContainer $dc
	 */
	public function scheduleUpdate(DataContainer $dc)
	{
		// Return if there is no ID
		if (!$dc->id)
		{
			return;
		}

		$objSession = System::getContainer()->get('session');

		// Store the ID in the session
		$session = $objSession->get('news_feed_updater');
		$session[] = $dc->id;
		$objSession->set('news_feed_updater', array_unique($session));
	}

	/**
	 * Return the IDs of the allowed news archives as array
	 *
	 * @return array
	 */
	public function getAllowedArchives()
	{
		if ($this->User->isAdmin)
		{
			$objArchive = NewsArchiveModel::findAll();
		}
		else
		{
			$objArchive = NewsArchiveModel::findMultipleByIds($this->User->news);
		}

		$return = array();

		if ($objArchive !== null)
		{
			while ($objArchive->next())
			{
				$return[$objArchive->id] = $objArchive->title;
			}
		}

		return $return;
	}

	/**
	 * Check the RSS-feed alias
	 *
	 * @param mixed         $varValue
	 * @param DataContainer $dc
	 *
	 * @return mixed
	 *
	 * @throws Exception
	 */
	public function checkFeedAlias($varValue, DataContainer $dc)
	{
		// No change or empty value
		if (!$varValue || $varValue == $dc->value)
		{
			return $varValue;
		}

		$varValue = StringUtil::standardize($varValue); // see #5096

		$this->import(Automator::class, 'Automator');
		$arrFeeds = $this->Automator->purgeXmlFiles(true);

		// Alias exists
		if (in_array($varValue, $arrFeeds))
		{
			throw new Exception(sprintf($GLOBALS['TL_LANG']['ERR']['aliasExists'], $varValue));
		}

		return $varValue;
	}

	/**
	 * Add the RSS-feed base URL
	 *
	 * @param mixed $varValue
	 *
	 * @return string
	 */
	public function addFeedBase($varValue)
	{
		if (!$varValue)
		{
			$varValue = Environment::get('base');
		}

		return $varValue;
	}
}
