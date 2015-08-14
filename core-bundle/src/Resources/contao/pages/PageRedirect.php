<?php

/**
 * Contao Open Source CMS
 *
 * Copyright (c) 2005-2015 Leo Feyer
 *
 * @license LGPL-3.0+
 */

namespace Contao;

use Symfony\Component\HttpFoundation\RedirectResponse;


/**
 * Provide methods to handle a redirect page.
 *
 * @author Leo Feyer <https://github.com/leofeyer>
 * @author Yanick Witschi <https://github.com/Toflar>
 */
class PageRedirect extends \Frontend
{

	/**
	 * Redirect to an external page
	 *
	 * @param \PageModel $objPage
	 */
	public function generate($objPage)
	{
		$this->redirect($this->replaceInsertTags($objPage->url, false), $this->getRedirectStatusCode($objPage));
	}

	/**
	 * Return a response object
	 *
	 * @param \PageModel $objPage
	 *
	 * @return RedirectResponse
	 */
	public function getResponse($objPage)
	{
		return new RedirectResponse($this->replaceInsertTags($objPage->url, false), $this->getRedirectStatusCode($objPage));
	}

	/**
	 * Return the redirect status code
	 *
	 * @param \PageModel $objPage
	 *
	 * @return integer
	 */
	protected function getRedirectStatusCode($objPage)
	{
		return ($objPage->redirect == 'temporary') ? 302 : 301;
	}
}
