<?php

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao;

use Symfony\Component\Security\Csrf\CsrfToken;

trigger_deprecation('contao/core-bundle', '4.0', 'Using the "Contao\RequestToken" class has been deprecated and will no longer work in Contao 5.0. Use the Symfony CSRF service via the container instead.');

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
		// ignore
	}

	/**
	 * Return the token
	 *
	 * @return string The request token
	 */
	public static function get()
	{
		$container = System::getContainer();

		return $container->get('contao.csrf.token_manager')->getDefaultTokenValue();
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
		if (\defined('BYPASS_TOKEN_CHECK') || Config::get('disableRefererCheck'))
		{
			return true;
		}

		// Check against the whitelist (thanks to Tristan Lins) (see #3164)
		if (Config::get('requestTokenWhitelist'))
		{
			$strHostname = gethostbyaddr($_SERVER['REMOTE_ADDR']);

			foreach (Config::get('requestTokenWhitelist') as $strDomain)
			{
				if ($strDomain == $strHostname || preg_match('/\.' . preg_quote($strDomain, '/') . '$/', $strHostname))
				{
					return true;
				}
			}
		}

		$container = System::getContainer();

		return $container->get('contao.csrf.token_manager')->isTokenValid(new CsrfToken($container->getParameter('contao.csrf_token_name'), $strToken));
	}
}

class_alias(RequestToken::class, 'RequestToken');
