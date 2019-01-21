<?php

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao;

use Contao\CoreBundle\Exception\NoActivePageFoundException;
use Symfony\Component\HttpFoundation\RedirectResponse;

/**
 * Provide methods to handle a website root page.
 *
 * @author Leo Feyer <https://github.com/leofeyer>
 * @author Yanick Witschi <https://github.com/Toflar>
 */
class PageRoot extends Frontend
{

	/**
	 * Redirect to the first active regular page
	 *
	 * @param integer $rootPageId
	 * @param boolean $blnReturn
	 * @param boolean $blnPreferAlias
	 *
	 * @return integer
	 */
	public function generate($rootPageId, $blnReturn=false, $blnPreferAlias=false)
	{
		if (!$blnReturn)
		{
			$this->redirect($this->getRedirectUrl($rootPageId), 302);
		}

		$objNextPage = $this->getNextPage($rootPageId);

		return ($blnPreferAlias && $objNextPage->alias != '') ? $objNextPage->alias : $objNextPage->id;
	}

	/**
	 * Return a response object
	 *
	 * @param integer $rootPageId
	 *
	 * @return RedirectResponse
	 */
	public function getResponse($rootPageId)
	{
		return new RedirectResponse($this->getRedirectUrl($rootPageId), 303);
	}

	/**
	 * Prepare the page object and redirect URL
	 *
	 * @param integer $rootPageId
	 *
	 * @return PageModel
	 *
	 * @throws NoActivePageFoundException
	 */
	protected function getNextPage($rootPageId)
	{
		$objNextPage = PageModel::findFirstPublishedByPid($rootPageId);

		// No published pages yet
		if (null === $objNextPage)
		{
			$this->log('No active page found under root page "' . $rootPageId . '")', __METHOD__, TL_ERROR);
			throw new NoActivePageFoundException('No active page found under root page.');
		}

		return $objNextPage;
	}

	/**
	 * Prepare the page object and redirect URL
	 *
	 * @param integer $rootPageId
	 *
	 * @return string
	 */
	protected function getRedirectUrl($rootPageId)
	{
		return $this->getNextPage($rootPageId)->getFrontendUrl();
	}
}

class_alias(PageRoot::class, 'PageRoot');
