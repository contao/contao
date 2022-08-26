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
use Contao\CoreBundle\Exception\AccessDeniedException;
use Contao\Input;
use Contao\System;

// Dynamically add the permission check and other callbacks
if (Input::get('do') == 'calendar')
{
	array_unshift($GLOBALS['TL_DCA']['tl_content']['config']['onload_callback'], array('tl_content_calendar', 'checkPermission'));
	$GLOBALS['TL_DCA']['tl_content']['config']['onload_callback'][] = array('tl_content_calendar', 'generateFeed');
}

/**
 * Provide miscellaneous methods that are used by the data configuration array.
 *
 * @property Calendar $Calendar
 */
class tl_content_calendar extends Backend
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
	 * Check permissions to edit table tl_content
	 */
	public function checkPermission()
	{
		if ($this->User->isAdmin)
		{
			return;
		}

		// Set the root IDs
		if (empty($this->User->calendars) || !is_array($this->User->calendars))
		{
			$root = array(0);
		}
		else
		{
			$root = $this->User->calendars;
		}

		// Check the current action
		switch (Input::get('act'))
		{
			case '': // empty
			case 'paste':
			case 'create':
			case 'select':
				// Check access to the news item
				$this->checkAccessToElement(CURRENT_ID, $root, true);
				break;

			case 'editAll':
			case 'deleteAll':
			case 'overrideAll':
			case 'cutAll':
			case 'copyAll':
				// Check access to the parent element if a content element is moved
				if (in_array(Input::get('act'), array('cutAll', 'copyAll')))
				{
					$this->checkAccessToElement(Input::get('pid'), $root, (Input::get('mode') == 2));
				}

				$objCes = $this->Database->prepare("SELECT id FROM tl_content WHERE ptable='tl_calendar_events' AND pid=?")
										 ->execute(CURRENT_ID);

				$objSession = System::getContainer()->get('session');

				$session = $objSession->all();
				$session['CURRENT']['IDS'] = array_intersect((array) $session['CURRENT']['IDS'], $objCes->fetchEach('id'));
				$objSession->replace($session);
				break;

			case 'cut':
			case 'copy':
				// Check access to the parent element if a content element is moved
				$this->checkAccessToElement(Input::get('pid'), $root, (Input::get('mode') == 2));
				// no break

			default:
				// Check access to the content element
				$this->checkAccessToElement(Input::get('id'), $root);
				break;
		}
	}

	/**
	 * Check access to a particular content element
	 *
	 * @param integer $id
	 * @param array   $root
	 * @param boolean $blnIsPid
	 *
	 * @throws AccessDeniedException
	 */
	protected function checkAccessToElement($id, $root, $blnIsPid=false)
	{
		if ($blnIsPid)
		{
			$objCalendar = $this->Database->prepare("SELECT a.id, n.id AS nid FROM tl_calendar_events n, tl_calendar a WHERE n.id=? AND n.pid=a.id")
										  ->limit(1)
										  ->execute($id);
		}
		else
		{
			$objCalendar = $this->Database->prepare("SELECT a.id, n.id AS nid FROM tl_content c, tl_calendar_events n, tl_calendar a WHERE c.id=? AND c.pid=n.id AND n.pid=a.id")
										  ->limit(1)
										  ->execute($id);
		}

		// Invalid ID
		if ($objCalendar->numRows < 1)
		{
			throw new AccessDeniedException('Invalid event content element ID ' . $id . '.');
		}

		// The calendar is not mounted
		if (!in_array($objCalendar->id, $root))
		{
			throw new AccessDeniedException('Not enough permissions to modify article ID ' . $objCalendar->nid . ' in calendar ID ' . $objCalendar->id . '.');
		}
	}

	/**
	 * Check for modified calendar feeds and update the XML files if necessary
	 */
	public function generateFeed()
	{
		$objSession = System::getContainer()->get('session');
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

		$this->import(Calendar::class, 'Calendar');

		foreach ($session as $id)
		{
			$this->Calendar->generateFeedsByCalendar($id);
		}

		if ($request)
		{
			$request->attributes->set('_scope', $origScope);
		}

		$objSession->set('calendar_feed_updater', null);
	}
}
