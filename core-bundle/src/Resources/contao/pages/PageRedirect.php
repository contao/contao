<?php

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao;

use Contao\CoreBundle\InsertTag\InsertTagParser;
use Symfony\Component\HttpFoundation\RedirectResponse;

/**
 * Provide methods to handle a redirect page.
 *
 * @author Leo Feyer <https://github.com/leofeyer>
 * @author Yanick Witschi <https://github.com/Toflar>
 */
class PageRedirect extends Frontend
{
	/**
	 * Redirect to an external page
	 *
	 * @param PageModel $objPage
	 */
	public function generate($objPage)
	{
		$this->redirect(System::getContainer()->get(InsertTagParser::class)->replaceInline($objPage->url), $this->getRedirectStatusCode($objPage));
	}

	/**
	 * Return a response object
	 *
	 * @param PageModel $objPage
	 *
	 * @return RedirectResponse
	 */
	public function getResponse($objPage)
	{
		return new RedirectResponse(System::getContainer()->get(InsertTagParser::class)->replaceInline($objPage->url), $this->getRedirectStatusCode($objPage));
	}

	/**
	 * Return the redirect status code
	 *
	 * @param PageModel $objPage
	 *
	 * @return integer
	 */
	protected function getRedirectStatusCode($objPage)
	{
		return ($objPage->redirect == 'temporary') ? 303 : 301;
	}
}

class_alias(PageRedirect::class, 'PageRedirect');
