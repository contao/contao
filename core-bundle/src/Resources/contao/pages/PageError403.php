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
 *
 * @author Leo Feyer <https://github.com/leofeyer>
 */
class PageError403 extends Frontend
{

	/**
	 * Generate an error 403 page
	 *
	 * @param PageModel|integer $objRootPage
	 */
	public function generate($objRootPage=null)
	{
		/** @var PageModel $objPage */
		global $objPage;

		$obj403 = $this->prepare($objRootPage);
		$objPage = $obj403->loadDetails();

		/** @var PageRegular $objHandler */
		$objHandler = new $GLOBALS['TL_PTY']['regular']();

		header('HTTP/1.1 403 Forbidden');
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

		$obj403 = $this->prepare($objRootPage);
		$objPage = $obj403->loadDetails();

		/** @var PageRegular $objHandler */
		$objHandler = new $GLOBALS['TL_PTY']['regular']();

		return $objHandler->getResponse($objPage)->setStatusCode(403);
	}

	/**
	 * Prepare the output
	 *
	 * @param PageModel|integer $objRootPage
	 *
	 * @return PageModel
	 *
	 * @throws AccessDeniedException
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

		// Look for a 403 page
		$obj403 = PageModel::find403ByPid($objRootPage->id);

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
				$this->log('Forward page ID "' . $obj403->jumpTo . '" does not exist', __METHOD__, TL_ERROR);
				throw new ForwardPageNotFoundException('Forward page not found');
			}

			$this->redirect($objNextPage->getFrontendUrl(), (($obj403->redirect == 'temporary') ? 302 : 301));
		}

		return $obj403;
	}
}

class_alias(PageError403::class, 'PageError403');
