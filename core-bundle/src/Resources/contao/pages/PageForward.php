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
use Symfony\Component\HttpFoundation\RedirectResponse;

/**
 * Provide methods to handle a forward page.
 *
 * @author Leo Feyer <https://github.com/leofeyer>
 * @author Yanick Witschi <https://github.com/Toflar>
 */
class PageForward extends Frontend
{

	/**
	 * Redirect to an internal page
	 *
	 * @param PageModel $objPage
	 */
	public function generate($objPage)
	{
		$this->redirect($this->getForwardUrl($objPage), $this->getRedirectStatusCode($objPage));
	}

	/**
	 * Return a response object
	 *
	 * @param PageModel $objPage
	 *
	 * @return RedirectResponse
	 */
	public function getResponse($objPage)
	{
		return new RedirectResponse($this->getForwardUrl($objPage), $this->getRedirectStatusCode($objPage));
	}

	/**
	 * Return the URL to the jumpTo or first published page
	 *
	 * @param PageModel $objPage
	 *
	 * @return string
	 *
	 * @throws ForwardPageNotFoundException
	 */
	protected function getForwardUrl($objPage)
	{
		if ($objPage->jumpTo)
		{
			/** @var PageModel $objNextPage */
			$objNextPage = $objPage->getRelated('jumpTo');
		}
		else
		{
			$objNextPage = PageModel::findFirstPublishedRegularByPid($objPage->id);
		}

		// Forward page does not exist
		if (!$objNextPage instanceof PageModel)
		{
			$this->log('Forward page ID "' . $objPage->jumpTo . '" does not exist', __METHOD__, TL_ERROR);
			throw new ForwardPageNotFoundException('Forward page not found');
		}

		$strGet = '';
		$strQuery = Environment::get('queryString');
		$arrQuery = array();

		// Extract the query string keys (see #5867)
		if ($strQuery != '')
		{
			$arrChunks = explode('&', $strQuery);

			foreach ($arrChunks as $strChunk)
			{
				list($k) = explode('=', $strChunk, 2);
				$arrQuery[] = $k;
			}
		}

		// Add $_GET parameters
		if (!empty($_GET))
		{
			foreach (array_keys($_GET) as $key)
			{
				if (Config::get('addLanguageToUrl') && $key == 'language')
				{
					continue;
				}

				// Ignore the query string parameters (see #5867)
				if (\in_array($key, $arrQuery))
				{
					continue;
				}

				// Ignore the auto_item parameter (see #5886)
				if ($key == 'auto_item')
				{
					$strGet .= '/' . Input::get($key);
				}
				else
				{
					$strGet .= '/' . $key . '/' . Input::get($key);
				}
			}
		}

		// Append the query string (see #5867)
		if ($strQuery != '')
		{
			$strQuery = '?' . $strQuery;
		}

		return $objNextPage->getAbsoluteUrl($strGet) . $strQuery;
	}

	/**
	 * Return the redirect status code
	 *
	 * @param PageModel $objPage
	 *
	 * @return integer
	 */
	protected function getRedirectStatusCode($objPage)
	{
		return ($objPage->redirect == 'temporary') ? 302 : 301;
	}
}

class_alias(PageForward::class, 'PageForward');
