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
use Contao\CoreBundle\Exception\AccessDeniedException;
use Contao\Database;
use Contao\DataContainer;
use Contao\Input;
use Contao\News;
use Contao\System;

// Dynamically add the permission check and other callbacks
if (Input::get('do') == 'news')
{
	System::loadLanguageFile('tl_news');

	array_unshift($GLOBALS['TL_DCA']['tl_content']['config']['onload_callback'], array('tl_content_news', 'checkPermission'));
}

/**
 * Provide miscellaneous methods that are used by the data configuration array.
 *
 * @property News $News
 *
 * @internal
 */
class tl_content_news extends Backend
{
	/**
	 * Check permissions to edit table tl_content
	 *
	 * @param DataContainer $dc
	 */
	public function checkPermission(DataContainer $dc)
	{
		$user = BackendUser::getInstance();

		if ($user->isAdmin)
		{
			return;
		}

		// Set the root IDs
		if (empty($user->news) || !is_array($user->news))
		{
			$root = array(0);
		}
		else
		{
			$root = $user->news;
		}

		// Check the current action
		switch (Input::get('act'))
		{
			case '': // empty
			case 'paste':
			case 'select':
				// Check access to the news item
				$this->checkAccessToElement($dc->currentPid, $root, true);
				break;

			case 'create':
				// Check access to the parent element if a content element is created
				$this->checkAccessToElement(Input::get('pid'), $root, Input::get('mode') == 2);
				break;

			case 'editAll':
			case 'deleteAll':
			case 'overrideAll':
			case 'cutAll':
			case 'copyAll':
				// Check access to the parent element if a content element is moved
				if (in_array(Input::get('act'), array('cutAll', 'copyAll')))
				{
					$this->checkAccessToElement(Input::get('pid'), $root, Input::get('mode') == 2);
				}

				$objCes = Database::getInstance()
					->prepare("SELECT id FROM tl_content WHERE ptable=? AND pid=?")
					->execute($dc->parentTable, $dc->currentPid);

				$objSession = System::getContainer()->get('request_stack')->getSession();

				$session = $objSession->all();
				$session['CURRENT']['IDS'] = array_intersect((array) $session['CURRENT']['IDS'], $objCes->fetchEach('id'));
				$objSession->replace($session);
				break;

			case 'cut':
			case 'copy':
				// Check access to the parent element if a content element is moved
				$this->checkAccessToElement(Input::get('pid'), $root, Input::get('mode') == 2);
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
			$objArchive = Database::getInstance()
				->prepare("SELECT a.id, n.id AS nid FROM tl_news n, tl_news_archive a WHERE n.id=? AND n.pid=a.id")
				->limit(1)
				->execute($id);
		}
		else
		{
			$objArchive = Database::getInstance()
				->prepare("SELECT a.id, n.id AS nid FROM tl_content c, tl_news n, tl_news_archive a WHERE c.id=? AND c.pid=n.id AND n.pid=a.id")
				->limit(1)
				->execute($id);
		}

		// Invalid ID
		if ($objArchive->numRows < 1)
		{
			throw new AccessDeniedException('Invalid news content element ID ' . $id . '.');
		}

		// The news archive is not mounted
		if (!in_array($objArchive->id, $root))
		{
			throw new AccessDeniedException('Not enough permissions to modify article ID ' . $objArchive->nid . ' in news archive ID ' . $objArchive->id . '.');
		}
	}
}
