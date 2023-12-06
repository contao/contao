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
use Contao\Calendar;
use Contao\CalendarBundle\Security\ContaoCalendarPermissions;
use Contao\CoreBundle\Security\ContaoCorePermissions;
use Contao\Database;
use Contao\DataContainer;
use Contao\DC_Table;
use Contao\Image;
use Contao\PageModel;
use Contao\StringUtil;
use Contao\System;
use Symfony\Component\HttpFoundation\Session\Attribute\AttributeBagInterface;

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
			array('tl_calendar', 'generateFeed')
		),
		'oncreate_callback' => array
		(
			array('tl_calendar', 'adjustPermissions')
		),
		'oncopy_callback' => array
		(
			array('tl_calendar', 'adjustPermissions')
		),
		'onsubmit_callback' => array
		(
			array('tl_calendar', 'scheduleUpdate')
		),
		'oninvalidate_cache_tags_callback' => array
		(
			array('tl_calendar', 'addSitemapCacheInvalidationTag'),
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
			'panelLayout'             => 'filter;search,limit',
			'defaultSearchField'      => 'title'
		),
		'label' => array
		(
			'fields'                  => array('title'),
			'format'                  => '%s'
		),
		'global_operations' => array
		(
			'feeds' => array
			(
				'href'                => 'table=tl_calendar_feed',
				'class'               => 'header_rss',
				'attributes'          => 'onclick="Backend.getScrollOffset()"',
				'button_callback'     => array('tl_calendar', 'manageFeeds')
			),
			'all'
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
			'reference'               => &$GLOBALS['TL_LANG']['tl_calendar'],
			'sql'                     => "varchar(32) NOT NULL default 'notify_admin'"
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
 * @property Calendar $Calendar
 *
 * @internal
 */
class tl_calendar extends Backend
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
			unset($GLOBALS['TL_DCA']['tl_calendar']['fields']['allowComments']);
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

		/** @var AttributeBagInterface $objSessionBag */
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
	 * Check for modified calendar feeds and update the XML files if necessary
	 */
	public function generateFeed()
	{
		$objSession = System::getContainer()->get('request_stack')->getSession();
		$session = $objSession->get('calendar_feed_updater');

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

		$calendar = new Calendar();

		foreach ($session as $id)
		{
			$calendar->generateFeedsByCalendar($id);
		}

		if ($request)
		{
			$request->attributes->set('_scope', $origScope);
		}

		$objSession->set('calendar_feed_updater', null);
	}

	/**
	 * Schedule a calendar feed update
	 *
	 * This method is triggered when a single calendar or multiple calendars
	 * are modified (edit/editAll).
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

		$objSession = System::getContainer()->get('request_stack')->getSession();

		// Store the ID in the session
		$session = $objSession->get('calendar_feed_updater');
		$session[] = $dc->id;
		$objSession->set('calendar_feed_updater', array_unique($session));
	}

	/**
	 * Return the manage feeds button
	 *
	 * @param string $href
	 * @param string $label
	 * @param string $title
	 * @param string $class
	 * @param string $attributes
	 *
	 * @return string
	 */
	public function manageFeeds($href, $label, $title, $class, $attributes)
	{
		$user = BackendUser::getInstance();

		return ($user->isAdmin || !empty($user->calendarfeeds) || !empty($user->calendarfeedp)) ? '<a href="' . $this->addToUrl($href) . '" class="' . $class . '" title="' . StringUtil::specialchars($title) . '"' . $attributes . '>' . $label . '</a> ' : '';
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
