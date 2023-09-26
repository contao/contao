<?php

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao;

use Contao\CoreBundle\Exception\ForwardPageNotFoundException;
use Contao\CoreBundle\Exception\PageNotFoundException;
use Contao\CoreBundle\Util\LocaleUtil;
use Symfony\Component\HttpFoundation\Response;

/**
 * Provide methods to handle an error 404 page.
 */
class PageError404 extends Frontend
{
	/**
	 * Generate an error 404 page
	 *
	 * @param PageModel|null $page
	 *
	 * @deprecated Deprecated since Contao 4.9, to be removed in Contao 5; use
	 *             the PageError404::getResponse() method instead
	 */
	public function generate($page=null)
	{
		trigger_deprecation('contao/core-bundle', '4.9', 'Using PageError404::generate() has been deprecated in Contao 4.9 and will be removed in Contao 5.0. Use the PageError404::getResponse() method instead.');

		/** @var PageModel $objPage */
		global $objPage;

		$obj404 = $this->prepare($page);
		$objPage = $obj404->loadDetails();

		// Reset inherited cache timeouts (see #231)
		if (!$objPage->includeCache)
		{
			$objPage->cache = 0;
			$objPage->clientCache = 0;
		}

		/** @var PageRegular $objHandler */
		$objHandler = new $GLOBALS['TL_PTY']['regular']();

		header('HTTP/1.1 404 Not Found');
		$objHandler->generate($objPage);
	}

	/**
	 * Return a response object
	 *
	 * @param PageModel|null $page
	 *
	 * @return Response
	 */
	public function getResponse($page=null)
	{
		/** @var PageModel $objPage */
		global $objPage;

		$obj404 = $this->prepare($page);
		$objPage = $obj404->loadDetails();

		// Reset inherited cache timeouts (see #231)
		if (!$objPage->includeCache)
		{
			$objPage->cache = 0;
			$objPage->clientCache = 0;
		}

		/** @var PageRegular $objHandler */
		$objHandler = new $GLOBALS['TL_PTY']['regular']();

		return $objHandler->getResponse($objPage)->setStatusCode(404);
	}

	/**
	 * Prepare the output
	 *
	 * @param PageModel|null $page
	 *
	 * @return PageModel
	 *
	 * @internal Do not call this method in your code. It will be made private in Contao 5.0.
	 */
	protected function prepare($page=null)
	{
		$obj404 = null;

		if ($page instanceof PageModel && $page->type === 'error_404')
		{
			// We don't actually need a root page, we just need the inherited properties to redirect a 404
			$obj404 = $objRootPage = $page->loadDetails();
		}
		else
		{
			$objRootPage = $this->getRootPageFromUrl();
		}

		// Forward if the language should be but is not set (see #4028)
		if ($objRootPage->urlPrefix && System::getContainer()->getParameter('contao.legacy_routing'))
		{
			// Get the request string without the script name
			$strRequest = Environment::get('relativeRequest');

			// Only redirect if there is no language fragment (see #4669)
			if ($strRequest && !preg_match('@^[a-z]{2}(-[A-Z]{2})?/@', $strRequest))
			{
				// Handle language fragments without trailing slash (see #7666)
				if (preg_match('@^[a-z]{2}(-[A-Z]{2})?$@', $strRequest))
				{
					$this->redirect(Environment::get('request') . '/', 301);
				}
				else
				{
					if ($strRequest == Environment::get('request'))
					{
						$strRequest = LocaleUtil::formatAsLanguageTag($objRootPage->language) . '/' . $strRequest;
					}
					else
					{
						$strRequest = Environment::get('script') . '/' . LocaleUtil::formatAsLanguageTag($objRootPage->language) . '/' . $strRequest;
					}

					$this->redirect($strRequest);
				}
			}
		}

		// Look for a 404 page
		if (null === $obj404)
		{
			$obj404 = PageModel::find404ByPid($objRootPage->id);
		}

		// Die if there is no page at all
		if (null === $obj404)
		{
			throw new PageNotFoundException('Page not found: ' . Environment::get('uri'));
		}

		// Forward to another page
		if ($obj404->autoforward && $obj404->jumpTo)
		{
			$objNextPage = PageModel::findPublishedById($obj404->jumpTo);

			if (null === $objNextPage)
			{
				System::getContainer()->get('monolog.logger.contao.error')->error('Forward page ID "' . $obj404->jumpTo . '" does not exist');

				throw new ForwardPageNotFoundException('Forward page not found');
			}

			$this->redirect($objNextPage->getFrontendUrl());
		}

		return $obj404;
	}
}

class_alias(PageError404::class, 'PageError404');
