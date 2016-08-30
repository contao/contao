<?php

/**
 * Contao Open Source CMS
 *
 * Copyright (c) 2005-2016 Leo Feyer
 *
 * @license LGPL-3.0+
 */

namespace Contao;

use Symfony\Component\HttpFoundation\Response;


/**
 * Class FrontendTemplate
 *
 * @property integer $id
 * @property string  $keywords
 * @property string  $content
 * @property array   $sections
 * @property string  $sPosition
 * @property string  $tag
 *
 * @author Leo Feyer <https://github.com/leofeyer>
 */
class FrontendTemplate extends \Template
{

	/**
	 * Add a hook to modify the template output
	 *
	 * @return string The template markup
	 */
	public function parse()
	{
		/** @var PageModel $objPage */
		global $objPage;

		// Adjust the output format
		if ($objPage->outputFormat != '')
		{
			$this->strFormat = $objPage->outputFormat;
		}

		$strBuffer = parent::parse();

		// HOOK: add custom parse filters
		if (isset($GLOBALS['TL_HOOKS']['parseFrontendTemplate']) && is_array($GLOBALS['TL_HOOKS']['parseFrontendTemplate']))
		{
			foreach ($GLOBALS['TL_HOOKS']['parseFrontendTemplate'] as $callback)
			{
				$this->import($callback[0]);
				$strBuffer = $this->{$callback[0]}->{$callback[1]}($strBuffer, $this->strTemplate);
			}
		}

		return $strBuffer;
	}


	/**
	 * Send the response to the client
	 *
	 * @param bool $blnCheckRequest If true, check for unsued $_GET parameters
	 *
	 * @deprecated Deprecated since Contao 4.0, to be removed in Contao 5.0.
	 *             Use FrontendTemplate::getResponse() instead.
	 */
	public function output($blnCheckRequest=false)
	{
		$this->compile($blnCheckRequest);

		parent::output();
	}


	/**
	 * Return a response object
	 *
	 * @param bool $blnCheckRequest If true, check for unsued $_GET parameters
	 *
	 * @return Response The response object
	 */
	public function getResponse($blnCheckRequest=false)
	{
		$this->compile($blnCheckRequest);

		$response = parent::getResponse();
		$this->setCacheHeaders($response);

		return $response;
	}


	/**
	 * Compile the template
	 *
	 * @param bool $blnCheckRequest If true, check for unsued $_GET parameters
	 *
	 * @throws \UnusedArgumentsException If there are unused $_GET parameters
	 *
	 * @internal Do not call this method in your code. It will be made private in Contao 5.0.
	 */
	protected function compile($blnCheckRequest=false)
	{
		$this->keywords = '';
		$arrKeywords = \StringUtil::trimsplit(',', $GLOBALS['TL_KEYWORDS']);

		// Add the meta keywords
		if (strlen($arrKeywords[0]))
		{
			$this->keywords = str_replace(array("\n", "\r", '"'), array(' ' , '', ''), implode(', ', array_unique($arrKeywords)));
		}

		// Parse the template
		$this->strBuffer = $this->parse();

		// HOOK: add custom output filters
		if (isset($GLOBALS['TL_HOOKS']['outputFrontendTemplate']) && is_array($GLOBALS['TL_HOOKS']['outputFrontendTemplate']))
		{
			foreach ($GLOBALS['TL_HOOKS']['outputFrontendTemplate'] as $callback)
			{
				$this->import($callback[0]);
				$this->strBuffer = $this->{$callback[0]}->{$callback[1]}($this->strBuffer, $this->strTemplate);
			}
		}

		// Unset only after the output has been cached (see #7824)
		unset($_SESSION['LOGIN_ERROR']);

		// Replace insert tags
		$this->strBuffer = $this->replaceInsertTags($this->strBuffer);
		$this->strBuffer = $this->replaceDynamicScriptTags($this->strBuffer); // see #4203

		// HOOK: allow to modify the compiled markup (see #4291)
		if (isset($GLOBALS['TL_HOOKS']['modifyFrontendPage']) && is_array($GLOBALS['TL_HOOKS']['modifyFrontendPage']))
		{
			foreach ($GLOBALS['TL_HOOKS']['modifyFrontendPage'] as $callback)
			{
				$this->import($callback[0]);
				$this->strBuffer = $this->{$callback[0]}->{$callback[1]}($this->strBuffer, $this->strTemplate);
			}
		}

		// Check whether all $_GET parameters have been used (see #4277)
		if ($blnCheckRequest && \Input::hasUnusedGet())
		{
			throw new \UnusedArgumentsException();
		}

		parent::compile();
	}


	/**
	 * Return a custom layout section
	 *
	 * @param string $key      The section name
	 * @param string $template An optional template name
	 */
	public function section($key, $template=null)
	{
		if (empty($this->sections[$key]))
		{
			return;
		}

		$this->id = $key;
		$this->content = $this->sections[$key];

		if ($template === null)
		{
			$template = 'block_section';
		}

		include $this->getTemplate($template, $this->strFormat);
	}


	/**
	 * Return the custom layout sections
	 *
	 * @param string $key      An optional section name
	 * @param string $template An optional template name
	 */
	public function sections($key=null, $template=null)
	{
		if (!array_filter($this->sections))
		{
			return;
		}

		// The key does not match
		if ($key && $this->sPosition != $key)
		{
			return;
		}

		if ($template === null)
		{
			$template = 'block_sections';
		}

		include $this->getTemplate($template, $this->strFormat);
	}


	/**
	 * Point to `Frontend::addToUrl()` in front end templates (see #6736)
	 *
	 * @param string  $strRequest      The request string to be added
	 * @param boolean $blnIgnoreParams If true, the $_GET parameters will be ignored
	 * @param array   $arrUnset        An optional array of keys to unset
	 *
	 * @return string The new URI string
	 */
	public static function addToUrl($strRequest, $blnIgnoreParams=false, $arrUnset=array())
	{
		return \Frontend::addToUrl($strRequest, $blnIgnoreParams, $arrUnset);
	}


	/**
	 * Check whether there is an authenticated back end user
	 *
	 * @return boolean True if there is an authenticated back end user
	 */
	public function hasAuthenticatedBackendUser()
	{
		if (!isset($_COOKIE['BE_USER_AUTH']))
		{
			return false;
		}

		return Input::cookie('BE_USER_AUTH') == $this->getSessionHash('BE_USER_AUTH');
	}


	/**
	 * Add the template output to the cache and add the cache headers
	 *
	 * @deprecated Deprecated since Contao 4.3, to be removed in Contao 5.0.
	 *             Use proper response caching headers instead.
	 */
	protected function addToCache()
	{
		@trigger_error('Using FrontendTemplate::addToCache() has been deprecated and will no longer work in Contao 5.0. Use proper response caching headers instead.', E_USER_DEPRECATED);
	}


	/**
	 * Add the template output to the search index
	 *
	 * @deprecated Deprecated since Contao 4.0, to be removed in Contao 5.0.
	 *             Use the kernel.terminate event instead.
	 */
	protected function addToSearchIndex()
	{
		@trigger_error('Using FrontendTemplate::addToSearchIndex() has been deprecated and will no longer work in Contao 5.0. Use the kernel.terminate event instead.', E_USER_DEPRECATED);
	}


	/**
	 * Return a custom layout section
	 *
	 * @param string $strKey The section name
	 *
	 * @return string The section markup
	 *
	 * @deprecated Deprecated since Contao 4.0, to be removed in Contao 5.0.
	 *             Use FrontendTemplate::section() instead.
	 */
	public function getCustomSection($strKey)
	{
		@trigger_error('Using FrontendTemplate::getCustomSection() has been deprecated and will no longer work in Contao 5.0. Use FrontendTemplate::section() instead.', E_USER_DEPRECATED);

		return '<div id="' . $strKey . '">' . $this->sections[$strKey] . '</div>' . "\n";
	}


	/**
	 * Return all custom layout sections
	 *
	 * @param string $strKey An optional section name
	 *
	 * @return string The section markup
	 *
	 * @deprecated Deprecated since Contao 4.0, to be removed in Contao 5.0.
	 *             Use FrontendTemplate::sections() instead.
	 */
	public function getCustomSections($strKey=null)
	{
		@trigger_error('Using FrontendTemplate::getCustomSections() has been deprecated and will no longer work in Contao 5.0. Use FrontendTemplate::sections() instead.', E_USER_DEPRECATED);

		if ($strKey != '' && $this->sPosition != $strKey)
		{
			return '';
		}

		$tag = 'div';

		if ($strKey == 'main')
		{
			/** @var PageModel $objPage */
			global $objPage;

			// Use the section tag in HTML5
			if ($objPage->outputFormat == 'html5')
			{
				$tag = 'section';
			}
		}

		$sections = '';

		// Standardize the IDs (thanks to Tsarma) (see #4251)
		foreach ($this->sections as $k=>$v)
		{
			$sections .= "\n" . '<' . $tag . ' id="' . \StringUtil::standardize($k, true) . '">' . "\n" . '<div class="inside">' . "\n" . $v . "\n" . '</div>' . "\n" . '</' . $tag . '>' . "\n";
		}

		if ($sections == '')
		{
			return '';
		}

		return '<div class="custom">' . "\n" . $sections . "\n" . '</div>' . "\n";
	}

	/**
	 * Set the cache headers according to the page settings.
	 *
	 * @param Response $response
	 */
	private function setCacheHeaders(Response $response)
	{
		/** @var $objPage \PageModel */
		global $objPage;

		if (false === $objPage->cache && false === $objPage->clientCache)
		{
			return;
		}

		// If FE_USER_LOGGED_IN or BE_USER_LOGGED_IN every request is private
		// Moreover, mobile layout is deprecated and never cached
		if (true === FE_USER_LOGGED_IN || true === BE_USER_LOGGED_IN || true === $objPage->isMobile)
		{
			$response->setPrivate();
			return;
		}

		if ($objPage->clientCache > 0)
		{
			$response->setMaxAge($objPage->clientCache);
		}

		if ($objPage->cache > 0)
		{
			$response->setSharedMaxAge($objPage->cache);
		}
	}
}
