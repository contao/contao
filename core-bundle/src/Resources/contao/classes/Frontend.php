<?php

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao;

use Contao\CoreBundle\Exception\NoRootPageFoundException;
use Contao\CoreBundle\Search\Document;
use Contao\CoreBundle\Util\LocaleUtil;
use Symfony\Cmf\Component\Routing\RouteObjectInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Provide methods to manage front end controllers.
 */
abstract class Frontend extends Controller
{
	/**
	 * Meta array
	 * @var array
	 */
	protected $arrMeta = array();

	/**
	 * Aux array
	 * @var array
	 */
	protected $arrAux = array();

	/**
	 * Processed files array
	 * @var array
	 */
	protected $arrProcessed = array();

	/**
	 * Load the database object
	 *
	 * Make the constructor public, so pages can be instantiated (see #6182)
	 */
	public function __construct()
	{
		parent::__construct();
		$this->import(Database::class, 'Database');
	}

	/**
	 * Try to find a root page based on language and URL
	 *
	 * @return PageModel
	 */
	public static function getRootPageFromUrl()
	{
		$objRequest = System::getContainer()->get('request_stack')->getCurrentRequest();

		if ($objRequest instanceof Request)
		{
			$objPage = $objRequest->attributes->get('pageModel');

			if ($objPage instanceof PageModel)
			{
				$objPage->loadDetails();

				return PageModel::findByPk($objPage->rootId);
			}
		}

		throw new NoRootPageFoundException('No root page found');
	}

	/**
	 * Overwrite the parent method as front end URLs are handled differently
	 *
	 * @param string  $strRequest
	 * @param boolean $blnIgnoreParams
	 * @param array   $arrUnset
	 *
	 * @return string
	 */
	public static function addToUrl($strRequest, $blnIgnoreParams=false, $arrUnset=array())
	{
		/** @var PageModel $objPage */
		global $objPage;

		$arrGet = $blnIgnoreParams ? array() : $_GET;

		// Clean the $_GET values (thanks to thyon)
		foreach (array_keys($arrGet) as $key)
		{
			$arrGet[$key] = Input::get($key, true, true);
		}

		$arrFragments = preg_split('/&(amp;)?/i', $strRequest);

		// Merge the new request string
		foreach ($arrFragments as $strFragment)
		{
			list($key, $value) = explode('=', $strFragment);

			if (!$value)
			{
				unset($arrGet[$key]);
			}
			else
			{
				$arrGet[$key] = $value;
			}
		}

		// Unset the language parameter
		if ($objPage->urlPrefix)
		{
			unset($arrGet['language']);
		}

		$strParams    = '';
		$strConnector = '/';
		$strSeparator = '/';

		// Compile the parameters string
		foreach ($arrGet as $k=>$v)
		{
			if ($k == 'auto_item')
			{
				$strParams = $strConnector . urlencode($v) . $strParams;
			}
			else
			{
				$strParams .= $strConnector . urlencode($k) . $strSeparator . urlencode($v);
			}
		}

		$strUrl = System::getContainer()->get('router')->generate(RouteObjectInterface::OBJECT_BASED_ROUTE_NAME, array(RouteObjectInterface::CONTENT_OBJECT => $objPage, 'parameters' => $strParams));
		$strUrl = substr($strUrl, \strlen(Environment::get('path')) + 1);

		return $strUrl;
	}

	/**
	 * Redirect to a jumpTo page or reload the current page
	 *
	 * @param integer|array $intId
	 * @param string        $strParams
	 * @param string        $strForceLang
	 */
	protected function jumpToOrReload($intId, $strParams=null, $strForceLang=null)
	{
		if ($strForceLang !== null)
		{
			trigger_deprecation('contao/core-bundle', '4.0', 'Using "Contao\Frontend::jumpToOrReload()" with $strForceLang has been deprecated and will no longer work in Contao 5.0.');
		}

		/** @var PageModel $objPage */
		global $objPage;

		// Always redirect if there are additional arguments (see #5734)
		$blnForceRedirect = ($strParams !== null || $strForceLang !== null);

		if (\is_array($intId))
		{
			$intId = $intId['id'] ?? 0;
		}

		if ($intId > 0 && ($intId != $objPage->id || $blnForceRedirect) && ($objNextPage = PageModel::findPublishedById($intId)) !== null)
		{
			$this->redirect($objNextPage->getFrontendUrl($strParams, $strForceLang));
		}

		$this->reload();
	}

	/**
	 * Check whether a back end or front end user is logged in
	 *
	 * @param string $strCookie
	 *
	 * @return boolean
	 *
	 * @deprecated Deprecated since Contao 4.5, to be removed in Contao 5.0.
	 *             Use Symfony security instead.
	 */
	protected function getLoginStatus($strCookie)
	{
		trigger_deprecation('contao/core-bundle', '4.5', 'Using "Contao\Frontend::getLoginStatus()" has been deprecated and will no longer work in Contao 5.0. Use Symfony security instead.');

		$objTokenChecker = System::getContainer()->get('contao.security.token_checker');

		if ($strCookie == 'BE_USER_AUTH' && $objTokenChecker->hasBackendUser())
		{
			// Always return false if we are not in preview mode (show hidden elements)
			if (TL_MODE == 'FE' && !$objTokenChecker->isPreviewMode())
			{
				return false;
			}

			return true;
		}

		if ($strCookie == 'FE_USER_AUTH' && $objTokenChecker->hasFrontendUser())
		{
			return true;
		}

		return false;
	}

	/**
	 * Get the metadata from a serialized string
	 *
	 * @param string $strData
	 * @param string $strLanguage
	 *
	 * @return array
	 */
	public static function getMetaData($strData, $strLanguage)
	{
		if (empty($strLanguage))
		{
			return array();
		}

		$arrData = StringUtil::deserialize($strData);

		// Convert the language to a locale (see #5678)
		$strLanguage = LocaleUtil::formatAsLocale($strLanguage);

		if (!\is_array($arrData) || !isset($arrData[$strLanguage]))
		{
			return array();
		}

		return $arrData[$strLanguage];
	}

	/**
	 * Prepare a text to be used in the meta description tag
	 *
	 * @param string $strText
	 *
	 * @return string
	 *
	 * @deprecated Deprecated since Contao 4.12, to be removed in Contao 5.0;
	 *             use the Contao\CoreBundle\String\HtmlDecoder service instead
	 */
	protected function prepareMetaDescription($strText)
	{
		trigger_deprecation('contao/core-bundle', '4.12', 'Using "Contao\Frontend::prepareMetaDescription()" has been deprecated and will no longer work Contao 5.0. Use the "Contao\CoreBundle\String\HtmlDecoder" service instead.');

		$strText = System::getContainer()->get('contao.insert_tag.parser')->replaceInline((string) $strText);
		$strText = strip_tags($strText);
		$strText = str_replace("\n", ' ', $strText);
		$strText = StringUtil::substr($strText, 320);

		return trim($strText);
	}

	/**
	 * Return the cron timeout in seconds
	 *
	 * @return integer
	 */
	public static function getCronTimeout()
	{
		if (!empty($GLOBALS['TL_CRON']['minutely']))
		{
			return 60;
		}

		if (!empty($GLOBALS['TL_CRON']['hourly']))
		{
			return 3600;
		}

		return 86400; // daily
	}

	/**
	 * Index a page if applicable
	 *
	 * @param Response $response
	 *
	 * @deprecated Deprecated since Contao 4.9, to be removed in Contao 5.0.
	 *             Use the "contao.search.indexer" service instead.
	 */
	public static function indexPageIfApplicable(Response $response)
	{
		trigger_deprecation('contao/core-bundle', '4.9', 'Using "Contao\Frontend::indexPageIfApplicable()" has been deprecated and will no longer work in Contao 5.0. Use the "contao.search.indexer" service instead.');

		$searchIndexer = System::getContainer()->get('contao.search.indexer');

		// The search indexer is disabled
		if ($searchIndexer === null)
		{
			return;
		}

		$request = System::getContainer()->get('request_stack')->getCurrentRequest();

		if ($request === null)
		{
			throw new \RuntimeException('The request stack did not contain a request');
		}

		$document = Document::createFromRequestResponse($request, $response);

		$searchIndexer->index($document);
	}

	/**
	 * Check whether there is a cached version of the page and return a response object
	 *
	 * @return Response|null
	 *
	 * @deprecated Deprecated since Contao 4.3, to be removed in Contao 5.0.
	 *             Use proper response caching headers instead.
	 */
	public static function getResponseFromCache()
	{
		trigger_deprecation('contao/core-bundle', '4.3', 'Using "Contao\Frontend::getResponseFromCache()" has been deprecated and will no longer work in Contao 5.0. Use proper response caching headers instead.');

		return null;
	}
}
