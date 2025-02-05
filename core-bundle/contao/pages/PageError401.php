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
use Contao\CoreBundle\Exception\InsufficientAuthenticationException;
use Symfony\Component\HttpFoundation\Response;

/**
 * Provide methods to handle an error 401 page.
 */
class PageError401 extends Frontend
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

		$obj401 = $this->prepare($objRootPage);
		$objPage = $obj401->loadDetails();

		// Reset inherited cache timeouts (see #231)
		if (!$objPage->includeCache)
		{
			$objPage->cache = 0;
			$objPage->clientCache = 0;
		}

		/** @var PageRegular $objHandler */
		$objHandler = new $GLOBALS['TL_PTY']['regular']();

		return $objHandler->getResponse($objPage)->setStatusCode(401);
	}

	/**
	 * Prepare the output
	 *
	 * @param PageModel|null $objRootPage
	 *
	 * @return PageModel
	 *
	 * @throws InsufficientAuthenticationException
	 */
	private function prepare(PageModel|null $objRootPage=null)
	{
		// Use the given root page object if available (thanks to Andreas Schempp)
		if ($objRootPage === null)
		{
			$objRootPage = $this->getRootPageFromUrl();
			$obj401 = PageModel::find401ByPid($objRootPage->id);
		}
		else
		{
			$obj401 = $objRootPage->type === 'error_401' ? $objRootPage : PageModel::find401ByPid($objRootPage->id);
		}

		// Die if there is no page at all
		if (null === $obj401)
		{
			throw new InsufficientAuthenticationException('Not authenticated');
		}

		// Forward to another page
		if ($obj401->autoforward && $obj401->jumpTo)
		{
			$objNextPage = PageModel::findPublishedById($obj401->jumpTo);

			if (null === $objNextPage)
			{
				System::getContainer()->get('monolog.logger.contao.error')->error('Forward page ID "' . $obj401->jumpTo . '" does not exist');

				throw new ForwardPageNotFoundException('Forward page not found');
			}

			// Add the referer so the login module can redirect back
			$url = $objNextPage->getAbsoluteUrl() . '?redirect=' . rawurlencode(Environment::get('uri'));

			$this->redirect(System::getContainer()->get('uri_signer')->sign($url));
		}

		return $obj401;
	}
}
