<?php

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

$GLOBALS['TL_DCA']['tl_calendar'] = array
(

	// Config
	'config' => array
	(
		'dataContainer'               => 'Table',
		'ctable'                      => array('tl_calendar_events'),
		'switchToEdit'                => true,
		'enableVersioning'            => true,
		'markAsCopy'                  => 'title',
		'onload_callback' => array
		(
			array('tl_calendar', 'checkPermission'),
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
			'mode'                    => 1,
			'fields'                  => array('title'),
			'flag'                    => 1,
			'panelLayout'             => 'filter;search,limit'
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
				'label'               => &$GLOBALS['TL_LANG']['tl_calendar']['feeds'],
				'href'                => 'table=tl_calendar_feed',
				'class'               => 'header_rss',
				'attributes'          => 'onclick="Backend.getScrollOffset()"',
				'button_callback'     => array('tl_calendar', 'manageFeeds')
			),
			'all' => array
			(
				'label'               => &$GLOBALS['TL_LANG']['MSC']['all'],
				'href'                => 'act=select',
				'class'               => 'header_edit_all',
				'attributes'          => 'onclick="Backend.getScrollOffset()" accesskey="e"'
			)
		),
		'operations' => array
		(
			'edit' => array
			(
				'label'               => &$GLOBALS['TL_LANG']['tl_calendar']['edit'],
				'href'                => 'table=tl_calendar_events',
				'icon'                => 'edit.svg'
			),
			'editheader' => array
			(
				'label'               => &$GLOBALS['TL_LANG']['tl_calendar']['editheader'],
				'href'                => 'act=edit',
				'icon'                => 'header.svg',
				'button_callback'     => array('tl_calendar', 'editHeader')
			),
			'copy' => array
			(
				'label'               => &$GLOBALS['TL_LANG']['tl_calendar']['copy'],
				'href'                => 'act=copy',
				'icon'                => 'copy.svg',
				'button_callback'     => array('tl_calendar', 'copyCalendar')
			),
			'delete' => array
			(
				'label'               => &$GLOBALS['TL_LANG']['tl_calendar']['delete'],
				'href'                => 'act=delete',
				'icon'                => 'delete.svg',
				'attributes'          => 'onclick="if(!confirm(\'' . $GLOBALS['TL_LANG']['MSC']['deleteConfirm'] . '\'))return false;Backend.getScrollOffset()"',
				'button_callback'     => array('tl_calendar', 'deleteCalendar')
			),
			'show' => array
			(
				'label'               => &$GLOBALS['TL_LANG']['tl_calendar']['show'],
				'href'                => 'act=show',
				'icon'                => 'show.svg'
			)
		)
	),

	// Palettes
	'palettes' => array
	(
		'__selector__'                => array('protected', 'allowComments'),
		'default'                     => '{title_legend},title,jumpTo;{protected_legend:hide},protected;{comments_legend:hide},allowComments'
	),

	// Subpalettes
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
			'sql'                     => "int(10) unsigned NOT NULL default '0'"
		),
		'title' => array
		(
			'label'                   => &$GLOBALS['TL_LANG']['tl_calendar']['title'],
			'exclude'                 => true,
			'search'                  => true,
			'inputType'               => 'text',
			'eval'                    => array('mandatory'=>true, 'maxlength'=>255, 'tl_class'=>'w50'),
			'sql'                     => "varchar(255) NOT NULL default ''"
		),
		'jumpTo' => array
		(
			'label'                   => &$GLOBALS['TL_LANG']['tl_calendar']['jumpTo'],
			'exclude'                 => true,
			'inputType'               => 'pageTree',
			'foreignKey'              => 'tl_page.title',
			'eval'                    => array('mandatory'=>true, 'fieldType'=>'radio', 'tl_class'=>'clr'),
			'sql'                     => "int(10) unsigned NOT NULL default '0'",
			'relation'                => array('type'=>'hasOne', 'load'=>'lazy')
		),
		'protected' => array
		(
			'label'                   => &$GLOBALS['TL_LANG']['tl_calendar']['protected'],
			'exclude'                 => true,
			'filter'                  => true,
			'inputType'               => 'checkbox',
			'eval'                    => array('submitOnChange'=>true),
			'sql'                     => "char(1) NOT NULL default ''"
		),
		'groups' => array
		(
			'label'                   => &$GLOBALS['TL_LANG']['tl_calendar']['groups'],
			'exclude'                 => true,
			'inputType'               => 'checkbox',
			'foreignKey'              => 'tl_member_group.name',
			'eval'                    => array('mandatory'=>true, 'multiple'=>true),
			'sql'                     => "blob NULL",
			'relation'                => array('type'=>'hasMany', 'load'=>'lazy')
		),
		'allowComments' => array
		(
			'label'                   => &$GLOBALS['TL_LANG']['tl_calendar']['allowComments'],
			'exclude'                 => true,
			'filter'                  => true,
			'inputType'               => 'checkbox',
			'eval'                    => array('submitOnChange'=>true),
			'sql'                     => "char(1) NOT NULL default ''"
		),
		'notify' => array
		(
			'label'                   => &$GLOBALS['TL_LANG']['tl_calendar']['notify'],
			'default'                 => 'notify_admin',
			'exclude'                 => true,
			'inputType'               => 'select',
			'options'                 => array('notify_admin', 'notify_author', 'notify_both'),
			'eval'                    => array('tl_class'=>'w50'),
			'reference'               => &$GLOBALS['TL_LANG']['tl_calendar'],
			'sql'                     => "varchar(32) NOT NULL default ''"
		),
		'sortOrder' => array
		(
			'label'                   => &$GLOBALS['TL_LANG']['tl_calendar']['sortOrder'],
			'default'                 => 'ascending',
			'exclude'                 => true,
			'inputType'               => 'select',
			'options'                 => array('ascending', 'descending'),
			'reference'               => &$GLOBALS['TL_LANG']['MSC'],
			'eval'                    => array('tl_class'=>'w50 clr'),
			'sql'                     => "varchar(32) NOT NULL default ''"
		),
		'perPage' => array
		(
			'label'                   => &$GLOBALS['TL_LANG']['tl_calendar']['perPage'],
			'exclude'                 => true,
			'inputType'               => 'text',
			'eval'                    => array('rgxp'=>'natural', 'tl_class'=>'w50'),
			'sql'                     => "smallint(5) unsigned NOT NULL default '0'"
		),
		'moderate' => array
		(
			'label'                   => &$GLOBALS['TL_LANG']['tl_calendar']['moderate'],
			'exclude'                 => true,
			'inputType'               => 'checkbox',
			'eval'                    => array('tl_class'=>'w50'),
			'sql'                     => "char(1) NOT NULL default ''"
		),
		'bbcode' => array
		(
			'label'                   => &$GLOBALS['TL_LANG']['tl_calendar']['bbcode'],
			'exclude'                 => true,
			'inputType'               => 'checkbox',
			'eval'                    => array('tl_class'=>'w50'),
			'sql'                     => "char(1) NOT NULL default ''"
		),
		'requireLogin' => array
		(
			'label'                   => &$GLOBALS['TL_LANG']['tl_calendar']['requireLogin'],
			'exclude'                 => true,
			'inputType'               => 'checkbox',
			'eval'                    => array('tl_class'=>'w50'),
			'sql'                     => "char(1) NOT NULL default ''"
		),
		'disableCaptcha' => array
		(
			'label'                   => &$GLOBALS['TL_LANG']['tl_calendar']['disableCaptcha'],
			'exclude'                 => true,
			'inputType'               => 'checkbox',
			'eval'                    => array('tl_class'=>'w50'),
			'sql'                     => "char(1) NOT NULL default ''"
		)
	)
);

/**
 * Provide miscellaneous methods that are used by the data configuration array.
 *
 * @property Contao\Calendar $Calendar
 *
 * @author Leo Feyer <https://github.com/leofeyer>
 */
class tl_calendar extends Contao\Backend
{

	/**
	 * Import the back end user object
	 */
	public function __construct()
	{
		parent::__construct();
		$this->import('Contao\BackendUser', 'User');
	}

	/**
	 * Check permissions to edit table tl_calendar
	 *
	 * @throws Contao\CoreBundle\Exception\AccessDeniedException
	 */
	public function checkPermission()
	{
		$bundles = Contao\System::getContainer()->getParameter('kernel.bundles');

		// HOOK: comments extension required
		if (!isset($bundles['ContaoCommentsBundle']))
		{
			unset($GLOBALS['TL_DCA']['tl_calendar']['fields']['allowComments']);
		}

		if ($this->User->isAdmin)
		{
			return;
		}

		// Set root IDs
		if (empty($this->User->calendars) || !\is_array($this->User->calendars))
		{
			$root = array(0);
		}
		else
		{
			$root = $this->User->calendars;
		}

		$GLOBALS['TL_DCA']['tl_calendar']['list']['sorting']['root'] = $root;

		// Check permissions to add calendars
		if (!$this->User->hasAccess('create', 'calendarp'))
		{
			$GLOBALS['TL_DCA']['tl_calendar']['config']['closed'] = true;
			$GLOBALS['TL_DCA']['tl_calendar']['config']['notCreatable'] = true;
			$GLOBALS['TL_DCA']['tl_calendar']['config']['notCopyable'] = true;
		}

		// Check permissions to delete calendars
		if (!$this->User->hasAccess('delete', 'calendarp'))
		{
			$GLOBALS['TL_DCA']['tl_calendar']['config']['notDeletable'] = true;
		}

		/** @var Symfony\Component\HttpFoundation\Session\SessionInterface $objSession */
		$objSession = Contao\System::getContainer()->get('session');

		// Check current action
		switch (Contao\Input::get('act'))
		{
			case 'select':
				// Allow
				break;

			case 'create':
				if (!$this->User->hasAccess('create', 'calendarp'))
				{
					throw new Contao\CoreBundle\Exception\AccessDeniedException('Not enough permissions to create calendars.');
				}
				break;

			case 'edit':
			case 'copy':
			case 'delete':
			case 'show':
				if (!\in_array(Contao\Input::get('id'), $root) || (Contao\Input::get('act') == 'delete' && !$this->User->hasAccess('delete', 'calendarp')))
				{
					throw new Contao\CoreBundle\Exception\AccessDeniedException('Not enough permissions to ' . Contao\Input::get('act') . ' calendar ID ' . Contao\Input::get('id') . '.');
				}
				break;

			case 'editAll':
			case 'deleteAll':
			case 'overrideAll':
			case 'copyAll':
				$session = $objSession->all();
				if (Contao\Input::get('act') == 'deleteAll' && !$this->User->hasAccess('delete', 'calendarp'))
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
				if (\strlen(Contao\Input::get('act')))
				{
					throw new Contao\CoreBundle\Exception\AccessDeniedException('Not enough permissions to ' . Contao\Input::get('act') . ' calendars.');
				}
				break;
		}
	}

	/**
	 * Add the new calendar to the permissions
	 *
	 * @param $insertId
	 */
	public function adjustPermissions($insertId)
	{
		// The oncreate_callback passes $insertId as second argument
		if (\func_num_args() == 4)
		{
			$insertId = func_get_arg(1);
		}

		if ($this->User->isAdmin)
		{
			return;
		}

		// Set root IDs
		if (empty($this->User->calendars) || !\is_array($this->User->calendars))
		{
			$root = array(0);
		}
		else
		{
			$root = $this->User->calendars;
		}

		// The calendar is enabled already
		if (\in_array($insertId, $root))
		{
			return;
		}

		/** @var Symfony\Component\HttpFoundation\Session\Attribute\AttributeBagInterface $objSessionBag */
		$objSessionBag = Contao\System::getContainer()->get('session')->getBag('contao_backend');

		$arrNew = $objSessionBag->get('new_records');

		if (\is_array($arrNew['tl_calendar']) && \in_array($insertId, $arrNew['tl_calendar']))
		{
			// Add the permissions on group level
			if ($this->User->inherit != 'custom')
			{
				$objGroup = $this->Database->execute("SELECT id, calendars, calendarp FROM tl_user_group WHERE id IN(" . implode(',', array_map('\intval', $this->User->groups)) . ")");

				while ($objGroup->next())
				{
					$arrCalendarp = Contao\StringUtil::deserialize($objGroup->calendarp);

					if (\is_array($arrCalendarp) && \in_array('create', $arrCalendarp))
					{
						$arrCalendars = Contao\StringUtil::deserialize($objGroup->calendars, true);
						$arrCalendars[] = $insertId;

						$this->Database->prepare("UPDATE tl_user_group SET calendars=? WHERE id=?")
									   ->execute(serialize($arrCalendars), $objGroup->id);
					}
				}
			}

			// Add the permissions on user level
			if ($this->User->inherit != 'group')
			{
				$objUser = $this->Database->prepare("SELECT calendars, calendarp FROM tl_user WHERE id=?")
										   ->limit(1)
										   ->execute($this->User->id);

				$arrCalendarp = Contao\StringUtil::deserialize($objUser->calendarp);

				if (\is_array($arrCalendarp) && \in_array('create', $arrCalendarp))
				{
					$arrCalendars = Contao\StringUtil::deserialize($objUser->calendars, true);
					$arrCalendars[] = $insertId;

					$this->Database->prepare("UPDATE tl_user SET calendars=? WHERE id=?")
								   ->execute(serialize($arrCalendars), $this->User->id);
				}
			}

			// Add the new element to the user object
			$root[] = $insertId;
			$this->User->calendars = $root;
		}
	}

	/**
	 * Check for modified calendar feeds and update the XML files if necessary
	 */
	public function generateFeed()
	{
		/** @var Symfony\Component\HttpFoundation\Session\SessionInterface $objSession */
		$objSession = Contao\System::getContainer()->get('session');

		$session = $objSession->get('calendar_feed_updater');

		if (empty($session) || !\is_array($session))
		{
			return;
		}

		$this->import('Contao\Calendar', 'Calendar');

		foreach ($session as $id)
		{
			$this->Calendar->generateFeedsByCalendar($id);
		}

		$this->import('Contao\Automator', 'Automator');
		$this->Automator->generateSitemap();

		$objSession->set('calendar_feed_updater', null);
	}

	/**
	 * Schedule a calendar feed update
	 *
	 * This method is triggered when a single calendar or multiple calendars
	 * are modified (edit/editAll).
	 *
	 * @param Contao\DataContainer $dc
	 */
	public function scheduleUpdate(Contao\DataContainer $dc)
	{
		// Return if there is no ID
		if (!$dc->id)
		{
			return;
		}

		/** @var Symfony\Component\HttpFoundation\Session\SessionInterface $objSession */
		$objSession = Contao\System::getContainer()->get('session');

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
		return ($this->User->isAdmin || !empty($this->User->calendarfeeds) || !empty($this->User->calendarfeedp)) ? '<a href="'.$this->addToUrl($href).'" class="'.$class.'" title="'.Contao\StringUtil::specialchars($title).'"'.$attributes.'>'.$label.'</a> ' : '';
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
		return $this->User->canEditFieldsOf('tl_calendar') ? '<a href="'.$this->addToUrl($href.'&amp;id='.$row['id']).'" title="'.Contao\StringUtil::specialchars($title).'"'.$attributes.'>'.Contao\Image::getHtml($icon, $label).'</a> ' : Contao\Image::getHtml(preg_replace('/\.svg/i', '_.svg', $icon)).' ';
	}

	/**
	 * Return the copy calendar button
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
	public function copyCalendar($row, $href, $label, $title, $icon, $attributes)
	{
		return $this->User->hasAccess('create', 'calendarp') ? '<a href="'.$this->addToUrl($href.'&amp;id='.$row['id']).'" title="'.Contao\StringUtil::specialchars($title).'"'.$attributes.'>'.Contao\Image::getHtml($icon, $label).'</a> ' : Contao\Image::getHtml(preg_replace('/\.svg/i', '_.svg', $icon)).' ';
	}

	/**
	 * Return the delete calendar button
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
	public function deleteCalendar($row, $href, $label, $title, $icon, $attributes)
	{
		return $this->User->hasAccess('delete', 'calendarp') ? '<a href="'.$this->addToUrl($href.'&amp;id='.$row['id']).'" title="'.Contao\StringUtil::specialchars($title).'"'.$attributes.'>'.Contao\Image::getHtml($icon, $label).'</a> ' : Contao\Image::getHtml(preg_replace('/\.svg/i', '_.svg', $icon)).' ';
	}
}
