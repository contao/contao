<?php

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao;

use Contao\CoreBundle\Controller\Page\RedirectPageController;
use Contao\CoreBundle\Util\UrlUtil;
use Symfony\Component\HttpFoundation\RedirectResponse;

trigger_deprecation('contao/core-bundle', '5.3', 'Using the "%s" class has been deprecated and will no longer work in Contao 6.0. Use "%s" instead.', PageRedirect::class, RedirectPageController::class);

/**
 * Provide methods to handle a redirect page.
 */
class PageRedirect extends Frontend
{
	/**
	 * Return a response object
	 *
	 * @param PageModel $objPage
	 *
	 * @return RedirectResponse
	 */
	public function getResponse($objPage)
	{
		$this->prepare($objPage);

		$url = System::getContainer()->get('contao.insert_tag.parser')->replaceInline($objPage->url);
		$url = UrlUtil::makeAbsolute($url, Environment::get('base'));

		return new RedirectResponse($url, $this->getRedirectStatusCode($objPage));
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

	/**
	 * @param PageModel $objPage
	 */
	private function prepare($objPage)
	{
		// Deprecated since Contao 4.0, to be removed in Contao 6.0
		$GLOBALS['TL_LANGUAGE'] = $objPage->language;

		$locale = str_replace('-', '_', $objPage->language);

		$container = System::getContainer();
		$container->get('request_stack')->getCurrentRequest()->setLocale($locale);
		$container->get('translator')->setLocale($locale);
	}
}
