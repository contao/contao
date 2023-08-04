<?php

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao;

use Contao\CoreBundle\Config\Loader\XliffFileLoader;
use Contao\CoreBundle\Util\LocaleUtil;
use Contao\Database\Installer;
use Symfony\Component\DependencyInjection\Container;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\DependencyInjection\Exception\ServiceNotFoundException;
use Symfony\Component\Finder\SplFileInfo;
use Symfony\Component\HttpFoundation\Session\Session;

/**
 * Abstract library base class
 *
 * @property Automator                $Automator   The automator object
 * @property Config                   $Config      The config object
 * @property Database                 $Database    The database object
 * @property Environment              $Environment The environment object
 * @property Files                    $Files       The files object
 * @property Input                    $Input       The input object
 * @property Installer                $Installer   The database installer object
 * @property Messages                 $Messages    The messages object
 * @property Session                  $Session     The session object
 * @property BackendUser|FrontendUser $User        The user object
 */
abstract class System
{
	/**
	 * Container
	 * @var ContainerInterface
	 */
	protected static $objContainer;

	/**
	 * @var array|null
	 */
	private static $removedServiceIds;

	/**
	 * Default libraries
	 * @var array
	 */
	protected $arrObjects = array();

	/**
	 * Static objects
	 * @var array
	 */
	protected static $arrStaticObjects = array();

	/**
	 * Singletons
	 * @var array
	 */
	protected static $arrSingletons = array();

	/**
	 * Available languages
	 * @var array
	 */
	protected static $arrLanguages = array();

	/**
	 * Loaded language files
	 * @var array
	 */
	protected static $arrLanguageFiles = array();

	/**
	 * Available image sizes
	 * @var array
	 */
	protected static $arrImageSizes = array();

	/**
	 * Import the Config instance
	 */
	protected function __construct()
	{
		$this->import(Config::class, 'Config'); // backwards compatibility
	}

	/**
	 * Get an object property
	 *
	 * @param string $strKey The property name
	 *
	 * @return mixed|null The property value or null
	 */
	public function __get($strKey)
	{
		if (!isset($this->arrObjects[$strKey]))
		{
			return null;
		}

		trigger_deprecation('contao/core-bundle', '5.2', 'Using objects that have been imported via "Contao\System::import()" has been deprecated and will no longer work in Contao 6. Use "Contao\System::importStatic()" or dependency injection instead.');

		return $this->arrObjects[$strKey];
	}

	/**
	 * Import a library and make it accessible by its name or an optional key
	 *
	 * @param string|object $strClass The class name
	 * @param string|object $strKey   An optional key to store the object under
	 * @param boolean       $blnForce If true, existing objects will be overridden
	 *
	 * @throws ServiceNotFoundException
	 */
	protected function import($strClass, $strKey=null, $blnForce=false)
	{
		$strKey = $strKey ?: $strClass;

		if (\is_object($strKey))
		{
			$strKey = \get_class($strClass);
		}

		if ($blnForce || !isset($this->arrObjects[$strKey]))
		{
			$container = static::getContainer();

			if (null === $container)
			{
				throw new \RuntimeException('The Symfony container is not available, did you initialize the Contao framework?');
			}

			if (\is_object($strClass))
			{
				$this->arrObjects[$strKey] = $strClass;
			}
			elseif (isset(static::$arrSingletons[$strClass]))
			{
				$this->arrObjects[$strKey] = static::$arrSingletons[$strClass];
			}
			elseif ($container->has($strClass) && (strpos($strClass, '\\') !== false || !class_exists($strClass)))
			{
				$this->arrObjects[$strKey] = $container->get($strClass);
			}
			elseif (($container->getParameter('kernel.debug') || !class_exists($strClass)) && self::isServiceInlined($strClass))
			{
				// In debug mode, we check for inlined services before trying to create a new instance of the class
				throw new ServiceNotFoundException($strClass, null, null, array(), sprintf('The "%s" service or alias has been removed or inlined when the container was compiled. You should either make it public, or stop using the container directly and use dependency injection instead.', $strClass));
			}
			elseif (!class_exists($strClass))
			{
				throw new \RuntimeException('System::import() failed because class "' . $strClass . '" is not a valid class name or does not exist.');
			}
			elseif (\in_array('getInstance', get_class_methods($strClass)))
			{
				$this->arrObjects[$strKey] = static::$arrSingletons[$strClass] = \call_user_func(array($strClass, 'getInstance'));
			}
			else
			{
				try
				{
					$this->arrObjects[$strKey] = new $strClass();
				}
				catch (\Throwable $t)
				{
					if (!$container->getParameter('kernel.debug') && self::isServiceInlined($strClass))
					{
						throw new ServiceNotFoundException($strClass, null, null, array(), sprintf('The "%s" service or alias has been removed or inlined when the container was compiled. You should either make it public, or stop using the container directly and use dependency injection instead.', $strClass));
					}

					throw $t;
				}
			}
		}
	}

	/**
	 * Import a library in non-object context
	 *
	 * @param string|object $strClass The class name
	 * @param string|object $strKey   An optional key to store the object under
	 * @param boolean       $blnForce If true, existing objects will be overridden
	 *
	 * @throws ServiceNotFoundException
	 *
	 * @return object The imported object
	 */
	public static function importStatic($strClass, $strKey=null, $blnForce=false)
	{
		$strKey = $strKey ?: $strClass;

		if (\is_object($strKey))
		{
			$strKey = \get_class($strClass);
		}

		if ($blnForce || !isset(static::$arrStaticObjects[$strKey]))
		{
			$container = static::getContainer();

			if (null === $container)
			{
				throw new \RuntimeException('The Symfony container is not available, did you initialize the Contao framework?');
			}

			if (\is_object($strClass))
			{
				static::$arrStaticObjects[$strKey] = $strClass;
			}
			elseif (isset(static::$arrSingletons[$strClass]))
			{
				static::$arrStaticObjects[$strKey] = static::$arrSingletons[$strClass];
			}
			elseif ($container->has($strClass) && (strpos($strClass, '\\') !== false || !class_exists($strClass)))
			{
				static::$arrStaticObjects[$strKey] = $container->get($strClass);
			}
			elseif (($container->getParameter('kernel.debug') || !class_exists($strClass)) && self::isServiceInlined($strClass))
			{
				// In debug mode, we check for inlined services before trying to create a new instance of the class
				throw new ServiceNotFoundException($strClass, null, null, array(), sprintf('The "%s" service or alias has been removed or inlined when the container was compiled. You should either make it public, or stop using the container directly and use dependency injection instead.', $strClass));
			}
			elseif (!class_exists($strClass))
			{
				throw new \RuntimeException('System::importStatic() failed because class "' . $strClass . '" is not a valid class name or does not exist.');
			}
			elseif (\in_array('getInstance', get_class_methods($strClass)))
			{
				static::$arrStaticObjects[$strKey] = static::$arrSingletons[$strClass] = \call_user_func(array($strClass, 'getInstance'));
			}
			else
			{
				try
				{
					static::$arrStaticObjects[$strKey] = new $strClass();
				}
				catch (\Throwable $t)
				{
					if (!$container->getParameter('kernel.debug') && self::isServiceInlined($strClass))
					{
						throw new ServiceNotFoundException($strClass, null, null, array(), sprintf('The "%s" service or alias has been removed or inlined when the container was compiled. You should either make it public, or stop using the container directly and use dependency injection instead.', $strClass));
					}

					throw $t;
				}
			}
		}

		return static::$arrStaticObjects[$strKey];
	}

	private static function isServiceInlined($strClass)
	{
		$container = static::getContainer();

		if (!$container instanceof Container)
		{
			return false;
		}

		if (null === self::$removedServiceIds)
		{
			self::$removedServiceIds = $container->getRemovedIds();
		}

		return isset(self::$removedServiceIds[$strClass]);
	}

	/**
	 * Return the container object
	 *
	 * @return ContainerInterface The container object
	 */
	public static function getContainer()
	{
		return static::$objContainer;
	}

	/**
	 * Set the container object
	 *
	 * @param ContainerInterface $container The container object
	 */
	public static function setContainer(ContainerInterface $container)
	{
		static::$objContainer = $container;
	}

	/**
	 * Return the referer URL and optionally encode ampersands
	 *
	 * @param boolean $blnEncodeAmpersands If true, ampersands will be encoded
	 * @param string  $strTable            An optional table name
	 *
	 * @return string The referer URL
	 */
	public static function getReferer($blnEncodeAmpersands=false, $strTable=null)
	{
		$objSession = static::getContainer()->get('request_stack')->getSession();
		$ref = Input::get('ref');
		$key = Input::get('popup') ? 'popupReferer' : 'referer';
		$session = $objSession->get($key);
		$return = null;
		$request = static::getContainer()->get('request_stack')->getCurrentRequest();
		$isBackend = $request && static::getContainer()->get('contao.routing.scope_matcher')->isBackendRequest($request);
		$isFrontend = $request && static::getContainer()->get('contao.routing.scope_matcher')->isFrontendRequest($request);

		if (null !== $session)
		{
			// Unique referer ID
			if ($ref && isset($session[$ref]))
			{
				$session = $session[$ref];
			}
			elseif ($isBackend && \is_array($session))
			{
				$session = end($session);
			}

			// Use a specific referer
			if ($strTable && isset($session[$strTable]) && Input::get('act') != 'select')
			{
				$session['current'] = $session[$strTable];
			}

			// Remove parameters helper
			$cleanUrl = static function ($url, $params = array('rt', 'ref', 'revise')) {
				if (!$url || strpos($url, '?') === false)
				{
					return $url;
				}

				list($path, $query) = explode('?', $url, 2);

				parse_str($query, $pairs);

				foreach ($params as $param)
				{
					unset($pairs[$param]);
				}

				if (empty($pairs))
				{
					return $path;
				}

				return $path . '?' . http_build_query($pairs, '', '&', PHP_QUERY_RFC3986);
			};

			// Determine current or last
			$strUrl = ($cleanUrl($session['current'] ?? null) != $cleanUrl(Environment::get('requestUri'))) ? ($session['current'] ?? null) : ($session['last'] ?? null);

			// Remove the "toggle" and "toggle all" parameters
			$return = $cleanUrl($strUrl, array('tg', 'ptg'));
		}

		// Fallback to the generic referer in the front end
		if (!$return && $isFrontend)
		{
			$return = Environment::get('httpReferer');
		}

		// Fallback to the current URL if there is no referer
		if (!$return)
		{
			if ($isBackend)
			{
				$return = static::getContainer()->get('router')->generate('contao_backend');
			}
			else
			{
				$return = Environment::get('url');
			}
		}

		// Do not urldecode here!
		return preg_replace('/&(amp;)?/i', $blnEncodeAmpersands ? '&amp;' : '&', $return);
	}

	/**
	 * Load a set of language files
	 *
	 * @param string      $strName     The table name
	 * @param string|null $strLanguage An optional language code
	 * @param boolean     $blnNoCache  If true, the cache will be bypassed
	 */
	public static function loadLanguageFile($strName, $strLanguage=null, $blnNoCache=false)
	{
		if ($strLanguage === null)
		{
			$strLanguage = LocaleUtil::formatAsLocale($GLOBALS['TL_LANGUAGE'] ?? 'en');
		}

		// Fall back to English
		if (!$strLanguage)
		{
			$strLanguage = 'en';
		}

		if (1 !== preg_match('/^[a-z0-9_-]+$/i', $strName))
		{
			throw new \InvalidArgumentException(sprintf('Invalid language file name "%s"', $strName));
		}

		// Return if the language file has been loaded already
		if (!$blnNoCache && array_key_last(static::$arrLanguageFiles[$strName] ?? array()) === $strLanguage)
		{
			return;
		}

		$strCacheKey = $strLanguage;

		// Make sure the language exists
		if ($strLanguage != 'en' && !static::isInstalledLanguage($strLanguage))
		{
			$strShortLang = substr($strLanguage, 0, 2);

			// Fall back to "de" if "de_DE" does not exist
			if ($strShortLang != $strLanguage && static::isInstalledLanguage($strShortLang))
			{
				$strLanguage = $strShortLang;
			}

			// Fall back to English (see #6581)
			else
			{
				$strLanguage = 'en';
			}
		}

		// Unset to move the new array key to the last position
		unset(static::$arrLanguageFiles[$strName][$strCacheKey]);

		// Use a global cache variable to support nested calls
		static::$arrLanguageFiles[$strName][$strCacheKey] = $strLanguage;

		// Fall back to English
		$arrCreateLangs = ($strLanguage == 'en') ? array('en') : array('en', $strLanguage);

		// Prepare the XLIFF loader
		$xlfLoader = new XliffFileLoader(static::getContainer()->getParameter('kernel.project_dir'), true);
		$strCacheDir = static::getContainer()->getParameter('kernel.cache_dir');

		// Load the language(s)
		foreach ($arrCreateLangs as $strCreateLang)
		{
			// Try to load from cache
			if (file_exists($strCacheDir . '/contao/languages/' . $strCreateLang . '/' . $strName . '.php'))
			{
				include $strCacheDir . '/contao/languages/' . $strCreateLang . '/' . $strName . '.php';
			}
			else
			{
				// Find the given filename either as .php or .xlf file
				$finder = static::getContainer()->get('contao.resource_finder')->findIn('languages/' . $strCreateLang)->name('/^' . $strName . '\.(php|xlf)$/');

				/** @var SplFileInfo $file */
				foreach ($finder as $file)
				{
					switch ($file->getExtension())
					{
						case 'php':
							include $file;
							break;

						case 'xlf':
							$xlfLoader->load($file, $strCreateLang);
							break;

						default:
							throw new \RuntimeException(sprintf('Invalid language file extension: %s', $file->getExtension()));
					}
				}
			}
		}

		// Set MSC.textDirection (see #3360)
		if ('default' === $strName)
		{
			$GLOBALS['TL_LANG']['MSC']['textDirection'] = (\ResourceBundle::create($strLanguage, 'ICUDATA', true)['layout']['characters'] ?? null) === 'right-to-left' ? 'rtl' : 'ltr';
		}

		// HOOK: allow loading custom labels
		if (isset($GLOBALS['TL_HOOKS']['loadLanguageFile']) && \is_array($GLOBALS['TL_HOOKS']['loadLanguageFile']))
		{
			foreach ($GLOBALS['TL_HOOKS']['loadLanguageFile'] as $callback)
			{
				static::importStatic($callback[0])->{$callback[1]}($strName, $strLanguage, $strCacheKey);
			}
		}

		// Handle single quotes in the deleteConfirm message
		if ($strName == 'default' && isset($GLOBALS['TL_LANG']['MSC']['deleteConfirm']))
		{
			$GLOBALS['TL_LANG']['MSC']['deleteConfirm'] = str_replace("'", "\\'", $GLOBALS['TL_LANG']['MSC']['deleteConfirm']);
		}
	}

	/**
	 * Check whether a language is installed
	 *
	 * @param boolean $strLanguage The language code
	 *
	 * @return boolean True if the language is installed
	 */
	public static function isInstalledLanguage($strLanguage)
	{
		if (!isset(static::$arrLanguages[$strLanguage]))
		{
			if (LocaleUtil::canonicalize($strLanguage) !== $strLanguage)
			{
				return false;
			}

			$projectDir = self::getContainer()->getParameter('kernel.project_dir');

			if (is_dir($projectDir . '/vendor/contao/core-bundle/contao/languages/' . $strLanguage))
			{
				static::$arrLanguages[$strLanguage] = true;
			}
			elseif (is_dir(static::getContainer()->getParameter('kernel.cache_dir') . '/contao/languages/' . $strLanguage))
			{
				static::$arrLanguages[$strLanguage] = true;
			}
			else
			{
				static::$arrLanguages[$strLanguage] = static::getContainer()->get('contao.resource_finder')->findIn('languages')->depth(0)->directories()->name($strLanguage)->hasResults();
			}
		}

		return static::$arrLanguages[$strLanguage];
	}

	/**
	 * Urlencode a file path preserving slashes
	 *
	 * @param string $strPath The file path
	 *
	 * @return string The encoded file path
	 */
	public static function urlEncode($strPath)
	{
		return str_replace('%2F', '/', rawurlencode((string) $strPath));
	}

	/**
	 * Set a cookie
	 *
	 * @param string       $strName     The cookie name
	 * @param mixed        $varValue    The cookie value
	 * @param integer      $intExpires  The expiration date
	 * @param string|null  $strPath     An optional path
	 * @param string|null  $strDomain   An optional domain name
	 * @param boolean|null $blnSecure   If true, the secure flag will be set
	 * @param boolean      $blnHttpOnly If true, the http-only flag will be set
	 */
	public static function setCookie($strName, $varValue, $intExpires, $strPath=null, $strDomain=null, $blnSecure=null, $blnHttpOnly=false)
	{
		if (!$strPath)
		{
			$strPath = Environment::get('path') ?: '/'; // see #4390
		}

		if ($blnSecure === null)
		{
			$blnSecure = false;

			if ($request = static::getContainer()->get('request_stack')->getCurrentRequest())
			{
				$blnSecure = $request->isSecure();
			}
		}

		$objCookie = new \stdClass();

		$objCookie->strName     = $strName;
		$objCookie->varValue    = $varValue;
		$objCookie->intExpires  = $intExpires;
		$objCookie->strPath     = $strPath;
		$objCookie->strDomain   = $strDomain;
		$objCookie->blnSecure   = $blnSecure;
		$objCookie->blnHttpOnly = $blnHttpOnly;

		// HOOK: allow adding custom logic
		if (isset($GLOBALS['TL_HOOKS']['setCookie']) && \is_array($GLOBALS['TL_HOOKS']['setCookie']))
		{
			foreach ($GLOBALS['TL_HOOKS']['setCookie'] as $callback)
			{
				$objCookie = static::importStatic($callback[0])->{$callback[1]}($objCookie);
			}
		}

		setcookie($objCookie->strName, $objCookie->varValue, $objCookie->intExpires, $objCookie->strPath, $objCookie->strDomain, $objCookie->blnSecure, $objCookie->blnHttpOnly);
	}

	/**
	 * Convert a byte value into a human-readable format
	 *
	 * @param integer $intSize     The size in bytes
	 * @param integer $intDecimals The number of decimals to show
	 *
	 * @return string The human-readable size
	 */
	public static function getReadableSize($intSize, $intDecimals=1)
	{
		for ($i=0; $intSize>=1024; $i++)
		{
			$intSize /= 1024;
		}

		return static::getFormattedNumber($intSize, $intDecimals) . ' ' . $GLOBALS['TL_LANG']['UNITS'][$i];
	}

	/**
	 * Format a number
	 *
	 * @param mixed   $varNumber   An integer or float number
	 * @param integer $intDecimals The number of decimals to show
	 *
	 * @return mixed The formatted number
	 */
	public static function getFormattedNumber($varNumber, $intDecimals=2)
	{
		return number_format(round($varNumber, $intDecimals), $intDecimals, $GLOBALS['TL_LANG']['MSC']['decimalSeparator'], $GLOBALS['TL_LANG']['MSC']['thousandsSeparator']);
	}

	/**
	 * Anonymize an IP address by overriding the last chunk
	 *
	 * @param string $strIp The IP address
	 *
	 * @return string The encoded IP address
	 */
	public static function anonymizeIp($strIp)
	{
		// Localhost
		if ($strIp == '127.0.0.1' || $strIp == '::1')
		{
			return $strIp;
		}

		// IPv6
		if (strpos($strIp, ':') !== false)
		{
			return substr_replace($strIp, ':0000', strrpos($strIp, ':'));
		}

		// IPv4
		return substr_replace($strIp, '.0', strrpos($strIp, '.'));
	}
}
