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
use Contao\PageModel;
use Contao\StringUtil;
use Contao\System;

$GLOBALS['TL_DCA']['tl_calendar'] = array
(
	// Config
	'config' => array
	(
		'dataContainer'               => DC_Table::class,
		'ctable'                      => array('tl_calendar_events'),
		'switchToEdit'                => true,
		'enableVersioning'            => true,
		'markAsCopy'                  => 'title',
		'onload_callback' => array
		(
			array('tl_calendar', 'adjustDca'),
		),
		'oncreate_callback' => array
		(
			array('tl_calendar', 'adjustPermissions')
		),
		'oncopy_callback' => array
		(
			array('tl_calendar', 'adjustPermissions')
		),
		'oninvalidate_cache_tags_callback' => array
		(
			array('tl_calendar', 'addSitemapCacheInvalidationTag'),
		),
		'sql' => array
		(
			'keys' => array
			(
				'id' => 'primary',
				'tstamp' => 'index'
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
			'panelLayout'             => 'search;filter;limit',
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
		'__selector__'                => array('protected'),
		'default'                     => '{title_legend},title,jumpTo;{protected_legend:hide},protected'
	),

	// Sub-palettes
	'subpalettes' => array
	(
		'protected'                   => 'groups'
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
			'eval'                    => array('mandatory'=>true, 'basicEntities'=>true, 'maxlength'=>255, 'tl_class'=>'w50'),
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
		)
	)
);

/**
 * Provide miscellaneous methods that are used by the data configuration array.
 *
 * @internal
 */
class tl_calendar extends Backend
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
		if (empty($user->calendars) || !is_array($user->calendars))
		{
			$root = array(0);
		}
		else
		{
			$root = $user->calendars;
		}

		$GLOBALS['TL_DCA']['tl_calendar']['list']['sorting']['root'] = $root;
	}

	/**
	 * Add the new calendar to the permissions
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
		if (empty($user->calendars) || !is_array($user->calendars))
		{
			$root = array(0);
		}
		else
		{
			$root = $user->calendars;
		}

		// The calendar is enabled already
		if (in_array($insertId, $root))
		{
			return;
		}

		$objSessionBag = System::getContainer()->get('request_stack')->getSession()->getBag('contao_backend');
		$arrNew = $objSessionBag->get('new_records');

		if (is_array($arrNew['tl_calendar']) && in_array($insertId, $arrNew['tl_calendar']))
		{
			$db = Database::getInstance();

			// Add the permissions on group level
			if ($user->inherit != 'custom')
			{
				$objGroup = $db->execute("SELECT id, calendars, calendarp FROM tl_user_group WHERE id IN(" . implode(',', array_map('\intval', $user->groups)) . ")");

				while ($objGroup->next())
				{
					$arrCalendarp = StringUtil::deserialize($objGroup->calendarp);

					if (is_array($arrCalendarp) && in_array('create', $arrCalendarp))
					{
						$arrCalendars = StringUtil::deserialize($objGroup->calendars, true);
						$arrCalendars[] = $insertId;

						$db->prepare("UPDATE tl_user_group SET calendars=? WHERE id=?")->execute(serialize($arrCalendars), $objGroup->id);
					}
				}
			}

			// Add the permissions on user level
			if ($user->inherit != 'group')
			{
				$objUser = $db
					->prepare("SELECT calendars, calendarp FROM tl_user WHERE id=?")
					->limit(1)
					->execute($user->id);

				$arrCalendarp = StringUtil::deserialize($objUser->calendarp);

				if (is_array($arrCalendarp) && in_array('create', $arrCalendarp))
				{
					$arrCalendars = StringUtil::deserialize($objUser->calendars, true);
					$arrCalendars[] = $insertId;

					$db->prepare("UPDATE tl_user SET calendars=? WHERE id=?")->execute(serialize($arrCalendars), $user->id);
				}
			}

			// Add the new element to the user object
			$root[] = $insertId;
			$user->calendars = $root;
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
