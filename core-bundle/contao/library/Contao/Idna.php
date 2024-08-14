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
 * An idna_encode adapter class
 *
 * The class encodes and decodes internationalized domain names according to RFC
 * 3490. It also contains two helper methods to encode e-mails and URLs.
 *
 * Usage:
 *
 *     echo Idna::encode('bürger.de');
 *     echo Idna::encodeEmail('mit@bürger.de');
 *     echo Idna::encodeUrl('http://www.bürger.de');
 */
class Idna
{
	/**
	 * Encode an internationalized domain name
	 *
	 * @param string $strDomain The domain name
	 *
	 * @return string The encoded domain name
	 */
	public static function encode($strDomain)
	{
		if (!$strDomain || !\is_string($strDomain))
		{
			return '';
		}

		if (($encoded = idn_to_ascii($strDomain, IDNA_NONTRANSITIONAL_TO_ASCII)) === false)
		{
			return '';
		}

		return $encoded;
	}

	/**
	 * Decode an internationalized domain name
	 *
	 * @param string $strDomain The domain name
	 *
	 * @return string The decoded domain name
	 */
	public static function decode($strDomain)
	{
		if (!$strDomain || !\is_string($strDomain))
		{
			return '';
		}

		if (($decoded = idn_to_utf8($strDomain)) === false)
		{
			return '';
		}

		return $decoded;
	}

	/**
	 * Encode the domain in an e-mail address
	 *
	 * @param string $strEmail The e-mail address
	 *
	 * @return string The encoded e-mail address
	 */
	public static function encodeEmail($strEmail)
	{
		if (!$strEmail || !\is_string($strEmail))
		{
			return '';
		}

		if (!str_contains($strEmail, '@'))
		{
			return $strEmail; // see #6241
		}

		$arrChunks = explode('@', $strEmail);
		$strHost = array_pop($arrChunks);

		if (!$strHost)
		{
			return '';
		}

		$strQuery = null;

		// Strip the query string (see #2149)
		if (str_contains($strHost, '?'))
		{
			list($strHost, $strQuery) = explode('?', $strHost, 2);
		}

		$strHost = static::encode($strHost);

		if (!$strHost)
		{
			return '';
		}

		return implode('@', $arrChunks) . '@' . $strHost . ($strQuery ? '?' . $strQuery : '');
	}

	/**
	 * Decode the domain in an e-mail address
	 *
	 * @param string $strEmail The e-mail address
	 *
	 * @return string The decoded e-mail address
	 */
	public static function decodeEmail($strEmail)
	{
		if (!$strEmail || !\is_string($strEmail))
		{
			return '';
		}

		if (!str_contains($strEmail, '@'))
		{
			return $strEmail; // see #6241
		}

		$arrChunks = explode('@', $strEmail);
		$strHost = array_pop($arrChunks);

		if (!$strHost)
		{
			return '';
		}

		$strQuery = null;

		// Strip the query string (see #2149)
		if (str_contains($strHost, '?'))
		{
			list($strHost, $strQuery) = explode('?', $strHost, 2);
		}

		$strHost = static::decode($strHost);

		if (!$strHost)
		{
			return '';
		}

		return implode('@', $arrChunks) . '@' . $strHost . ($strQuery ? '?' . $strQuery : '');
	}

	/**
	 * Encode the domain in a URL
	 *
	 * @param string $strUrl The URL
	 *
	 * @return string The encoded URL
	 *
	 * @throws \InvalidArgumentException
	 */
	public static function encodeUrl($strUrl)
	{
		if (!$strUrl)
		{
			return '';
		}

		// Empty anchor (see #3555) or insert tag
		if ($strUrl == '#' || str_starts_with($strUrl, '{{'))
		{
			return $strUrl;
		}

		// E-mail address
		if (str_starts_with($strUrl, 'mailto:'))
		{
			return static::encodeEmail($strUrl);
		}

		$arrUrl = parse_url($strUrl);

		if (!isset($arrUrl['scheme']))
		{
			throw new \InvalidArgumentException(\sprintf('Expected a FQDN, got "%s"', $strUrl));
		}

		// Scheme
		$arrUrl['scheme'] .= ((substr($strUrl, \strlen($arrUrl['scheme']), 3) == '://') ? '://' : ':');

		// User
		if (isset($arrUrl['user']))
		{
			$arrUrl['user'] .= isset($arrUrl['pass']) ? ':' : '@';
		}

		// Password
		if (isset($arrUrl['pass']))
		{
			$arrUrl['pass'] .= '@';
		}

		// Host
		if (isset($arrUrl['host']))
		{
			$arrUrl['host'] = static::encode($arrUrl['host']);
		}

		// Port
		if (isset($arrUrl['port']))
		{
			$arrUrl['port'] = ':' . $arrUrl['port'];
		}

		// Path does not have to be altered

		// Query
		if (isset($arrUrl['query']))
		{
			$arrUrl['query'] = '?' . $arrUrl['query'];
		}

		// Anchor
		if (isset($arrUrl['fragment']))
		{
			$arrUrl['fragment'] = '#' . $arrUrl['fragment'];
		}

		$strReturn = '';

		foreach (array('scheme', 'user', 'pass', 'host', 'port', 'path', 'query', 'fragment') as $key)
		{
			if (isset($arrUrl[$key]))
			{
				$strReturn .= $arrUrl[$key];
			}
		}

		return $strReturn;
	}

	/**
	 * Decode the domain in a URL
	 *
	 * @param string $strUrl The URL
	 *
	 * @return string The decoded URL
	 *
	 * @throws \InvalidArgumentException
	 */
	public static function decodeUrl($strUrl)
	{
		if (!$strUrl)
		{
			return '';
		}

		// Empty anchor (see #3555) or insert tag
		if ($strUrl == '#' || str_starts_with($strUrl, '{{'))
		{
			return $strUrl;
		}

		// E-mail address
		if (str_starts_with($strUrl, 'mailto:'))
		{
			return static::decodeEmail($strUrl);
		}

		$arrUrl = parse_url($strUrl);

		if (!isset($arrUrl['scheme']))
		{
			throw new \InvalidArgumentException(\sprintf('Expected a FQDN, got "%s"', $strUrl));
		}

		// Scheme
		$arrUrl['scheme'] .= ((substr($strUrl, \strlen($arrUrl['scheme']), 3) == '://') ? '://' : ':');

		// User
		if (isset($arrUrl['user']))
		{
			$arrUrl['user'] .= isset($arrUrl['pass']) ? ':' : '@';
		}

		// Password
		if (isset($arrUrl['pass']))
		{
			$arrUrl['pass'] .= '@';
		}

		// Host
		if (isset($arrUrl['host']))
		{
			$arrUrl['host'] = static::decode($arrUrl['host']);
		}

		// Port
		if (isset($arrUrl['port']))
		{
			$arrUrl['port'] = ':' . $arrUrl['port'];
		}

		// Path does not have to be altered

		// Query
		if (isset($arrUrl['query']))
		{
			$arrUrl['query'] = '?' . $arrUrl['query'];
		}

		// Anchor
		if (isset($arrUrl['fragment']))
		{
			$arrUrl['fragment'] = '#' . $arrUrl['fragment'];
		}

		$strReturn = '';

		foreach (array('scheme', 'user', 'pass', 'host', 'port', 'path', 'query', 'fragment') as $key)
		{
			if (isset($arrUrl[$key]))
			{
				$strReturn .= $arrUrl[$key];
			}
		}

		return $strReturn;
	}
}
