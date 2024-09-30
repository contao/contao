<?php

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao;

use Contao\CoreBundle\Exception\AccessDeniedException;
use Contao\CoreBundle\Exception\ForwardPageNotFoundException;
use Symfony\Component\HttpFoundation\Response;

/**
 * Provide methods to handle an error 403 page.
 */
class PageError403 extends Frontend
{
	/**
	 * Return a response object
	 *
	 * @param PageModel|null $objRootPage
	 *
	 * @return Response
	 */
	public function getResponse(PageModel|null $objRootPage=null)
	{
		global $objPage;

		$obj403 = $this->prepare($objRootPage);
		$objPage = $obj403->loadDetails();

		// Reset inherited cache timeouts (see #231)
		if (!$objPage->includeCache)
		{
			$objPage->cache = 0;
			$objPage->clientCache = 0;
		}

		/** @var PageRegular $objHandler */
		$objHandler = new $GLOBALS['TL_PTY']['regular']();

		return $objHandler->getResponse($objPage)->setStatusCode(403);
	}

	/**
	 * Prepare the output
	 *
	 * @param PageModel|null $objRootPage
	 *
	 * @return PageModel
	 *
	 * @throws AccessDeniedException
	 */
	private function prepare(PageModel|null $objRootPage=null)
	{
		// Use the given root page object if available (thanks to Andreas Schempp)
		if ($objRootPage === null)
		{
			$objRootPage = $this->getRootPageFromUrl();
			$obj403 = PageModel::find403ByPid($objRootPage->id);
		}
		else
		{
			$obj403 = $objRootPage->type === 'error_403' ? $objRootPage : PageModel::find403ByPid($objRootPage->id);
		}

		// Die if there is no page at all
		if (null === $obj403)
		{
			throw new AccessDeniedException('Forbidden');
		}

		// Forward to another page
		if ($obj403->autoforward && $obj403->jumpTo)
		{
			$objNextPage = PageModel::findPublishedById($obj403->jumpTo);

			if (null === $objNextPage)
			{
				System::getContainer()->get('monolog.logger.contao.error')->error('Forward page ID "' . $obj403->jumpTo . '" does not exist');

				throw new ForwardPageNotFoundException('Forward page not found');
			}

			$this->redirect($objNextPage->getFrontendUrl());
		}

		return $obj403;
	}
}
