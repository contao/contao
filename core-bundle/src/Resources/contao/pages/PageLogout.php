<?php

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao;

use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Provide methods to handle a logout page.
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

		$container = System::getContainer();
		$token = $container->get('security.helper')->getToken();

		// Redirect immediately if there is no logged-in user (see #2388)
		if ($token === null)
		{
			return new RedirectResponse($strRedirect);
		}

		$pairs = array();
		$strLogoutUrl = $container->get('security.logout_url_generator')->getLogoutUrl();
		$request = Request::create($strLogoutUrl);

		if ($request->server->has('QUERY_STRING'))
		{
			parse_str($request->server->get('QUERY_STRING'), $pairs);
		}

		// Add the redirect= parameter to the logout URL
		$pairs['redirect'] = $strRedirect;

		$uri = $request->getSchemeAndHttpHost() . $request->getBaseUrl() . $request->getPathInfo() . '?' . http_build_query($pairs, '', '&', PHP_QUERY_RFC3986);

		return new RedirectResponse($uri, Response::HTTP_TEMPORARY_REDIRECT);
	}
}
