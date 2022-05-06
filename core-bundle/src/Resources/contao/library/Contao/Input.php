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
 * Safely read the user input
 *
 * The class functions as an adapter for the global input arrays ($_GET, $_POST,
 * $_COOKIE) and safely returns their values. To prevent XSS vulnerabilities,
 * you should always use the class when reading user input.
 *
 * Usage:
 *
 *     if (Input::get('action') == 'register')
 *     {
 *         $username = Input::post('username');
 *         $password = Input::post('password');
 *     }
 */
class Input
{
	/**
	 * Object instance (Singleton)
	 * @var Input
	 */
	protected static $objInstance;

	/**
	 * Cache
	 * @var array
	 */
	protected static $arrCache = array();

	/**
	 * Unused $_GET parameters
	 * @var array
	 */
	protected static $arrUnusedGet = array();

	/**
	 * Magic quotes setting
	 * @var boolean
	 */
	protected static $blnMagicQuotes = false;

	/**
	 * Clean the global GPC arrays
	 */
	public static function initialize()
	{
		$_GET    = static::cleanKey($_GET);
		$_POST   = static::cleanKey($_POST);
		$_COOKIE = static::cleanKey($_COOKIE);
	}

	/**
	 * Return a $_GET variable
	 *
	 * @param string  $strKey            The variable name
	 * @param boolean $blnDecodeEntities If true, all entities will be decoded
	 * @param boolean $blnKeepUnused     If true, the parameter will not be marked as used (see #4277)
	 *
	 * @return mixed The cleaned variable value
	 */
	public static function get($strKey, $blnDecodeEntities=false, $blnKeepUnused=false)
	{
		if (!isset($_GET[$strKey]))
		{
			return null;
		}

		$strCacheKey = $blnDecodeEntities ? 'getDecoded' : 'getEncoded';

		if (!isset(static::$arrCache[$strCacheKey][$strKey]))
		{
			$varValue = $_GET[$strKey];

			$varValue = static::decodeEntities($varValue);
			$varValue = static::xssClean($varValue, true);
			$varValue = static::stripTags($varValue);

			if (!$blnDecodeEntities)
			{
				$varValue = static::encodeSpecialChars($varValue);
			}

			$varValue = static::encodeInsertTags($varValue);

			static::$arrCache[$strCacheKey][$strKey] = $varValue;
		}

		// Mark the parameter as used (see #4277)
		if (!$blnKeepUnused)
		{
			unset(static::$arrUnusedGet[$strKey]);
		}

		return static::$arrCache[$strCacheKey][$strKey];
	}

	/**
	 * Return a $_POST variable
	 *
	 * @param string  $strKey            The variable name
	 * @param boolean $blnDecodeEntities If true, all entities will be decoded
	 *
	 * @return mixed The cleaned variable value
	 */
	public static function post($strKey, $blnDecodeEntities=false)
	{
		$strCacheKey = $blnDecodeEntities ? 'postDecoded' : 'postEncoded';

		if (!isset(static::$arrCache[$strCacheKey][$strKey]))
		{
			$varValue = static::findPost($strKey);

			if ($varValue === null)
			{
				return null;
			}

			$varValue = static::decodeEntities($varValue);
			$varValue = static::xssClean($varValue, true);
			$varValue = static::stripTags($varValue);

			if (!$blnDecodeEntities)
			{
				$varValue = static::encodeSpecialChars($varValue);
			}

			if (!\defined('TL_MODE') || TL_MODE != 'BE')
			{
				$varValue = static::encodeInsertTags($varValue);
			}

			static::$arrCache[$strCacheKey][$strKey] = $varValue;
		}

		return static::$arrCache[$strCacheKey][$strKey];
	}

	/**
	 * Return a $_POST variable preserving allowed HTML tags
	 *
	 * @param string  $strKey            The variable name
	 * @param boolean $blnDecodeEntities If true, all entities will be decoded
	 *
	 * @return mixed The cleaned variable value
	 */
	public static function postHtml($strKey, $blnDecodeEntities=false)
	{
		$strCacheKey = $blnDecodeEntities ? 'postHtmlDecoded' : 'postHtmlEncoded';

		if (!isset(static::$arrCache[$strCacheKey][$strKey]))
		{
			$varValue = static::findPost($strKey);

			if ($varValue === null)
			{
				return null;
			}

			$varValue = static::decodeEntities($varValue);
			$varValue = static::xssClean($varValue);
			$varValue = static::stripTags($varValue, Config::get('allowedTags'), Config::get('allowedAttributes'));

			if (!$blnDecodeEntities)
			{
				$varValue = static::encodeSpecialChars($varValue);
			}

			if (!\defined('TL_MODE') || TL_MODE != 'BE')
			{
				$varValue = static::encodeInsertTags($varValue);
			}

			static::$arrCache[$strCacheKey][$strKey] = $varValue;
		}

		return static::$arrCache[$strCacheKey][$strKey];
	}

	/**
	 * Return a raw, unsafe $_POST variable
	 *
	 * @param string $strKey The variable name
	 *
	 * @return mixed The raw variable value
	 */
	public static function postRaw($strKey)
	{
		$strCacheKey = 'postRaw';

		if (!isset(static::$arrCache[$strCacheKey][$strKey]))
		{
			$varValue = static::findPost($strKey);

			if ($varValue === null)
			{
				return null;
			}

			$varValue = static::preserveBasicEntities($varValue);
			$varValue = static::xssClean($varValue);

			if (!\defined('TL_MODE') || TL_MODE != 'BE')
			{
				$varValue = static::encodeInsertTags($varValue);
			}

			static::$arrCache[$strCacheKey][$strKey] = $varValue;
		}

		return static::$arrCache[$strCacheKey][$strKey];
	}

	/**
	 * Return a raw, unsafe and unfiltered $_POST variable
	 *
	 * @param string $strKey The variable name
	 *
	 * @return mixed The raw variable value
	 */
	public static function postUnsafeRaw($strKey)
	{
		$strCacheKey = 'postUnsafeRaw';

		if (!isset(static::$arrCache[$strCacheKey][$strKey]))
		{
			$varValue = static::findPost($strKey);

			if ($varValue === null)
			{
				return null;
			}

			static::$arrCache[$strCacheKey][$strKey] = $varValue;
		}

		return static::$arrCache[$strCacheKey][$strKey];
	}

	/**
	 * Return a $_COOKIE variable
	 *
	 * @param string  $strKey            The variable name
	 * @param boolean $blnDecodeEntities If true, all entities will be decoded
	 *
	 * @return mixed The cleaned variable value
	 */
	public static function cookie($strKey, $blnDecodeEntities=false)
	{
		if (!isset($_COOKIE[$strKey]))
		{
			return null;
		}

		$strCacheKey = $blnDecodeEntities ? 'cookieDecoded' : 'cookieEncoded';

		if (!isset(static::$arrCache[$strCacheKey][$strKey]))
		{
			$varValue = $_COOKIE[$strKey];

			$varValue = static::decodeEntities($varValue);
			$varValue = static::xssClean($varValue, true);
			$varValue = static::stripTags($varValue);

			if (!$blnDecodeEntities)
			{
				$varValue = static::encodeSpecialChars($varValue);
			}

			$varValue = static::encodeInsertTags($varValue);

			static::$arrCache[$strCacheKey][$strKey] = $varValue;
		}

		return static::$arrCache[$strCacheKey][$strKey];
	}

	/**
	 * Set a $_GET variable
	 *
	 * @param string  $strKey       The variable name
	 * @param mixed   $varValue     The variable value
	 * @param boolean $blnAddUnused If true, the value usage will be checked
	 */
	public static function setGet($strKey, $varValue, $blnAddUnused=false)
	{
		// Convert special characters (see #7829)
		$strKey = str_replace(array(' ', '.', '['), '_', $strKey);

		$strKey = static::cleanKey($strKey);

		unset(static::$arrCache['getEncoded'][$strKey], static::$arrCache['getDecoded'][$strKey]);

		if ($varValue === null)
		{
			unset($_GET[$strKey]);
		}
		else
		{
			$_GET[$strKey] = $varValue;

			if ($blnAddUnused)
			{
				static::setUnusedGet($strKey, $varValue); // see #4277
			}
		}
	}

	/**
	 * Set a $_POST variable
	 *
	 * @param string $strKey   The variable name
	 * @param mixed  $varValue The variable value
	 */
	public static function setPost($strKey, $varValue)
	{
		$strKey = static::cleanKey($strKey);

		unset(
			static::$arrCache['postEncoded'][$strKey],
			static::$arrCache['postDecoded'][$strKey],
			static::$arrCache['postHtmlEncoded'][$strKey],
			static::$arrCache['postHtmlDecoded'][$strKey],
			static::$arrCache['postRaw'][$strKey],
			static::$arrCache['postUnsafeRaw'][$strKey]
		);

		if ($varValue === null)
		{
			unset($_POST[$strKey]);
		}
		else
		{
			$_POST[$strKey] = $varValue;
		}
	}

	/**
	 * Set a $_COOKIE variable
	 *
	 * @param string $strKey   The variable name
	 * @param mixed  $varValue The variable value
	 */
	public static function setCookie($strKey, $varValue)
	{
		$strKey = static::cleanKey($strKey);

		unset(static::$arrCache['cookieEncoded'][$strKey], static::$arrCache['cookieDecoded'][$strKey]);

		if ($varValue === null)
		{
			unset($_COOKIE[$strKey]);
		}
		else
		{
			$_COOKIE[$strKey] = $varValue;
		}
	}

	/**
	 * Reset the internal cache
	 */
	public static function resetCache()
	{
		static::$arrCache = array();
	}

	/**
	 * Return whether there are unused GET parameters
	 *
	 * @return boolean True if there are unused GET parameters
	 */
	public static function hasUnusedGet()
	{
		return \count(static::$arrUnusedGet) > 0;
	}

	/**
	 * Return the unused GET parameters as array
	 *
	 * @return array The unused GET parameter array
	 */
	public static function getUnusedGet()
	{
		return array_keys(static::$arrUnusedGet);
	}

	/**
	 * Set an unused GET parameter
	 *
	 * @param string $strKey   The array key
	 * @param mixed  $varValue The array value
	 */
	public static function setUnusedGet($strKey, $varValue)
	{
		static::$arrUnusedGet[$strKey] = $varValue;
	}

	/**
	 * Reset the unused GET parameters
	 */
	public static function resetUnusedGet()
	{
		static::$arrUnusedGet = array();
	}

	/**
	 * Sanitize the variable names (thanks to Andreas Schempp)
	 *
	 * @param mixed $varValue A variable name or an array of variable names
	 *
	 * @return mixed The clean name or array of names
	 */
	public static function cleanKey($varValue)
	{
		// Recursively clean arrays
		if (\is_array($varValue))
		{
			$return = array();

			foreach ($varValue as $k=>$v)
			{
				$k = static::cleanKey($k);

				if (\is_array($v))
				{
					$v = static::cleanKey($v);
				}

				$return[$k] = $v;
			}

			return $return;
		}

		$varValue = static::decodeEntities($varValue);
		$varValue = static::xssClean($varValue, true);
		$varValue = static::stripTags($varValue);

		return $varValue;
	}

	/**
	 * Strip slashes
	 *
	 * @param mixed $varValue A string or array
	 *
	 * @return mixed The string or array without slashes
	 *
	 * @deprecated Deprecated since Contao 3.5, to be removed in Contao 5.
	 *             Since get_magic_quotes_gpc() always returns false in PHP 5.4+, the method was never actually executed.
	 */
	public static function stripSlashes($varValue)
	{
		return $varValue;
	}

	/**
	 * Strip HTML and PHP tags preserving HTML comments
	 *
	 * @param mixed  $varValue             A string or array
	 * @param string $strAllowedTags       A string of tags to preserve
	 * @param string $strAllowedAttributes A serialized string of attributes to preserve
	 *
	 * @return mixed The cleaned string or array
	 */
	public static function stripTags($varValue, $strAllowedTags='', $strAllowedAttributes='')
	{
		if ($strAllowedTags !== '' && \func_num_args() < 3)
		{
			trigger_deprecation('contao/core-bundle', '4.4', 'Using %s() with $strAllowedTags but without $strAllowedAttributes has been deprecated and will no longer work in Contao 5.0.', __METHOD__);
			$strAllowedAttributes = Config::get('allowedAttributes');
		}

		if (!$varValue)
		{
			return $varValue;
		}

		// Recursively clean arrays
		if (\is_array($varValue))
		{
			foreach ($varValue as $k=>$v)
			{
				$varValue[$k] = static::stripTags($v, $strAllowedTags, $strAllowedAttributes);
			}

			return $varValue;
		}

		$arrAllowedAttributes = array();

		foreach (StringUtil::deserialize($strAllowedAttributes, true) as $arrRow)
		{
			if (!empty($arrRow['key']) && !empty($arrRow['value']))
			{
				$arrAllowedAttributes[trim($arrRow['key'])] = StringUtil::trimsplit(',', $arrRow['value']);
			}
		}

		// Encode opening arrow brackets (see #3998)
		$varValue = preg_replace_callback(
			'@</?([^\s<>/]*)@',
			static function ($matches) use ($strAllowedTags)
			{
				if (!$matches[1] || stripos($strAllowedTags, '<' . strtolower($matches[1]) . '>') === false)
				{
					$matches[0] = str_replace('<', '&lt;', $matches[0]);
				}

				return $matches[0];
			},
			$varValue
		);

		// Strip the tags
		$varValue = strip_tags($varValue, $strAllowedTags);

		// Restore HTML comments and recheck for encoded null bytes
		$varValue = str_replace(array('&lt;!--', '&lt;![', '\\0'), array('<!--', '<![', '&#92;0'), $varValue);

		// Strip attributes
		if ($strAllowedTags)
		{
			$varValue = self::stripAttributes($varValue, $strAllowedTags, $arrAllowedAttributes);
		}

		return $varValue;
	}

	/**
	 * Strip HTML attributes and normalize them to lowercase and double quotes
	 *
	 * @param string $strHtml
	 * @param string $strAllowedTags
	 * @param array  $arrAllowedAttributes
	 *
	 * @return string
	 */
	private static function stripAttributes($strHtml, $strAllowedTags, $arrAllowedAttributes)
	{
		// Skip if all attributes are allowed on all tags
		if (\in_array('*', $arrAllowedAttributes['*'] ?? array(), true))
		{
			return $strHtml;
		}

		$blnCommentOpen = false;
		$strOpenRawtext = null;

		// Match every single starting and closing tag or special characters outside of tags
		return preg_replace_callback(
			'@</?([^\s<>/]*)([^<>]*)>?|-->|[>"\'=]+@',
			static function ($matches) use ($strAllowedTags, $arrAllowedAttributes, &$blnCommentOpen, &$strOpenRawtext)
			{
				$strTagName = strtolower($matches[1] ?? '');

				if ($strOpenRawtext === $strTagName && '/' === $matches[0][1])
				{
					$strOpenRawtext = null;

					return '</' . $strTagName . '>';
				}

				if (null !== $strOpenRawtext)
				{
					return $matches[0];
				}

				if ($blnCommentOpen && substr($matches[0], -3) === '-->')
				{
					$blnCommentOpen = false;

					return static::encodeSpecialChars(substr($matches[0], 0, -3)) . '-->';
				}

				if (!$blnCommentOpen && 0 === strncmp($matches[0], '<!--', 4))
				{
					if (substr($matches[0], -3) === '-->')
					{
						return '<!--' . static::encodeSpecialChars(substr($matches[0], 4, -3)) . '-->';
					}

					$blnCommentOpen = true;

					return '<!--' . static::encodeSpecialChars(substr($matches[0], 4));
				}

				// Matched special characters or tag is invalid or not allowed, return everything encoded
				if ($strTagName == '' || stripos($strAllowedTags, '<' . $strTagName . '>') === false)
				{
					return static::encodeSpecialChars($matches[0]);
				}

				// Closing tags have no attributes
				if ('/' === substr($matches[0], 1, 1))
				{
					return '</' . $strTagName . '>';
				}

				// Special parsing for RCDATA and RAWTEXT elements https://html.spec.whatwg.org/#rcdata-state
				if (!$blnCommentOpen && \in_array($strTagName, array('script', 'title', 'textarea', 'style', 'xmp', 'iframe', 'noembed', 'noframes', 'noscript')))
				{
					$strOpenRawtext = $strTagName;
				}

				$arrAttributes = self::getAttributesFromTag($matches[2]);

				// Only keep allowed attributes
				$arrAttributes = array_filter(
					$arrAttributes,
					static function ($strAttribute) use ($strTagName, $arrAllowedAttributes)
					{
						// Skip if all attributes are allowed
						if (\in_array('*', $arrAllowedAttributes[$strTagName] ?? array(), true))
						{
							return true;
						}

						$arrCandidates = array($strAttribute);

						// Check for wildcard attributes like data-*
						if (false !== $intDashPosition = strpos($strAttribute, '-'))
						{
							$arrCandidates[] = substr($strAttribute, 0, $intDashPosition + 1) . '*';
						}

						foreach ($arrCandidates as $strCandidate)
						{
							if (
								\in_array($strCandidate, $arrAllowedAttributes['*'] ?? array(), true)
								|| \in_array($strCandidate, $arrAllowedAttributes[$strTagName] ?? array(), true)
							) {
								return true;
							}
						}

						return false;
					},
					ARRAY_FILTER_USE_KEY
				);

				// Build the tag in its normalized form
				$strReturn = '<' . $strTagName;

				foreach ($arrAttributes as $strAttributeName => $strAttributeValue)
				{
					// The value was already encoded by the getAttributesFromTag() method
					$strReturn .= ' ' . $strAttributeName . '="' . $strAttributeValue . '"';
				}

				$strReturn .= '>';

				return $strReturn;
			},
			$strHtml
		);
	}

	/**
	 * Get the attributes as key/value pairs with the values already encoded for HTML
	 *
	 * @param string $strAttributes
	 *
	 * @return array
	 */
	private static function getAttributesFromTag($strAttributes)
	{
		// Match every attribute name value pair
		if (!preg_match_all('@\s+([a-z][a-z0-9_:-]*)(?:\s*=\s*("[^"]*"|\'[^\']*\'|[^\s>]*))?@i', $strAttributes, $matches, PREG_SET_ORDER))
		{
			return array();
		}

		$arrAttributes = array();

		foreach ($matches as $arrMatch)
		{
			$strAttribute = strtolower($arrMatch[1]);

			// Skip attributes that end with dashes or use a double dash
			if (substr($strAttribute, -1) === '-' || false !== strpos($strAttribute, '--'))
			{
				continue;
			}

			// Default to empty string for the value
			$strValue = $arrMatch[2] ?? '';

			// Remove the quotes if matched by the regular expression
			if (
				(strpos($strValue, '"') === 0 && substr($strValue, -1) === '"')
				|| (strpos($strValue, "'") === 0 && substr($strValue, -1) === "'")
			) {
				$strValue = substr($strValue, 1, -1);
			}

			// Encode all special characters and insert tags that are not encoded yet
			if (1 === preg_match('((?:^|:)(?:src|srcset|href|action|formaction|codebase|cite|background|longdesc|profile|usemap|classid|data|icon|manifest|poster|archive)$)', $strAttribute))
			{
				$strValue = StringUtil::specialcharsUrl($strValue);
			}
			else
			{
				$strValue = StringUtil::specialcharsAttribute($strValue);
			}

			$arrAttributes[$strAttribute] = $strValue;
		}

		return $arrAttributes;
	}

	/**
	 * Clean a value and try to prevent XSS attacks
	 *
	 * @param mixed   $varValue      A string or array
	 * @param boolean $blnStrictMode If true, the function removes also JavaScript event handlers
	 *
	 * @return mixed The cleaned string or array
	 */
	public static function xssClean($varValue, $blnStrictMode=false)
	{
		if (!$varValue)
		{
			return $varValue;
		}

		// Recursively clean arrays
		if (\is_array($varValue))
		{
			foreach ($varValue as $k=>$v)
			{
				$varValue[$k] = static::xssClean($v);
			}

			return $varValue;
		}

		// Return if the value is not a string
		if (\is_bool($varValue) || is_numeric($varValue))
		{
			return $varValue;
		}

		// Validate standard character entities and UTF16 two byte encoding
		$varValue = preg_replace('/(&#*\w+)[\x00-\x20]+;/i', '$1;', $varValue);

		// Remove carriage returns
		$varValue = preg_replace('/\r+/', '', $varValue);

		// Replace unicode entities
		$varValue = preg_replace_callback('~&#x([0-9a-f]+);~i', static function ($matches) { return mb_chr(hexdec($matches[1])); }, $varValue);
		$varValue = preg_replace_callback('~&#([0-9]+);~', static function ($matches) { return mb_chr($matches[1]); }, $varValue);

		// Remove null bytes
		$varValue = str_replace(array(\chr(0), '\\0'), array('', '&#92;0'), $varValue);

		// Define a list of keywords
		$arrKeywords = array
		(
			'/\bj\s*a\s*v\s*a\s*s\s*c\s*r\s*i\s*p\s*t\b/is', // javascript
			'/\bv\s*b\s*s\s*c\s*r\s*i\s*p\s*t\b/is', // vbscript
			'/\bv\s*b\s*s\s*c\s*r\s*p\s*t\b/is', // vbscrpt
			'/\bs\s*c\s*r\s*i\s*p\s*t\b/is', //script
			'/\ba\s*p\s*p\s*l\s*e\s*t\b/is', // applet
			'/\ba\s*l\s*e\s*r\s*t\b/is', // alert
			'/\bd\s*o\s*c\s*u\s*m\s*e\s*n\s*t\b/is', // document
			'/\bw\s*r\s*i\s*t\s*e\b/is', // write
			'/\bc\s*o\s*o\s*k\s*i\s*e\b/is', // cookie
			'/\bw\s*i\s*n\s*d\s*o\s*w\b/is' // window
		);

		// Compact exploded keywords like "j a v a s c r i p t"
		foreach ($arrKeywords as $strKeyword)
		{
			$arrMatches = array();
			preg_match_all($strKeyword, $varValue, $arrMatches);

			foreach ($arrMatches[0] as $strMatch)
			{
				$varValue = str_replace($strMatch, preg_replace('/\s*/', '', $strMatch), $varValue);
			}
		}

		$arrRegexp[] = '/<(a|img)[^>]*[^a-z](<script|<xss)[^>]*>/is';
		$arrRegexp[] = '/<(a|img)[^>]*[^a-z]document\.cookie[^>]*>/is';
		$arrRegexp[] = '/<(a|img)[^>]*[^a-z]vbscri?pt\s*:[^>]*>/is';
		$arrRegexp[] = '/<(a|img)[^>]*[^a-z]expression\s*\([^>]*>/is';

		// Also remove event handlers and JavaScript in strict mode
		if ($blnStrictMode)
		{
			$arrRegexp[] = '/vbscri?pt\s*:/is';
			$arrRegexp[] = '/javascript\s*:/is';
			$arrRegexp[] = '/<\s*embed.*swf/is';
			$arrRegexp[] = '/<(a|img)[^>]*[^a-z]alert\s*\([^>]*>/is';
			$arrRegexp[] = '/<(a|img)[^>]*[^a-z]javascript\s*:[^>]*>/is';
			$arrRegexp[] = '/<(a|img)[^>]*[^a-z]window\.[^>]*>/is';
			$arrRegexp[] = '/<(a|img)[^>]*[^a-z]document\.[^>]*>/is';
			$arrRegexp[] = '/<[^>]*[^a-z]onabort\s*=[^>]*>/is';
			$arrRegexp[] = '/<[^>]*[^a-z]onblur\s*=[^>]*>/is';
			$arrRegexp[] = '/<[^>]*[^a-z]onchange\s*=[^>]*>/is';
			$arrRegexp[] = '/<[^>]*[^a-z]onclick\s*=[^>]*>/is';
			$arrRegexp[] = '/<[^>]*[^a-z]onerror\s*=[^>]*>/is';
			$arrRegexp[] = '/<[^>]*[^a-z]onfocus\s*=[^>]*>/is';
			$arrRegexp[] = '/<[^>]*[^a-z]onkeypress\s*=[^>]*>/is';
			$arrRegexp[] = '/<[^>]*[^a-z]onkeydown\s*=[^>]*>/is';
			$arrRegexp[] = '/<[^>]*[^a-z]onkeyup\s*=[^>]*>/is';
			$arrRegexp[] = '/<[^>]*[^a-z]onload\s*=[^>]*>/is';
			$arrRegexp[] = '/<[^>]*[^a-z]onmouseover\s*=[^>]*>/is';
			$arrRegexp[] = '/<[^>]*[^a-z]onmouseup\s*=[^>]*>/is';
			$arrRegexp[] = '/<[^>]*[^a-z]onmousedown\s*=[^>]*>/is';
			$arrRegexp[] = '/<[^>]*[^a-z]onmouseout\s*=[^>]*>/is';
			$arrRegexp[] = '/<[^>]*[^a-z]onreset\s*=[^>]*>/is';
			$arrRegexp[] = '/<[^>]*[^a-z]onselect\s*=[^>]*>/is';
			$arrRegexp[] = '/<[^>]*[^a-z]onsubmit\s*=[^>]*>/is';
			$arrRegexp[] = '/<[^>]*[^a-z]onunload\s*=[^>]*>/is';
			$arrRegexp[] = '/<[^>]*[^a-z]onresize\s*=[^>]*>/is';
		}

		$varValue = preg_replace($arrRegexp, '', $varValue);

		// Recheck for encoded null bytes
		$varValue = str_replace('\\0', '&#92;0', $varValue);

		return $varValue;
	}

	/**
	 * Decode HTML entities
	 *
	 * @param mixed $varValue A string or array
	 *
	 * @return mixed The decoded string or array
	 */
	public static function decodeEntities($varValue)
	{
		if (!$varValue)
		{
			return $varValue;
		}

		// Recursively clean arrays
		if (\is_array($varValue))
		{
			foreach ($varValue as $k=>$v)
			{
				$varValue[$k] = static::decodeEntities($v);
			}

			return $varValue;
		}

		// Preserve basic entities
		$varValue = static::preserveBasicEntities($varValue);
		$varValue = html_entity_decode($varValue, ENT_QUOTES, System::getContainer()->getParameter('kernel.charset'));

		return $varValue;
	}

	/**
	 * Preserve basic entities by replacing them with square brackets (e.g. &amp; becomes [amp])
	 *
	 * @param mixed $varValue A string or array
	 *
	 * @return mixed The string or array with the converted entities
	 */
	public static function preserveBasicEntities($varValue)
	{
		if (!$varValue)
		{
			return $varValue;
		}

		// Recursively clean arrays
		if (\is_array($varValue))
		{
			foreach ($varValue as $k=>$v)
			{
				$varValue[$k] = static::preserveBasicEntities($v);
			}

			return $varValue;
		}

		$varValue = str_replace
		(
			array('[&amp;]', '&amp;', '[&lt;]', '&lt;', '[&gt;]', '&gt;', '[&nbsp;]', '&nbsp;', '[&shy;]', '&shy;'),
			array('[&]', '[&]', '[lt]', '[lt]', '[gt]', '[gt]', '[nbsp]', '[nbsp]', '[-]', '[-]'),
			$varValue
		);

		return $varValue;
	}

	/**
	 * Encode special characters which are potentially dangerous
	 *
	 * @param mixed $varValue A string or array
	 *
	 * @return mixed The encoded string or array
	 */
	public static function encodeSpecialChars($varValue)
	{
		if (!$varValue)
		{
			return $varValue;
		}

		// Recursively clean arrays
		if (\is_array($varValue))
		{
			foreach ($varValue as $k=>$v)
			{
				$varValue[$k] = static::encodeSpecialChars($v);
			}

			return $varValue;
		}

		$arrSearch = array(
			'#', '<', '>', '(', ')', '\\', '=', '"', "'",
			// Revert double encoded #
			'&&#35;35;', '&&#35;60;', '&&#35;62;', '&&#35;40;', '&&#35;41;', '&&#35;92;', '&&#35;61;', '&&#35;34;', '&&#35;39;',
		);

		$arrReplace = array(
			'&#35;', '&#60;', '&#62;', '&#40;', '&#41;', '&#92;', '&#61;', '&#34;', '&#39;',
			'&#35;', '&#60;', '&#62;', '&#40;', '&#41;', '&#92;', '&#61;', '&#34;', '&#39;',
		);

		return str_replace($arrSearch, $arrReplace, $varValue);
	}

	/**
	 * Encode the opening and closing delimiters of insert tags
	 *
	 * @param string|array $varValue The input string
	 *
	 * @return string|array The encoded input string
	 */
	public static function encodeInsertTags($varValue)
	{
		// Recursively encode insert tags
		if (\is_array($varValue))
		{
			foreach ($varValue as $k=>$v)
			{
				$varValue[$k] = static::encodeInsertTags($v);
			}

			return $varValue;
		}

		return str_replace(array('{{', '}}'), array('&#123;&#123;', '&#125;&#125;'), (string) $varValue);
	}

	/**
	 * Fallback to the session form data if there is no post data
	 *
	 * @param string $strKey The variable name
	 *
	 * @return mixed The variable value
	 */
	public static function findPost($strKey)
	{
		if (isset($_POST[$strKey]))
		{
			return $_POST[$strKey];
		}

		// Do not check for $request->hasPreviousSession() and early return here (see #3971)
		if (isset($_SESSION['FORM_DATA'][$strKey]))
		{
			return ($strKey == 'FORM_SUBMIT') ? preg_replace('/^auto_/i', '', $_SESSION['FORM_DATA'][$strKey]) : $_SESSION['FORM_DATA'][$strKey];
		}

		return null;
	}

	/**
	 * Clean the keys of the request arrays
	 *
	 * @deprecated Deprecated since Contao 4.0, to be removed in Contao 5.0.
	 *             The Input class is now static.
	 */
	protected function __construct()
	{
		static::initialize();
	}

	/**
	 * Prevent cloning of the object (Singleton)
	 *
	 * @deprecated Deprecated since Contao 4.0, to be removed in Contao 5.0.
	 *             The Input class is now static.
	 */
	final public function __clone()
	{
	}

	/**
	 * Return the object instance (Singleton)
	 *
	 * @return Input The object instance
	 *
	 * @deprecated Deprecated since Contao 4.0, to be removed in Contao 5.0.
	 *             The Input class is now static.
	 */
	public static function getInstance()
	{
		trigger_deprecation('contao/core-bundle', '4.0', 'Using "Contao\Input::getInstance()" has been deprecated and will no longer work in Contao 5.0. The "Contao\Input" class is now static.');

		if (static::$objInstance === null)
		{
			static::$objInstance = new static();
		}

		return static::$objInstance;
	}
}

class_alias(Input::class, 'Input');
