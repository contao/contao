<?php

/**
 * Contao Open Source CMS
 *
 * Copyright (c) 2005-2015 Leo Feyer
 *
 * @license LGPL-3.0+
 */

namespace Contao;

use Symfony\Component\Security\Csrf\CsrfToken;


/**
 * Generates and validates request tokens
 *
 * The class tries to read and validate the request token from the user session
 * and creates a new token if there is none.
 *
 * Usage:
 *
 *     echo RequestToken::get();
 *
 *     if (!RequestToken::validate('TOKEN'))
 *     {
 *         throw new Exception("Invalid request token");
 *     }
 *
 * @author Leo Feyer <https://github.com/leofeyer>
 *
 * @deprecated Deprecated since Contao 4.0, to be removed in Contao 5.0.
 *             Use the Symfony CSRF service via the container instead.
 */
class RequestToken
{

	/**
	 * Read the token from the session or generate a new one
	 */
	public static function initialize()
	{
		@trigger_error('Using RequestToken::initialize() has been deprecated and will no longer work in Contao 5.0. Use the Symfony CSRF service via the container instead.', E_USER_DEPRECATED);
	}


	/**
	 * Return the token
	 *
	 * @return string The request token
	 */
	public static function get()
	{
		$container = \System::getContainer();
		$name = $container->getParameter('contao.csrf_token_name');

		return $container->get('security.csrf.token_manager')->getToken($name)->getValue();
	}


	/**
	 * Validate a token
	 *
	 * @param string $strToken The request token
	 *
	 * @return boolean True if the token matches the stored one
	 */
	public static function validate($strToken)
	{
		// The feature has been disabled
		if (\Config::get('disableRefererCheck') || defined('BYPASS_TOKEN_CHECK'))
		{
			return true;
		}

		// Check against the whitelist (thanks to Tristan Lins) (see #3164)
		if (\Config::get('requestTokenWhitelist'))
		{
			$strHostname = gethostbyaddr($_SERVER['REMOTE_ADDR']);

			foreach (\Config::get('requestTokenWhitelist') as $strDomain)
			{
				if ($strDomain == $strHostname || preg_match('/\.' . preg_quote($strDomain, '/') . '$/', $strHostname))
				{
					return true;
				}
			}
		}

		$container = \System::getContainer();
		$token = new CsrfToken($container->getParameter('contao.csrf_token_name'), $strToken);

		return $container->get('security.csrf.token_manager')->isTokenValid($token);
	}
}
