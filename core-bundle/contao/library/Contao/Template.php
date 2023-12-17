<?php

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao;

use Contao\CoreBundle\Routing\ResponseContext\JsonLd\JsonLdManager;
use Contao\Image\ImageInterface;
use Contao\Image\PictureConfiguration;
use MatthiasMullie\Minify\CSS;
use MatthiasMullie\Minify\JS;
use ParagonIE\CSPBuilder\CSPBuilder;
use Spatie\SchemaOrg\Graph;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\VarDumper\VarDumper;

/**
 * Parses and outputs template files
 *
 * The class supports loading template files, adding variables to them and then
 * printing them to the screen. It functions as abstract parent class for the
 * two core classes "BackendTemplate" and "FrontendTemplate".
 *
 * Usage:
 *
 *     $template = new BackendTemplate();
 *     $template->name = 'Leo Feyer';
 *     $template->getResponse();
 *
 * @property string       $style
 * @property array|string $cssID
 * @property string       $class
 * @property string       $inColumn
 * @property string       $headline
 * @property array        $hl
 * @property string       $content
 * @property string       $action
 * @property boolean      $enforceTwoFactor
 * @property string       $targetPath
 * @property string       $message
 * @property string       $href
 * @property string       $twoFactor
 * @property string       $explain
 * @property string       $active
 * @property string       $enableButton
 * @property string       $disableButton
 * @property boolean      $enable
 * @property boolean      $isEnabled
 * @property string       $secret
 * @property string       $textCode
 * @property string       $qrCode
 * @property string       $scan
 * @property string       $verify
 * @property string       $verifyHelp
 * @property boolean      $showBackupCodes
 * @property array        $backupCodes
 * @property boolean      $trustedDevicesEnabled
 * @property array        $trustedDevices
 * @property string       $currentDevice
 *
 * @deprecated Deprecated since Contao 5.0, to be removed in Contao 6.0;
 *             use Twig templates instead
 */
abstract class Template extends Controller
{
	use TemplateInheritance;

	/**
	 * Output buffer
	 * @var string
	 */
	protected $strBuffer;

	/**
	 * Content type
	 * @var string
	 */
	protected $strContentType;

	/**
	 * Template data
	 * @var array
	 */
	protected $arrData = array();

	/**
	 * Valid JavaScipt types
	 * @var array
	 * @see http://www.w3.org/TR/html5/scripting-1.html#scriptingLanguages
	 */
	protected static $validJavaScriptTypes = array
	(
		'application/ecmascript',
		'application/javascript',
		'application/x-ecmascript',
		'application/x-javascript',
		'text/ecmascript',
		'text/javascript',
		'text/javascript1.0',
		'text/javascript1.1',
		'text/javascript1.2',
		'text/javascript1.3',
		'text/javascript1.4',
		'text/javascript1.5',
		'text/jscript',
		'text/livescript',
		'text/x-ecmascript',
		'text/x-javascript',
	);

	/**
	 * Create a new template object
	 *
	 * @param string $strTemplate    The template name
	 * @param string $strContentType The content type (defaults to "text/html")
	 */
	public function __construct($strTemplate='', $strContentType='text/html')
	{
		parent::__construct();

		$this->strTemplate = $strTemplate;
		$this->strContentType = $strContentType;
	}

	/**
	 * Set an object property
	 *
	 * @param string $strKey   The property name
	 * @param mixed  $varValue The property value
	 */
	public function __set($strKey, $varValue)
	{
		$this->arrData[$strKey] = $varValue;
	}

	/**
	 * Return an object property
	 *
	 * @param string $strKey The property name
	 *
	 * @return mixed The property value
	 */
	public function __get($strKey)
	{
		if (isset($this->arrData[$strKey]))
		{
			if (\is_object($this->arrData[$strKey]) && \is_callable($this->arrData[$strKey]))
			{
				return $this->arrData[$strKey]();
			}

			return $this->arrData[$strKey];
		}

		if ($strKey === 'requestToken' && !\array_key_exists($strKey, $this->arrData))
		{
			return htmlspecialchars(System::getContainer()->get('contao.csrf.token_manager')->getDefaultTokenValue());
		}

		return parent::__get($strKey);
	}

	/**
	 * Execute a callable and return the result
	 *
	 * @param string $strKey    The name of the key
	 * @param array  $arrParams The parameters array
	 *
	 * @return mixed The callable return value
	 *
	 * @throws \InvalidArgumentException If the callable does not exist
	 */
	public function __call($strKey, $arrParams)
	{
		if (!isset($this->arrData[$strKey]) || !\is_callable($this->arrData[$strKey]))
		{
			throw new \InvalidArgumentException("$strKey is not set or not a callable");
		}

		return ($this->arrData[$strKey])(...$arrParams);
	}

	/**
	 * Check whether a property is set
	 *
	 * @param string $strKey The property name
	 *
	 * @return boolean True if the property is set
	 */
	public function __isset($strKey)
	{
		return isset($this->arrData[$strKey]) || ($strKey === 'requestToken' && !\array_key_exists($strKey, $this->arrData));
	}

	/**
	 * Set the template data from an array
	 *
	 * @param array $arrData The data array
	 */
	public function setData($arrData)
	{
		$this->arrData = $arrData;
	}

	/**
	 * Return the template data as array
	 *
	 * @return array The data array
	 */
	public function getData()
	{
		return $this->arrData;
	}

	/**
	 * Set the template name
	 *
	 * @param string $strTemplate The template name
	 */
	public function setName($strTemplate)
	{
		$this->strTemplate = $strTemplate;
	}

	/**
	 * Return the template name
	 *
	 * @return string The template name
	 */
	public function getName()
	{
		return $this->strTemplate;
	}

	/**
	 * Set the output format
	 *
	 * @param string $strFormat The output format
	 */
	public function setFormat($strFormat)
	{
		$this->strFormat = $strFormat;
	}

	/**
	 * Return the output format
	 *
	 * @return string The output format
	 */
	public function getFormat()
	{
		return $this->strFormat;
	}

	/**
	 * Print all template variables to the screen using the Symfony VarDumper component
	 */
	public function dumpTemplateVars()
	{
		VarDumper::dump($this->arrData);
	}

	/**
	 * Parse the template file and return it as string
	 *
	 * @return string The template markup
	 */
	public function parse()
	{
		if (!$this->strTemplate)
		{
			return '';
		}

		// HOOK: add custom parse filters
		if (isset($GLOBALS['TL_HOOKS']['parseTemplate']) && \is_array($GLOBALS['TL_HOOKS']['parseTemplate']))
		{
			foreach ($GLOBALS['TL_HOOKS']['parseTemplate'] as $callback)
			{
				System::importStatic($callback[0])->{$callback[1]}($this);
			}
		}

		return $this->inherit();
	}

	/**
	 * Return a response object
	 *
	 * @return Response The response object
	 */
	public function getResponse()
	{
		if (!$this->strBuffer)
		{
			$this->strBuffer = $this->parse();
		}

		$response = new Response($this->strBuffer);
		$response->headers->set('Content-Type', $this->strContentType);
		$response->setCharset(System::getContainer()->getParameter('kernel.charset'));

		return $response;
	}

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

		if (!$responseContext || !$responseContext->has(JsonLdManager::class))
		{
			return;
		}

		/** @var JsonLdManager $jsonLdManager */
		$jsonLdManager = $responseContext->get(JsonLdManager::class);
		$type = $jsonLdManager->createSchemaOrgTypeFromArray($jsonLd);

		$jsonLdManager
			->getGraphForSchema(JsonLdManager::SCHEMA_ORG)
			->set($type, $jsonLd['identifier'] ?? Graph::IDENTIFIER_DEFAULT)
		;
	}

	/**
	 * Returns a nonce for the given CSP directive.
	 */
	public function nonce(string $directive): string
	{
		$responseContext = System::getContainer()->get('contao.routing.response_context_accessor')->getResponseContext();

		if (!$responseContext || !$responseContext->has(CSPBuilder::class))
		{
			return '';
		}

		/** @var CSPBuilder $csp */
		$csp = $responseContext->get(CSPBuilder::class);

		return $csp->nonce($directive);
	}

	/**
	 * Adds a source to the given CSP directive.
	 */
	public function addCspSource(string $directive, string $source): void
	{
		$responseContext = System::getContainer()->get('contao.routing.response_context_accessor')->getResponseContext();

		if (!$responseContext || !$responseContext->has(CSPBuilder::class))
		{
			return;
		}

		/** @var CSPBuilder $csp */
		$csp = $responseContext->get(CSPBuilder::class);
		$csp->addSource($directive, $source);
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

	/**
	 * Minify the HTML markup preserving pre, script, style and textarea tags
	 *
	 * @param string $strHtml The HTML markup
	 *
	 * @return string The minified HTML markup
	 */
	public function minifyHtml($strHtml)
	{
		if (System::getContainer()->getParameter('kernel.debug'))
		{
			return $strHtml;
		}

		// Split the markup based on the tags that shall be preserved
		$arrChunks = preg_split('@(</?pre[^>]*>)|(</?script[^>]*>)|(</?style[^>]*>)|( ?</?textarea[^>]*>)@i', $strHtml, -1, PREG_SPLIT_DELIM_CAPTURE|PREG_SPLIT_NO_EMPTY);

		$strHtml = '';
		$blnPreserveNext = false;
		$blnOptimizeNext = false;
		$strType = null;

		// Check for valid JavaScript types (see #7927)
		$isJavaScript = static function ($strChunk) {
			$typeMatch = array();

			if (preg_match('/\stype\s*=\s*(?:(?J)(["\'])\s*(?<type>.*?)\s*\1|(?<type>[^\s>]+))/i', $strChunk, $typeMatch) && !\in_array(strtolower($typeMatch['type']), static::$validJavaScriptTypes))
			{
				return false;
			}

			if (preg_match('/\slanguage\s*=\s*(?:(?J)(["\'])\s*(?<type>.*?)\s*\1|(?<type>[^\s>]+))/i', $strChunk, $typeMatch) && !\in_array('text/' . strtolower($typeMatch['type']), static::$validJavaScriptTypes))
			{
				return false;
			}

			return true;
		};

		// Recombine the markup
		foreach ($arrChunks as $strChunk)
		{
			if (strncasecmp($strChunk, '<pre', 4) === 0 || strncasecmp(ltrim($strChunk), '<textarea', 9) === 0)
			{
				$blnPreserveNext = true;
			}
			elseif (strncasecmp($strChunk, '<script', 7) === 0)
			{
				if ($isJavaScript($strChunk))
				{
					$blnOptimizeNext = true;
					$strType = 'js';
				}
				else
				{
					$blnPreserveNext = true;
				}
			}
			elseif (strncasecmp($strChunk, '<style', 6) === 0)
			{
				$blnOptimizeNext = true;
				$strType = 'css';
			}
			elseif ($blnPreserveNext)
			{
				$blnPreserveNext = false;
			}
			elseif ($blnOptimizeNext)
			{
				$blnOptimizeNext = false;

				// Minify inline scripts
				if ($strType == 'js')
				{
					$objMinify = new JS();
					$objMinify->add($strChunk);
					$strChunk = $objMinify->minify();
				}
				elseif ($strType == 'css')
				{
					$objMinify = new CSS();
					$objMinify->add($strChunk);
					$strChunk = $objMinify->minify();
				}
			}
			else
			{
				// Remove line indentations and trailing spaces
				$strChunk = str_replace("\r", '', $strChunk);
				$strChunk = preg_replace(array('/^[\t ]+/m', '/[\t ]+$/m', '/\n\n+/'), array('', '', "\n"), $strChunk);
			}

			$strHtml .= $strChunk;
		}

		return trim($strHtml);
	}

	/**
	 * Generate the markup for a style sheet tag
	 *
	 * @param string $href  The script path
	 * @param string $media The media type string
	 * @param mixed  $mtime The file mtime
	 *
	 * @return string The markup string
	 */
	public static function generateStyleTag($href, $media=null, $mtime=false)
	{
		// Add the filemtime if not given and not an external file
		if ($mtime === null && !preg_match('@^https?://@', $href))
		{
			$container = System::getContainer();
			$projectDir = $container->getParameter('kernel.project_dir');

			if (file_exists($projectDir . '/' . $href))
			{
				$mtime = filemtime($projectDir . '/' . $href);
			}
			else
			{
				$webDir = StringUtil::stripRootDir($container->getParameter('contao.web_dir'));

				// Handle public bundle resources in the contao.web_dir folder
				if (file_exists($projectDir . '/' . $webDir . '/' . $href))
				{
					$mtime = filemtime($projectDir . '/' . $webDir . '/' . $href);
				}
			}
		}

		if ($mtime)
		{
			$href .= '?v=' . substr(md5($mtime), 0, 8);
		}

		return '<link rel="stylesheet" href="' . $href . '"' . (($media && $media != 'all') ? ' media="' . $media . '"' : '') . '>';
	}

	/**
	 * Generate the markup for inline CSS code
	 *
	 * @param string $script The CSS code
	 *
	 * @return string The markup string
	 */
	public static function generateInlineStyle($script)
	{
		$nonce = self::nonce('style-src');

		return '<style'.($nonce ? ' nonce="' . $nonce . '"' : '').'>' . $script . '</style>';
	}

	/**
	 * Generate the markup for a JavaScript tag
	 *
	 * @param string      $src            The script path
	 * @param boolean     $async          True to add the async attribute
	 * @param mixed       $mtime          The file mtime
	 * @param string|null $hash           An optional integrity hash
	 * @param string|null $crossorigin    An optional crossorigin attribute
	 * @param string|null $referrerpolicy An optional referrerpolicy attribute
	 * @param boolean     $defer          True to add the defer attribute
	 *
	 * @return string The markup string
	 */
	public static function generateScriptTag($src, $async=false, $mtime=false, $hash=null, $crossorigin=null, $referrerpolicy=null, $defer=false)
	{
		// Add the filemtime if not given and not an external file
		if ($mtime === null && !preg_match('@^https?://@', $src))
		{
			$container = System::getContainer();
			$projectDir = $container->getParameter('kernel.project_dir');

			if (file_exists($projectDir . '/' . $src))
			{
				$mtime = filemtime($projectDir . '/' . $src);
			}
			else
			{
				$webDir = StringUtil::stripRootDir($container->getParameter('contao.web_dir'));

				// Handle public bundle resources in the contao.web_dir folder
				if (file_exists($projectDir . '/' . $webDir . '/' . $src))
				{
					$mtime = filemtime($projectDir . '/' . $webDir . '/' . $src);
				}
			}
		}

		if ($mtime)
		{
			$src .= '?v=' . substr(md5($mtime), 0, 8);
		}

		return '<script src="' . $src . '"' . ($async ? ' async' : '') . ($hash ? ' integrity="' . $hash . '"' : '') . ($crossorigin ? ' crossorigin="' . $crossorigin . '"' : '') . ($referrerpolicy ? ' referrerpolicy="' . $referrerpolicy . '"' : '') . ($defer ? ' defer' : '') . '></script>';
	}

	/**
	 * Generate the markup for an inline JavaScript
	 *
	 * @param string $script The JavaScript code
	 *
	 * @return string The markup string
	 */
	public static function generateInlineScript($script)
	{
		$nonce = self::nonce('script-src');

		return '<script'.($nonce ? ' nonce="' . $nonce . '"' : '').'>' . $script . '</script>';
	}

	/**
	 * Generate the markup for an RSS feed tag
	 *
	 * @param string $href   The script path
	 * @param string $format The feed format
	 * @param string $title  The feed title
	 *
	 * @return string The markup string
	 */
	public static function generateFeedTag($href, $format, $title)
	{
		return '<link type="application/' . $format . '+xml" rel="alternate" href="' . $href . '" title="' . StringUtil::specialchars($title) . '">';
	}
}
