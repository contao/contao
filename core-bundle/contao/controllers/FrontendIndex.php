<?php

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao;

use Contao\CoreBundle\Controller\Page\RegularPageController;
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
	 *
	 * @deprecated Deprecated since Contao 5.7, to be removed in Contao 6;
	 *             use the AbstractPageController instead.
	 */
	public function renderPage(PageModel $pageModel): Response
	{
		trigger_deprecation('contao/core-bundle', '5.7', 'Using "%s()" is deprecated and will no longer work in Contao 6. Use the AbstractPageController instead.', __METHOD__);

		return System::getContainer()->get(RegularPageController::class)($pageModel);
	}

	/**
	 * @internal
	 */
	public function renderLegacy(PageModel $pageModel): Response
	{
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

		$pageType = $GLOBALS['TL_PTY'][$objPage->type] ?? PageRegular::class;
		$objHandler = new $pageType();

		return $objHandler->getResponse($objPage, true);
	}
}
