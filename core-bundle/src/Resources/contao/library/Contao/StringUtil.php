<?php

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao;

use Symfony\Component\Filesystem\Path;

/**
 * Provides string manipulation methods
 *
 * Usage:
 *
 *     $short = StringUtil::substr($str, 32);
 *     $html  = StringUtil::substrHtml($str, 32);
 *     $decoded = StringUtil::decodeEntities($str);
 */
class StringUtil
{
	/**
	 * Shorten a string to a given number of characters
	 *
	 * The function preserves words, so the result might be a bit shorter or
	 * longer than the number of characters given. It strips all tags.
	 *
	 * @param string  $strString        The string to shorten
	 * @param integer $intNumberOfChars The target number of characters
	 * @param string  $strEllipsis      An optional ellipsis to append to the shortened string
	 *
	 * @return string The shortened string
	 */
	public static function substr($strString, $intNumberOfChars, $strEllipsis=' …')
	{
		$strString = preg_replace('/[\t\n\r]+/', ' ', $strString);
		$strString = strip_tags($strString);

		if (mb_strlen($strString) <= $intNumberOfChars)
		{
			return $strString;
		}

		$intCharCount = 0;
		$arrWords = array();
		$arrChunks = preg_split('/\s+/', $strString);

		foreach ($arrChunks as $strChunk)
		{
			$intCharCount += mb_strlen(static::decodeEntities($strChunk));

			if ($intCharCount++ <= $intNumberOfChars)
			{
				$arrWords[] = $strChunk;
				continue;
			}

			// If the first word is longer than $intNumberOfChars already, shorten it
			// with mb_substr() so the method does not return an empty string.
			if (empty($arrWords))
			{
				$arrWords[] = mb_substr($strChunk, 0, $intNumberOfChars);
			}

			break;
		}

		if ($strEllipsis === false)
		{
			trigger_deprecation('contao/core-bundle', '4.0', 'Passing "false" as third argument to "Contao\StringUtil::substr()" has been deprecated and will no longer work in Contao 5.0. Pass an empty string instead.');
			$strEllipsis = '';
		}

		// Deprecated since Contao 4.0, to be removed in Contao 5.0
		if ($strEllipsis === true)
		{
			trigger_deprecation('contao/core-bundle', '4.0', 'Passing "true" as third argument to "Contao\StringUtil::substr()" has been deprecated and will no longer work in Contao 5.0. Pass the ellipsis string instead.');
			$strEllipsis = ' …';
		}

		return implode(' ', $arrWords) . $strEllipsis;
	}

	/**
	 * Shorten an HTML string to a given number of characters
	 *
	 * The function preserves words, so the result might be a bit shorter or
	 * longer than the number of characters given. It preserves allowed tags.
	 *
	 * @param string  $strString        The string to shorten
	 * @param integer $intNumberOfChars The target number of characters
	 *
	 * @return string The shortened HTML string
	 */
	public static function substrHtml($strString, $intNumberOfChars)
	{
		$strReturn = '';
		$intCharCount = 0;
		$arrOpenTags = array();
		$arrTagBuffer = array();
		$arrEmptyTags = array('area', 'base', 'br', 'col', 'embed', 'hr', 'img', 'input', 'link', 'meta', 'param', 'source', 'track', 'wbr');

		$strString = preg_replace('/[\t\n\r]+/', ' ', $strString);
		$strString = strip_tags($strString, Config::get('allowedTags'));
		$strString = preg_replace('/ +/', ' ', $strString);

		// Separate tags and text
		$arrChunks = preg_split('/(<[^>]+>)/', $strString, -1, PREG_SPLIT_DELIM_CAPTURE|PREG_SPLIT_NO_EMPTY);

		for ($i=0, $c=\count($arrChunks); $i<$c; $i++)
		{
			// Buffer tags to include them later
			if (preg_match('/<([^>]+)>/', $arrChunks[$i]))
			{
				$arrTagBuffer[] = $arrChunks[$i];
				continue;
			}

			$buffer = $arrChunks[$i];

			// Get the substring of the current text
			if (!$arrChunks[$i] = static::substr($arrChunks[$i], ($intNumberOfChars - $intCharCount), false))
			{
				break;
			}

			$blnModified = ($buffer !== $arrChunks[$i]);
			$intCharCount += mb_strlen(static::decodeEntities($arrChunks[$i]));

			if ($intCharCount <= $intNumberOfChars)
			{
				foreach ($arrTagBuffer as $strTag)
				{
					$strTagName = strtolower(trim($strTag));

					// Extract the tag name (see #5669)
					if (($pos = strpos($strTagName, ' ')) !== false)
					{
						$strTagName = substr($strTagName, 1, $pos - 1);
					}
					else
					{
						$strTagName = substr($strTagName, 1, -1);
					}

					// Skip empty tags
					if (\in_array($strTagName, $arrEmptyTags))
					{
						continue;
					}

					// Store opening tags in the open_tags array
					if (strncmp($strTagName, '/', 1) !== 0)
					{
						if ($i<$c || !empty($arrChunks[$i]))
						{
							$arrOpenTags[] = $strTagName;
						}

						continue;
					}

					// Closing tags will be removed from the "open tags" array
					if ($i<$c || !empty($arrChunks[$i]))
					{
						$arrOpenTags = array_values($arrOpenTags);

						for ($j=\count($arrOpenTags)-1; $j>=0; $j--)
						{
							if ($strTagName == '/' . $arrOpenTags[$j])
							{
								unset($arrOpenTags[$j]);
								break;
							}
						}
					}
				}

				// If the current chunk contains text, add tags and text to the return string
				if ($i<$c || \strlen($arrChunks[$i]))
				{
					$strReturn .= implode('', $arrTagBuffer) . $arrChunks[$i];
				}

				// Stop after the first shortened chunk (see #7311)
				if ($blnModified)
				{
					break;
				}

				$arrTagBuffer = array();
				continue;
			}

			break;
		}

		// Close all remaining open tags
		krsort($arrOpenTags);

		foreach ($arrOpenTags as $strTag)
		{
			$strReturn .= '</' . $strTag . '>';
		}

		return trim($strReturn);
	}

	/**
	 * Decode all entities
	 *
	 * @param mixed   $strString     The string to decode
	 * @param integer $strQuoteStyle The quote style (defaults to ENT_QUOTES)
	 * @param string  $strCharset    An optional charset
	 *
	 * @return string The decoded string
	 */
	public static function decodeEntities($strString, $strQuoteStyle=ENT_QUOTES, $strCharset=null)
	{
		if ((string) $strString === '')
		{
			return '';
		}

		if ($strCharset === null)
		{
			$strCharset = 'UTF-8';
		}
		else
		{
			trigger_deprecation('contao/core-bundle', '4.13', 'Passing a charset to StringUtil::decodeEntities() has been deprecated and will no longer work in Contao 5.0. Always use UTF-8 instead.');
		}

		$strString = preg_replace('/(&#*\w+)[\x00-\x20]+;/i', '$1;', $strString);
		$strString = preg_replace('/(&#x*)([0-9a-f]+);/i', '$1$2;', $strString);

		return html_entity_decode($strString, $strQuoteStyle, $strCharset);
	}

	/**
	 * Restore basic entities
	 *
	 * @param string|array $strBuffer The string with the tags to be replaced
	 *
	 * @return string|array The string with the original entities
	 */
	public static function restoreBasicEntities($strBuffer)
	{
		return str_replace(array('[&]', '[&amp;]', '[lt]', '[gt]', '[nbsp]', '[-]'), array('&amp;', '&amp;', '&lt;', '&gt;', '&nbsp;', '&shy;'), $strBuffer);
	}

	/**
	 * Generate an alias from a string
	 *
	 * @param string $strString The string
	 *
	 * @return string The alias
	 */
	public static function generateAlias($strString)
	{
		$strString = static::decodeEntities($strString);
		$strString = static::restoreBasicEntities($strString);
		$strString = static::standardize(strip_tags($strString));

		// Remove the prefix if the alias is not numeric (see #707)
		if (strncmp($strString, 'id-', 3) === 0 && !is_numeric($strSubstr = substr($strString, 3)))
		{
			$strString = $strSubstr;
		}

		return $strString;
	}

	/**
	 * Prepare a slug
	 *
	 * @param string $strSlug The slug
	 *
	 * @return string
	 */
	public static function prepareSlug($strSlug)
	{
		$strSlug = static::stripInsertTags($strSlug);
		$strSlug = static::restoreBasicEntities($strSlug);
		$strSlug = static::decodeEntities($strSlug);

		return $strSlug;
	}

	/**
	 * Censor a single word or an array of words within a string
	 *
	 * @param string $strString  The string to censor
	 * @param mixed  $varWords   A string or array or words to replace
	 * @param string $strReplace An optional replacement string
	 *
	 * @return string The cleaned string
	 */
	public static function censor($strString, $varWords, $strReplace='')
	{
		foreach ((array) $varWords as $strWord)
		{
			$strString = preg_replace('/\b(' . str_replace('\*', '\w*?', preg_quote($strWord, '/')) . ')\b/i', $strReplace, $strString);
		}

		return $strString;
	}

	/**
	 * Encode all e-mail addresses within a string
	 *
	 * @param string $strString The string to encode
	 *
	 * @return string The encoded string
	 */
	public static function encodeEmail($strString)
	{
		if (strpos($strString, '@') === false)
		{
			return $strString;
		}

		$arrEmails = static::extractEmail($strString, Config::get('allowedTags'));

		foreach ($arrEmails as $strEmail)
		{
			$strEncoded = '';
			$arrCharacters = mb_str_split($strEmail);

			foreach ($arrCharacters as $index => $strCharacter)
			{
				$strEncoded .= sprintf(($index % 2) ? '&#x%X;' : '&#%s;', mb_ord($strCharacter));
			}

			$strString = str_replace($strEmail, $strEncoded, $strString);
		}

		return str_replace('mailto:', '&#109;&#97;&#105;&#108;&#116;&#111;&#58;', $strString);
	}

	/**
	 * Extract all e-mail addresses from a string
	 *
	 * @param string $strString      The string
	 * @param string $strAllowedTags A list of allowed HTML tags
	 *
	 * @return array The e-mail addresses
	 */
	public static function extractEmail($strString, $strAllowedTags='')
	{
		$arrEmails = array();

		if (strpos($strString, '@') === false)
		{
			return $arrEmails;
		}

		// Find all mailto: addresses
		preg_match_all('/mailto:(?:[^\x00-\x20\x22\x40\x7F]{1,64}+|\x22[^\x00-\x1F\x7F]{1,64}?\x22)@(?:\[(?:IPv)?[a-f0-9.:]{1,47}]|[\w.-]{1,252}\.[a-z]{2,63}\b)/u', $strString, $matches);

		foreach ($matches[0] as &$strEmail)
		{
			$strEmail = str_replace('mailto:', '', $strEmail);

			if (Validator::isEmail($strEmail))
			{
				$arrEmails[] = $strEmail;
			}
		}

		unset($strEmail);

		// Encode opening arrow brackets (see #3998)
		$strString = preg_replace_callback('@</?([^\s<>/]*)@', static function ($matches) use ($strAllowedTags)
		{
			if (!$matches[1] || stripos($strAllowedTags, '<' . strtolower($matches[1]) . '>') === false)
			{
				$matches[0] = str_replace('<', '&lt;', $matches[0]);
			}

			return $matches[0];
		}, $strString);

		// Find all addresses in the plain text
		preg_match_all('/(?:[^\x00-\x20\x22\x40\x7F]{1,64}|\x22[^\x00-\x1F\x7F]{1,64}?\x22)@(?:\[(?:IPv)?[a-f0-9.:]{1,47}]|[\w.-]{1,252}\.[a-z]{2,63}\b)/u', strip_tags($strString), $matches);

		foreach ($matches[0] as &$strEmail)
		{
			$strEmail = str_replace('&lt;', '<', $strEmail);

			if (Validator::isEmail($strEmail))
			{
				$arrEmails[] = $strEmail;
			}
		}

		return array_unique($arrEmails);
	}

	/**
	 * Split a friendly-name e-mail address and return name and e-mail as array
	 *
	 * @param string $strEmail A friendly-name e-mail address
	 *
	 * @return array An array with name and e-mail address
	 */
	public static function splitFriendlyEmail($strEmail)
	{
		if (strpos($strEmail, '<') !== false)
		{
			return array_map('trim', explode(' <', str_replace('>', '', $strEmail)));
		}

		if (strpos($strEmail, '[') !== false)
		{
			return array_map('trim', explode(' [', str_replace(']', '', $strEmail)));
		}

		return array('', $strEmail);
	}

	/**
	 * Wrap words after a particular number of characers
	 *
	 * @param string  $strString The string to wrap
	 * @param integer $strLength The number of characters to wrap after
	 * @param string  $strBreak  An optional break character
	 *
	 * @return string The wrapped string
	 */
	public static function wordWrap($strString, $strLength=75, $strBreak="\n")
	{
		return wordwrap($strString, $strLength, $strBreak);
	}

	/**
	 * Highlight a phrase within a string
	 *
	 * @param string $strString     The string
	 * @param string $strPhrase     The phrase to highlight
	 * @param string $strOpeningTag The opening tag (defaults to <strong>)
	 * @param string $strClosingTag The closing tag (defaults to </strong>)
	 *
	 * @return string The highlighted string
	 */
	public static function highlight($strString, $strPhrase, $strOpeningTag='<strong>', $strClosingTag='</strong>')
	{
		if (!$strString || !$strPhrase)
		{
			return $strString;
		}

		return preg_replace('/(' . preg_quote($strPhrase, '/') . ')/i', $strOpeningTag . '\\1' . $strClosingTag, $strString);
	}

	/**
	 * Split a string of comma separated values
	 *
	 * @param string $strString    The string to split
	 * @param string $strDelimiter An optional delimiter
	 *
	 * @return array The string chunks
	 */
	public static function splitCsv($strString, $strDelimiter=',')
	{
		$arrValues = preg_split('/' . $strDelimiter . '(?=(?:[^"]*"[^"]*")*(?![^"]*"))/', $strString);

		foreach ($arrValues as $k=>$v)
		{
			$arrValues[$k] = trim($v, ' "');
		}

		return $arrValues;
	}

	/**
	 * Convert a string to XHTML
	 *
	 * @param string $strString The HTML5 string
	 *
	 * @return string The XHTML string
	 *
	 * @deprecated Deprecated since Contao 4.9, to be removed in Contao 5.0
	 */
	public static function toXhtml($strString)
	{
		trigger_deprecation('contao/core-bundle', '4.9', 'The "StringUtil::toXhtml()" method has been deprecated and will no longer work in Contao 5.0.');

		$arrPregReplace = array
		(
			'/<(br|hr|img)([^>]*)>/i' => '<$1$2 />', // Close stand-alone tags
			'/ border="[^"]*"/'       => ''          // Remove deprecated attributes
		);

		$arrStrReplace = array
		(
			'/ />'             => ' />',        // Fix incorrectly closed tags
			'<b>'              => '<strong>',   // Replace <b> with <strong>
			'</b>'             => '</strong>',
			'<i>'              => '<em>',       // Replace <i> with <em>
			'</i>'             => '</em>',
			'<u>'              => '<span style="text-decoration:underline">',
			'</u>'             => '</span>',
			' target="_self"'  => '',
			' target="_blank"' => ' onclick="return !window.open(this.href)"'
		);

		$strString = preg_replace(array_keys($arrPregReplace), $arrPregReplace, $strString);
		$strString = str_ireplace(array_keys($arrStrReplace), $arrStrReplace, $strString);

		return $strString;
	}

	/**
	 * Convert a string to HTML5
	 *
	 * @param string $strString The XHTML string
	 *
	 * @return string The HTML5 string
	 *
	 * @deprecated Deprecated since Contao 4.13, to be removed in Contao 5.0
	 */
	public static function toHtml5($strString)
	{
		trigger_deprecation('contao/core-bundle', '4.13', 'The "StringUtil::toHtml5()" method has been deprecated and will no longer work in Contao 5.0.');

		$arrPregReplace = array
		(
			'/<(br|hr|img)([^>]*) \/>/i'                  => '<$1$2>',             // Close stand-alone tags
			'/ (cellpadding|cellspacing|border)="[^"]*"/' => '',                   // Remove deprecated attributes
			'/ rel="lightbox(\[([^\]]+)\])?"/'            => ' data-lightbox="$2"' // see #4073
		);

		$arrStrReplace = array
		(
			'<u>'                                              => '<span style="text-decoration:underline">',
			'</u>'                                             => '</span>',
			' target="_self"'                                  => '',
			' onclick="window.open(this.href); return false"'  => ' target="_blank"',
			' onclick="window.open(this.href);return false"'   => ' target="_blank"',
			' onclick="window.open(this.href); return false;"' => ' target="_blank"'
		);

		$strString = preg_replace(array_keys($arrPregReplace), $arrPregReplace, $strString);
		$strString = str_ireplace(array_keys($arrStrReplace), $arrStrReplace, $strString);

		return $strString;
	}

	/**
	 * Parse simple tokens
	 *
	 * @param string $strString    The string to be parsed
	 * @param array  $arrData      The replacement data
	 * @param array  $blnAllowHtml Whether HTML should be decoded inside conditions
	 *
	 * @return string The converted string
	 *
	 * @throws \RuntimeException         If $strString cannot be parsed
	 * @throws \InvalidArgumentException If there are incorrectly formatted if-tags
	 *
	 * @deprecated Deprecated since Contao 4.10, to be removed in Contao 5.
	 *             Use the contao.string.simple_token_parser service instead.
	 */
	public static function parseSimpleTokens($strString, $arrData, $blnAllowHtml = true)
	{
		trigger_deprecation('contao/core-bundle', '4.10', 'Using "Contao\StringUtil::parseSimpleTokens()" has been deprecated and will no longer work in Contao 5.0. Use the "contao.string.simple_token_parser" service instead.');

		return System::getContainer()->get('contao.string.simple_token_parser')->parse($strString, $arrData, $blnAllowHtml);
	}

	/**
	 * Convert a UUID string to binary data
	 *
	 * @param string $uuid The UUID string
	 *
	 * @return string The binary data
	 */
	public static function uuidToBin($uuid)
	{
		return hex2bin(str_replace('-', '', $uuid));
	}

	/**
	 * Get a UUID string from binary data
	 *
	 * @param string $data The binary data
	 *
	 * @return string The UUID string
	 */
	public static function binToUuid($data)
	{
		return implode('-', unpack('H8time_low/H4time_mid/H4time_high/H4clock_seq/H12node', $data));
	}

	/**
	 * Encode a string with Crockford’s Base32 (0123456789ABCDEFGHJKMNPQRSTVWXYZ)
	 *
	 * @see StringUtil::decodeBase32()
	 */
	public static function encodeBase32(string $bytes): string
	{
		$result = array();

		foreach (str_split($bytes, 5) as $chunk)
		{
			$result[] = substr(
				str_pad(
					strtr(
						base_convert(bin2hex(str_pad($chunk, 5, "\0")), 16, 32),
						'ijklmnopqrstuv',
						'jkmnpqrstvwxyz', // Crockford's Base32
					),
					8,
					'0',
					STR_PAD_LEFT,
				),
				0,
				(int) ceil(\strlen($chunk) * 8 / 5),
			);
		}

		return strtoupper(implode('', $result));
	}

	/**
	 * Decode a Crockford’s Base32 encoded string
	 *
	 * Uppercase and lowercase letters are supported. Letters O and o are
	 * interpreted as 0. Letters I, i, L and l are interpreted as 1.
	 *
	 * @see StringUtil::encodeBase32()
	 */
	public static function decodeBase32(string $base32String): string
	{
		if (1 !== preg_match('/^[0-9a-tv-z]*$/i', $base32String))
		{
			throw new \InvalidArgumentException('Base32 string must only consist of digits and letters except "U"');
		}

		$result = array();

		foreach (str_split($base32String, 8) as $chunk)
		{
			$result[] = substr(
				hex2bin(
					str_pad(
						base_convert(
							strtr(
								str_pad(strtolower($chunk), 8, '0'),
								'oiljkmnpqrstvwxyz', // Crockford's Base32
								'011ijklmnopqrstuv',
							),
							32,
							16,
						),
						10,
						'0',
						STR_PAD_LEFT,
					),
				),
				0,
				(int) floor(\strlen($chunk) * 5 / 8),
			);
		}

		return implode('', $result);
	}

	/**
	 * Convert file paths inside "src" attributes to insert tags
	 *
	 * @param string $data The markup string
	 *
	 * @return string The markup with file paths converted to insert tags
	 */
	public static function srcToInsertTag($data)
	{
		$return = '';
		$paths = preg_split('/((src|href)="([^"]+)")/i', $data, -1, PREG_SPLIT_DELIM_CAPTURE);

		for ($i=0, $c=\count($paths); $i<$c; $i+=4)
		{
			$return .= $paths[$i];

			if (!isset($paths[$i+1]))
			{
				continue;
			}

			$file = FilesModel::findByPath($paths[$i+3]);

			if ($file !== null)
			{
				$return .= $paths[$i+2] . '="{{file::' . static::binToUuid($file->uuid) . '|urlattr}}"';
			}
			else
			{
				$return .= $paths[$i+2] . '="' . $paths[$i+3] . '"';
			}
		}

		return $return;
	}

	/**
	 * Convert insert tags inside "src" attributes to file paths
	 *
	 * @param string $data The markup string
	 *
	 * @return string The markup with insert tags converted to file paths
	 */
	public static function insertTagToSrc($data)
	{
		$return = '';
		$paths = preg_split('/((src|href)="([^"]*){{file::([^"}|]+)[^"}]*}}")/i', $data, -1, PREG_SPLIT_DELIM_CAPTURE);

		for ($i=0, $c=\count($paths); $i<$c; $i+=5)
		{
			$return .= $paths[$i];

			if (!isset($paths[$i+1]))
			{
				continue;
			}

			$file = FilesModel::findByUuid($paths[$i+4]);

			if ($file !== null)
			{
				$return .= $paths[$i+2] . '="' . $paths[$i+3] . $file->path . '"';
			}
			else
			{
				$return .= $paths[$i+1];
			}
		}

		return $return;
	}

	/**
	 * Sanitize a file name
	 *
	 * @param string $strName The file name
	 *
	 * @return string The sanitized file name
	 */
	public static function sanitizeFileName($strName)
	{
		// Remove invisible control characters and unused code points
		$strName = preg_replace('/[\pC]/u', '', $strName);

		if ($strName === null)
		{
			throw new \InvalidArgumentException('The file name could not be sanitzied');
		}

		// Remove special characters not supported on e.g. Windows
		return str_replace(array('\\', '/', ':', '*', '?', '"', '<', '>', '|'), '-', $strName);
	}

	/**
	 * Resolve a flagged URL such as assets/js/core.js|static|10184084
	 *
	 * @param string $url The URL
	 *
	 * @return \stdClass The options object
	 */
	public static function resolveFlaggedUrl(&$url)
	{
		$options = new \stdClass();

		// Defaults
		$options->static = false;
		$options->media  = null;
		$options->mtime  = null;
		$options->async  = false;

		$chunks = explode('|', $url);

		// Remove the flags from the URL
		$url = $chunks[0];

		for ($i=1, $c=\count($chunks); $i<$c; $i++)
		{
			if (empty($chunks[$i]))
			{
				continue;
			}

			switch ($chunks[$i])
			{
				case 'static':
					$options->static = true;
					break;

				case 'async':
					$options->async = true;
					break;

				case is_numeric($chunks[$i]):
					$options->mtime = $chunks[$i];
					break;

				default:
					$options->media = $chunks[$i];
					break;
			}
		}

		return $options;
	}

	/**
	 * Convert the character encoding
	 *
	 * @param string $str  The input string
	 * @param string $to   The target character set
	 * @param string $from An optional source character set
	 *
	 * @return string The converted string
	 */
	public static function convertEncoding($str, $to, $from=null)
	{
		if ($str !== null && !\is_scalar($str) && !(\is_object($str) && method_exists($str, '__toString')))
		{
			trigger_deprecation('contao/core-bundle', '4.9', 'Passing a non-stringable argument to StringUtil::convertEncoding() has been deprecated an will no longer work in Contao 5.0.');

			return '';
		}

		$str = (string) $str;

		if ('' === $str)
		{
			return $str;
		}

		if (!$from)
		{
			$from = mb_detect_encoding($str, 'ASCII,ISO-2022-JP,UTF-8,EUC-JP,ISO-8859-1');
		}

		if ($from == $to)
		{
			return $str;
		}

		return mb_convert_encoding($str, $to, $from);
	}

	/**
	 * Convert special characters to HTML entities preventing double conversions
	 *
	 * @param string  $strString          The input string
	 * @param boolean $blnStripInsertTags True to strip insert tags
	 * @param boolean $blnDoubleEncode    True to encode existing html entities
	 *
	 * @return string The converted string
	 */
	public static function specialchars($strString, $blnStripInsertTags=false, $blnDoubleEncode=false)
	{
		if ($blnStripInsertTags)
		{
			$strString = static::stripInsertTags($strString);
		}

		return htmlspecialchars((string) $strString, ENT_QUOTES, $GLOBALS['TL_CONFIG']['characterSet'] ?? 'UTF-8', $blnDoubleEncode);
	}

	/**
	 * Encodes specialchars and nested insert tags for attributes
	 *
	 * @param string  $strString          The input string
	 * @param boolean $blnStripInsertTags True to strip insert tags
	 * @param boolean $blnDoubleEncode    True to encode existing html entities
	 *
	 * @return string The converted string
	 */
	public static function specialcharsAttribute($strString, $blnStripInsertTags=false, $blnDoubleEncode=false)
	{
		$strString = self::specialchars($strString, $blnStripInsertTags, $blnDoubleEncode);

		// Improve compatibility with JSON in attributes if no insert tags are present
		if ($strString === self::stripInsertTags($strString))
		{
			$strString = str_replace('}}', '&#125;&#125;', $strString);
		}

		// Encode insert tags too
		$strString = preg_replace('/(?:\|attr)?}}/', '|attr}}', $strString);
		$strString = str_replace('|urlattr|attr}}', '|urlattr}}', $strString);

		// Encode all remaining single closing curly braces
		return preg_replace_callback('/}}?/', static fn ($match) => \strlen($match[0]) === 2 ? $match[0] : '&#125;', $strString);
	}

	/**
	 * Encodes disallowed protocols and specialchars for URL attributes
	 *
	 * @param string  $strString          The input string
	 * @param boolean $blnStripInsertTags True to strip insert tags
	 * @param boolean $blnDoubleEncode    True to encode existing html entities
	 *
	 * @return string The converted string
	 */
	public static function specialcharsUrl($strString, $blnStripInsertTags=false, $blnDoubleEncode=false)
	{
		$strString = self::specialchars($strString, $blnStripInsertTags, $blnDoubleEncode);

		// Encode insert tags too
		$strString = preg_replace('/(?:\|urlattr|\|attr)?}}/', '|urlattr}}', $strString);

		// Encode all remaining single closing curly braces
		$strString = preg_replace_callback('/}}?/', static fn ($match) => \strlen($match[0]) === 2 ? $match[0] : '&#125;', $strString);

		$colonRegEx = '('
			. ':'                 // Plain text colon
			. '|'                 // OR
			. '&colon;'           // Named entity
			. '|'                 // OR
			. '&#(?:'             // Start of entity
				. 'x0*+3a'        // Hex number 3A
				. '(?![0-9a-f])'  // Must not be followed by another hex digit
				. '|'             // OR
				. '0*+58'         // Decimal number 58
				. '(?![0-9])'     // Must not be followed by another digit
			. ');?'               // Optional semicolon
		. ')i';

		$arrAllowedUrlProtocols = System::getContainer()->getParameter('contao.sanitizer.allowed_url_protocols');

		// URL-encode colon to prevent disallowed protocols
		if (
			!preg_match('(^(?:' . implode('|', array_map('preg_quote', $arrAllowedUrlProtocols)) . '):)i', self::decodeEntities($strString))
			&& preg_match($colonRegEx, self::stripInsertTags($strString))
		) {
			$arrChunks = preg_split('/({{[^{}]*}})/', $strString, -1, PREG_SPLIT_DELIM_CAPTURE);
			$strString = '';

			foreach ($arrChunks as $index => $strChunk)
			{
				$strString .= ($index % 2) ? $strChunk : preg_replace($colonRegEx, '%3A', $strChunk);
			}
		}

		return $strString;
	}

	/**
	 * Remove Contao insert tags from a string
	 *
	 * @param string $strString The input string
	 *
	 * @return string The converted string
	 */
	public static function stripInsertTags($strString)
	{
		$count = 0;

		do
		{
			$strString = preg_replace('/{{[^{}]*}}/', '', $strString, -1, $count);
		}
		while ($count > 0);

		return $strString;
	}

	/**
	 * Standardize a parameter (strip special characters and convert spaces)
	 *
	 * @param string  $strString            The input string
	 * @param boolean $blnPreserveUppercase True to preserver uppercase characters
	 *
	 * @return string The converted string
	 */
	public static function standardize($strString, $blnPreserveUppercase=false)
	{
		$arrSearch = array('/[^\pN\pL \.\&\/_-]+/u', '/[ \.\&\/-]+/');
		$arrReplace = array('', '-');

		$strString = html_entity_decode($strString, ENT_QUOTES, $GLOBALS['TL_CONFIG']['characterSet'] ?? 'UTF-8');
		$strString = static::stripInsertTags($strString);
		$strString = preg_replace($arrSearch, $arrReplace, $strString);

		if (is_numeric(substr($strString, 0, 1)))
		{
			$strString = 'id-' . $strString;
		}

		if (!$blnPreserveUppercase)
		{
			$strString = mb_strtolower($strString);
		}

		return trim($strString, '-');
	}

	/**
	 * Return an unserialized array or the argument
	 *
	 * @param mixed   $varValue      The serialized string
	 * @param boolean $blnForceArray True to always return an array
	 *
	 * @return mixed The unserialized array or the unprocessed input value
	 */
	public static function deserialize($varValue, $blnForceArray=false)
	{
		// Already an array
		if (\is_array($varValue))
		{
			return $varValue;
		}

		// Null
		if ($varValue === null)
		{
			return $blnForceArray ? array() : null;
		}

		// Not a string
		if (!\is_string($varValue))
		{
			return $blnForceArray ? array($varValue) : $varValue;
		}

		// Empty string
		if (trim($varValue) === '')
		{
			return $blnForceArray ? array() : '';
		}

		// Not a serialized array (see #1486)
		if (strncmp($varValue, 'a:', 2) !== 0)
		{
			return $blnForceArray ? array($varValue) : $varValue;
		}

		// Potentially including an object (see #6724)
		if (preg_match('/[OoC]:\+?[0-9]+:"/', $varValue))
		{
			trigger_error('StringUtil::deserialize() does not allow serialized objects', E_USER_WARNING);

			return $blnForceArray ? array($varValue) : $varValue;
		}

		$varUnserialized = @unserialize($varValue, array('allowed_classes' => false));

		if (\is_array($varUnserialized))
		{
			$varValue = $varUnserialized;
		}
		elseif ($blnForceArray)
		{
			$varValue = array($varValue);
		}

		return $varValue;
	}

	/**
	 * Split a string into fragments, remove whitespace and return fragments as array
	 *
	 * @param string $strPattern The split pattern
	 * @param string $strString  The input string
	 *
	 * @return array The fragments array
	 */
	public static function trimsplit($strPattern, $strString)
	{
		// Split
		if (\strlen($strPattern) == 1)
		{
			$arrFragments = array_map('trim', explode($strPattern, $strString));
		}
		else
		{
			$arrFragments = array_map('trim', preg_split('/' . $strPattern . '/ui', $strString));
		}

		// Empty array
		if (\count($arrFragments) < 2 && !\strlen($arrFragments[0]))
		{
			$arrFragments = array();
		}

		return $arrFragments;
	}

	/**
	 * Strip the Contao root dir from the given absolute path
	 *
	 * @param string $path
	 *
	 * @return string
	 *
	 * @throws \InvalidArgumentException
	 */
	public static function stripRootDir($path)
	{
		// Compare normalized version of the paths
		$projectDir = Path::normalize(System::getContainer()->getParameter('kernel.project_dir'));
		$normalizedPath = Path::normalize($path);
		$length = \strlen($projectDir);

		if (strncmp($normalizedPath, $projectDir, $length) !== 0 || \strlen($normalizedPath) <= $length || $normalizedPath[$length] !== '/')
		{
			throw new \InvalidArgumentException(sprintf('Path "%s" is not inside the Contao root dir "%s"', $path, $projectDir));
		}

		return (string) substr($path, $length + 1);
	}

	/**
	 * Convert all ampersands into their HTML entity (default) or unencoded value
	 *
	 * @param string  $strString
	 * @param boolean $blnEncode
	 *
	 * @return string
	 */
	public static function ampersand($strString, $blnEncode=true): string
	{
		return preg_replace('/&(amp;)?/i', ($blnEncode ? '&amp;' : '&'), $strString);
	}

	/**
	 * Convert an input-encoded string back to the raw UTF-8 value it originated from
	 *
	 * It handles all Contao input encoding specifics like basic entities and encoded entities.
	 */
	public static function revertInputEncoding(string $strValue): string
	{
		$strValue = static::restoreBasicEntities($strValue);
		$strValue = static::decodeEntities($strValue);

		// Ensure valid UTF-8
		if (preg_match('//u', $strValue) !== 1)
		{
			$substituteCharacter = mb_substitute_character();
			mb_substitute_character(0xFFFD);

			$strValue = mb_convert_encoding($strValue, 'UTF-8', 'UTF-8');

			mb_substitute_character($substituteCharacter);
		}

		return $strValue;
	}

	/**
	 * Convert an input-encoded string to plain text UTF-8
	 *
	 * Strips or replaces insert tags, strips HTML tags, decodes entities, escapes insert tag braces.
	 *
	 * @param bool $blnRemoveInsertTags True to remove insert tags instead of replacing them
	 *
	 * @deprecated Deprecated since Contao 4.13, to be removed in Contao 5;
	 *             use the Contao\CoreBundle\String\HtmlDecoder service instead
	 */
	public static function inputEncodedToPlainText(string $strValue, bool $blnRemoveInsertTags = false): string
	{
		trigger_deprecation('contao/core-bundle', '4.13', 'Using "StringUtil::inputEncodedToPlainText()" has been deprecated and will no longer work in Contao 5.0. Use the "Contao\CoreBundle\String\HtmlDecoder" service instead.');

		return System::getContainer()->get('contao.string.html_decoder')->inputEncodedToPlainText($strValue, $blnRemoveInsertTags);
	}

	/**
	 * Convert an HTML string to plain text with normalized white space
	 *
	 * It handles all Contao input encoding specifics like insert tags, basic
	 * entities and encoded entities and is meant to be used with content from
	 * fields that have the allowHtml flag enabled.
	 *
	 * @param bool $blnRemoveInsertTags True to remove insert tags instead of replacing them
	 *
	 * @deprecated Deprecated since Contao 4.13, to be removed in Contao 5;
	 *             use the Contao\CoreBundle\String\HtmlDecoder service instead
	 */
	public static function htmlToPlainText(string $strValue, bool $blnRemoveInsertTags = false): string
	{
		trigger_deprecation('contao/core-bundle', '4.13', 'Using "StringUtil::htmlToPlainText()" has been deprecated and will no longer work in Contao 5.0. Use the "Contao\CoreBundle\String\HtmlDecoder" service instead.');

		return System::getContainer()->get('contao.string.html_decoder')->htmlToPlainText($strValue, $blnRemoveInsertTags);
	}

	/**
	 * @param float|int $number
	 */
	public static function numberToString($number, int $precision = null): string
	{
		if (\is_int($number))
		{
			if (null === $precision)
			{
				return (string) $number;
			}

			$number = (float) $number;
		}

		if (!\is_float($number))
		{
			throw new \TypeError(sprintf('Argument 1 passed to %s() must be of the type int|float, %s given', __METHOD__, get_debug_type($number)));
		}

		if ($precision === null)
		{
			$precision = (int) \ini_get('precision');
		}

		// Special value from PHP ini
		if ($precision === -1)
		{
			$precision = 14;
		}

		if ($precision <= 1)
		{
			throw new \InvalidArgumentException(sprintf('Precision must be greater than 1, "%s" given.', $precision));
		}

		if (!preg_match('/^(-?)(\d)\.(\d+)e([+-]\d+)$/', sprintf('%.' . ($precision - 1) . 'e', $number), $match))
		{
			throw new \InvalidArgumentException(sprintf('Unable to convert "%s" into a string representation.', $number));
		}

		$significantDigits = rtrim($match[2] . $match[3], '0');
		$shiftBy = (int) $match[4] + 1;

		$signPart = $match[1];
		$wholePart = substr(str_pad($significantDigits, $shiftBy, '0'), 0, max(0, $shiftBy)) ?: '0';
		$decimalPart = str_repeat('0', max(0, -$shiftBy)) . substr($significantDigits, max(0, $shiftBy));

		return rtrim("$signPart$wholePart.$decimalPart", '.');
	}
}

class_alias(StringUtil::class, 'StringUtil');
