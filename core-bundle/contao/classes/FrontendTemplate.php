<?php

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao;

use Contao\CoreBundle\EventListener\MakeResponsePrivateListener;
use Symfony\Component\HttpFoundation\Response;

/**
 * Class FrontendTemplate
 *
 * @property integer $id
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
	 * Unused route parameters check
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
	 * Return a response object
	 *
	 * @param bool $blnCheckRequest      If true, check for unused route parameters
	 * @param bool $blnForceCacheHeaders
	 *
	 * @return Response The response object
	 */
	public function getResponse($blnCheckRequest=false, $blnForceCacheHeaders=false)
	{
		$this->blnCheckRequest = $blnCheckRequest;

		$this->compile();

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
	 * @throws UnusedArgumentsException If there are unused route parameters
	 */
	private function compile()
	{
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

		// Check whether all route parameters have been used (see #4277)
		if ($this->blnCheckRequest && Input::getUnusedRouteParameters())
		{
			throw new UnusedArgumentsException('Unused arguments: ' . implode(', ', Input::getUnusedRouteParameters()));
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

		// Do not cache the response if caching was not configured
		if ($objPage->cache < 1 && $objPage->clientCache < 1)
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

			/**
			 * We vary on cookies if a response is cacheable by the shared
			 * cache, so a reverse proxy does not load a response from cache if
			 * the _request_ contains a cookie.
			 *
			 * This DOES NOT mean that we generate a cache entry for every
			 * response containing a cookie! Responses with cookies will always
			 * be private.
			 *
			 * @see MakeResponsePrivateListener
			 *
			 * However, we want to be able to force the reverse proxy to load a
			 * response from cache, even if the request contains a cookie – in
			 * case the admin has configured to do so. A typical use case would
			 * be serving public pages from cache to logged in members.
			 */
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
