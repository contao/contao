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
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Exception\ExceptionInterface as RoutingExceptionInterface;
use Symfony\Component\Routing\Exception\MissingMandatoryParametersException;

/**
 * Provide methods to manage front end controllers.
 *
 * @author Leo Feyer <https://github.com/leofeyer>
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
	 * Split the current request into fragments, strip the URL suffix, recreate the $_GET array and return the page ID
	 *
	 * @return mixed
	 *
	 * @deprecated Deprecated since Contao 4.7, to be removed in Contao 5.0.
	 *             Use the Symfony routing instead.
	 */
	public static function getPageIdFromUrl()
	{
		@trigger_error('Using Frontend::getPageIdFromUrl() has been deprecated and will no longer work in Contao 5.0. Use the Symfony routing instead.', E_USER_DEPRECATED);

		$strRequest = Environment::get('relativeRequest');

		if (!$strRequest)
		{
			return null;
		}

		// Get the request without the query string
		list($strRequest) = explode('?', $strRequest, 2);

		// URL decode here (see #6232)
		$strRequest = rawurldecode($strRequest);

		// The request string must not contain "auto_item" (see #4012)
		if (strpos($strRequest, '/auto_item/') !== false)
		{
			return false;
		}

		// Extract the language
		if (Config::get('addLanguageToUrl'))
		{
			$arrMatches = array();

			// Use the matches instead of substr() (thanks to Mario Müller)
			if (preg_match('@^([a-z]{2}(-[A-Z]{2})?)/(.*)$@', $strRequest, $arrMatches))
			{
				Input::setGet('language', $arrMatches[1]);

				// Trigger the root page if only the language was given
				if (!$arrMatches[3])
				{
					return null;
				}

				$strRequest = $arrMatches[3];
			}
			else
			{
				return false; // Language not provided
			}
		}

		// Remove the URL suffix if not just a language root (e.g. en/) is requested
		if ($strRequest && (!Config::get('addLanguageToUrl') || !preg_match('@^[a-z]{2}(-[A-Z]{2})?/$@', $strRequest)))
		{
			$intSuffixLength = \strlen(Config::get('urlSuffix'));

			// Return false if the URL suffix does not match (see #2864)
			if ($intSuffixLength > 0)
			{
				if (substr($strRequest, -$intSuffixLength) != Config::get('urlSuffix'))
				{
					return false;
				}

				$strRequest = substr($strRequest, 0, -$intSuffixLength);
			}
		}

		$arrFragments = null;

		// Use folder-style URLs
		if (Config::get('folderUrl') && strpos($strRequest, '/') !== false)
		{
			$strAlias = $strRequest;
			$arrOptions = array($strAlias);

			// Compile all possible aliases by applying dirname() to the request (e.g. news/archive/item, news/archive, news)
			while ($strAlias != '/' && strpos($strAlias, '/') !== false)
			{
				$strAlias = \dirname($strAlias);
				$arrOptions[] = $strAlias;
			}

			/** @var PageModel $objPageModel */
			$objPageModel = System::getContainer()->get('contao.framework')->getAdapter(PageModel::class);

			// Check if there are pages with a matching alias
			$objPages = $objPageModel->findByAliases($arrOptions);

			if ($objPages !== null)
			{
				$arrPages = array();

				// Order by domain and language
				while ($objPages->next())
				{
					/** @var PageModel $objModel */
					$objModel = $objPages->current();
					$objPage  = $objModel->loadDetails();

					$domain = $objPage->domain ?: '*';
					$arrPages[$domain][$objPage->rootLanguage][] = $objPage;

					// Also store the fallback language
					if ($objPage->rootIsFallback)
					{
						$arrPages[$domain]['*'][] = $objPage;
					}
				}

				$arrAliases = array();
				$strHost = Environment::get('host');

				// Look for a root page whose domain name matches the host name
				$arrLangs = $arrPages[$strHost] ?? $arrPages['*'] ?? array();

				// Use the first result (see #4872)
				if (!Config::get('addLanguageToUrl'))
				{
					$arrAliases = current($arrLangs);
				}
				// Try to find a page matching the language parameter
				elseif (($lang = Input::get('language')) && isset($arrLangs[$lang]))
				{
					$arrAliases = $arrLangs[$lang];
				}

				// Return if there are no matches
				if (empty($arrAliases))
				{
					return false;
				}

				$objPage = $arrAliases[0];

				// The request consists of the alias only
				if ($strRequest == $objPage->alias)
				{
					$arrFragments = array($strRequest);
				}
				// Remove the alias from the request string, explode it and then re-insert the alias at the beginning
				else
				{
					$arrFragments = explode('/', substr($strRequest, \strlen($objPage->alias) + 1));
					array_unshift($arrFragments, $objPage->alias);
				}
			}
		}

		// If folderUrl is deactivated or did not find a matching page
		if ($arrFragments === null)
		{
			if ($strRequest == '/')
			{
				return false;
			}

			$arrFragments = explode('/', $strRequest);
		}

		// Add the second fragment as auto_item if the number of fragments is even
		if (\count($arrFragments) % 2 == 0)
		{
			if (!Config::get('useAutoItem'))
			{
				return false; // see #264
			}

			array_insert($arrFragments, 1, array('auto_item'));
		}

		// HOOK: add custom logic
		if (isset($GLOBALS['TL_HOOKS']['getPageIdFromUrl']) && \is_array($GLOBALS['TL_HOOKS']['getPageIdFromUrl']))
		{
			foreach ($GLOBALS['TL_HOOKS']['getPageIdFromUrl'] as $callback)
			{
				$arrFragments = static::importStatic($callback[0])->{$callback[1]}($arrFragments);
			}
		}

		// Return if the alias is empty (see #4702 and #4972)
		if (!$arrFragments[0] && \count($arrFragments) > 1)
		{
			return false;
		}

		// Add the fragments to the $_GET array
		for ($i=1, $c=\count($arrFragments); $i<$c; $i+=2)
		{
			// Return false if the key is empty (see #4702 and #263)
			if (!$arrFragments[$i])
			{
				return false;
			}

			// Return false if there is a duplicate parameter (duplicate content) (see #4277)
			if (isset($_GET[$arrFragments[$i]]))
			{
				return false;
			}

			// Return false if the request contains an auto_item keyword (duplicate content) (see #4012)
			if (Config::get('useAutoItem') && \in_array($arrFragments[$i], $GLOBALS['TL_AUTO_ITEM']))
			{
				return false;
			}

			Input::setGet($arrFragments[$i], $arrFragments[$i+1], true);
		}

		return $arrFragments[0] ?: null;
	}

	/**
	 * Return the root page ID
	 *
	 * @return integer
	 *
	 * @deprecated Deprecated since Contao 4.0, to be removed in Contao 5.0.
	 *             Use Frontend::getRootPageFromUrl()->id instead.
	 */
	public static function getRootIdFromUrl()
	{
		@trigger_error('Using Frontend::getRootIdFromUrl() has been deprecated and will no longer work in Contao 5.0. Use Frontend::getRootPageFromUrl()->id instead.', E_USER_DEPRECATED);

		return static::getRootPageFromUrl()->id;
	}

	/**
	 * Try to find a root page based on language and URL
	 *
	 * @return PageModel
	 */
	public static function getRootPageFromUrl()
	{
		$accept_language = Environment::get('httpAcceptLanguage');

		// Get the language from the URL if it is not set (see #456)
		if (!isset($_GET['language']) && Config::get('addLanguageToUrl'))
		{
			$arrMatches = array();

			// Get the request without the query string
			list($strRequest) = explode('?', Environment::get('relativeRequest'), 2);

			if (preg_match('@^([a-z]{2}(-[A-Z]{2})?)/@', $strRequest, $arrMatches))
			{
				Input::setGet('language', $arrMatches[1]);
			}
		}

		// The language is set in the URL
		if (!empty($_GET['language']) && Config::get('addLanguageToUrl'))
		{
			$strUri = Environment::get('url') . '/' . Input::get('language') . '/';
		}

		// No language given
		else
		{
			// Always load the language fall back root if "doNotRedirectEmpty" is enabled
			if (Config::get('addLanguageToUrl') && Config::get('doNotRedirectEmpty'))
			{
				$accept_language = '-';
			}

			$strUri = Environment::get('url') . '/';
		}

		$objRequest = Request::create($strUri);
		$objRequest->headers->set('Accept-Language', $accept_language);

		try
		{
			$arrParameters = System::getContainer()->get('contao.routing.nested_matcher')->matchRequest($objRequest);
		}
		catch (RoutingExceptionInterface $exception)
		{
			try
			{
				$arrParameters = System::getContainer()->get('contao.routing.nested_404_matcher')->matchRequest($objRequest);
			}
			catch (RoutingExceptionInterface $exception)
			{
				throw new NoRootPageFoundException('No root page found', 0, $exception);
			}
		}

		$objRootPage = $arrParameters['pageModel'] ?? null;

		if (!$objRootPage instanceof PageModel)
		{
			throw new MissingMandatoryParametersException('Every Contao route must have a "pageModel" parameter');
		}

		// Redirect to the website root or language root (e.g. en/)
		if (!Environment::get('relativeRequest'))
		{
			if (Config::get('addLanguageToUrl') && !Config::get('doNotRedirectEmpty'))
			{
				$arrParams = array('_locale' => $objRootPage->language);

				$strUrl = System::getContainer()->get('router')->generate('contao_index', $arrParams);
				$strUrl = substr($strUrl, \strlen(Environment::get('path')) + 1);

				static::redirect($strUrl);
			}

			// Redirect if the page alias is not "index" or "/" (see #8498, #8560 and #1210)
			elseif ($objRootPage->type !== 'root' && !\in_array($objRootPage->alias, array('index', '/')))
			{
				static::redirect($objRootPage->getAbsoluteUrl());
			}
		}

		if ($objRootPage->type != 'root')
		{
			return PageModel::findByPk($objRootPage->rootId);
		}

		return $objRootPage;
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
		if (Config::get('addLanguageToUrl'))
		{
			unset($arrGet['language']);
		}

		$strParams    = '';
		$strConnector = '/';
		$strSeparator = '/';

		// Compile the parameters string
		foreach ($arrGet as $k=>$v)
		{
			// Omit the key if it is an auto_item key (see #5037)
			if (Config::get('useAutoItem') && ($k == 'auto_item' || \in_array($k, $GLOBALS['TL_AUTO_ITEM'])))
			{
				$strParams = $strConnector . urlencode($v) . $strParams;
			}
			else
			{
				$strParams .= $strConnector . urlencode($k) . $strSeparator . urlencode($v);
			}
		}

		/** @var PageModel $objPage */
		global $objPage;

		$pageId = $objPage->alias ?: $objPage->id;

		// Get the page ID from URL if not set
		if (empty($pageId))
		{
			$pageId = static::getPageIdFromUrl();
		}

		$arrParams = array();
		$arrParams['alias'] = $pageId . $strParams;

		if (Config::get('addLanguageToUrl'))
		{
			$arrParams['_locale'] = $objPage->rootLanguage;
		}

		$strUrl = System::getContainer()->get('router')->generate('contao_frontend', $arrParams);
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
			@trigger_error('Using Frontend::jumpToOrReload() with $strForceLang has been deprecated and will no longer work in Contao 5.0.', E_USER_DEPRECATED);
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
			if (!$strForceLang)
			{
				$this->redirect($objNextPage->getAbsoluteUrl($strParams));
			}
			else
			{
				$this->redirect($objNextPage->getFrontendUrl($strParams, $strForceLang));
			}
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
		@trigger_error('Using Frontend::getLoginStatus() has been deprecated and will no longer work in Contao 5.0. Use Symfony security instead.', E_USER_DEPRECATED);

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
	 * Get the meta data from a serialized string
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
		$strLanguage = str_replace('-', '_', $strLanguage);

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
	 */
	protected function prepareMetaDescription($strText)
	{
		$strText = $this->replaceInsertTags($strText, false);
		$strText = strip_tags($strText);
		$strText = str_replace("\n", ' ', $strText);
		$strText = StringUtil::substr($strText, 320);

		return trim($strText);
	}

	/**
	 * Return the cron timeout in seconds
	 *
	 * @return integer
	 *
	 * @deprecated Deprecated since Contao 4.9, to be removed in Contao 5.0.
	 */
	public static function getCronTimeout()
	{
		trigger_deprecation('contao/core-bundle', '4.9', 'Calling "%s()" has been deprecated and will no longer work in Contao 5.0.', __METHOD__);

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
		@trigger_error('Using Frontend::indexPageIfApplicable() has been deprecated and will no longer work in Contao 5.0. Use the "contao.search.indexer" service instead.', E_USER_DEPRECATED);

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
		@trigger_error('Using Frontend::getResponseFromCache() has been deprecated and will no longer work in Contao 5.0. Use proper response caching headers instead.', E_USER_DEPRECATED);

		return null;
	}
}

class_alias(Frontend::class, 'Frontend');
