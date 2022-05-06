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
	 * Generate an error 401 page
	 *
	 * @param PageModel|integer|null $objRootPage
	 *
	 * @deprecated Deprecated since Contao 4.9, to be removed in Contao 5; use
	 *             the PageError401::getResponse() method instead
	 */
	public function generate($objRootPage=null)
	{
		trigger_deprecation('contao/core-bundle', '4.19', 'Using PageError401::generate() has been deprecated in Contao 4.9 and will be removed in Contao 5.0. Use the PageError401::getResponse() method instead.');

		if (is_numeric($objRootPage))
		{
			trigger_deprecation('contao/core-bundle', '4.13', 'Passing a numeric ID to PageError401::generate() has been deprecated and will no longer work in Contao 5.0.');
		}

		/** @var PageModel $objPage */
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

		header('HTTP/1.1 401 Unauthorized');
		$objHandler->generate($objPage);
	}

	/**
	 * Return a response object
	 *
	 * @param PageModel|integer|null $objRootPage
	 *
	 * @return Response
	 */
	public function getResponse($objRootPage=null)
	{
		if (is_numeric($objRootPage))
		{
			trigger_deprecation('contao/core-bundle', '4.13', 'Passing a numeric ID to PageError401::getResponse() has been deprecated and will no longer work in Contao 5.0.');
		}

		/** @var PageModel $objPage */
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
	 * @param PageModel|integer|null $objRootPage
	 *
	 * @return PageModel
	 *
	 * @throws InsufficientAuthenticationException
	 *
	 * @internal Do not call this method in your code. It will be made private in Contao 5.0.
	 */
	protected function prepare($objRootPage=null)
	{
		// Use the given root page object if available (thanks to Andreas Schempp)
		if ($objRootPage === null)
		{
			$objRootPage = $this->getRootPageFromUrl();
			$obj401 = PageModel::find401ByPid($objRootPage->id);
		}
		elseif ($objRootPage instanceof PageModel)
		{
			$obj401 = $objRootPage->type === 'error_401' ? $objRootPage : PageModel::find401ByPid($objRootPage->id);
		}
		else
		{
			$obj401 = PageModel::find401ByPid(is_numeric($objRootPage) ? $objRootPage : $objRootPage->id);
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
			$url = $objNextPage->getAbsoluteUrl() . '?redirect=' . rawurlencode(Environment::get('base') . Environment::get('request'));

			$this->redirect(System::getContainer()->get('uri_signer')->sign($url));
		}

		return $obj401;
	}
}

class_alias(PageError401::class, 'PageError401');
