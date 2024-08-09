<?php

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao;

use Contao\CoreBundle\Routing\ResponseContext\Csp\CspHandler;
use Contao\CoreBundle\Routing\ResponseContext\JsonLd\JsonLdManager;
use Contao\CoreBundle\String\HtmlAttributes;
use Contao\Image\ImageInterface;
use Contao\Image\PictureConfiguration;
use Spatie\SchemaOrg\Graph;

/**
 * Provides template methods.
 *
 * @internal
 */
trait TemplateTrait
{
	/**
	 * Generate a URL for the given route
	 *
	 * @param string $strName   The route name
	 * @param array  $arrParams The route parameters
	 *
	 * @return string The route
	 */
	public function route($strName, $arrParams=array())
	{
		return StringUtil::ampersand(System::getContainer()->get('router')->generate($strName, $arrParams));
	}

	/**
	 * Return the preview route
	 *
	 * @param string $strName   The route name
	 * @param array  $arrParams The route parameters
	 *
	 * @return string The route
	 */
	public function previewRoute($strName, $arrParams=array())
	{
		$container = System::getContainer();

		if (!$previewScript = $container->getParameter('contao.preview_script'))
		{
			return $this->route($strName, $arrParams);
		}

		$router = $container->get('router');

		$context = $router->getContext();
		$context->setBaseUrl($previewScript);

		$strUrl = $router->generate($strName, $arrParams);

		$context->setBaseUrl('');

		return StringUtil::ampersand($strUrl);
	}

	/**
	 * Returns a translated message
	 *
	 * @param string $strId
	 * @param array  $arrParams
	 * @param string $strDomain
	 * @param string $locale
	 *
	 * @return string
	 */
	public function trans($strId, array $arrParams=array(), $strDomain='contao_default', $locale=null)
	{
		return System::getContainer()->get('translator')->trans($strId, $arrParams, $strDomain, $locale);
	}

	/**
	 * Helper method to allow quick access in the Contao templates for safe raw (unencoded) output.
	 * It replaces (or optionally removes) Contao insert tags and removes all HTML.
	 *
	 * Be careful when using this. It must NOT be used within regular HTML when $value
	 * is uncontrolled user input. It's useful to ensure raw values within e.g. <code> examples
	 * or JSON-LD arrays.
	 */
	public function rawPlainText(string $value, bool $removeInsertTags = false): string
	{
		return System::getContainer()->get('contao.string.html_decoder')->inputEncodedToPlainText($value, $removeInsertTags);
	}

	/**
	 * Helper method to allow quick access in the Contao templates for safe raw (unencoded) output.
	 *
	 * Compared to $this->rawPlainText() it adds new lines before and after block level HTML elements
	 * and only then removes the rest of the HTML tags.
	 *
	 * Be careful when using this. It must NOT be used within regular HTML when $value
	 * is uncontrolled user input. It's useful to ensure raw values within e.g. <code> examples
	 * or JSON-LD arrays.
	 */
	public function rawHtmlToPlainText(string $value, bool $removeInsertTags = false): string
	{
		return System::getContainer()->get('contao.string.html_decoder')->htmlToPlainText($value, $removeInsertTags);
	}

	/**
	 * Adds schema.org JSON-LD data to the current response context
	 */
	public function addSchemaOrg(array $jsonLd): void
	{
		$responseContext = System::getContainer()->get('contao.routing.response_context_accessor')->getResponseContext();

		if (!$responseContext?->has(JsonLdManager::class))
		{
			return;
		}

		$jsonLdManager = $responseContext->get(JsonLdManager::class);
		$type = $jsonLdManager->createSchemaOrgTypeFromArray($jsonLd);

		$jsonLdManager
			->getGraphForSchema(JsonLdManager::SCHEMA_ORG)
			->set($type, $jsonLd['identifier'] ?? Graph::IDENTIFIER_DEFAULT)
		;
	}

	/**
	 * @param iterable<string, string|int|bool|\Stringable|null>|string|self|null $attributes
	 */
	public function attr(HtmlAttributes|iterable|string|null $attributes = null): HtmlAttributes
	{
		return new HtmlAttributes($attributes);
	}

	/**
	 * Returns a nonce for the given CSP directive.
	 */
	public function nonce(string $directive): string|null
	{
		$responseContext = System::getContainer()->get('contao.routing.response_context_accessor')->getResponseContext();

		if (!$responseContext?->has(CspHandler::class))
		{
			return null;
		}

		return $responseContext->get(CspHandler::class)->getNonce($directive);
	}

	/**
	 * Adds a source to the given CSP directive.
	 */
	public function addCspSource(array|string $directives, string $source): void
	{
		$responseContext = System::getContainer()->get('contao.routing.response_context_accessor')->getResponseContext();

		if (!$responseContext?->has(CspHandler::class))
		{
			return;
		}

		$csp = $responseContext->get(CspHandler::class);

		foreach ((array) $directives as $directive)
		{
			$csp->addSource($directive, $source);
		}
	}

	/**
	 * Adds a CSP hash for the given script.
	 */
	public function addCspHash(string $directive, string $script, string $algorithm = 'sha384'): void
	{
		$responseContext = System::getContainer()->get('contao.routing.response_context_accessor')->getResponseContext();

		if (!$responseContext?->has(CspHandler::class))
		{
			return;
		}

		$csp = $responseContext->get(CspHandler::class);
		$csp->addHash($directive, $script, $algorithm);
	}

	/**
	 * @deprecated Deprecated since Contao 5.3, to be removed in Contao 6;
	 *             use cspUnsafeInlineStyle() instead.
	 */
	public function cspInlineStyle(string $style, string $algorithm = 'sha384'): string
	{
		trigger_deprecation('contao/core-bundle', '5.3', 'Using "%s()" has been deprecated and will no longer work in Contao 6. Use "cspUnsafeInlineStyle()" instead.', __METHOD__);

		return $this->cspUnsafeInlineStyle($style, $algorithm);
	}

	/**
	 * Adds a CSP hash for a given inline style and also adds the 'unsafe-hashes' source to the directive automatically.
	 *
	 * ATTENTION: Only pass trusted styles to this method!
	 */
	public function cspUnsafeInlineStyle(string $style, string $algorithm = 'sha384'): string
	{
		$responseContext = System::getContainer()->get('contao.routing.response_context_accessor')->getResponseContext();

		if ($responseContext?->has(CspHandler::class))
		{
			$csp = $responseContext->get(CspHandler::class);
			$csp
				->addHash('style-src', $style, $algorithm)
				->addSource('style-src', "'unsafe-hashes'")
			;
		}

		return $style;
	}

	/**
	 * Extracts all inline CSS style attributes of a given HTML string and automatically adds CSP hashes for those
	 * to the current response context. The list of allowed styles can be configured in contao.csp.allowed_inline_styles.
	 */
	public function cspInlineStyles(string|null $html): string|null
	{
		if (!$html)
		{
			return $html;
		}

		$responseContext = System::getContainer()->get('contao.routing.response_context_accessor')->getResponseContext();

		if (!$responseContext?->has(CspHandler::class))
		{
			return $html;
		}

		$styleProcessor = System::getContainer()->get('contao.csp.wysiwyg_style_processor');

		if (!$styles = $styleProcessor->extractStyles($html))
		{
			return $html;
		}

		$csp = $responseContext->get(CspHandler::class);

		foreach ($styles as $style)
		{
			$csp->addHash('style-src', $style);
		}

		$csp->addSource('style-src', "'unsafe-hashes'");

		return $html;
	}

	/**
	 * Render a figure
	 *
	 * The provided configuration array is used to configure a FigureBuilder.
	 * If not explicitly set, the default template "image.html5" will be used
	 * to render the results. To use the core's default Twig template, pass
	 * "@ContaoCore/Image/Studio/figure.html.twig" as $template argument.
	 *
	 * @param int|string|FilesModel|ImageInterface       $from          Can be a FilesModel, an ImageInterface, a tl_files UUID/ID/path or a file system path
	 * @param int|string|array|PictureConfiguration|null $size          A picture size configuration or reference
	 * @param array<string, mixed>                       $configuration Configuration for the FigureBuilder
	 * @param string                                     $template      A Contao or Twig template
	 *
	 * @return string|null Returns null if the resource is invalid
	 */
	public function figure($from, $size, $configuration = array(), $template = 'image')
	{
		return System::getContainer()->get('contao.image.studio.figure_renderer')->render($from, $size, $configuration, $template);
	}

	/**
	 * Returns an asset path
	 *
	 * @param string      $path
	 * @param string|null $packageName
	 *
	 * @return string
	 */
	public function asset($path, $packageName = null)
	{
		return System::getContainer()->get('assets.packages')->getUrl($path, $packageName);
	}

	/**
	 * Returns an asset version
	 *
	 * @param string      $path
	 * @param string|null $packageName
	 *
	 * @return string
	 */
	public function assetVersion($path, $packageName = null)
	{
		return System::getContainer()->get('assets.packages')->getVersion($path, $packageName);
	}

	/**
	 * Returns a container parameter
	 *
	 * @param string $strKey
	 *
	 * @return mixed
	 */
	public function param($strKey)
	{
		return System::getContainer()->getParameter($strKey);
	}

	/**
	 * Prefixes a relative URL
	 *
	 * @param string $url
	 *
	 * @return string
	 */
	public function prefixUrl($url)
	{
		if (!Validator::isRelativeUrl($url))
		{
			return $url;
		}

		return Environment::get('path') . '/' . $url;
	}
}
