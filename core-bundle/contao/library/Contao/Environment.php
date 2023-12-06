<?php

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao;

use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\Request;

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
	 * @param string       $strKey  The variable name
	 * @param Request|null $request The request to get the variable from, defaults to the current request
	 *
	 * @return mixed The variable value
	 */
	public static function get($strKey, Request|null $request = null)
	{
		// Return from cache if it was set via Environment::set()
		if (isset(static::$arrCache[$strKey]))
		{
			return static::$arrCache[$strKey];
		}

		$request ??= self::getRequest();

		if (\in_array($strKey, get_class_methods(self::class)))
		{
			return static::$strKey($request);
		}

		$arrChunks = preg_split('/([A-Z][a-z]*)/', $strKey, -1, PREG_SPLIT_DELIM_CAPTURE|PREG_SPLIT_NO_EMPTY);
		$strServerKey = strtoupper(implode('_', $arrChunks));

		return $request->server->get($strServerKey);
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
	protected static function scriptFilename(Request $request)
	{
		return str_replace('//', '/', strtr($request->server->get('SCRIPT_FILENAME'), '\\', '/'));
	}

	/**
	 * Return the relative path to the script (e.g. /website/index.php)
	 *
	 * @return string The relative path to the script
	 */
	protected static function scriptName(Request $request)
	{
		return $request->getScriptName();
	}

	/**
	 * Alias for scriptName()
	 *
	 * @return string The script name
	 */
	protected static function phpSelf(Request $request)
	{
		return static::scriptName($request);
	}

	/**
	 * Return the document root (e.g. /home/www/user/)
	 *
	 * Calculated as SCRIPT_FILENAME minus SCRIPT_NAME as some CGI versions
	 * and mod-rewrite rules might return an incorrect DOCUMENT_ROOT.
	 *
	 * @return string The document root
	 */
	protected static function documentRoot(Request $request)
	{
		$strDocumentRoot = '';
		$arrUriSegments = array();
		$scriptName = static::get('scriptName');
		$scriptFilename = static::get('scriptFilename');

		// Fallback to DOCUMENT_ROOT if SCRIPT_FILENAME and SCRIPT_NAME point to different files
		if (basename($scriptName) != basename($scriptFilename))
		{
			return str_replace(array('\\', '//'), '/', realpath($request->server->get('DOCUMENT_ROOT')));
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
	protected static function queryString(Request $request)
	{
		return static::encodeRequestString($request->getQueryString());
	}

	/**
	 * Return the request URI [path]?[query] (e.g. /contao/index.php?id=2)
	 *
	 * @return string The request URI
	 */
	protected static function requestUri(Request $request)
	{
		return static::encodeRequestString($request->getRequestUri());
	}

	/**
	 * Return the first eight accepted languages as array
	 *
	 * @return array The languages array
	 */
	protected static function httpAcceptLanguage(Request $request)
	{
		return \array_slice(str_replace('_', '-', $request->getLanguages()), 0, 8);
	}

	/**
	 * Return accepted encoding types as array
	 *
	 * @return array The encoding types array
	 */
	protected static function httpAcceptEncoding(Request $request)
	{
		return $request->getEncodings();
	}

	/**
	 * Return the user agent as string
	 *
	 * @return string The user agent string
	 */
	protected static function httpUserAgent(Request $request)
	{
		return substr(strip_tags($request->headers->get('User-Agent')), 0, 255);
	}

	/**
	 * Return the HTTP Host
	 *
	 * @return string The host name
	 */
	protected static function httpHost(Request $request)
	{
		return preg_replace('/[^A-Za-z0-9[\].:_-]/', '', $request->getHttpHost());
	}

	/**
	 * Return the HTTP X-Forwarded-Host
	 *
	 * @return string The name of the X-Forwarded-Host
	 */
	protected static function httpXForwardedHost(Request $request)
	{
		return preg_replace('/[^A-Za-z0-9[\].:-]/', '', $request->headers->get('X-Forwarded-Host', ''));
	}

	/**
	 * Return true if the current page was requested via an SSL connection
	 *
	 * @return boolean True if SSL is enabled
	 */
	protected static function ssl(Request $request)
	{
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
	protected static function ip(Request $request)
	{
		return $request->getClientIp();
	}

	/**
	 * Return the SERVER_ADDR
	 *
	 * @return string The IP address of the server
	 */
	protected static function server(Request $request)
	{
		$strServer = $request->server->get('SERVER_ADDR', $request->server->get('LOCAL_ADDR'));

		// Special workaround for Strato users
		if (empty($strServer))
		{
			$strServer = @gethostbyname($request->server->get('SERVER_NAME'));
		}

		return $strServer;
	}

	/**
	 * Return the relative path to the base directory (e.g. /path)
	 *
	 * @return string The relative path to the installation
	 */
	protected static function path(Request $request)
	{
		return $request->getBasePath();
	}

	/**
	 * Return the relative path to the script (e.g. index.php)
	 *
	 * @return string The relative path to the script
	 */
	protected static function script()
	{
		trigger_deprecation('contao/core-bundle', '5.0', 'Using "%s()" has been deprecated and will no longer work in Contao 6. Use "%s::%s" instead.', __METHOD__, __CLASS__, 'scriptName');

		return preg_replace('/^' . preg_quote(static::get('path'), '/') . '\/?/', '', static::get('scriptName'));
	}

	/**
	 * Return the relative path to the script and include the request (e.g. index.php?id=2)
	 *
	 * @return string The relative path to the script including the request string
	 */
	protected static function request()
	{
		trigger_deprecation('contao/core-bundle', '5.0', 'Using "%s()" has been deprecated and will no longer work in Contao 6. Use "%s::%s" instead.', __METHOD__, __CLASS__, 'requestUri');

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
	protected static function isAjaxRequest(Request $request)
	{
		return $request->isXmlHttpRequest();
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

	private static function getRequest(): Request
	{
		if ($request = System::getContainer()?->get('request_stack', ContainerInterface::NULL_ON_INVALID_REFERENCE)?->getCurrentRequest())
		{
			return $request;
		}

		trigger_deprecation('contao/core-bundle', '5.0', 'Getting data from $_SERVER with the "%s" class has been deprecated and will no longer work in Contao 6. Make sure the request_stack has a request instead.', __CLASS__);

		return new Request(server: $_SERVER);
	}
}
