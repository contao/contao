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
use Symfony\Component\HttpFoundation\Response;

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
		$strLogoutUrl = System::getContainer()->get('security.logout_url_generator')->getLogoutUrl();
		$strRedirect = Environment::get('base');

		// Redirect to last page visited
		if ($objPage->redirectBack && ($strReferer = $this->getReferer()))
		{
			$strRedirect = $strReferer;
		}

		// Redirect to jumpTo page
		elseif (($objTarget = $objPage->getRelated('jumpTo')) instanceof PageModel)
		{
			/** @var PageModel $objTarget */
			$strRedirect = $objTarget->getAbsoluteUrl();
		}

		$uri = Http::createFromString($strLogoutUrl);

		// Add the redirect= parameter to the logout URL
		$query = new Query($uri->getQuery());
		$query = $query->merge('redirect=' . $strRedirect);

		return new RedirectResponse((string) $uri->withQuery((string) $query), Response::HTTP_TEMPORARY_REDIRECT);
	}
}

class_alias(PageLogout::class, 'PageLogout');
