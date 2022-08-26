<?php

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
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
 * @property array   $positions
 * @property array   $matches
 * @property string  $tag
 */
class FrontendTemplate extends Template
{
	use FrontendTemplateTrait;

	/**
	 * Unused $_GET check
	 * @var boolean
	 */
	protected $blnCheckRequest = false;

	/**
	 * Add a hook to modify the template output
	 *
	 * @return string The template markup
	 */
	public function parse()
	{
		$strBuffer = parent::parse();

		// HOOK: add custom parse filters
		if (isset($GLOBALS['TL_HOOKS']['parseFrontendTemplate']) && \is_array($GLOBALS['TL_HOOKS']['parseFrontendTemplate']))
		{
			foreach ($GLOBALS['TL_HOOKS']['parseFrontendTemplate'] as $callback)
			{
				$this->import($callback[0]);
				$strBuffer = $this->{$callback[0]}->{$callback[1]}($strBuffer, $this->strTemplate, $this);
			}
		}

		return $strBuffer;
	}

	/**
	 * Send the response to the client
	 *
	 * @param bool $blnCheckRequest If true, check for unused $_GET parameters
	 *
	 * @deprecated Deprecated since Contao 4.0, to be removed in Contao 5.0.
	 *             Use FrontendTemplate::getResponse() instead.
	 */
	public function output($blnCheckRequest=false)
	{
		$this->blnCheckRequest = $blnCheckRequest;

		parent::output();
	}

	/**
	 * Return a response object
	 *
	 * @param bool $blnCheckRequest      If true, check for unused $_GET parameters
	 * @param bool $blnForceCacheHeaders
	 *
	 * @return Response The response object
	 */
	public function getResponse($blnCheckRequest=false, $blnForceCacheHeaders=false)
	{
		$this->blnCheckRequest = $blnCheckRequest;

		$response = parent::getResponse();

		if ($blnForceCacheHeaders || 0 === strncmp('fe_', $this->strTemplate, 3))
		{
			return $this->setCacheHeaders($response);
		}

		return $response;
	}

	/**
	 * Compile the template
	 *
	 * @throws UnusedArgumentsException If there are unused $_GET parameters
	 *
	 * @internal Do not call this method in your code. It will be made private in Contao 5.0.
	 */
	protected function compile()
	{
		$this->keywords = '';

		// Backwards compatibility
		$arrKeywords = StringUtil::trimsplit(',', $GLOBALS['TL_KEYWORDS'] ?? '');

		// Add the meta keywords
		if (isset($arrKeywords[0]))
		{
			$this->keywords = str_replace(array("\n", "\r", '"'), array(' ', '', ''), implode(', ', array_unique($arrKeywords)));
		}

		// Parse the template
		$this->strBuffer = $this->parse();

		// HOOK: add custom output filters
		if (isset($GLOBALS['TL_HOOKS']['outputFrontendTemplate']) && \is_array($GLOBALS['TL_HOOKS']['outputFrontendTemplate']))
		{
			foreach ($GLOBALS['TL_HOOKS']['outputFrontendTemplate'] as $callback)
			{
				$this->import($callback[0]);
				$this->strBuffer = $this->{$callback[0]}->{$callback[1]}($this->strBuffer, $this->strTemplate);
			}
		}

		$this->strBuffer = System::getContainer()->get('contao.insert_tag.parser')->replace($this->strBuffer);
		$this->strBuffer = $this->replaceDynamicScriptTags($this->strBuffer); // see #4203

		// HOOK: allow to modify the compiled markup (see #4291)
		if (isset($GLOBALS['TL_HOOKS']['modifyFrontendPage']) && \is_array($GLOBALS['TL_HOOKS']['modifyFrontendPage']))
		{
			foreach ($GLOBALS['TL_HOOKS']['modifyFrontendPage'] as $callback)
			{
				$this->import($callback[0]);
				$this->strBuffer = $this->{$callback[0]}->{$callback[1]}($this->strBuffer, $this->strTemplate);
			}
		}

		// Check whether all $_GET parameters have been used (see #4277)
		if ($this->blnCheckRequest && Input::hasUnusedGet())
		{
			throw new UnusedArgumentsException('Unused arguments: ' . implode(', ', Input::getUnusedGet()));
		}

		/** @var PageModel|null $objPage */
		global $objPage;

		// Minify the markup
		if ($objPage !== null && $objPage->minifyMarkup)
		{
			$this->strBuffer = $this->minifyHtml($this->strBuffer);
		}

		// Replace literal insert tags (see #670, #3249)
		$this->strBuffer = preg_replace_callback(
			'/<script[^>]*>.*?<\/script[^>]*>|\[[{}]]/is',
			static function ($matches)
			{
				return $matches[0][0] === '<' ? $matches[0] : '&#' . \ord($matches[0][1]) . ';&#' . \ord($matches[0][1]) . ';';
			},
			$this->strBuffer
		);

		parent::compile();
	}

	/**
	 * Set the cache headers according to the page settings.
	 *
	 * @param Response $response The response object
	 *
	 * @return Response The response object
	 */
	private function setCacheHeaders(Response $response)
	{
		/** @var PageModel $objPage */
		global $objPage;

		// Do not cache the response if caching was not configured at all or disabled explicitly
		if (($objPage->cache === false || $objPage->cache < 1) && ($objPage->clientCache === false || $objPage->clientCache < 1))
		{
			$response->headers->set('Cache-Control', 'no-cache, no-store');

			return $response->setPrivate(); // Make sure the response is private
		}

		// Private cache
		if ($objPage->clientCache > 0)
		{
			$response->setMaxAge($objPage->clientCache);
			$response->setPrivate(); // Make sure the response is private
		}

		// Shared cache
		if ($objPage->cache > 0)
		{
			$response->setSharedMaxAge($objPage->cache); // Automatically sets the response to public

			// We vary on cookies if a response is cacheable by the shared
			// cache, so a reverse proxy does not load a response from cache if
			// the _request_ contains a cookie.
			//
			// This DOES NOT mean that we generate a cache entry for every
			// response containing a cookie! Responses with cookies will always
			// be private (@see Contao\CoreBundle\EventListener\MakeResponsePrivateListener).
			//
			// However, we want to be able to force the reverse proxy to load a
			// response from cache, even if the request contains a cookie – in
			// case the admin has configured to do so. A typical use case would
			// be serving public pages from cache to logged in members.
			if (!$objPage->alwaysLoadFromCache)
			{
				$response->setVary(array('Cookie'));
			}

			// Tag the page (see #2137)
			System::getContainer()->get('contao.cache.entity_tags')->tagWithModelInstance($objPage);
		}

		return $response;
	}
}

class_alias(FrontendTemplate::class, 'FrontendTemplate');
