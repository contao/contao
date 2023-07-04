<?php

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao;

use Contao\CoreBundle\Exception\AccessDeniedException;
use Contao\CoreBundle\Exception\PageNotFoundException;
use Symfony\Component\HttpFoundation\Response;

/**
 * Main front end controller.
 */
class FrontendIndex extends Frontend
{
	/**
	 * Render a page
	 *
	 * @return Response
	 *
	 * @throws \LogicException
	 * @throws PageNotFoundException
	 * @throws AccessDeniedException
	 */
	public function renderPage(PageModel $pageModel)
	{
		/** @var PageModel $objPage */
		global $objPage;

		$objPage = $pageModel;

		// Inherit the settings from the parent pages
		$objPage->loadDetails();

		// Set the admin e-mail address
		if ($objPage->adminEmail)
		{
			list($GLOBALS['TL_ADMIN_NAME'], $GLOBALS['TL_ADMIN_EMAIL']) = StringUtil::splitFriendlyEmail($objPage->adminEmail);
		}
		else
		{
			list($GLOBALS['TL_ADMIN_NAME'], $GLOBALS['TL_ADMIN_EMAIL']) = StringUtil::splitFriendlyEmail(Config::get('adminEmail'));
		}

		// Backup some globals (see #7659)
		$arrBackup = array(
			$GLOBALS['TL_HEAD'] ?? array(),
			$GLOBALS['TL_BODY'] ?? array(),
			$GLOBALS['TL_MOOTOOLS'] ?? array(),
			$GLOBALS['TL_JQUERY'] ?? array(),
			$GLOBALS['TL_USER_CSS'] ?? array(),
			$GLOBALS['TL_FRAMEWORK_CSS'] ?? array()
		);

		try
		{
			$pageType = $GLOBALS['TL_PTY'][$objPage->type] ?? PageRegular::class;
			$objHandler = new $pageType();

			return $objHandler->getResponse($objPage, true);
		}

		// Render the error page (see #5570)
		catch (UnusedArgumentsException $e)
		{
			// Restore the globals (see #7659)
			list(
				$GLOBALS['TL_HEAD'],
				$GLOBALS['TL_BODY'],
				$GLOBALS['TL_MOOTOOLS'],
				$GLOBALS['TL_JQUERY'],
				$GLOBALS['TL_USER_CSS'],
				$GLOBALS['TL_FRAMEWORK_CSS']
			) = $arrBackup;

			throw $e;
		}
	}
}
