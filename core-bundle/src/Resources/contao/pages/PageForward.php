<?php

/**
 * Contao Open Source CMS
 *
 * Copyright (c) 2005-2015 Leo Feyer
 *
 * @license LGPL-3.0+
 */

namespace Contao;

use Contao\CoreBundle\Exception\ForwardPageNotFoundHttpException;


/**
 * Provide methods to handle a forward page.
 *
 * @author Leo Feyer <https://github.com/leofeyer>
 */
class PageForward extends \Frontend
{

	/**
	 * Redirect to an internal page
     *
	 * @param \PageModel $objPage
	 */
	public function generate($objPage)
	{
		// Forward to the jumpTo or first published page
		if ($objPage->jumpTo)
		{
			/** @var \PageModel $objNextPage */
			$objNextPage = $objPage->getRelated('jumpTo');
		}
		else
		{
			$objNextPage = \PageModel::findFirstPublishedRegularByPid($objPage->id);
		}

		// Forward page does not exist
		if ($objNextPage === null)
		{
			$this->log('Forward page ID "' . $objPage->jumpTo . '" does not exist', __METHOD__, TL_ERROR);
			throw new ForwardPageNotFoundHttpException('Forward page not found');
		}

		$strForceLang = null;

		// Check the target page language (see #4706)
		if (\Config::get('addLanguageToUrl'))
		{
			$objNextPage->loadDetails(); // see #3983
			$strForceLang = $objNextPage->language;
		}

		$strGet = '';
		$strQuery = \Environment::get('queryString');
		$arrQuery = array();

		// Extract the query string keys (see #5867)
		if ($strQuery != '')
		{
			$arrChunks = explode('&', $strQuery);

			foreach ($arrChunks as $strChunk)
			{
				list($k,) = explode('=', $strChunk, 2);
				$arrQuery[] = $k;
			}
		}

		// Add $_GET parameters
		if (!empty($_GET))
		{
			foreach (array_keys($_GET) as $key)
			{
				if (\Config::get('addLanguageToUrl') && $key == 'language')
				{
					continue;
				}

				// Ignore the query string parameters (see #5867)
				if (in_array($key, $arrQuery))
				{
					continue;
				}

				// Ignore the auto_item parameter (see #5886)
				if ($key == 'auto_item')
				{
					$strGet .= '/' . \Input::get($key);
				}
				else
				{
					$strGet .= '/' . $key . '/' . \Input::get($key);
				}
			}
		}

		// Append the query string (see #5867)
		if ($strQuery != '')
		{
			$strQuery = '?' . $strQuery;
		}

		$this->redirect($this->generateFrontendUrl($objNextPage->row(), $strGet, $strForceLang) . $strQuery, (($objPage->redirect == 'temporary') ? 302 : 301));
	}
}
