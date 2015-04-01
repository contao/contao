<?php

/**
 * Contao Open Source CMS
 *
 * Copyright (c) 2005-2015 Leo Feyer
 *
 * @license LGPL-3.0+
 */

namespace Contao;
use Symfony\Component\HttpKernel\KernelInterface;
use Symfony\Component\Security\Csrf\CsrfToken;
use Symfony\Component\Security\Csrf\CsrfTokenManagerInterface;


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
 * @deprecated Deprecated since version 4.0, to be removed in version 5.0, use
 * the Symfony CSRF service via the container instead!
 */
class RequestToken
{
	/**
	 * Read the token from the session or generate a new one
	 */
	public static function initialize()
	{
		// Backwards compatibility
	}


	/**
	 * Return the token
	 *
	 * @return string The request token
	 */
	public static function get()
	{
		/** @var KernelInterface $kernel */
		global $kernel;

		/** @var CsrfTokenManagerInterface $tokenManager */
		$tokenManager = $kernel->getContainer()->get('security.csrf.token_manager');

		/** @var CsrfToken $token */
		$token = $tokenManager->getToken($kernel->getContainer()->getParameter('contao.csrf_token_name'));

		return $token->getValue();
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

		/** @var KernelInterface $kernel */
		global $kernel;

		/** @var CsrfTokenManagerInterface $tokenManager */
		$tokenManager = $kernel->getContainer()->get('security.csrf.token_manager');

		$token = new CsrfToken($kernel->getContainer()->getParameter('contao.csrf_token_name'), $strToken);

		return $tokenManager->isTokenValid($token);
	}
}
