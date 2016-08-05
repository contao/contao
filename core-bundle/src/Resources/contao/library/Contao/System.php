<?php

/**
 * Contao Open Source CMS
 *
 * Copyright (c) 2005-2016 Leo Feyer
 *
 * @license LGPL-3.0+
 */

namespace Contao;

use Contao\CoreBundle\Config\Loader\PhpFileLoader;
use Contao\CoreBundle\Config\Loader\XliffFileLoader;
use Contao\CoreBundle\Monolog\ContaoContext;
use League\Uri\Components\Query;
use Patchwork\Utf8;
use Psr\Log\LogLevel;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\Finder\SplFileInfo;
use Symfony\Component\HttpFoundation\Session\SessionInterface;


/**
 * Abstract library base class
 *
 * The class provides miscellaneous methods that are used all throughout the
 * application. It is the base class of the Contao library which provides the
 * central "import" method to load other library classes.
 *
 * Usage:
 *
 *     class MyClass extends \System
 *     {
 *         public function __construct()
 *         {
 *             $this->import('Database');
 *         }
 *     }
 *
 * @property \Automator                                $Automator   The automator object
 * @property \Config                                   $Config      The config object
 * @property \Database                                 $Database    The database object
 * @property \Environment                              $Environment The environment object
 * @property \Files                                    $Files       The files object
 * @property \Input                                    $Input       The input object
 * @property \Database\Installer                       $Installer   The database installer object
 * @property \Database\Updater                         $Updater     The database updater object
 * @property \Messages                                 $Messages    The messages object
 * @property \Session                                  $Session     The session object
 * @property \StyleSheets                              $StyleSheets The style sheets object
 * @property \BackendTemplate|\FrontendTemplate|object $Template    The template object
 * @property \BackendUser|\FrontendUser|object         $User        The user object
 *
 * @author Leo Feyer <https://github.com/leofeyer>
 */
abstract class System
{

	/**
	 * Container
	 * @var ContainerInterface
	 */
	protected static $objContainer;

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
	 * Import the Config and Session instances
	 */
	protected function __construct()
	{
		$this->import('Config');
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
	 * @param string  $strClass The class name
	 * @param string  $strKey   An optional key to store the object under
	 * @param boolean $blnForce If true, existing objects will be overridden
	 */
	protected function import($strClass, $strKey=null, $blnForce=false)
	{
		$strKey = $strKey ?: $strClass;

		if ($blnForce || !isset($this->arrObjects[$strKey]))
		{
			$container = static::getContainer();

			if (!class_exists($strClass) && $container->has($strClass))
			{
				$this->arrObjects[$strKey] = $container->get($strClass);
			}
			elseif (in_array('getInstance', get_class_methods($strClass)))
			{
				$this->arrObjects[$strKey] = call_user_func(array($strClass, 'getInstance'));
			}
			else
			{
				$this->arrObjects[$strKey] = new $strClass();
			}
		}
	}


	/**
	 * Import a library in non-object context
	 *
	 * @param string  $strClass The class name
	 * @param string  $strKey   An optional key to store the object under
	 * @param boolean $blnForce If true, existing objects will be overridden
	 *
	 * @return object The imported object
	 */
	public static function importStatic($strClass, $strKey=null, $blnForce=false)
	{
		$strKey = $strKey ?: $strClass;

		if ($blnForce || !isset(static::$arrStaticObjects[$strKey]))
		{
			$container = static::getContainer();

			if (!class_exists($strClass) && $container->has($strClass))
			{
				static::$arrStaticObjects[$strKey] = $container->get($strClass);
			}
			elseif (in_array('getInstance', get_class_methods($strClass)))
			{
				static::$arrStaticObjects[$strKey] = call_user_func(array($strClass, 'getInstance'));
			}
			else
			{
				static::$arrStaticObjects[$strKey] = new $strClass();
			}
		}

		return static::$arrStaticObjects[$strKey];
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
	 *             Use the logger service instead.
	 */
	public static function log($strText, $strFunction, $strCategory)
	{
		trigger_error('Using System::log() has been deprecated and will no longer work in Contao 5.0. Use the logger service instead', E_USER_DEPRECATED);

		$level = TL_ERROR === $strCategory ? LogLevel::ERROR : LogLevel::INFO;
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
		/** @var SessionInterface $objSession */
		$objSession = static::getContainer()->get('session');

		$ref = \Input::get('ref');
		$key = \Input::get('popup') ? 'popupReferer' : 'referer';
		$session = $objSession->get($key);

		// Unique referer ID
		if ($ref && isset($session[$ref]))
		{
			$session = $session[$ref];
		}
		elseif (TL_MODE == 'BE' && is_array($session))
		{
			$session = end($session);
		}

		// Use a specific referer
		if ($strTable != '' && isset($session[$strTable]) && \Input::get('act') != 'select')
		{
			$session['current'] = $session[$strTable];
		}

		// Remove parameters helper
		$cleanUrl = function ($url, $params=array('rt', 'ref'))
		{
			if ($url == '' || strpos($url, '?') === false)
			{
				return $url;
			}

			list($path, $query) = explode('?', $url, 2);

			/** @var Query $queryObj */
			$queryObj = new Query($query);
			$queryObj = $queryObj->without($params);

			return $path . $queryObj->getUriComponent();
		};

		// Determine current or last
		$strUrl = ($cleanUrl($session['current']) != $cleanUrl(\Environment::get('request'))) ? $session['current'] : $session['last'];

		// Remove the "toggle" and "toggle all" parameters
		$return = $cleanUrl($strUrl, array('tg', 'ptg'));

		// Fallback to the generic referer in the front end
		if ($return == '' && TL_MODE == 'FE')
		{
			$return = \Environment::get('httpReferer');
		}

		// Fallback to the current URL if there is no referer
		if ($return == '')
		{
			$return = (TL_MODE == 'BE') ? 'contao/main.php' : \Environment::get('url');
		}

		// Do not urldecode here!
		return ampersand($return, $blnEncodeAmpersands);
	}


	/**
	 * Load a set of language files
	 *
	 * @param string  $strName     The table name
	 * @param boolean $strLanguage An optional language code
	 * @param boolean $blnNoCache  If true, the cache will be bypassed
	 */
	public static function loadLanguageFile($strName, $strLanguage=null, $blnNoCache=false)
	{
		if ($strLanguage === null)
		{
			$strLanguage = str_replace('-', '_', $GLOBALS['TL_LANGUAGE']);
		}

		// Fall back to English
		if ($strLanguage == '')
		{
			$strLanguage = 'en';
		}

		// Return if the language file has been loaded already
		if (isset(static::$arrLanguageFiles[$strName][$strLanguage]) && !$blnNoCache)
		{
			return;
		}

		$strCacheKey = $strLanguage;

		// Make sure the language exists
		if (!static::isInstalledLanguage($strLanguage))
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

		// Use a global cache variable to support nested calls
		static::$arrLanguageFiles[$strName][$strCacheKey] = $strLanguage;

		// Fall back to English
		$arrCreateLangs = ($strLanguage == 'en') ? array('en') : array('en', $strLanguage);

		// Prepare the XLIFF loader
		$xlfLoader = new XliffFileLoader(static::getContainer()->getParameter('kernel.root_dir'), true);

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
				try
				{
					$files = static::getContainer()->get('contao.resource_locator')->locate('languages/' . $strCreateLang . '/' . $strName . '.php', null, false);
				}
				catch (\InvalidArgumentException $e)
				{
					$files = array();
				}

				foreach ($files as $file)
				{
					include $file;
				}

				try
				{
					$files = static::getContainer()->get('contao.resource_locator')->locate('languages/' . $strCreateLang . '/' . $strName . '.xlf', null, false);
				}
				catch (\InvalidArgumentException $e)
				{
					$files = array();
				}

				foreach ($files as $file)
				{
					$xlfLoader->load($file, $strCreateLang);
				}
			}
		}

		// HOOK: allow to load custom labels
		if (isset($GLOBALS['TL_HOOKS']['loadLanguageFile']) && is_array($GLOBALS['TL_HOOKS']['loadLanguageFile']))
		{
			foreach ($GLOBALS['TL_HOOKS']['loadLanguageFile'] as $callback)
			{
				static::importStatic($callback[0])->{$callback[1]}($strName, $strLanguage, $strCacheKey);
			}
		}

		// Handle single quotes in the deleteConfirm message
		if ($strName == 'default')
		{
			$GLOBALS['TL_LANG']['MSC']['deleteConfirm'] = str_replace("'", "\\'", $GLOBALS['TL_LANG']['MSC']['deleteConfirm']);
		}

		// Local configuration file
		if (file_exists(TL_ROOT . '/system/config/langconfig.php'))
		{
			@trigger_error('Using the langconfig.php file has been deprecated and will no longer work in Contao 5.0. Create one or more language files in app/Resources/contao/languages instead.', E_USER_DEPRECATED);
			include TL_ROOT . '/system/config/langconfig.php';
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
			if (is_dir(TL_ROOT . '/vendor/contao/core-bundle/src/Resources/contao/languages/' . $strLanguage))
			{
				static::$arrLanguages[$strLanguage] = true;
			}
			elseif (is_dir(static::getContainer()->getParameter('kernel.cache_dir') . '/contao/languages/' . $strLanguage))
			{
				static::$arrLanguages[$strLanguage] = true;
			}
			else
			{
				/** @var SplFileInfo[] $files */
				$files = static::getContainer()->get('contao.resource_finder')->findIn('languages')->depth(0)->directories()->name($strLanguage);
				static::$arrLanguages[$strLanguage] = count($files) > 0;
			}
		}

		return static::$arrLanguages[$strLanguage];
	}


	/**
	 * Return the countries as array
	 *
	 * @return array An array of country names
	 */
	public static function getCountries()
	{
		$return = array();
		$countries = array();
		$arrAux = array();

		static::loadLanguageFile('countries');
		include __DIR__ . '/../../config/countries.php';

		foreach ($countries as $strKey=>$strName)
		{
			$arrAux[$strKey] = isset($GLOBALS['TL_LANG']['CNT'][$strKey]) ? Utf8::toAscii($GLOBALS['TL_LANG']['CNT'][$strKey]) : $strName;
		}

		asort($arrAux);

		foreach (array_keys($arrAux) as $strKey)
		{
			$return[$strKey] = isset($GLOBALS['TL_LANG']['CNT'][$strKey]) ? $GLOBALS['TL_LANG']['CNT'][$strKey] : $countries[$strKey];
		}

		// HOOK: add custom logic
		if (isset($GLOBALS['TL_HOOKS']['getCountries']) && is_array($GLOBALS['TL_HOOKS']['getCountries']))
		{
			foreach ($GLOBALS['TL_HOOKS']['getCountries'] as $callback)
			{
				static::importStatic($callback[0])->{$callback[1]}($return, $countries);
			}
		}

		return $return;
	}


	/**
	 * Return the available languages as array
	 *
	 * @param boolean $blnInstalledOnly If true, return only installed languages
	 *
	 * @return array An array of languages
	 */
	public static function getLanguages($blnInstalledOnly=false)
	{
		$return = array();
		$languages = array();
		$arrAux = array();
		$langsNative = array();

		static::loadLanguageFile('languages');
		include __DIR__ . '/../../config/languages.php';

		foreach ($languages as $strKey=>$strName)
		{
			$arrAux[$strKey] = isset($GLOBALS['TL_LANG']['LNG'][$strKey]) ? Utf8::toAscii($GLOBALS['TL_LANG']['LNG'][$strKey]) : $strName;
		}

		asort($arrAux);
		$arrBackendLanguages = scan(__DIR__ . '/../../languages');

		foreach (array_keys($arrAux) as $strKey)
		{
			if ($blnInstalledOnly && !in_array($strKey, $arrBackendLanguages))
			{
				continue;
			}

			$return[$strKey] = isset($GLOBALS['TL_LANG']['LNG'][$strKey]) ? $GLOBALS['TL_LANG']['LNG'][$strKey] : $languages[$strKey];

			if (isset($langsNative[$strKey]) && $langsNative[$strKey] != $return[$strKey])
			{
				$return[$strKey] .= ' - ' . $langsNative[$strKey];
			}
		}

		// HOOK: add custom logic
		if (isset($GLOBALS['TL_HOOKS']['getLanguages']) && is_array($GLOBALS['TL_HOOKS']['getLanguages']))
		{
			foreach ($GLOBALS['TL_HOOKS']['getLanguages'] as $callback)
			{
				static::importStatic($callback[0])->{$callback[1]}($return, $languages, $langsNative, $blnInstalledOnly);
			}
		}

		return $return;
	}


	/**
	 * Return the timezones as array
	 *
	 * @return array An array of timezones
	 */
	public static function getTimeZones()
	{
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
	 *             Use the contao.image.image_sizes service instead.
	 */
	public static function getImageSizes()
	{
		@trigger_error('Using System::getImageSizes() has been deprecated and will no longer work in Contao 5.0. Use the contao.image.image_sizes service instead.', E_USER_DEPRECATED);

		return static::getContainer()->get('contao.image.image_sizes')->getAllOptions();
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
		return str_replace('%2F', '/', rawurlencode($strPath));
	}


	/**
	 * Set a cookie
	 *
	 * @param string  $strName     The cookie name
	 * @param mixed   $varValue    The cookie value
	 * @param integer $intExpires  The expiration date
	 * @param string  $strPath     An optional path
	 * @param string  $strDomain   An optional domain name
	 * @param boolean $blnSecure   If true, the secure flag will be set
	 * @param boolean $blnHttpOnly If true, the http-only flag will be set
	 */
	public static function setCookie($strName, $varValue, $intExpires, $strPath=null, $strDomain=null, $blnSecure=false, $blnHttpOnly=false)
	{
		if ($strPath == '')
		{
			$strPath = \Environment::get('path') ?: '/'; // see #4390
		}

		$objCookie = new \stdClass();

		$objCookie->strName     = $strName;
		$objCookie->varValue    = $varValue;
		$objCookie->intExpires  = $intExpires;
		$objCookie->strPath     = $strPath;
		$objCookie->strDomain   = $strDomain;
		$objCookie->blnSecure   = $blnSecure;
		$objCookie->blnHttpOnly = $blnHttpOnly;

		// HOOK: allow to add custom logic
		if (isset($GLOBALS['TL_HOOKS']['setCookie']) && is_array($GLOBALS['TL_HOOKS']['setCookie']))
		{
			foreach ($GLOBALS['TL_HOOKS']['setCookie'] as $callback)
			{
				$objCookie = static::importStatic($callback[0])->{$callback[1]}($objCookie);
			}
		}

		setcookie($objCookie->strName, $objCookie->varValue, $objCookie->intExpires, $objCookie->strPath, $objCookie->strDomain, $objCookie->blnSecure, $objCookie->blnHttpOnly);
	}


	/**
	 * Convert a byte value into a human readable format
	 *
	 * @param integer $intSize     The size in bytes
	 * @param integer $intDecimals The number of decimals to show
	 *
	 * @return string The human readable size
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
	 */
	public static function getSessionHash($strCookie)
	{
		$container = static::getContainer();
		$strHash = $container->get('session')->getId();

		if (!$container->getParameter('contao.security.disable_ip_check'))
		{
			$strHash .= \Environment::get('ip');
		}

		$strHash .= $strCookie;

		return sha1($strHash);
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
		// The feature has been disabled
		if (!\Config::get('privacyAnonymizeIp'))
		{
			return $strIp;
		}

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
		else
		{
			return substr_replace($strIp, '.0', strrpos($strIp, '.'));
		}
	}


	/**
	 * Read the contents of a PHP file, stripping the opening and closing PHP tags
	 *
	 * @param string $strName The name of the PHP file
	 *
	 * @return string The PHP code without the PHP tags
	 *
	 * @deprecated Deprecated since Contao 4.0, to be removed in Contao 5.0.
	 *             Use the Contao\CoreBundle\Config\Loader\PhpFileLoader instead.
	 */
	protected static function readPhpFileWithoutTags($strName)
	{
		@trigger_error('Using System::readPhpFileWithoutTags() has been deprecated and will no longer work in Contao 5.0. Use the Contao\CoreBundle\Config\Loader\PhpFileLoader instead.', E_USER_DEPRECATED);

		// Convert to absolute path
		if (strpos($strName, TL_ROOT . '/') === false)
		{
			$strName = TL_ROOT . '/' . $strName;
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
	 *             Use the Contao\CoreBundle\Config\Loader\XliffFileLoader instead.
	 */
	public static function convertXlfToPhp($strName, $strLanguage, $blnLoad=false)
	{
		@trigger_error('Using System::convertXlfToPhp() has been deprecated and will no longer work in Contao 5.0. Use the Contao\CoreBundle\Config\Loader\XliffFileLoader instead.', E_USER_DEPRECATED);

		// Convert to absolute path
		if (strpos($strName, TL_ROOT . '/') === false)
		{
			$strName = TL_ROOT . '/' . $strName;
		}

		$loader = new XliffFileLoader(static::getContainer()->getParameter('kernel.root_dir'), $blnLoad);

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
		@trigger_error('Using System::parseDate() has been deprecated and will no longer work in Contao 5.0. Use Date::parse() instead.', E_USER_DEPRECATED);

		return \Date::parse($strFormat, $intTstamp);
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
		@trigger_error('Using System::addToUrl() has been deprecated and will no longer work in Contao 5.0. Use Controller::addToUrl() instead.', E_USER_DEPRECATED);

		return \Controller::addToUrl($strRequest);
	}


	/**
	 * Reload the current page
	 *
	 * @deprecated Deprecated since Contao 4.0, to be removed in Contao 5.0.
	 *             Use Controller::reload() instead.
	 */
	public static function reload()
	{
		@trigger_error('Using System::reload() has been deprecated and will no longer work in Contao 5.0. Use Controller::reload() instead.', E_USER_DEPRECATED);

		\Controller::reload();
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
		@trigger_error('Using System::redirect() has been deprecated and will no longer work in Contao 5.0. Use Controller::redirect() instead.', E_USER_DEPRECATED);

		\Controller::redirect($strLocation, $intStatus);
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
		@trigger_error('Using System::addErrorMessage() has been deprecated and will no longer work in Contao 5.0. Use Message::addError() instead.', E_USER_DEPRECATED);

		\Message::addError($strMessage);
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
		@trigger_error('Using System::addConfirmationMessage() has been deprecated and will no longer work in Contao 5.0. Use Message::addConfirmation() instead.', E_USER_DEPRECATED);

		\Message::addConfirmation($strMessage);
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
		@trigger_error('Using System::addNewMessage() has been deprecated and will no longer work in Contao 5.0. Use Message::addNew() instead.', E_USER_DEPRECATED);

		\Message::addNew($strMessage);
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
		@trigger_error('Using System::addInfoMessage() has been deprecated and will no longer work in Contao 5.0. Use Message::addInfo() instead.', E_USER_DEPRECATED);

		\Message::addInfo($strMessage);
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
		@trigger_error('Using System::addRawMessage() has been deprecated and will no longer work in Contao 5.0. Use Message::addRaw() instead.', E_USER_DEPRECATED);

		\Message::addRaw($strMessage);
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
		@trigger_error('Using System::addMessage() has been deprecated and will no longer work in Contao 5.0. Use Message::add() instead.', E_USER_DEPRECATED);

		\Message::add($strMessage, $strType);
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
	protected function getMessages($strScope=TL_MODE)
	{
		@trigger_error('Using System::getMessages() has been deprecated and will no longer work in Contao 5.0. Use Message::generate() instead.', E_USER_DEPRECATED);

		return \Message::generate($strScope);
	}


	/**
	 * Reset the message system
	 *
	 * @deprecated Deprecated since Contao 4.0, to be removed in Contao 5.0.
	 *             Use Message::reset() instead.
	 */
	protected function resetMessages()
	{
		@trigger_error('Using System::resetMessages() has been deprecated and will no longer work in Contao 5.0. Use Message::reset() instead.', E_USER_DEPRECATED);

		\Message::reset();
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
		@trigger_error('Using System::getMessageTypes() has been deprecated and will no longer work in Contao 5.0. Use Message::getTypes() instead.', E_USER_DEPRECATED);

		return \Message::getTypes();
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
		@trigger_error('Using System::idnaEncode() has been deprecated and will no longer work in Contao 5.0. Use Idna::encode() instead.', E_USER_DEPRECATED);

		return \Idna::encode($strDomain);
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
		@trigger_error('Using System::idnaDecode() has been deprecated and will no longer work in Contao 5.0. Use Idna::decode() instead.', E_USER_DEPRECATED);

		return \Idna::decode($strDomain);
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
		@trigger_error('Using System::idnaEncodeEmail() has been deprecated and will no longer work in Contao 5.0. Use Idna::encodeEmail() instead.', E_USER_DEPRECATED);

		return \Idna::encodeEmail($strEmail);
	}


	/**
	 * Encode the domain in an URL
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
		@trigger_error('Using System::idnaEncodeUrl() has been deprecated and will no longer work in Contao 5.0. Use Idna::encodeUrl() instead.', E_USER_DEPRECATED);

		return \Idna::encodeUrl($strUrl);
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
		@trigger_error('Using System::isValidEmailAddress() has been deprecated and will no longer work in Contao 5.0. Use Validator::isEmail() instead.', E_USER_DEPRECATED);

		return \Validator::isEmail($strEmail);
	}


	/**
	 * Split a friendly-name e-address and return name and e-mail as array
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
		@trigger_error('Using System::splitFriendlyName() has been deprecated and will no longer work in Contao 5.0. Use StringUtil::splitFriendlyEmail() instead.', E_USER_DEPRECATED);

		return \StringUtil::splitFriendlyEmail($strEmail);
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
		@trigger_error('Using System::getIndexFreeRequest() has been deprecated and will no longer work in Contao 5.0. Use Environment::get("indexFreeRequest") instead.', E_USER_DEPRECATED);

		return ampersand(\Environment::get('indexFreeRequest'), $blnAmpersand);
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
		@trigger_error('Using System::getModelClassFromTable() has been deprecated and will no longer work in Contao 5.0. Use Model::getClassFromTable() instead.', E_USER_DEPRECATED);

		return \Model::getClassFromTable($strTable);
	}


	/**
	 * Enable a back end module
	 *
	 * @return boolean True if the module was enabled
	 *
	 * @deprecated Deprecated since Contao 4.0, to be removed in Contao 5.0.
	 *             Use Composer to add or remove modules.
	 */
	public static function enableModule()
	{
		@trigger_error('Using System::enableModule() has been deprecated and will no longer work in Contao 5.0. Use Composer to add or remove modules.', E_USER_DEPRECATED);
	}


	/**
	 * Disable a back end module
	 *
	 * @return boolean True if the module was disabled
	 *
	 * @deprecated Deprecated since Contao 4.0, to be removed in Contao 5.0.
	 *             Use Composer to add or remove modules.
	 */
	public static function disableModule()
	{
		@trigger_error('Using System::disableModule() has been deprecated and will no longer work in Contao 5.0. Use Composer to add or remove modules.', E_USER_DEPRECATED);
	}
}
