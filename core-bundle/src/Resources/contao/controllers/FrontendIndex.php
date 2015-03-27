<?php

/**
 * Contao Open Source CMS
 *
 * Copyright (c) 2005-2015 Leo Feyer
 *
 * @license LGPL-3.0+
 */

namespace Contao;

use Symfony\Component\HttpFoundation\Response;


/**
 * Main front end controller.
 *
 * @author Leo Feyer <https://github.com/leofeyer>
 */
class FrontendIndex extends \Frontend
{

	/**
	 * Initialize the object
	 */
	public function __construct()
	{
		// Load the user object before calling the parent constructor
		$this->import('FrontendUser', 'User');
		parent::__construct();

		// Check whether a user is logged in
		define('BE_USER_LOGGED_IN', $this->getLoginStatus('BE_USER_AUTH'));
		define('FE_USER_LOGGED_IN', $this->getLoginStatus('FE_USER_AUTH'));

		// No back end user logged in
		if (!$_SESSION['DISABLE_CACHE'])
		{
			// Maintenance mode (see #4561 and #6353)
			if (\Config::get('maintenanceMode'))
			{
				header('HTTP/1.1 503 Service Unavailable');
				die_nicely('be_unavailable', 'This site is currently down for maintenance. Please come back later.');
			}
		}
	}


	/**
	 * Run the controller
	 *
	 * @return Response
	 */
	public function run()
	{
		global $objPage;

		$pageId = $this->getPageIdFromUrl();
		$objRootPage = null;

		// Load a website root page object if there is no page ID
		if ($pageId === null)
		{
			$objRootPage = $this->getRootPageFromUrl();

			/** @var \PageRoot $objHandler */
			$objHandler = new $GLOBALS['TL_PTY']['root']();
			$pageId = $objHandler->generate($objRootPage->id, true, true);
		}

		// Throw a 404 error if the request is not a Contao request (see #2864)
		elseif ($pageId === false)
		{
			$this->User->authenticate();

			/** @var \PageError404 $objHandler */
			$objHandler = new $GLOBALS['TL_PTY']['error_404']();

			return $objHandler->getResponse($pageId);
		}

		// Get the current page object(s)
		$objPage = \PageModel::findPublishedByIdOrAlias($pageId);

		// Check the URL and language of each page if there are multiple results
		if ($objPage !== null && $objPage->count() > 1)
		{
			$objNewPage = null;
			$arrPages = array();

			// Order by domain and language
			while ($objPage->next())
			{
				/** @var \PageModel $objModel */
				$objModel = $objPage->current();
				$objCurrentPage = $objModel->loadDetails();

				$domain = $objCurrentPage->domain ?: '*';
				$arrPages[$domain][$objCurrentPage->rootLanguage] = $objCurrentPage;

				// Also store the fallback language
				if ($objCurrentPage->rootIsFallback)
				{
					$arrPages[$domain]['*'] = $objCurrentPage;
				}
			}

			$strHost = \Environment::get('host');

			// Look for a root page whose domain name matches the host name
			if (isset($arrPages[$strHost]))
			{
				$arrLangs = $arrPages[$strHost];
			}
			else
			{
				$arrLangs = $arrPages['*'] ?: array(); // empty domain
			}

			// Use the first result (see #4872)
			if (!\Config::get('addLanguageToUrl'))
			{
				$objNewPage = current($arrLangs);
			}

			// Try to find a page matching the language parameter
			elseif (($lang = \Input::get('language')) != '' && isset($arrLangs[$lang]))
			{
				$objNewPage = $arrLangs[$lang];
			}

			// Store the page object
			if (is_object($objNewPage))
			{
				$objPage = $objNewPage;
			}
		}

		// Throw a 404 error if the page could not be found or the result is still ambiguous
		if ($objPage === null || ($objPage instanceof \Model\Collection && $objPage->count() != 1))
		{
			$this->User->authenticate();

			/** @var \PageError404 $objHandler */
			$objHandler = new $GLOBALS['TL_PTY']['error_404']();

			return $objHandler->getResponse($pageId);
		}

		// Make sure $objPage is a Model
		if ($objPage instanceof \Model\Collection)
		{
			$objPage = $objPage->current();
		}

		// If the page has an alias, it can no longer be called via ID (see #7661)
		if ($objPage->alias != '' && $pageId == $objPage->id)
		{
			$this->User->authenticate();

			/** @var \PageError404 $objHandler */
			$objHandler = new $GLOBALS['TL_PTY']['error_404']();

			return $objHandler->generate($pageId);
		}

		// Load a website root page object (will redirect to the first active regular page)
		if ($objPage->type == 'root')
		{
			/** @var \PageRoot $objHandler */
			$objHandler = new $GLOBALS['TL_PTY']['root']();
			$objHandler->generate($objPage->id);
		}

		// Inherit the settings from the parent pages if it has not been done yet
		if (!is_bool($objPage->protected))
		{
			$objPage->loadDetails();
		}

		// Set the admin e-mail address
		if ($objPage->adminEmail != '')
		{
			list($GLOBALS['TL_ADMIN_NAME'], $GLOBALS['TL_ADMIN_EMAIL']) = \String::splitFriendlyEmail($objPage->adminEmail);
		}
		else
		{
			list($GLOBALS['TL_ADMIN_NAME'], $GLOBALS['TL_ADMIN_EMAIL']) = \String::splitFriendlyEmail(\Config::get('adminEmail'));
		}

		// Exit if the root page has not been published (see #2425)
		// Do not try to load the 404 page, it can cause an infinite loop!
		if (!BE_USER_LOGGED_IN && !$objPage->rootIsPublic)
		{
			header('HTTP/1.1 404 Not Found');
			die_nicely('be_no_page', 'Page not found');
		}

		// Check wether the language matches the root page language
		if (\Config::get('addLanguageToUrl') && \Input::get('language') != $objPage->rootLanguage)
		{
			$this->User->authenticate();

			/** @var \PageError404 $objHandler */
			$objHandler = new $GLOBALS['TL_PTY']['error_404']();

			return $objHandler->getResponse($pageId);
		}

		// Check whether there are domain name restrictions
		if ($objPage->domain != '')
		{
			// Load an error 404 page object
			if ($objPage->domain != \Environment::get('host'))
			{
				$this->User->authenticate();

				/** @var \PageError404 $objHandler */
				$objHandler = new $GLOBALS['TL_PTY']['error_404']();

				return $objHandler->getResponse($objPage->id, $objPage->domain, \Environment::get('host'));
			}
		}

		// Authenticate the user
		if (!$this->User->authenticate() && $objPage->protected && !BE_USER_LOGGED_IN)
		{
			/** @var \PageError403 $objHandler */
			$objHandler = new $GLOBALS['TL_PTY']['error_403']();

			return $objHandler->getResponse($pageId, $objRootPage);
		}

		// Check the user groups if the page is protected
		if ($objPage->protected && !BE_USER_LOGGED_IN)
		{
			$arrGroups = $objPage->groups; // required for empty()

			if (!is_array($arrGroups) || empty($arrGroups) || !count(array_intersect($arrGroups, $this->User->groups)))
			{
				$this->log('Page "' . $pageId . '" can only be accessed by groups "' . implode(', ', (array) $objPage->groups) . '" (current user groups: ' . implode(', ', $this->User->groups) . ')', __METHOD__, TL_ERROR);

				/** @var \PageError403 $objHandler */
				$objHandler = new $GLOBALS['TL_PTY']['error_403']();

				return $objHandler->getResponse($pageId, $objRootPage);
			}
		}

		/** @var \PageRegular|\PageError403|\PageError404 $objHandler */
		$objHandler = new $GLOBALS['TL_PTY'][$objPage->type]();

		// Backup some globals (see #7659)
		$arrHead = $GLOBALS['TL_HEAD'];
		$arrBody = $GLOBALS['TL_BODY'];
		$arrMootools = $GLOBALS['TL_MOOTOOLS'];
		$arrJquery = $GLOBALS['TL_JQUERY'];

		try
		{
			// Generate the page
			switch ($objPage->type)
			{
				case 'root':
				case 'error_404':
					return $objHandler->getResponse($pageId);
					break;

				case 'error_403':
					return $objHandler->getResponse($pageId, $objRootPage);
					break;

				default:
					return $objHandler->getResponse($objPage, true);
					break;
			}
		}

		// Render the error page (see #5570)
		catch (\UnusedArgumentsException $e)
		{
			// Restore the globals (see #7659)
			$GLOBALS['TL_HEAD'] = $arrHead;
			$GLOBALS['TL_BODY'] = $arrBody;
			$GLOBALS['TL_MOOTOOLS'] = $arrMootools;
			$GLOBALS['TL_JQUERY'] = $arrJquery;

			/** @var \PageError404 $objHandler */
			$objHandler = new $GLOBALS['TL_PTY']['error_404']();

			return $objHandler->getResponse($pageId, null, null, true);
		}
	}


	/**
	 * Try to load the page from the cache
	 *
	 * @deprecated Now uses the kernel.request event
	 */
	protected function outputFromCache()
	{
	}
}
