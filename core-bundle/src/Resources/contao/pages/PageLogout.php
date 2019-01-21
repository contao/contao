<?php

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao;

use League\Uri\Components\Query;
use League\Uri\Http;
use Symfony\Component\HttpFoundation\RedirectResponse;

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
	 * @return RedirectResponse
	 */
	public function getResponse($objPage)
	{
		// Set last page visited
		if ($objPage->redirectBack)
		{
			$_SESSION['LAST_PAGE_VISITED'] = $this->getReferer();
		}

		$strLogoutUrl = System::getContainer()->get('security.logout_url_generator')->getLogoutUrl();
		$strRedirect = Environment::get('base');

		// Redirect to last page visited
		if ($objPage->redirectBack && !empty($_SESSION['LAST_PAGE_VISITED']))
		{
			$strRedirect = $_SESSION['LAST_PAGE_VISITED'];
		}

		// Redirect to jumpTo page
		elseif ($objPage->jumpTo && ($objTarget = $objPage->getRelated('jumpTo')) instanceof PageModel)
		{
			/** @var PageModel $objTarget */
			$strRedirect = $objTarget->getAbsoluteUrl();
		}

		$uri = Http::createFromString($strLogoutUrl);

		// Add the redirect= parameter to the logout URL
		$query = new Query($uri->getQuery());
		$query = $query->merge('redirect=' . $strRedirect);

		return new RedirectResponse((string) $uri->withQuery((string) $query));
	}
}

class_alias(PageLogout::class, 'PageLogout');
