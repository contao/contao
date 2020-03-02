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
 *
 * @author Leo Feyer <https://github.com/leofeyer>
 */
class PageError404 extends Frontend
{
	/**
	 * Generate an error 404 page
	 */
	public function generate()
	{
		/** @var PageModel $objPage */
		global $objPage;

		$obj404 = $this->prepare();
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
	 * @return Response
	 */
	public function getResponse()
	{
		/** @var PageModel $objPage */
		global $objPage;

		$obj404 = $this->prepare();
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
	 * @return PageModel
	 *
	 * @internal Do not call this method in your code. It will be made private in Contao 5.0.
	 */
	protected function prepare()
	{
		// Find the matching root page
		$objRootPage = $this->getRootPageFromUrl();

		// Look for a 404 page
		$obj404 = PageModel::find404ByPid($objRootPage->id);

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
				$this->log('Forward page ID "' . $obj404->jumpTo . '" does not exist', __METHOD__, TL_ERROR);

				throw new ForwardPageNotFoundException('Forward page not found');
			}

			$this->redirect($objNextPage->getFrontendUrl(), (($obj404->redirect == 'temporary') ? 302 : 301));
		}

		return $obj404;
	}
}

class_alias(PageError404::class, 'PageError404');
