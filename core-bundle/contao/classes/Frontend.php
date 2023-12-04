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
use Contao\CoreBundle\Routing\Page\PageRoute;
use Contao\CoreBundle\Util\LocaleUtil;
use Symfony\Cmf\Component\Routing\RouteObjectInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Exception\ExceptionInterface;

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
		$this->import(Database::class, 'Database'); // backwards compatibility
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

		$arrGet = array();

		if (!$blnIgnoreParams)
		{
			foreach (Input::getKeys() as $key)
			{
				$arrGet[$key] = Input::get($key, true, true);
			}
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

		return System::getContainer()->get('router')->generate(PageRoute::PAGE_BASED_ROUTE_NAME, array(RouteObjectInterface::CONTENT_OBJECT => $objPage, 'parameters' => $strParams));
	}

	/**
	 * Redirect to a jumpTo page or reload the current page
	 *
	 * @param integer|array $intId
	 * @param string        $strParams
	 * @param string        $strForceLang
	 */
	protected function jumpToOrReload($intId, $strParams=null)
	{
		/** @var PageModel $objPage */
		global $objPage;

		// Always redirect if there are additional arguments (see #5734)
		$blnForceRedirect = $strParams !== null;

		if (\is_array($intId))
		{
			$intId = $intId['id'] ?? 0;
		}

		if ($intId > 0 && ($intId != $objPage->id || $blnForceRedirect) && ($objNextPage = PageModel::findPublishedById($intId)) !== null)
		{
			$urlGenerator = System::getContainer()->get('contao.routing.content_url_generator');

			$this->redirect($urlGenerator->generate($objNextPage, $strParams ? array('parameters' => $strParams) : array()));
		}

		$this->reload();
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
}
