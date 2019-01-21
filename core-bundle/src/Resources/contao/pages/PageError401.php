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
 *
 * @author Leo Feyer <https://github.com/leofeyer>
 */
class PageError401 extends Frontend
{

	/**
	 * Generate an error 401 page
	 *
	 * @param PageModel|integer $objRootPage
	 */
	public function generate($objRootPage=null)
	{
		/** @var PageModel $objPage */
		global $objPage;

		$obj401 = $this->prepare($objRootPage);
		$objPage = $obj401->loadDetails();

		/** @var PageRegular $objHandler */
		$objHandler = new $GLOBALS['TL_PTY']['regular']();

		header('HTTP/1.1 401 Unauthorized');
		$objHandler->generate($objPage);
	}

	/**
	 * Return a response object
	 *
	 * @param PageModel|integer $objRootPage
	 *
	 * @return Response
	 */
	public function getResponse($objRootPage=null)
	{
		/** @var PageModel $objPage */
		global $objPage;

		$obj401 = $this->prepare($objRootPage);
		$objPage = $obj401->loadDetails();

		/** @var PageRegular $objHandler */
		$objHandler = new $GLOBALS['TL_PTY']['regular']();

		return $objHandler->getResponse($objPage)->setStatusCode(401);
	}

	/**
	 * Prepare the output
	 *
	 * @param PageModel|integer $objRootPage
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
		}
		else
		{
			$objRootPage = PageModel::findPublishedById(\is_int($objRootPage) ? $objRootPage : $objRootPage->id);
		}

		// Look for a 401 page
		$obj401 = PageModel::find401ByPid($objRootPage->id);

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
				$this->log('Forward page ID "' . $obj401->jumpTo . '" does not exist', __METHOD__, TL_ERROR);
				throw new ForwardPageNotFoundException('Forward page not found');
			}

			$this->redirect($objNextPage->getFrontendUrl(), (($obj401->redirect == 'temporary') ? 302 : 301));
		}

		return $obj401;
	}
}

class_alias(PageError401::class, 'PageError401');
