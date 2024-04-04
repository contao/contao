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
use Symfony\Component\HttpFoundation\Response;

/**
 * Provide methods to handle an error 404 page.
 */
class PageError404 extends Frontend
{
	/**
	 * Return a response object
	 *
	 * @param PageModel|null $page
	 *
	 * @return Response
	 */
	public function getResponse($page=null)
	{
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
	 */
	private function prepare($page=null)
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
