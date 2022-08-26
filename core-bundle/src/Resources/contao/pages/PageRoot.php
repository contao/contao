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

trigger_deprecation('contao/core-bundle', '4.12', 'Using the "Contao\PageRoot" class been deprecated and will no longer work in Contao 5.0. Use Symfony routing instead.');

/**
 * Provide methods to handle a website root page.
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
	 *
	 * @deprecated Deprecated since Contao 4.9, to be removed in Contao 5; use
	 *             the PageRoot::getResponse() method instead
	 */
	public function generate($rootPageId, $blnReturn=false, $blnPreferAlias=false)
	{
		trigger_deprecation('contao/core-bundle', '4.9', 'Using PageRoot::generate() has been deprecated in Contao 4.9 and will be removed in Contao 5.0. Use the PageRoot::getResponse() method instead.');

		if (!$blnReturn)
		{
			$this->redirect($this->getRedirectUrl($rootPageId));
		}

		$objNextPage = $this->getNextPage($rootPageId);

		return ($blnPreferAlias && $objNextPage->alias) ? $objNextPage->alias : $objNextPage->id;
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
		return new RedirectResponse($this->getRedirectUrl($rootPageId));
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
			System::getContainer()->get('monolog.logger.contao.error')->error('No active page found under root page "' . $rootPageId . '"');

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
