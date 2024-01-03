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
use Contao\Database;
use Contao\DataContainer;
use Contao\DC_Table;
use Contao\News;
use Contao\PageModel;
use Contao\StringUtil;
use Contao\System;
use Symfony\Component\HttpFoundation\Session\Attribute\AttributeBagInterface;

$GLOBALS['TL_DCA']['tl_news_archive'] = array
(
	// Config
	'config' => array
	(
		'dataContainer'               => DC_Table::class,
		'ctable'                      => array('tl_news'),
		'switchToEdit'                => true,
		'enableVersioning'            => true,
		'markAsCopy'                  => 'title',
		'onload_callback' => array
		(
			array('tl_news_archive', 'adjustDca')
		),
		'oncreate_callback' => array
		(
			array('tl_news_archive', 'adjustPermissions')
		),
		'oncopy_callback' => array
		(
			array('tl_news_archive', 'adjustPermissions')
		),
		'oninvalidate_cache_tags_callback' => array
		(
			array('tl_news_archive', 'addSitemapCacheInvalidationTag'),
		),
		'sql' => array
		(
			'keys' => array
			(
				'id' => 'primary',
				'tstamp' => 'index',
				'jumpTo' => 'index',
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
			'panelLayout'             => 'filter;search,limit',
			'defaultSearchField'      => 'title'
		),
		'label' => array
		(
			'fields'                  => array('title'),
			'format'                  => '%s'
		)
	),

	// Palettes
	'palettes' => array
	(
		'__selector__'                => array('protected', 'allowComments'),
		'default'                     => '{title_legend},title,jumpTo;{protected_legend:hide},protected;{comments_legend:hide},allowComments'
	),

	// Sub-palettes
	'subpalettes' => array
	(
		'protected'                   => 'groups',
		'allowComments'               => 'notify,sortOrder,perPage,moderate,bbcode,requireLogin,disableCaptcha'
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
		'jumpTo' => array
		(
			'inputType'               => 'pageTree',
			'foreignKey'              => 'tl_page.title',
			'eval'                    => array('mandatory'=>true, 'fieldType'=>'radio', 'tl_class'=>'clr'),
			'sql'                     => "int(10) unsigned NOT NULL default 0",
			'relation'                => array('type'=>'hasOne', 'load'=>'lazy')
		),
		'protected' => array
		(
			'filter'                  => true,
			'inputType'               => 'checkbox',
			'eval'                    => array('submitOnChange'=>true),
			'sql'                     => array('type' => 'boolean', 'default' => false)
		),
		'groups' => array
		(
			'inputType'               => 'checkbox',
			'foreignKey'              => 'tl_member_group.name',
			'eval'                    => array('mandatory'=>true, 'multiple'=>true),
			'sql'                     => "blob NULL",
			'relation'                => array('type'=>'hasMany', 'load'=>'lazy')
		),
		'allowComments' => array
		(
			'filter'                  => true,
			'inputType'               => 'checkbox',
			'eval'                    => array('submitOnChange'=>true),
			'sql'                     => array('type' => 'boolean', 'default' => false)
		),
		'notify' => array
		(
			'inputType'               => 'select',
			'options'                 => array('notify_admin', 'notify_author', 'notify_both'),
			'eval'                    => array('tl_class'=>'w50'),
			'reference'               => &$GLOBALS['TL_LANG']['tl_news_archive'],
			'sql'                     => "varchar(16) NOT NULL default 'notify_admin'"
		),
		'sortOrder' => array
		(
			'inputType'               => 'select',
			'options'                 => array('ascending', 'descending'),
			'reference'               => &$GLOBALS['TL_LANG']['MSC'],
			'eval'                    => array('tl_class'=>'w50 clr'),
			'sql'                     => "varchar(32) NOT NULL default 'ascending'"
		),
		'perPage' => array
		(
			'inputType'               => 'text',
			'eval'                    => array('rgxp'=>'natural', 'tl_class'=>'w50'),
			'sql'                     => "smallint(5) unsigned NOT NULL default 0"
		),
		'moderate' => array
		(
			'inputType'               => 'checkbox',
			'eval'                    => array('tl_class'=>'w50'),
			'sql'                     => array('type' => 'boolean', 'default' => false)
		),
		'bbcode' => array
		(
			'inputType'               => 'checkbox',
			'eval'                    => array('tl_class'=>'w50'),
			'sql'                     => array('type' => 'boolean', 'default' => false)
		),
		'requireLogin' => array
		(
			'inputType'               => 'checkbox',
			'eval'                    => array('tl_class'=>'w50'),
			'sql'                     => array('type' => 'boolean', 'default' => false)
		),
		'disableCaptcha' => array
		(
			'inputType'               => 'checkbox',
			'eval'                    => array('tl_class'=>'w50'),
			'sql'                     => array('type' => 'boolean', 'default' => false)
		)
	)
);

/**
 * Provide miscellaneous methods that are used by the data configuration array.
 *
 * @property News $News
 *
 * @internal
 */
class tl_news_archive extends Backend
{
	/**
	 * Set the root IDs and unset the "allowComments" field if the comments bundle is not available.
	 */
	public function adjustDca()
	{
		$bundles = System::getContainer()->getParameter('kernel.bundles');

		// HOOK: comments extension required
		if (!isset($bundles['ContaoCommentsBundle']))
		{
			unset($GLOBALS['TL_DCA']['tl_news_archive']['fields']['allowComments']);
		}

		$user = BackendUser::getInstance();

		if ($user->isAdmin)
		{
			return;
		}

		// Set root IDs
		if (empty($user->news) || !is_array($user->news))
		{
			$root = array(0);
		}
		else
		{
			$root = $user->news;
		}

		$GLOBALS['TL_DCA']['tl_news_archive']['list']['sorting']['root'] = $root;
	}

	/**
	 * Add the new archive to the permissions
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
		if (empty($user->news) || !is_array($user->news))
		{
			$root = array(0);
		}
		else
		{
			$root = $user->news;
		}

		// The archive is enabled already
		if (in_array($insertId, $root))
		{
			return;
		}

		$db = Database::getInstance();

		/** @var AttributeBagInterface $objSessionBag */
		$objSessionBag = System::getContainer()->get('request_stack')->getSession()->getBag('contao_backend');
		$arrNew = $objSessionBag->get('new_records');

		if (is_array($arrNew['tl_news_archive']) && in_array($insertId, $arrNew['tl_news_archive']))
		{
			// Add the permissions on group level
			if ($user->inherit != 'custom')
			{
				$objGroup = $db->execute("SELECT id, news, newp FROM tl_user_group WHERE id IN(" . implode(',', array_map('\intval', $user->groups)) . ")");

				while ($objGroup->next())
				{
					$arrNewp = StringUtil::deserialize($objGroup->newp);

					if (is_array($arrNewp) && in_array('create', $arrNewp))
					{
						$arrNews = StringUtil::deserialize($objGroup->news, true);
						$arrNews[] = $insertId;

						$db->prepare("UPDATE tl_user_group SET news=? WHERE id=?")->execute(serialize($arrNews), $objGroup->id);
					}
				}
			}

			// Add the permissions on user level
			if ($user->inherit != 'group')
			{
				$objUser = $db
					->prepare("SELECT news, newp FROM tl_user WHERE id=?")
					->limit(1)
					->execute($user->id);

				$arrNewp = StringUtil::deserialize($objUser->newp);

				if (is_array($arrNewp) && in_array('create', $arrNewp))
				{
					$arrNews = StringUtil::deserialize($objUser->news, true);
					$arrNews[] = $insertId;

					$db->prepare("UPDATE tl_user SET news=? WHERE id=?")->execute(serialize($arrNews), $user->id);
				}
			}

			// Add the new element to the user object
			$root[] = $insertId;
			$user->news = $root;
		}
	}

	/**
	 * @param DataContainer $dc
	 *
	 * @return array
	 */
	public function addSitemapCacheInvalidationTag($dc, array $tags)
	{
		$pageModel = PageModel::findWithDetails($dc->activeRecord->jumpTo);

		if ($pageModel === null)
		{
			return $tags;
		}

		return array_merge($tags, array('contao.sitemap.' . $pageModel->rootId));
	}
}
