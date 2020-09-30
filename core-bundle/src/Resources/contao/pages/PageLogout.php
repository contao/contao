<?php

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao;

use Contao\CoreBundle\Controller\Page\LogoutPageController;
use Symfony\Component\HttpFoundation\Response;

trigger_deprecation('contao/core-bundle', '4.11', 'Page types are deprecated, use page controllers instead.');

/**
 * Provide methods to handle a logout page.
 *
 * @author Leo Feyer <https://github.com/leofeyer>
 */
class PageLogout extends Frontend
{
	/**
	 * Return a redirect response object
	 *
	 * @param PageModel $objPage
	 *
	 * @return Response
	 */
	public function getResponse($objPage)
	{
		$objRequest = System::getContainer()->get('request_stack')->getCurrentRequest();

		return System::getContainer()->get(LogoutPageController::class)($objPage, $objRequest);
	}
}

class_alias(PageLogout::class, 'PageLogout');
