<?php

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao;

use Contao\CoreBundle\Config\Loader\PhpFileLoader;
use Contao\CoreBundle\Config\Loader\XliffFileLoader;
use Contao\CoreBundle\Monolog\ContaoContext;
use Contao\CoreBundle\Util\LocaleUtil;
use Contao\Database\Installer;
use Contao\Database\Updater;
use Psr\Log\LogLevel;
use Symfony\Component\DependencyInjection\Container;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\DependencyInjection\Exception\ServiceNotFoundException;
use Symfony\Component\Filesystem\Path;
use Symfony\Component\Finder\SplFileInfo;
use Symfony\Component\HttpFoundation\Session\Session;

/**
 * Abstract library base class
 *
 * The class provides miscellaneous methods that are used all throughout the
 * application. It is the base class of the Contao library which provides the
 * central "import" method to load other library classes.
 *
 * Usage:
 *
 *     class MyClass extends System
 *     {
 *         public function __construct()
 *         {
 *             $this->import('Database');
 *         }
 *     }
 *
 * @property Automator                        $Automator   The automator object
 * @property Config                           $Config      The config object
 * @property Database                         $Database    The database object
 * @property Environment                      $Environment The environment object
 * @property Files                            $Files       The files object
 * @property Input                            $Input       The input object
 * @property Installer                        $Installer   The database installer object
 * @property Updater                          $Updater     The database updater object
 * @property Messages                         $Messages    The messages object
 * @property Session                          $Session     The session object
 * @property StyleSheets                      $StyleSheets The style sheets object
 * @property BackendTemplate|FrontendTemplate $Template    The template object (TODO: remove this line in Contao 5.0)
 * @property BackendUser|FrontendUser         $User        The user object
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
	 * Cache
	 * @var array
	 * @deprecated Deprecated since Contao 4.0, to be removed in Contao 5.0.
	 */
	protected $arrCache = array();

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
	 * Available language files
	 * @var array|false|null
	 */
	protected static $arrAvailableLanguageFiles;

	/**
	 * Import the Config instance
	 */
	protected function __construct()
	{
		$this->import(Config::class, 'Config');
	}

	/**
	 * Get an object property
	 *
	 * Lazy load the Input and Environment libraries (which are now static) and
	 * only include them as object property if an old module requires it
	 *
	 * @param string $strKey The property name
	 *
	 * @return mixed|null The property value or null
	 */
	public function __get($strKey)
	{
		if (!isset($this->arrObjects[$strKey]))
		{
			/** @var Input|Environment|Session|string $strKey */
			if ($strKey == 'Input' || $strKey == 'Environment' || $strKey == 'Session')
			{
				$this->arrObjects[$strKey] = $strKey::getInstance();
			}
			else
			{
				return null;
			}
		}

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
	 * Add a log entry to the database
	 *
	 * @param string $strText     The log message
	 * @param string $strFunction The function name
	 * @param string $strCategory The category name
	 *
	 * @deprecated Deprecated since Contao 4.2, to be removed in Contao 5.
	 *             Use the logger service with your context or any of the predefined monolog.logger.contao services instead.
	 */
	public static function log($strText, $strFunction, $strCategory)
	{
		trigger_deprecation('contao/core-bundle', '4.2', 'Using "Contao\System::log()" has been deprecated and will no longer work in Contao 5.0. Use the "logger" service or any of the predefined "monolog.logger.contao" services instead.');

		$level = 'ERROR' === $strCategory ? LogLevel::ERROR : LogLevel::INFO;
		$logger = static::getContainer()->get('monolog.logger.contao');

		$logger->log($level, $strText, array('contao' => new ContaoContext($strFunction, $strCategory)));
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
		$objSession = static::getContainer()->get('session');
		$ref = Input::get('ref');
		$key = Input::get('popup') ? 'popupReferer' : 'referer';
		$session = $objSession->get($key);
		$return = null;

		if (null !== $session)
		{
			// Unique referer ID
			if ($ref && isset($session[$ref]))
			{
				$session = $session[$ref];
			}
			elseif (\defined('TL_MODE') && TL_MODE == 'BE' && \is_array($session))
			{
				$session = end($session);
			}

			// Use a specific referer
			if ($strTable && isset($session[$strTable]) && Input::get('act') != 'select')
			{
				$session['current'] = $session[$strTable];
			}

			// Remove parameters helper
			$cleanUrl = static function ($url, $params = array('rt', 'ref'))
			{
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
			$strUrl = ($cleanUrl($session['current'] ?? null) != $cleanUrl(Environment::get('request'))) ? ($session['current'] ?? null) : ($session['last'] ?? null);

			// Remove the "toggle" and "toggle all" parameters
			$return = $cleanUrl($strUrl, array('tg', 'ptg'));
		}

		// Fallback to the generic referer in the front end
		if (!$return && \defined('TL_MODE') && TL_MODE == 'FE')
		{
			$return = Environment::get('httpReferer');
		}

		// Fallback to the current URL if there is no referer
		if (!$return)
		{
			if (\defined('TL_MODE') && TL_MODE == 'BE')
			{
				$return = static::getContainer()->get('router')->generate('contao_backend');
			}
			else
			{
				$return = Environment::get('url');
			}
		}

		// Do not urldecode here!
		return preg_replace('/&(amp;)?/i', ($blnEncodeAmpersands ? '&amp;' : '&'), $return);
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

		// Backwards compatibility
		if ('languages' === $strName)
		{
			// Reset previously loaded languages without destroying references
			foreach (array_keys($GLOBALS['TL_LANG']['LNG'] ?? array()) as $strLocale)
			{
				$GLOBALS['TL_LANG']['LNG'][$strLocale] = null;
			}

			foreach (self::getContainer()->get('contao.intl.locales')->getLocales($strCacheKey) as $strLocale => $strLabel)
			{
				$GLOBALS['TL_LANG']['LNG'][$strLocale] = $strLabel;
			}
		}

		// Backwards compatibility
		if ('countries' === $strName)
		{
			// Reset previously loaded countries without destroying references
			foreach (array_keys($GLOBALS['TL_LANG']['CNT'] ?? array()) as $strLocale)
			{
				$GLOBALS['TL_LANG']['CNT'][$strLocale] = null;
			}

			foreach (self::getContainer()->get('contao.intl.countries')->getCountries($strCacheKey) as $strCountryCode => $strLabel)
			{
				$GLOBALS['TL_LANG']['CNT'][strtolower($strCountryCode)] = $strLabel;
			}
		}

		// Fall back to English
		$arrCreateLangs = ($strLanguage == 'en') ? array('en') : array('en', $strLanguage);

		// Prepare the XLIFF loader
		$xlfLoader = new XliffFileLoader(static::getContainer()->getParameter('kernel.project_dir'), true);
		$strCacheDir = static::getContainer()->getParameter('kernel.cache_dir');

		if (null === self::$arrAvailableLanguageFiles)
		{
			$availLangFilesPath = Path::join($strCacheDir, 'contao/config/available-language-files.php');
			self::$arrAvailableLanguageFiles = file_exists($availLangFilesPath) ? include $availLangFilesPath : false;
		}

		// Load the language(s)
		foreach ($arrCreateLangs as $strCreateLang)
		{
			// Skip languages that are not available (#6454)
			if (\is_array(self::$arrAvailableLanguageFiles) && !isset(self::$arrAvailableLanguageFiles[$strCreateLang][$strName]))
			{
				continue;
			}

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

		$projectDir = self::getContainer()->getParameter('kernel.project_dir');

		// Local configuration file
		if (file_exists($projectDir . '/system/config/langconfig.php'))
		{
			trigger_deprecation('contao/core-bundle', '4.3', 'Using the "langconfig.php" file has been deprecated and will no longer work in Contao 5.0. Create custom language files in the "contao/languages" folder instead.');
			include $projectDir . '/system/config/langconfig.php';
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

			if (is_dir($projectDir . '/vendor/contao/core-bundle/src/Resources/contao/languages/' . $strLanguage))
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
	 * Return the countries as array
	 *
	 * @return array An array of country names
	 *
	 * @deprecated Deprecated since Contao 4.12, to be removed in Contao 5;
	 *             use the Contao\CoreBundle\Intl\Countries service instead
	 */
	public static function getCountries()
	{
		trigger_deprecation('contao/core-bundle', '4.12', 'Using the %s method has been deprecated and will no longer work in Contao 5.0. Use the "contao.intl.countries" service instead.', __METHOD__);

		$arrCountries = self::getContainer()->get('contao.intl.countries')->getCountries();

		return array_combine(array_map('strtolower', array_keys($arrCountries)), $arrCountries);
	}

	/**
	 * Return the available languages as array
	 *
	 * @param boolean $blnInstalledOnly If true, return only installed languages
	 *
	 * @return array An array of languages
	 *
	 * @deprecated Deprecated since Contao 4.12, to be removed in Contao 5;
	 *             use the Contao\CoreBundle\Intl\Locales service instead
	 */
	public static function getLanguages($blnInstalledOnly=false)
	{
		trigger_deprecation('contao/core-bundle', '4.12', 'Using the %s method has been deprecated and will no longer work in Contao 5.0. Use the "contao.intl.locales" service instead.', __METHOD__);

		if ($blnInstalledOnly)
		{
			return self::getContainer()->get('contao.intl.locales')->getEnabledLocales(null, true);
		}

		return self::getContainer()->get('contao.intl.locales')->getLocales(null, true);
	}

	/**
	 * Return the timezones as array
	 *
	 * @return array An array of timezones
	 */
	public static function getTimeZones()
	{
		trigger_deprecation('contao/core-bundle', '4.13', 'Using the %s method has been deprecated and will no longer work in Contao 5.0. Use the DateTimeZone::listIdentifiers() instead.', __METHOD__);

		$arrReturn = array();
		$timezones = array();

		require __DIR__ . '/../../config/timezones.php';

		foreach ($timezones as $strGroup=>$arrTimezones)
		{
			foreach ($arrTimezones as $strTimezone)
			{
				$arrReturn[$strGroup][] = $strTimezone;
			}
		}

		return $arrReturn;
	}

	/**
	 * Return all image sizes as array
	 *
	 * @return array The available image sizes
	 *
	 * @deprecated Deprecated since Contao 4.1, to be removed in Contao 5.
	 *             Use the contao.image.sizes service instead.
	 */
	public static function getImageSizes()
	{
		trigger_deprecation('contao/core-bundle', '4.1', 'Using "Contao\System::getImageSizes()" has been deprecated and will no longer work in Contao 5.0. Use the "contao.image.sizes" service instead.');

		return static::getContainer()->get('contao.image.sizes')->getAllOptions();
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
	 * Return the session hash
	 *
	 * @param string $strCookie The cookie name
	 *
	 * @return string The session hash
	 *
	 * @deprecated Deprecated since Contao 4.5, to be removed in Contao 5.0.
	 *             Use Symfony authentication instead.
	 */
	public static function getSessionHash($strCookie)
	{
		trigger_deprecation('contao/core-bundle', '4.5', 'Using "Contao\System::getSessionHash()" has been deprecated and will no longer work in Contao 5.0. Use Symfony authentication instead.');

		$session = static::getContainer()->get('session');

		if (!$session->isStarted())
		{
			$session->start();
		}

		return sha1($session->getId() . $strCookie);
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

	/**
	 * Read the contents of a PHP file, stripping the opening and closing PHP tags
	 *
	 * @param string $strName The name of the PHP file
	 *
	 * @return string The PHP code without the PHP tags
	 *
	 * @deprecated Deprecated since Contao 4.0, to be removed in Contao 5.0.
	 *             Use the Contao\CoreBundle\Config\Loader\PhpFileLoader class instead.
	 */
	protected static function readPhpFileWithoutTags($strName)
	{
		trigger_deprecation('contao/core-bundle', '4.0', 'Using "Contao\System::readPhpFileWithoutTags()" has been deprecated and will no longer work in Contao 5.0. Use the "Contao\CoreBundle\Config\Loader\PhpFileLoader" class instead.');

		$projectDir = self::getContainer()->getParameter('kernel.project_dir');

		// Convert to absolute path
		if (strpos($strName, $projectDir . '/') === false)
		{
			$strName = $projectDir . '/' . $strName;
		}

		$loader = new PhpFileLoader();

		return $loader->load($strName);
	}

	/**
	 * Convert an .xlf file into a PHP language file
	 *
	 * @param string  $strName     The name of the .xlf file
	 * @param string  $strLanguage The language code
	 * @param boolean $blnLoad     Add the labels to the global language array
	 *
	 * @return string The PHP code
	 *
	 * @deprecated Deprecated since Contao 4.0, to be removed in Contao 5.0.
	 *             Use the Contao\CoreBundle\Config\Loader\XliffFileLoader class instead.
	 */
	public static function convertXlfToPhp($strName, $strLanguage, $blnLoad=false)
	{
		trigger_deprecation('contao/core-bundle', '4.0', 'Using "Contao\System::convertXlfToPhp()" has been deprecated and will no longer work in Contao 5.0. Use the "Contao\CoreBundle\Config\Loader\XliffFileLoader" class instead.');

		$projectDir = self::getContainer()->getParameter('kernel.project_dir');

		// Convert to absolute path
		if (strpos($strName, $projectDir . '/') === false)
		{
			$strName = $projectDir . '/' . $strName;
		}

		$loader = new XliffFileLoader(static::getContainer()->getParameter('kernel.project_dir'), $blnLoad);

		return $loader->load($strName, $strLanguage);
	}

	/**
	 * Parse a date format string and translate textual representations
	 *
	 * @param string  $strFormat The date format string
	 * @param integer $intTstamp An optional timestamp
	 *
	 * @return string The textual representation of the date
	 *
	 * @deprecated Deprecated since Contao 4.0, to be removed in Contao 5.0.
	 *             Use Date::parse() instead.
	 */
	public static function parseDate($strFormat, $intTstamp=null)
	{
		trigger_deprecation('contao/core-bundle', '4.0', 'Using "Contao\System::parseDate()" has been deprecated and will no longer work in Contao 5.0. Use "Contao\Date::parse()" instead.');

		return Date::parse($strFormat, $intTstamp);
	}

	/**
	 * Add a request string to the current URL
	 *
	 * @param string $strRequest The string to be added
	 *
	 * @return string The new URL
	 *
	 * @deprecated Deprecated since Contao 4.0, to be removed in Contao 5.0.
	 *             Use Controller::addToUrl() instead.
	 */
	public static function addToUrl($strRequest)
	{
		trigger_deprecation('contao/core-bundle', '4.0', 'Using "Contao\System::addToUrl()" has been deprecated and will no longer work in Contao 5.0. Use "Contao\Controller::addToUrl()" instead.');

		return Controller::addToUrl($strRequest);
	}

	/**
	 * Reload the current page
	 *
	 * @deprecated Deprecated since Contao 4.0, to be removed in Contao 5.0.
	 *             Use Controller::reload() instead.
	 */
	public static function reload()
	{
		trigger_deprecation('contao/core-bundle', '4.0', 'Using "Contao\System::reload()" has been deprecated and will no longer work in Contao 5.0. Use "Contao\Controller::reload()" instead.');

		Controller::reload();
	}

	/**
	 * Redirect to another page
	 *
	 * @param string  $strLocation The target URL
	 * @param integer $intStatus   The HTTP status code (defaults to 303)
	 *
	 * @deprecated Deprecated since Contao 4.0, to be removed in Contao 5.0.
	 *             Use Controller::redirect() instead.
	 */
	public static function redirect($strLocation, $intStatus=303)
	{
		trigger_deprecation('contao/core-bundle', '4.0', 'Using "Contao\System::redirect()" has been deprecated and will no longer work in Contao 5.0. Use "Contao\Controller::redirect()" instead.');

		Controller::redirect($strLocation, $intStatus);
	}

	/**
	 * Add an error message
	 *
	 * @param string $strMessage The error message
	 *
	 * @deprecated Deprecated since Contao 4.0, to be removed in Contao 5.0.
	 *             Use Message::addError() instead.
	 */
	protected function addErrorMessage($strMessage)
	{
		trigger_deprecation('contao/core-bundle', '4.0', 'Using "Contao\System::addErrorMessage()" has been deprecated and will no longer work in Contao 5.0. Use "Contao\Message::addError()" instead.');

		Message::addError($strMessage);
	}

	/**
	 * Add a confirmation message
	 *
	 * @param string $strMessage The confirmation
	 *
	 * @deprecated Deprecated since Contao 4.0, to be removed in Contao 5.0.
	 *             Use Message::addConfirmation() instead.
	 */
	protected function addConfirmationMessage($strMessage)
	{
		trigger_deprecation('contao/core-bundle', '4.0', 'Using "Contao\System::addConfirmationMessage()" has been deprecated and will no longer work in Contao 5.0. Use "Contao\Message::addConfirmation()" instead.');

		Message::addConfirmation($strMessage);
	}

	/**
	 * Add a new message
	 *
	 * @param string $strMessage The new message
	 *
	 * @deprecated Deprecated since Contao 4.0, to be removed in Contao 5.0.
	 *             Use Message::addNew() instead.
	 */
	protected function addNewMessage($strMessage)
	{
		trigger_deprecation('contao/core-bundle', '4.0', 'Using "Contao\System::addNewMessage()" has been deprecated and will no longer work in Contao 5.0. Use "Contao\Message::addNew()" instead.');

		Message::addNew($strMessage);
	}

	/**
	 * Add an info message
	 *
	 * @param string $strMessage The info message
	 *
	 * @deprecated Deprecated since Contao 4.0, to be removed in Contao 5.0.
	 *             Use Message::addInfo() instead.
	 */
	protected function addInfoMessage($strMessage)
	{
		trigger_deprecation('contao/core-bundle', '4.0', 'Using "Contao\System::addInfoMessage()" has been deprecated and will no longer work in Contao 5.0. Use "Contao\Message::addInfo()" instead.');

		Message::addInfo($strMessage);
	}

	/**
	 * Add an unformatted message
	 *
	 * @param string $strMessage The unformatted message
	 *
	 * @deprecated Deprecated since Contao 4.0, to be removed in Contao 5.0.
	 *             Use Message::addRaw() instead.
	 */
	protected function addRawMessage($strMessage)
	{
		trigger_deprecation('contao/core-bundle', '4.0', 'Using "Contao\System::addRawMessage()" has been deprecated and will no longer work in Contao 5.0. Use "Contao\Message::addRaw()" instead.');

		Message::addRaw($strMessage);
	}

	/**
	 * Add a message
	 *
	 * @param string $strMessage The message
	 * @param string $strType    The message type
	 *
	 * @deprecated Deprecated since Contao 4.0, to be removed in Contao 5.0.
	 *             Use Message::add() instead.
	 */
	protected function addMessage($strMessage, $strType)
	{
		trigger_deprecation('contao/core-bundle', '4.0', 'Using "Contao\System::addMessage()" has been deprecated and will no longer work in Contao 5.0. Use "Contao\Message::add()" instead.');

		Message::add($strMessage, $strType);
	}

	/**
	 * Return all messages as HTML
	 *
	 * @param string $strScope An optional message scope
	 *
	 * @return string The messages HTML markup
	 *
	 * @deprecated Deprecated since Contao 4.0, to be removed in Contao 5.0.
	 *             Use Message::generate() instead.
	 */
	protected function getMessages($strScope=null)
	{
		trigger_deprecation('contao/core-bundle', '4.0', 'Using "Contao\System::getMessages()" has been deprecated and will no longer work in Contao 5.0. Use "Contao\Message::generate()" instead.');

		return Message::generate($strScope ?? TL_MODE);
	}

	/**
	 * Reset the message system
	 *
	 * @deprecated Deprecated since Contao 4.0, to be removed in Contao 5.0.
	 *             Use Message::reset() instead.
	 */
	protected function resetMessages()
	{
		trigger_deprecation('contao/core-bundle', '4.0', 'Using "Contao\System::resetMessages()" has been deprecated and will no longer work in Contao 5.0. Use "Contao\Message::reset()" instead.');

		Message::reset();
	}

	/**
	 * Return all available message types
	 *
	 * @return array An array of message types
	 *
	 * @deprecated Deprecated since Contao 4.0, to be removed in Contao 5.0.
	 *             Use Message::getTypes() instead.
	 */
	protected function getMessageTypes()
	{
		trigger_deprecation('contao/core-bundle', '4.0', 'Using "Contao\System::getMessageTypes()" has been deprecated and will no longer work in Contao 5.0. Use "Contao\Message::getTypes()" instead.');

		return Message::getTypes();
	}

	/**
	 * Encode an internationalized domain name
	 *
	 * @param string $strDomain The domain name
	 *
	 * @return string The encoded domain name
	 *
	 * @deprecated Deprecated since Contao 4.0, to be removed in Contao 5.0.
	 *             Use Idna::encode() instead.
	 */
	protected function idnaEncode($strDomain)
	{
		trigger_deprecation('contao/core-bundle', '4.0', 'Using "Contao\System::idnaEncode()" has been deprecated and will no longer work in Contao 5.0. Use "Contao\Idna::encode()" instead.');

		return Idna::encode($strDomain);
	}

	/**
	 * Decode an internationalized domain name
	 *
	 * @param string $strDomain The domain name
	 *
	 * @return string The decoded domain name
	 *
	 * @deprecated Deprecated since Contao 4.0, to be removed in Contao 5.0.
	 *             Use Idna::decode() instead.
	 */
	protected function idnaDecode($strDomain)
	{
		trigger_deprecation('contao/core-bundle', '4.0', 'Using "Contao\System::idnaDecode()" has been deprecated and will no longer work in Contao 5.0. Use "Contao\Idna::decode()" instead.');

		return Idna::decode($strDomain);
	}

	/**
	 * Encode the domain in an e-mail address
	 *
	 * @param string $strEmail The e-mail address
	 *
	 * @return string The encoded e-mail address
	 *
	 * @deprecated Deprecated since Contao 4.0, to be removed in Contao 5.0.
	 *             Use Idna::encodeEmail() instead.
	 */
	protected function idnaEncodeEmail($strEmail)
	{
		trigger_deprecation('contao/core-bundle', '4.0', 'Using "Contao\System::idnaEncodeEmail()" has been deprecated and will no longer work in Contao 5.0. Use "Contao\Idna::encodeEmail()" instead.');

		return Idna::encodeEmail($strEmail);
	}

	/**
	 * Encode the domain in a URL
	 *
	 * @param string $strUrl The URL
	 *
	 * @return string The encoded URL
	 *
	 * @deprecated Deprecated since Contao 4.0, to be removed in Contao 5.0.
	 *             Use Idna::encodeUrl() instead.
	 */
	protected function idnaEncodeUrl($strUrl)
	{
		trigger_deprecation('contao/core-bundle', '4.0', 'Using "Contao\System::idnaEncodeUrl()" has been deprecated and will no longer work in Contao 5.0. Use "Contao\Idna::encodeUrl()" instead.');

		return Idna::encodeUrl($strUrl);
	}

	/**
	 * Validate an e-mail address
	 *
	 * @param string $strEmail The e-mail address
	 *
	 * @return boolean True if it is a valid e-mail address
	 *
	 * @deprecated Deprecated since Contao 4.0, to be removed in Contao 5.0.
	 *             Use Validator::isEmail() instead.
	 */
	protected function isValidEmailAddress($strEmail)
	{
		trigger_deprecation('contao/core-bundle', '4.0', 'Using "Contao\System::isValidEmailAddress()" has been deprecated and will no longer work in Contao 5.0. Use "Contao\Validator::isEmail()" instead.');

		return Validator::isEmail($strEmail);
	}

	/**
	 * Split a friendly-name e-mail address and return name and e-mail as array
	 *
	 * @param string $strEmail A friendly-name e-mail address
	 *
	 * @return array An array with name and e-mail address
	 *
	 * @deprecated Deprecated since Contao 4.0, to be removed in Contao 5.0.
	 *             Use StringUtil::splitFriendlyEmail() instead.
	 */
	public static function splitFriendlyName($strEmail)
	{
		trigger_deprecation('contao/core-bundle', '4.0', 'Using "Contao\System::splitFriendlyName()" has been deprecated and will no longer work in Contao 5.0. Use "Contao\StringUtil::splitFriendlyEmail()" instead.');

		return StringUtil::splitFriendlyEmail($strEmail);
	}

	/**
	 * Return the request string without the script name
	 *
	 * @param boolean $blnAmpersand If true, ampersands will be encoded
	 *
	 * @return string The request string
	 *
	 * @deprecated Deprecated since Contao 4.0, to be removed in Contao 5.0.
	 *             Use Environment::get("indexFreeRequest") instead.
	 */
	public static function getIndexFreeRequest($blnAmpersand=true)
	{
		trigger_deprecation('contao/core-bundle', '4.0', 'Using "Contao\System::getIndexFreeRequest()" has been deprecated and will no longer work in Contao 5.0. Use "Contao\Environment::get(\'indexFreeRequest\')" instead.');

		return StringUtil::ampersand(Environment::get('indexFreeRequest'), $blnAmpersand);
	}

	/**
	 * Compile a Model class name from a table name (e.g. tl_form_field becomes FormFieldModel)
	 *
	 * @param string $strTable The table name
	 *
	 * @return string The model class name
	 *
	 * @deprecated Deprecated since Contao 4.0, to be removed in Contao 5.0.
	 *             Use Model::getClassFromTable() instead.
	 */
	public static function getModelClassFromTable($strTable)
	{
		trigger_deprecation('contao/core-bundle', '4.0', 'Using "Contao\System::getModelClassFromTable()" has been deprecated and will no longer work in Contao 5.0. Use "Contao\Model::getClassFromTable()" instead.');

		return Model::getClassFromTable($strTable);
	}

	/**
	 * Enable a back end module
	 *
	 * @deprecated Deprecated since Contao 4.0, to be removed in Contao 5.0.
	 *             Use Composer to add or remove modules.
	 */
	public static function enableModule()
	{
		trigger_deprecation('contao/core-bundle', '4.0', 'Using "Contao\System::enableModule()" has been deprecated and will no longer work in Contao 5.0. Use Composer to add or remove modules.');
	}

	/**
	 * Disable a back end module
	 *
	 * @deprecated Deprecated since Contao 4.0, to be removed in Contao 5.0.
	 *             Use Composer to add or remove modules.
	 */
	public static function disableModule()
	{
		trigger_deprecation('contao/core-bundle', '4.0', 'Using "Contao\System::disableModule()" has been deprecated and will no longer work in Contao 5.0. Use Composer to add or remove modules.');
	}
}

class_alias(System::class, 'System');
