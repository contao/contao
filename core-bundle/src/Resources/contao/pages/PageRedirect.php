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

/**
 * Provide methods to handle a redirect page.
 */
class PageRedirect extends Frontend
{
	/**
	 * Redirect to an external page
	 *
	 * @param PageModel $objPage
	 *
	 * @deprecated Deprecated since Contao 4.9, to be removed in Contao 5; use
	 *             the PageRedirect::getResponse() method instead
	 */
	public function generate($objPage)
	{
		trigger_deprecation('contao/core-bundle', '4.9', 'Using PageRedirect::generate() has been deprecated in Contao 4.9 and will be removed in Contao 5.0. Use the PageRedirect::getResponse() method instead.');

		$this->prepare($objPage);

		$this->redirect(System::getContainer()->get('contao.insert_tag.parser')->replaceInline($objPage->url), $this->getRedirectStatusCode($objPage));
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
		$this->prepare($objPage);

		return new RedirectResponse(System::getContainer()->get('contao.insert_tag.parser')->replaceInline($objPage->url), $this->getRedirectStatusCode($objPage));
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
		$GLOBALS['TL_LANGUAGE'] = $objPage->language;

		$locale = str_replace('-', '_', $objPage->language);

		$container = System::getContainer();
		$container->get('request_stack')->getCurrentRequest()->setLocale($locale);
		$container->get('translator')->setLocale($locale);
	}
}

class_alias(PageRedirect::class, 'PageRedirect');
