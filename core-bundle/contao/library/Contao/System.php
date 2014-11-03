<?php

/**
 * Contao Open Source CMS
 *
 * Copyright (c) 2005-2014 Leo Feyer
 *
 * @package Library
 * @link    https://contao.org
 * @license http://www.gnu.org/licenses/lgpl-3.0.html LGPL
 */

namespace Contao;

use Contao\CoreBundle\HttpKernel\ContaoKernelInterface;
use Symfony\Component\DependencyInjection\Container;


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
 * @package   Library
 * @author    Leo Feyer <https://github.com/leofeyer>
 * @copyright Leo Feyer 2005-2014
 */
abstract class System
{

	/**
	 * Cache
	 * @var array
	 */
	protected $arrCache = array(); // Backwards compatibility

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
	 * The Symfony kernel
	 * @var ContaoKernelInterface
	 */
	protected static $objKernel;

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
		$this->import('Session');
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
			if ($strKey == 'Input' || $strKey == 'Environment')
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
			$this->arrObjects[$strKey] = (in_array('getInstance', get_class_methods($strClass))) ? call_user_func(array($strClass, 'getInstance')) : new $strClass();
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
			static::$arrStaticObjects[$strKey] = (in_array('getInstance', get_class_methods($strClass))) ? call_user_func(array($strClass, 'getInstance')) : new $strClass();
		}

		return static::$arrStaticObjects[$strKey];
	}


	/**
	 * Add a log entry to the database
	 *
	 * @param string $strText     The log message
	 * @param string $strFunction The function name
	 * @param string $strCategory The category name
	 */
	public static function log($strText, $strFunction, $strCategory)
	{
		$strUa = 'N/A';
		$strIp = '127.0.0.1';

		if (\Environment::get('httpUserAgent'))
		{
			$strUa = \Environment::get('httpUserAgent');
		}
		if (\Environment::get('remoteAddr'))
		{
			$strIp = static::anonymizeIp(\Environment::get('ip'));
		}

		\Database::getInstance()->prepare("INSERT INTO tl_log (tstamp, source, action, username, text, func, ip, browser) VALUES(?, ?, ?, ?, ?, ?, ?, ?)")
							   ->execute(time(), (TL_MODE == 'FE' ? 'FE' : 'BE'), $strCategory, ($GLOBALS['TL_USERNAME'] ? $GLOBALS['TL_USERNAME'] : ''), specialchars($strText), $strFunction, $strIp, $strUa);

		// HOOK: allow to add custom loggers
		if (isset($GLOBALS['TL_HOOKS']['addLogEntry']) && is_array($GLOBALS['TL_HOOKS']['addLogEntry']))
		{
			foreach ($GLOBALS['TL_HOOKS']['addLogEntry'] as $callback)
			{
				static::importStatic($callback[0])->$callback[1]($strText, $strFunction, $strCategory);
			}
		}
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
		$ref = \Input::get('ref');
		$key = \Input::get('popup') ? 'popupReferer' : 'referer';
		$session = \Session::getInstance()->get($key);

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

		// Determine current or last
		$strUrl = ($session['current'] != \Environment::get('request')) ? $session['current'] : $session['last'];

		// Remove "toggle" and "toggle all" parameters
		$return = preg_replace('/(&(amp;)?|\?)p?tg=[^& ]*/i', '', $strUrl);

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

		// Load the language(s)
		foreach ($arrCreateLangs as $strCreateLang)
		{
			$strCacheFile = 'system/cache/language/' . $strCreateLang . '/' . $strName . '.php';

			// Try to load from cache
			if (!\Config::get('bypassCache') && file_exists(TL_ROOT . '/' . $strCacheFile))
			{
				include TL_ROOT . '/' . $strCacheFile;
			}
			else
			{
				foreach (\System::getKernel()->getContaoBundles() as $bundle)
				{
					$strFile = $bundle->getContaoResourcesPath() . '/languages/' . $strCreateLang . '/' . $strName;

					if (file_exists($strFile . '.xlf'))
					{
						static::convertXlfToPhp($strFile . '.xlf', $strCreateLang, true);
					}
					elseif (file_exists($strFile . '.php'))
					{
						include $strFile . '.php';
					}
				}
			}
		}

		// HOOK: allow to load custom labels
		if (isset($GLOBALS['TL_HOOKS']['loadLanguageFile']) && is_array($GLOBALS['TL_HOOKS']['loadLanguageFile']))
		{
			foreach ($GLOBALS['TL_HOOKS']['loadLanguageFile'] as $callback)
			{
				static::importStatic($callback[0])->$callback[1]($strName, $strLanguage, $strCacheKey);
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
			$blnIsInstalled = is_dir(TL_ROOT . '/vendor/contao/core-bundle/contao/languages/' . $strLanguage);

			if (!$blnIsInstalled)
			{
				$blnIsInstalled = is_dir(TL_ROOT . '/system/cache/language/' . $strLanguage);
			}

			if (!$blnIsInstalled)
			{
				foreach (static::getKernel()->getContaoBundles() as $bundle)
				{
					if (is_dir(TL_ROOT . '/' . $bundle->getContaoResourcesPath() . '/languages/' . $strLanguage))
					{
						$blnIsInstalled = true;
						break;
					}
				}
			}

			static::$arrLanguages[$strLanguage] = $blnIsInstalled;
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
		include TL_ROOT . '/system/config/countries.php';

		foreach ($countries as $strKey=>$strName)
		{
			$arrAux[$strKey] = isset($GLOBALS['TL_LANG']['CNT'][$strKey]) ? utf8_romanize($GLOBALS['TL_LANG']['CNT'][$strKey]) : $strName;
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
				static::importStatic($callback[0])->$callback[1]($return, $countries);
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
		include TL_ROOT . '/system/config/languages.php';

		foreach ($languages as $strKey=>$strName)
		{
			$arrAux[$strKey] = isset($GLOBALS['TL_LANG']['LNG'][$strKey]) ? utf8_romanize($GLOBALS['TL_LANG']['LNG'][$strKey]) : $strName;
		}

		asort($arrAux);
		$arrBackendLanguages = scan(TL_ROOT . '/vendor/contao/core-bundle/contao/languages');

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
				static::importStatic($callback[0])->$callback[1]($return, $languages, $langsNative, $blnInstalledOnly);
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

		require TL_ROOT . '/system/config/timezones.php';

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
	 */
	public static function getImageSizes()
	{
		if (empty(static::$arrImageSizes))
		{
			try
			{
				$sizes = array();
				$imageSize = \Database::getInstance()->query("SELECT id, name, width, height FROM tl_image_size ORDER BY pid, name");

				while ($imageSize->next())
				{
					$sizes[$imageSize->id] = $imageSize->name;
					$sizes[$imageSize->id] .= ' (' . $imageSize->width . 'x' . $imageSize->height . ')';
				}

				static::$arrImageSizes = array_merge(array('image_sizes' => $sizes), $GLOBALS['TL_CROP']);
			}
			catch (\Exception $e)
			{
				static::$arrImageSizes = $GLOBALS['TL_CROP'];
			}
		}

		return static::$arrImageSizes;
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
				$objCookie = static::importStatic($callback[0])->$callback[1]($objCookie);
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
	 */
	protected static function readPhpFileWithoutTags($strName)
	{
		// Convert to absolute path
		if (strpos($strName, TL_ROOT . '/') === false)
		{
			$strName = TL_ROOT . '/' . $strName;
		}

		$strCode = rtrim(file_get_contents($strName));

		// Opening tag
		if (strncmp($strCode, '<?php', 5) === 0)
		{
			$strCode = substr($strCode, 5);
		}

		// die() statement
		$strCode = str_replace(array(
			" if (!defined('TL_ROOT')) die('You cannot access this file directly!');",
			" if (!defined('TL_ROOT')) die('You can not access this file directly!');"
		), '', $strCode);

		// Closing tag
		if (substr($strCode, -2) == '?>')
		{
			$strCode = substr($strCode, 0, -2);
		}

		return rtrim($strCode);
	}


	/**
	 * Convert an .xlf file into a PHP language file
	 *
	 * @param string  $strName     The name of the .xlf file
	 * @param string  $strLanguage The language code
	 * @param boolean $blnLoad     Add the labels to the global language array
	 *
	 * @return string The PHP code
	 */
	public static function convertXlfToPhp($strName, $strLanguage, $blnLoad=false)
	{
		// Read the .xlf file
		$xml = new \DOMDocument();
		$xml->preserveWhiteSpace = false;

		// Convert to absolute path
		if (strpos($strName, TL_ROOT . '/') === false)
		{
			$strName = TL_ROOT . '/' . $strName;
		}

		// Use loadXML() instead of load() (see 7192)
		$xml->loadXML(file_get_contents($strName));

		$return = "\n// " . str_replace(TL_ROOT . '/', '', $strName) . "\n";
		$units = $xml->getElementsByTagName('trans-unit');

		// Set up the quotekey function
		$quotekey = function($key)
		{
			if ($key === '0')
			{
				return 0;
			}
			elseif (is_numeric($key))
			{
				return intval($key);
			}
			else
			{
				return "'$key'";
			}
		};

		// Set up the quotevalue function
		$quotevalue = function($value)
		{
			if (strpos($value, '\n') !== false)
			{
				return '"' . str_replace('"', '\\"', $value) . '"';
			}
			else
			{
				return "'" . str_replace("'", "\\'", $value) . "'";
			}
		};

		// Add the labels
		foreach ($units as $unit)
		{
			$node = ($strLanguage == 'en') ? $unit->getElementsByTagName('source') : $unit->getElementsByTagName('target');

			if ($node === null || $node->item(0) === null)
			{
				continue;
			}

			$value = str_replace("\n", '\n', $node->item(0)->nodeValue);

			// Some closing </em> tags oddly have an extra space in
			if (strpos($value, '</ em>') !== false)
			{
				$value = str_replace('</ em>', '</em>', $value);
			}

			$chunks = explode('.', $unit->getAttribute('id'));

			// Handle keys with dots
			if (preg_match('/tl_layout\.[a-z]+\.css\./', $unit->getAttribute('id')))
			{
				$chunks = array($chunks[0], $chunks[1] . '.' . $chunks[2], $chunks[3]);
			}

			// Create the array entries
			switch (count($chunks))
			{
				case 2:
					$return .= "\$GLOBALS['TL_LANG']['" . $chunks[0] . "'][" . $quotekey($chunks[1]) . "] = " . $quotevalue($value) . ";\n";

					if ($blnLoad)
					{
						$GLOBALS['TL_LANG'][$chunks[0]][$chunks[1]] = $value;
					}
					break;

				case 3:
					$return .= "\$GLOBALS['TL_LANG']['" . $chunks[0] . "'][" . $quotekey($chunks[1]) . "][" . $quotekey($chunks[2]) . "] = " . $quotevalue($value) . ";\n";

					if ($blnLoad)
					{
						$GLOBALS['TL_LANG'][$chunks[0]][$chunks[1]][$chunks[2]] = $value;
					}
					break;

				case 4:
					$return .= "\$GLOBALS['TL_LANG']['" . $chunks[0] . "'][" . $quotekey($chunks[1]) . "][" . $quotekey($chunks[2]) . "][" . $quotekey($chunks[3]) . "] = " . $quotevalue($value) . ";\n";

					if ($blnLoad)
					{
						$GLOBALS['TL_LANG'][$chunks[0]][$chunks[1]][$chunks[2]][$chunks[3]] = $value;
					}
					break;
			}
		}

		return rtrim($return);
	}


	/**
	 * Return the installed version of a component
	 *
	 * @param string $strName The component name
	 *
	 * @return string|null The version number or null
	 */
	public static function getComponentVersion($strName)
	{
		$strCacheFile = 'system/cache/packages/installed.php';

		// Try to load from cache
		if (!\Config::get('bypassCache') && file_exists(TL_ROOT . '/' . $strCacheFile))
		{
			$arrPackages = include TL_ROOT . '/' . $strCacheFile;

			return $arrPackages[$strName];
		}

		$objJson = json_decode(file_get_contents(TL_ROOT . '/vendor/composer/installed.json'), true);

		// Try to find a matching package
		foreach ($objJson as $objPackage)
		{
			if ($objPackage['name'] == $strName)
			{
				$strVersion = substr($objPackage['version_normalized'], 0, strrpos($objPackage['version_normalized'], '.'));

				if (preg_match('/^[0-9]+\.[0-9]+\.[0-9]+$/', $strVersion))
				{
					return $strVersion;
				}
			}
		}

		return null;
	}


	/**
	 * Initialize the system
	 */
	public static function boot()
	{
		global $objConfig;

		if ($objConfig !== null)
		{
			return;
		}

		if (!defined('TL_MODE'))
		{
			define('TL_MODE', 'FE');
		}

		define('TL_START', microtime(true));
		define('TL_REFERER_ID', substr(md5(TL_START), 0, 8));
		define('TL_ROOT', dirname(static::getKernel()->getRootDir()));

		// Define the TL_SCRIPT constant (backwards compatibility)
		if (!defined('TL_SCRIPT'))
		{
			define('TL_SCRIPT', null);
		}

		// Define the login status constants in the back end (see #4099, #5279)
		if (TL_MODE == 'BE')
		{
			define('BE_USER_LOGGED_IN', false);
			define('FE_USER_LOGGED_IN', false);
		}

		require TL_ROOT . '/system/helper/functions.php';
		require TL_ROOT . '/system/config/constants.php';
		require TL_ROOT . '/system/helper/interface.php';
		require TL_ROOT . '/system/helper/exception.php';

		// Try to disable the PHPSESSID
		@ini_set('session.use_trans_sid', 0);
		@ini_set('session.cookie_httponly', true);

		// Set the error and exception handler
		@set_error_handler('__error');
		@set_exception_handler('__exception');

		// Log PHP errors
		@ini_set('error_log', TL_ROOT . '/system/logs/error.log');

		// Preload the configuration (see #5872)
		\Config::preload();

		// Override the SwiftMailer defaults
		\Swift::init(function() {
			$preferences = \Swift_Preferences::getInstance();
			$preferences->setTempDir(TL_ROOT . '/system/tmp')->setCacheType('disk');
			$preferences->setCharset(\Config::get('characterSet'));
		});

		// Alias the class and template loader (backwards compatibility)
		class_alias('Contao\\ClassLoader', 'ClassLoader');
		class_alias('Contao\\TemplateLoader', 'TemplateLoader');

		// Try to load the modules
		try
		{
			\ClassLoader::scanAndRegister();
		}
		catch (\UnresolvableDependenciesException $e)
		{
			die($e->getMessage()); // see #6343
		}

		// Define the relative path to the installation (see #5339)
		if (\Config::has('websitePath') && TL_SCRIPT != 'contao/install.php')
		{
			\Environment::set('path', \Config::get('websitePath'));
		}
		elseif (TL_MODE == 'BE')
		{
			\Environment::set('path', preg_replace('/\/contao\/[a-z]+\.php$/i', '', \Environment::get('scriptName')));
		}

		define('TL_PATH', \Environment::get('path')); // backwards compatibility

		// Start the session
		@session_set_cookie_params(0, (\Environment::get('path') ?: '/')); // see #5339
		@session_start();

		// Set the default language
		if (!isset($_SESSION['TL_LANGUAGE']))
		{
			$langs = \Environment::get('httpAcceptLanguage');
			array_push($langs, 'en'); // see #6533

			foreach ($langs as $lang)
			{
				if (is_dir(TL_ROOT . '/vendor/contao/module-core/contao/languages/' . str_replace('-', '_', $lang)))
				{
					$_SESSION['TL_LANGUAGE'] = $lang;
					break;
				}
			}

			unset($langs, $lang);
		}

		$GLOBALS['TL_LANGUAGE'] = $_SESSION['TL_LANGUAGE'];

		// Show the "insecure document root" message
		if (PHP_SAPI != 'cli' && TL_SCRIPT != 'contao/install.php' && substr(\Environment::get('path'), -4) == '/web' && !\Config::get('ignoreInsecureRoot'))
		{
			die_nicely('be_insecure', 'Your installation is not secure. Please set the document root to the <code>/web</code> subfolder.');
		}

		$objConfig = \Config::getInstance();

		// Show the "incomplete installation" message
		if (PHP_SAPI != 'cli' && TL_SCRIPT != 'contao/install.php' && !$objConfig->isComplete())
		{
			die_nicely('be_incomplete', 'The installation has not been completed. Open the Contao install tool to continue.');
		}

		\Input::initialize();

		// Always show error messages if logged into the install tool (see #5001)
		if (\Input::cookie('TL_INSTALL_AUTH') && !empty($_SESSION['TL_INSTALL_AUTH']) && \Input::cookie('TL_INSTALL_AUTH') == $_SESSION['TL_INSTALL_AUTH'] && $_SESSION['TL_INSTALL_EXPIRE'] > time())
		{
			\Config::set('displayErrors', 1);
		}

		// Configure the error handling
		@ini_set('display_errors', (\Config::get('displayErrors') ? 1 : 0));
		error_reporting((\Config::get('displayErrors') || \Config::get('logErrors')) ? \Config::get('errorReporting') : 0);

		// Set the timezone
		@ini_set('date.timezone', \Config::get('timeZone'));
		@date_default_timezone_set(\Config::get('timeZone'));

		// Set the mbstring encoding
		if (USE_MBSTRING && function_exists('mb_regex_encoding'))
		{
			mb_regex_encoding(\Config::get('characterSet'));
		}

		// HOOK: add custom logic (see #5665)
		if (isset($GLOBALS['TL_HOOKS']['initializeSystem']) && is_array($GLOBALS['TL_HOOKS']['initializeSystem']))
		{
			foreach ($GLOBALS['TL_HOOKS']['initializeSystem'] as $callback)
			{
				\System::importStatic($callback[0])->$callback[1]();
			}
		}

		// Include the custom initialization file
		if (file_exists(TL_ROOT . '/system/config/initconfig.php'))
		{
			include TL_ROOT . '/system/config/initconfig.php';
		}

		\RequestToken::initialize();

		// Check the request token upon POST requests
		if ($_POST && !\RequestToken::validate(\Input::post('REQUEST_TOKEN')))
		{
			// Force a JavaScript redirect upon Ajax requests (IE requires absolute link)
			if (\Environment::get('isAjaxRequest'))
			{
				header('HTTP/1.1 204 No Content');
				header('X-Ajax-Location: ' . \Environment::get('base') . 'contao/');
			}
			else
			{
				header('HTTP/1.1 400 Bad Request');
				die_nicely('be_referer', 'Invalid request token. Please <a href="javascript:window.location.href=window.location.href">go back</a> and try again.');
			}

			exit;
		}
	}


	/**
	 * Set the Symfony kernel
	 *
	 * @param ContaoKernelInterface $kernel The kernel object
	 */
	public static function setKernel(ContaoKernelInterface $kernel)
	{
		static::$objKernel = $kernel;
	}


	/**
	 * Return the Symfony kernel
	 *
	 * @return ContaoKernelInterface
	 */
	public static function getKernel()
	{
		return static::$objKernel;
	}


	/**
	 * Return the Symfony dependency injection container
	 *
	 * @return Container
	 */
	public static function getContainer()
	{
		return static::$objKernel->getContainer();
	}


	/**
	 * Parse a date format string and translate textual representations
	 *
	 * @param string  $strFormat The date format string
	 * @param integer $intTstamp An optional timestamp
	 *
	 * @return string The textual representation of the date
	 *
	 * @deprecated Use Date::parse() instead
	 */
	public static function parseDate($strFormat, $intTstamp=null)
	{
		return \Date::parse($strFormat, $intTstamp);
	}


	/**
	 * Add a request string to the current URL
	 *
	 * @param string $strRequest The string to be added
	 *
	 * @return string The new URL
	 *
	 * @deprecated Use Controller::addToUrl() instead
	 */
	public static function addToUrl($strRequest)
	{
		return \Controller::addToUrl($strRequest);
	}


	/**
	 * Reload the current page
	 *
	 * @deprecated Use Controller::reload() instead
	 */
	public static function reload()
	{
		\Controller::reload();
	}


	/**
	 * Redirect to another page
	 *
	 * @param string  $strLocation The target URL
	 * @param integer $intStatus   The HTTP status code (defaults to 303)
	 *
	 * @deprecated Use Controller::redirect() instead
	 */
	public static function redirect($strLocation, $intStatus=303)
	{
		\Controller::redirect($strLocation, $intStatus);
	}


	/**
	 * Add an error message
	 *
	 * @param string $strMessage The error message
	 *
	 * @deprecated Use Message::addError() instead
	 */
	protected function addErrorMessage($strMessage)
	{
		\Message::addError($strMessage);
	}


	/**
	 * Add a confirmation message
	 *
	 * @param string $strMessage The confirmation
	 *
	 * @deprecated Use Message::addConfirmation() instead
	 */
	protected function addConfirmationMessage($strMessage)
	{
		\Message::addConfirmation($strMessage);
	}


	/**
	 * Add a new message
	 *
	 * @param string $strMessage The new message
	 *
	 * @deprecated Use Message::addNew() instead
	 */
	protected function addNewMessage($strMessage)
	{
		\Message::addNew($strMessage);
	}


	/**
	 * Add an info message
	 *
	 * @param string $strMessage The info message
	 *
	 * @deprecated Use Message::addInfo() instead
	 */
	protected function addInfoMessage($strMessage)
	{
		\Message::addInfo($strMessage);
	}


	/**
	 * Add an unformatted message
	 *
	 * @param string $strMessage The unformatted message
	 *
	 * @deprecated Use Message::addRaw() instead
	 */
	protected function addRawMessage($strMessage)
	{
		\Message::addRaw($strMessage);
	}


	/**
	 * Add a message
	 *
	 * @param string $strMessage The message
	 * @param string $strType    The message type
	 *
	 * @deprecated Use Message::add() instead
	 */
	protected function addMessage($strMessage, $strType)
	{
		\Message::add($strMessage, $strType);
	}


	/**
	 * Return all messages as HTML
	 *
	 * @param string $strScope An optional message scope
	 *
	 * @return string The messages HTML markup
	 *
	 * @deprecated Use Message::generate() instead
	 */
	protected function getMessages($strScope=TL_MODE)
	{
		return \Message::generate($strScope);
	}


	/**
	 * Reset the message system
	 *
	 * @deprecated Use Message::reset() instead
	 */
	protected function resetMessages()
	{
		\Message::reset();
	}


	/**
	 * Return all available message types
	 *
	 * @return array An array of message types
	 *
	 * @deprecated Use Message::getTypes() instead
	 */
	protected function getMessageTypes()
	{
		return \Message::getTypes();
	}


	/**
	 * Encode an internationalized domain name
	 *
	 * @param string $strDomain The domain name
	 *
	 * @return string The encoded domain name
	 *
	 * @deprecated Use Idna::encode() instead
	 */
	protected function idnaEncode($strDomain)
	{
		return \Idna::encode($strDomain);
	}


	/**
	 * Decode an internationalized domain name
	 *
	 * @param string $strDomain The domain name
	 *
	 * @return string The decoded domain name
	 *
	 * @deprecated Use Idna::decode() instead
	 */
	protected function idnaDecode($strDomain)
	{
		return \Idna::decode($strDomain);
	}


	/**
	 * Encode the domain in an e-mail address
	 *
	 * @param string $strEmail The e-mail address
	 *
	 * @return string The encoded e-mail address
	 *
	 * @deprecated Use Idna::encodeEmail() instead
	 */
	protected function idnaEncodeEmail($strEmail)
	{
		return \Idna::encodeEmail($strEmail);
	}


	/**
	 * Encode the domain in an URL
	 *
	 * @param string $strUrl The URL
	 *
	 * @return string The encoded URL
	 *
	 * @deprecated Use Idna::encodeUrl() instead
	 */
	protected function idnaEncodeUrl($strUrl)
	{
		return \Idna::encodeUrl($strUrl);
	}


	/**
	 * Validate an e-mail address
	 *
	 * @param string $strEmail The e-mail address
	 *
	 * @return boolean True if it is a valid e-mail address
	 *
	 * @deprecated Use Validator::isEmail() instead
	 */
	protected function isValidEmailAddress($strEmail)
	{
		return \Validator::isEmail($strEmail);
	}


	/**
	 * Split a friendly-name e-address and return name and e-mail as array
	 *
	 * @param string $strEmail A friendly-name e-mail address
	 *
	 * @return array An array with name and e-mail address
	 *
	 * @deprecated Use String::splitFriendlyEmail() instead
	 */
	public static function splitFriendlyName($strEmail)
	{
		return \String::splitFriendlyEmail($strEmail);
	}


	/**
	 * Return the request string without the index.php fragment
	 *
	 * @param boolean $blnAmpersand If true, ampersands will be encoded
	 *
	 * @return string The request string
	 *
	 * @deprecated Use Environment::get('indexFreeRequest') instead
	 */
	public static function getIndexFreeRequest($blnAmpersand=true)
	{
		return ampersand(\Environment::get('indexFreeRequest'), $blnAmpersand);
	}


	/**
	 * Compile a Model class name from a table name (e.g. tl_form_field becomes FormFieldModel)
	 *
	 * @param string $strTable The table name
	 *
	 * @return string The model class name
	 *
	 * @deprecated Use Model::getClassFromTable() instead
	 */
	public static function getModelClassFromTable($strTable)
	{
		return \Model::getClassFromTable($strTable);
	}


	/**
	 * Enable a back end module
	 *
	 * @param string $strName The module name
	 *
	 * @return boolean True if the module was enabled
	 *
	 * @deprecated Use Composer to add or remove modules
	 */
	public static function enableModule($strName)
	{
	}


	/**
	 * Disable a back end module
	 *
	 * @param string $strName The module name
	 *
	 * @return boolean True if the module was disabled
	 *
	 * @deprecated Use Composer to add or remove modules
	 */
	public static function disableModule($strName)
	{
	}
}
