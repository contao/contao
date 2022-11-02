<?php

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao;

/**
 * Reads the environment variables
 *
 * The class returns the environment variables (which are stored in the PHP
 * $_SERVER array) independent of the operating system.
 *
 * Usage:
 *
 *     echo Environment::get('scriptName');
 *     echo Environment::get('requestUri');
 *
 * @author Leo Feyer <https://github.com/leofeyer>
 */
class Environment
{
	/**
	 * Object instance (Singleton)
	 * @var Environment
	 */
	protected static $objInstance;

	/**
	 * The SAPI name
	 * @var string
	 */
	protected static $strSapi = \PHP_SAPI;

	/**
	 * Cache
	 * @var array
	 */
	protected static $arrCache = array();

	/**
	 * Return an environment variable
	 *
	 * @param string $strKey The variable name
	 *
	 * @return mixed The variable value
	 */
	public static function get($strKey)
	{
		if (isset(static::$arrCache[$strKey]))
		{
			return static::$arrCache[$strKey];
		}

		if (\in_array($strKey, get_class_methods(self::class)))
		{
			static::$arrCache[$strKey] = static::$strKey();
		}
		else
		{
			$arrChunks = preg_split('/([A-Z][a-z]*)/', $strKey, -1, PREG_SPLIT_DELIM_CAPTURE|PREG_SPLIT_NO_EMPTY);
			$strServerKey = strtoupper(implode('_', $arrChunks));
			static::$arrCache[$strKey] = $_SERVER[$strServerKey];
		}

		return static::$arrCache[$strKey];
	}

	/**
	 * Set an environment variable
	 *
	 * @param string $strKey   The variable name
	 * @param mixed  $varValue The variable value
	 */
	public static function set($strKey, $varValue)
	{
		static::$arrCache[$strKey] = $varValue;
	}

	/**
	 * Reset the internal cache
	 */
	public static function reset()
	{
		static::$arrCache = array();
	}

	/**
	 * Return the absolute path to the script (e.g. /home/www/html/website/index.php)
	 *
	 * @return string The absolute path to the script
	 */
	protected static function scriptFilename()
	{
		return str_replace('//', '/', strtr((static::$strSapi == 'cgi' || static::$strSapi == 'isapi' || static::$strSapi == 'cgi-fcgi' || static::$strSapi == 'fpm-fcgi') && ($_SERVER['ORIG_PATH_TRANSLATED'] ?? $_SERVER['PATH_TRANSLATED'] ?? null) ? ($_SERVER['ORIG_PATH_TRANSLATED'] ?? $_SERVER['PATH_TRANSLATED']) : ($_SERVER['ORIG_SCRIPT_FILENAME'] ?? $_SERVER['SCRIPT_FILENAME'] ?? ''), '\\', '/'));
	}

	/**
	 * Return the relative path to the script (e.g. /website/index.php)
	 *
	 * @return string The relative path to the script
	 */
	protected static function scriptName()
	{
		$request = System::getContainer()->get('request_stack')->getCurrentRequest();

		if ($request === null)
		{
			return $_SERVER['ORIG_SCRIPT_NAME'] ?? $_SERVER['SCRIPT_NAME'];
		}

		return $request->getScriptName();
	}

	/**
	 * Alias for scriptName()
	 *
	 * @return string The script name
	 */
	protected static function phpSelf()
	{
		return static::scriptName();
	}

	/**
	 * Return the document root (e.g. /home/www/user/)
	 *
	 * Calculated as SCRIPT_FILENAME minus SCRIPT_NAME as some CGI versions
	 * and mod-rewrite rules might return an incorrect DOCUMENT_ROOT.
	 *
	 * @return string The document root
	 */
	protected static function documentRoot()
	{
		$strDocumentRoot = '';
		$arrUriSegments = array();
		$scriptName = static::get('scriptName');
		$scriptFilename = static::get('scriptFilename');

		// Fallback to DOCUMENT_ROOT if SCRIPT_FILENAME and SCRIPT_NAME point to different files
		if (basename($scriptName) != basename($scriptFilename))
		{
			return str_replace('//', '/', strtr(realpath($_SERVER['DOCUMENT_ROOT']), '\\', '/'));
		}

		if (0 === strncmp($scriptFilename, '/', 1))
		{
			$strDocumentRoot = '/';
		}

		$arrSnSegments = explode('/', strrev($scriptName));
		$arrSfnSegments = explode('/', strrev($scriptFilename));

		foreach ($arrSfnSegments as $k=>$v)
		{
			if (@$arrSnSegments[$k] != $v)
			{
				$arrUriSegments[] = $v;
			}
		}

		$strDocumentRoot .= strrev(implode('/', $arrUriSegments));

		if (\strlen($strDocumentRoot) < 2)
		{
			$strDocumentRoot = substr($scriptFilename, 0, -(\strlen($strDocumentRoot) + 1));
		}

		return str_replace('//', '/', strtr(realpath($strDocumentRoot), '\\', '/'));
	}

	/**
	 * Return the query string (e.g. id=2)
	 *
	 * @return string The query string
	 */
	protected static function queryString()
	{
		if (!isset($_SERVER['QUERY_STRING']))
		{
			return '';
		}

		return static::encodeRequestString($_SERVER['QUERY_STRING']);
	}

	/**
	 * Return the request URI [path]?[query] (e.g. /contao/index.php?id=2)
	 *
	 * @return string The request URI
	 */
	protected static function requestUri()
	{
		if (!empty($_SERVER['REQUEST_URI']))
		{
			$arrComponents = parse_url($_SERVER['REQUEST_URI']);
			$strRequest = $arrComponents['path'] . (isset($arrComponents['query']) ? '?' . $arrComponents['query'] : '');
		}
		else
		{
			$strRequest = '/' . preg_replace('/^\//', '', static::get('scriptName')) . (!empty($_SERVER['QUERY_STRING']) ? '?' . $_SERVER['QUERY_STRING'] : '');
		}

		return static::encodeRequestString($strRequest);
	}

	/**
	 * Return the first eight accepted languages as array
	 *
	 * @return array The languages array
	 *
	 * @author Leo Unglaub <https://github.com/LeoUnglaub>
	 */
	protected static function httpAcceptLanguage()
	{
		$arrAccepted = array();
		$arrLanguages = array();

		// The implementation differs from the original implementation and also works with .jp browsers
		preg_match_all('/([a-z]{1,8}(-[a-z]{1,8})?)\s*(;\s*q\s*=\s*(1|0\.[0-9]+))?/i', $_SERVER['HTTP_ACCEPT_LANGUAGE'], $arrAccepted);

		// Remove all invalid locales
		foreach ($arrAccepted[1] as $v)
		{
			$chunks = explode('-', $v);

			// Language plus dialect, e.g. en-US or fr-FR
			if (isset($chunks[1]))
			{
				$locale = $chunks[0] . '-' . strtoupper($chunks[1]);

				if (preg_match('/^[a-z]{2}(-[A-Z]{2})?$/', $locale))
				{
					$arrLanguages[] = $locale;
				}
			}

			$locale = $chunks[0];

			// Language only, e.g. en or fr (see #29)
			if (preg_match('/^[a-z]{2}$/', $locale))
			{
				$arrLanguages[] = $locale;
			}
		}

		return \array_slice(array_unique($arrLanguages), 0, 8);
	}

	/**
	 * Return accepted encoding types as array
	 *
	 * @return array The encoding types array
	 */
	protected static function httpAcceptEncoding()
	{
		return array_values(array_unique(explode(',', strtolower($_SERVER['HTTP_ACCEPT_ENCODING']))));
	}

	/**
	 * Return the user agent as string
	 *
	 * @return string The user agent string
	 */
	protected static function httpUserAgent()
	{
		$ua = strip_tags($_SERVER['HTTP_USER_AGENT']);
		$ua = preg_replace('/javascript|vbscri?pt|script|applet|alert|document|write|cookie/i', '', $ua);

		return substr($ua, 0, 255);
	}

	/**
	 * Return the HTTP Host
	 *
	 * @return string The host name
	 */
	protected static function httpHost()
	{
		if (!empty($_SERVER['HTTP_HOST']))
		{
			$host = $_SERVER['HTTP_HOST'];
		}
		else
		{
			$host = $_SERVER['SERVER_NAME'];

			if ($_SERVER['SERVER_PORT'] != 80)
			{
				$host .= ':' . $_SERVER['SERVER_PORT'];
			}
		}

		return preg_replace('/[^A-Za-z0-9[\].:_-]/', '', $host);
	}

	/**
	 * Return the HTTP X-Forwarded-Host
	 *
	 * @return string The name of the X-Forwarded-Host
	 */
	protected static function httpXForwardedHost()
	{
		if (!isset($_SERVER['HTTP_X_FORWARDED_HOST']))
		{
			return '';
		}

		return preg_replace('/[^A-Za-z0-9[\].:-]/', '', $_SERVER['HTTP_X_FORWARDED_HOST']);
	}

	/**
	 * Return true if the current page was requested via an SSL connection
	 *
	 * @return boolean True if SSL is enabled
	 */
	protected static function ssl()
	{
		$request = System::getContainer()->get('request_stack')->getCurrentRequest();

		if ($request === null)
		{
			return false;
		}

		return $request->isSecure();
	}

	/**
	 * Return the current URL without path or query string
	 *
	 * @return string The URL
	 */
	protected static function url()
	{
		return (static::get('ssl') ? 'https://' : 'http://') . static::get('httpHost');
	}

	/**
	 * Return the current URL with path or query string
	 *
	 * @return string The URL
	 */
	protected static function uri()
	{
		return static::get('url') . static::get('requestUri');
	}

	/**
	 * Return the real REMOTE_ADDR even if a proxy server is used
	 *
	 * @return string The IP address of the client
	 */
	protected static function ip()
	{
		$request = System::getContainer()->get('request_stack')->getCurrentRequest();

		if ($request === null)
		{
			return '';
		}

		return $request->getClientIp();
	}

	/**
	 * Return the SERVER_ADDR
	 *
	 * @return string The IP address of the server
	 */
	protected static function server()
	{
		$strServer = !empty($_SERVER['SERVER_ADDR']) ? $_SERVER['SERVER_ADDR'] : $_SERVER['LOCAL_ADDR'];

		// Special workaround for Strato users
		if (empty($strServer))
		{
			$strServer = @gethostbyname($_SERVER['SERVER_NAME']);
		}

		return $strServer;
	}

	/**
	 * Return the relative path to the base directory (e.g. /path)
	 *
	 * @return string The relative path to the installation
	 */
	protected static function path()
	{
		$request = System::getContainer()->get('request_stack')->getCurrentRequest();

		if ($request === null)
		{
			return '';
		}

		return $request->getBasePath();
	}

	/**
	 * Return the relative path to the script (e.g. index.php)
	 *
	 * @return string The relative path to the script
	 */
	protected static function script()
	{
		return preg_replace('/^' . preg_quote(static::get('path'), '/') . '\/?/', '', static::get('scriptName'));
	}

	/**
	 * Return the relative path to the script and include the request (e.g. index.php?id=2)
	 *
	 * @return string The relative path to the script including the request string
	 */
	protected static function request()
	{
		return preg_replace('/^' . preg_quote(static::get('path'), '/') . '\/?/', '', static::get('requestUri'));
	}

	/**
	 * Return the request string without the script name (e.g. en/news.html)
	 *
	 * @return string The base URL
	 */
	protected static function relativeRequest()
	{
		return preg_replace('/^' . preg_quote(static::get('script'), '/') . '\/?/', '', static::get('request'));
	}

	/**
	 * Return the request string without the index.php fragment
	 *
	 * @return string The request string without the index.php fragment
	 */
	protected static function indexFreeRequest()
	{
		$strRequest = static::get('request');

		if ($strRequest == static::get('script'))
		{
			return '';
		}

		return $strRequest;
	}

	/**
	 * Return the URL and path that can be used in a <base> tag
	 *
	 * @return string The base URL
	 */
	protected static function base()
	{
		return static::get('url') . static::get('path') . '/';
	}

	/**
	 * Return the host name
	 *
	 * @return string The host name
	 */
	protected static function host()
	{
		return preg_replace('/:\d+$/', '', static::get('httpHost'));
	}

	/**
	 * Return true on Ajax requests
	 *
	 * @return boolean True if it is an Ajax request
	 */
	protected static function isAjaxRequest()
	{
		if (!isset($_SERVER['HTTP_X_REQUESTED_WITH']))
		{
			return false;
		}

		return $_SERVER['HTTP_X_REQUESTED_WITH'] == 'XMLHttpRequest';
	}

	/**
	 * Return the operating system and the browser name and version
	 *
	 * @return object The agent information
	 */
	protected static function agent()
	{
		$ua = static::get('httpUserAgent');

		$return = new \stdClass();
		$return->string = $ua;

		$os = 'unknown';
		$mobile = false;
		$browser = 'other';
		$shorty = '';
		$version = '';
		$engine = '';

		// Operating system
		foreach (Config::get('os') as $k=>$v)
		{
			if (stripos($ua, $k) !== false)
			{
				$os = $v['os'];
				$mobile = $v['mobile'];
				break;
			}
		}

		$return->os = $os;

		// Browser and version
		foreach (Config::get('browser') as $k=>$v)
		{
			if (stripos($ua, $k) !== false)
			{
				$browser = $v['browser'];
				$shorty  = $v['shorty'];
				$version = preg_replace($v['version'], '$1', $ua);
				$engine  = $v['engine'];
				break;
			}
		}

		$versions = explode('.', $version);
		$version  = $versions[0];

		$return->class = $os . ' ' . $browser . ' ' . $engine;

		// Add the version number if available
		if ($version)
		{
			$return->class .= ' ' . $shorty . $version;
		}

		// Android tablets are not mobile (see #4150 and #5869)
		if ($os == 'android' && $engine != 'presto' && stripos($ua, 'mobile') === false)
		{
			$mobile = false;
		}

		// Mark mobile devices
		if ($mobile)
		{
			$return->class .= ' mobile';
		}

		$return->browser  = $browser;
		$return->shorty   = $shorty;
		$return->version  = $version;
		$return->engine   = $engine;
		$return->versions = $versions;
		$return->mobile   = $mobile;

		return $return;
	}

	/**
	 * Encode a request string preserving certain reserved characters
	 *
	 * @param string $strRequest The request string
	 *
	 * @return string The encoded request string
	 */
	protected static function encodeRequestString($strRequest)
	{
		return preg_replace_callback('/[^A-Za-z0-9\-_.~&=+,\/?%\[\]]+/', static function ($matches) { return rawurlencode($matches[0]); }, $strRequest);
	}

	/**
	 * Prevent direct instantiation (Singleton)
	 *
	 * @deprecated Deprecated since Contao 4.0, to be removed in Contao 5.0.
	 *             The Environment class is now static.
	 */
	protected function __construct()
	{
	}

	/**
	 * Prevent cloning of the object (Singleton)
	 *
	 * @deprecated Deprecated since Contao 4.0, to be removed in Contao 5.0.
	 *             The Environment class is now static.
	 */
	final public function __clone()
	{
	}

	/**
	 * Return an environment variable
	 *
	 * @param string $strKey The variable name
	 *
	 * @return string The variable value
	 *
	 * @deprecated Deprecated since Contao 4.0, to be removed in Contao 5.0.
	 *             Use Environment::get() instead.
	 */
	public function __get($strKey)
	{
		return static::get($strKey);
	}

	/**
	 * Set an environment variable
	 *
	 * @param string $strKey   The variable name
	 * @param mixed  $varValue The variable value
	 *
	 * @deprecated Deprecated since Contao 4.0, to be removed in Contao 5.0.
	 *             Use Environment::set() instead.
	 */
	public function __set($strKey, $varValue)
	{
		static::set($strKey, $varValue);
	}

	/**
	 * Return the object instance (Singleton)
	 *
	 * @return Environment The object instance
	 *
	 * @deprecated Deprecated since Contao 4.0, to be removed in Contao 5.0.
	 *             The Environment class is now static.
	 */
	public static function getInstance()
	{
		@trigger_error('Using Environment::getInstance() has been deprecated and will no longer work in Contao 5.0. The Environment class is now static.', E_USER_DEPRECATED);

		if (static::$objInstance === null)
		{
			static::$objInstance = new static();
		}

		return static::$objInstance;
	}
}

class_alias(Environment::class, 'Environment');
