<?php

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

use Contao\ArrayUtil;
use Contao\Folder;
use Contao\StringUtil;
use Contao\System;
use Symfony\Component\String\UnicodeString;

/**
 * Add a log entry
 *
 * @param string $strMessage
 * @param string $strLog
 *
 * @deprecated Deprecated since Contao 4.0, to be removed in Contao 5.0.
 *             Use the logger service instead.
 */
function log_message($strMessage, $strLog=null)
{
	trigger_deprecation('contao/core-bundle', '4.0', 'Using "log_message()" has been deprecated and will no longer work in Contao 5.0. Use the logger service instead.');

	if ($strLog === null)
	{
		$strLog = 'prod-' . date('Y-m-d') . '.log';
	}

	$strLogsDir = null;

	if (($container = System::getContainer()) !== null)
	{
		$strLogsDir = $container->getParameter('kernel.logs_dir');
	}

	if (!$strLogsDir)
	{
		$strLogsDir = $container->getParameter('kernel.project_dir') . '/var/logs';
	}

	error_log(sprintf("[%s] %s\n", date('d-M-Y H:i:s'), $strMessage), 3, $strLogsDir . '/' . $strLog);
}

/**
 * Scan a directory and return its files and folders as array
 *
 * @param string  $strFolder
 * @param boolean $blnUncached
 *
 * @return array
 *
 * @deprecated Deprecated since Contao 4.10, to be removed in Contao 5.0.
 */
function scan($strFolder, $blnUncached=false)
{
	trigger_deprecation('contao/core-bundle', '4.10', 'Using "scan()" has been deprecated and will no longer work in Contao 5.0. Use "Contao\Folder::scan()" instead.');

	return Folder::scan($strFolder, $blnUncached);
}

/**
 * Convert special characters to HTML entities and make sure that
 * entities are never double converted.
 *
 * @param string  $strString
 * @param boolean $blnStripInsertTags
 *
 * @return string
 *
 * @deprecated Using specialchars() has been deprecated and will no longer work in Contao 5.0.
 *             Use StringUtil::specialchars() instead.
 */
function specialchars($strString, $blnStripInsertTags=false)
{
	trigger_deprecation('contao/core-bundle', '4.0', 'Using "specialchars()" has been deprecated and will no longer work in Contao 5.0. Use "Contao\StringUtil::specialchars()" instead.');

	if ($blnStripInsertTags)
	{
		$strString = strip_insert_tags($strString);
	}

	return htmlspecialchars($strString, ENT_QUOTES, System::getContainer()->getParameter('kernel.charset'), false);
}

/**
 * Standardize a parameter (strip special characters and convert spaces)
 *
 * @param string  $strString
 * @param boolean $blnPreserveUppercase
 *
 * @return string
 *
 * @deprecated Using standardize() has been deprecated and will no longer work in Contao 5.0.
 *             Use StringUtil::standardize() instead.
 */
function standardize($strString, $blnPreserveUppercase=false)
{
	trigger_deprecation('contao/core-bundle', '4.0', 'Using "standardize()" has been deprecated and will no longer work in Contao 5.0. Use "Contao\StringUtil::standardize()" instead.');

	$arrSearch = array('/[^\pN\pL \.\&\/_-]+/u', '/[ \.\&\/-]+/');
	$arrReplace = array('', '-');

	$strString = html_entity_decode($strString, ENT_QUOTES, System::getContainer()->getParameter('kernel.charset'));
	$strString = strip_insert_tags($strString);
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
 * Remove Contao insert tags from a string
 *
 * @param string $strString
 *
 * @return string
 *
 * @deprecated Using strip_insert_tags() has been deprecated and will no longer work in Contao 5.0.
 *             Use StringUtil::stripInsertTags() instead.
 */
function strip_insert_tags($strString)
{
	trigger_deprecation('contao/core-bundle', '4.0', 'Using "strip_insert_tags()" has been deprecated and will no longer work in Contao 5.0. Use "Contao\StringUtil::stripInsertTags()" instead.');

	$count = 0;

	do
	{
		$strString = preg_replace('/{{[^{}]*}}/', '', $strString, -1, $count);
	}
	while ($count > 0);

	return $strString;
}

/**
 * Return an unserialized array or the argument
 *
 * @param mixed   $varValue
 * @param boolean $blnForceArray
 *
 * @return mixed
 *
 * @deprecated Using deserialize() has been deprecated and will no longer work in Contao 5.0.
 *             Use StringUtil::deserialize() instead.
 */
function deserialize($varValue, $blnForceArray=false)
{
	trigger_deprecation('contao/core-bundle', '4.0', 'Using "deserialize()" has been deprecated and will no longer work in Contao 5.0. Use "Contao\StringUtil::deserialize()" instead.');

	// Already an array
	if (is_array($varValue))
	{
		return $varValue;
	}

	// Null
	if ($varValue === null)
	{
		return $blnForceArray ? array() : null;
	}

	// Not a string
	if (!is_string($varValue))
	{
		return $blnForceArray ? array($varValue) : $varValue;
	}

	// Empty string
	if (trim($varValue) === '')
	{
		return $blnForceArray ? array() : '';
	}

	// Potentially including an object (see #6724)
	if (preg_match('/[OoC]:\+?[0-9]+:"/', $varValue))
	{
		trigger_error('The deserialize() function does not allow serialized objects', E_USER_WARNING);

		return $blnForceArray ? array($varValue) : $varValue;
	}

	$varUnserialized = @unserialize($varValue, array('allowed_classes' => false));

	if (is_array($varUnserialized))
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
 * @param string $strPattern
 * @param string $strString
 *
 * @return array
 *
 * @deprecated Using trimsplit() has been deprecated and will no longer work in Contao 5.0.
 *             Use StringUtil::trimsplit() instead.
 */
function trimsplit($strPattern, $strString)
{
	trigger_deprecation('contao/core-bundle', '4.0', 'Using "trimsplit()" has been deprecated and will no longer work in Contao 5.0. Use "Contao\StringUtil::trimsplit()" instead.');

	// Split
	if (strlen($strPattern) == 1)
	{
		$arrFragments = array_map('trim', explode($strPattern, $strString));
	}
	else
	{
		$arrFragments = array_map('trim', preg_split('/' . $strPattern . '/ui', $strString));
	}

	// Empty array
	if (count($arrFragments) < 2 && !strlen($arrFragments[0]))
	{
		$arrFragments = array();
	}

	return $arrFragments;
}

/**
 * Convert all ampersands into their HTML entity (default) or unencoded value
 *
 * @param string  $strString
 * @param boolean $blnEncode
 *
 * @return string
 *
 * @deprecated Deprecated since Contao 4.10, to be removed in Contao 5.0.
 */
function ampersand($strString, $blnEncode=true)
{
	trigger_deprecation('contao/core-bundle', '4.10', 'Using "ampersand()" has been deprecated and will no longer work in Contao 5.0. Use "Contao\StringUtil::ampersand()" instead.');

	return StringUtil::ampersand($strString, $blnEncode);
}

/**
 * Replace line breaks with HTML5-style <br> tags
 *
 * @param string  $str
 * @param boolean $xhtml
 *
 * @return string
 *
 * @deprecated Deprecated since Contao 4.10, to be removed in Contao 5.0.
 */
function nl2br_html5($str, $xhtml=false)
{
	trigger_deprecation('contao/core-bundle', '4.0', 'Using "nl2br_html5()" has been deprecated and will no longer work in Contao 5.0.');

	return nl2br($str, $xhtml);
}

/**
 * Replace line breaks with XHTML-style <br /> tags
 *
 * @param string $str
 *
 * @return string
 *
 * @deprecated Deprecated since Contao 4.10, to be removed in Contao 5.0.
 */
function nl2br_xhtml($str)
{
	trigger_deprecation('contao/core-bundle', '4.0', 'Using "nl2br_xhtml()" has been deprecated and will no longer work in Contao 5.0.');

	return nl2br($str);
}

/**
 * Replace line breaks with <br> tags preserving preformatted text
 *
 * @param string  $str
 * @param boolean $xhtml
 *
 * @return string
 *
 * @deprecated Deprecated since Contao 4.10, to be removed in Contao 5.0.
 */
function nl2br_pre($str, $xhtml=false)
{
	trigger_deprecation('contao/core-bundle', '4.0', 'Using "nl2br_pre()" has been deprecated and will no longer work in Contao 5.0.');

	return preg_replace('/\r?\n/', $xhtml ? '<br />' : '<br>', $str);
}

/**
 * Compare two file names using a case-insensitive "natural order" algorithm
 *
 * @param string $a
 * @param string $b
 *
 * @return integer
 *
 * @deprecated Deprecated since Contao 4.10, to be removed in Contao 5.0.
 */
function basename_natcasecmp($a, $b)
{
	trigger_deprecation('contao/core-bundle', '4.0', 'Using "basename_natcasecmp()" has been deprecated and will no longer work in Contao 5.0.');

	return strnatcasecmp(basename($a), basename($b));
}

/**
 * Compare two file names using a case-insensitive, reverse "natural order" algorithm
 *
 * @param string $a
 * @param string $b
 *
 * @return integer
 *
 * @deprecated Deprecated since Contao 4.10, to be removed in Contao 5.0.
 */
function basename_natcasercmp($a, $b)
{
	trigger_deprecation('contao/core-bundle', '4.0', 'Using "basename_natcasercmp()" has been deprecated and will no longer work in Contao 5.0.');

	return -strnatcasecmp(basename($a), basename($b));
}

/**
 * Sort an array by keys using a case-insensitive "natural order" algorithm
 *
 * @param array $arrArray
 *
 * @return array
 *
 * @deprecated Deprecated since Contao 4.10, to be removed in Contao 5.0.
 */
function natcaseksort($arrArray)
{
	trigger_deprecation('contao/core-bundle', '4.10', 'Using "natcaseksort()" has been deprecated and will no longer work in Contao 5.0. Use "uksort()" with "strnatcasecmp" instead.');

	uksort($arrArray, 'strnatcasecmp');

	return $arrArray;
}

/**
 * Compare two values based on their length (ascending)
 *
 * @param integer $a
 * @param integer $b
 *
 * @return integer
 *
 * @deprecated Deprecated since Contao 4.10, to be removed in Contao 5.0.
 */
function length_sort_asc($a, $b)
{
	trigger_deprecation('contao/core-bundle', '4.0', 'Using "length_sort_asc()" has been deprecated and will no longer work in Contao 5.0. Use a closure instead.');

	return strlen($a) - strlen($b);
}

/**
 * Compare two values based on their length (descending)
 *
 * @param integer $a
 * @param integer $b
 *
 * @return integer
 *
 * @deprecated Deprecated since Contao 4.10, to be removed in Contao 5.0.
 */
function length_sort_desc($a, $b)
{
	trigger_deprecation('contao/core-bundle', '4.0', 'Using "length_sort_desc()" has been deprecated and will no longer work in Contao 5.0. Use a closure instead.');

	return strlen($b) - strlen($a);
}

/**
 * Insert a parameter or array into an existing array at a particular index
 *
 * @param array   $arrCurrent
 * @param integer $intIndex
 * @param mixed   $arrNew
 *
 * @deprecated Deprecated since Contao 4.10, to be removed in Contao 5.0.
 */
function array_insert(&$arrCurrent, $intIndex, $arrNew)
{
	trigger_deprecation('contao/core-bundle', '4.0', 'Using "array_insert()" has been deprecated and will no longer work in Contao 5.0. Use "Contao\ArrayUtil::arrayInsert()" instead.');

	ArrayUtil::arrayInsert($arrCurrent, $intIndex, $arrNew);
}

/**
 * Duplicate a particular element of an array
 *
 * @param array   $arrStack
 * @param integer $intIndex
 *
 * @return array
 *
 * @deprecated Deprecated since Contao 4.3, to be removed in Contao 5.0.
 */
function array_duplicate($arrStack, $intIndex)
{
	trigger_deprecation('contao/core-bundle', '4.0', 'Using "array_duplicate()" has been deprecated and will no longer work in Contao 5.0.');

	$arrBuffer = $arrStack;
	$arrStack = array();

	for ($i=0; $i<=$intIndex; $i++)
	{
		$arrStack[] = $arrBuffer[$i];
	}

	for ($i=$intIndex, $c=count($arrBuffer); $i<$c; $i++)
	{
		$arrStack[] = $arrBuffer[$i];
	}

	return $arrStack;
}

/**
 * Move an array element one position up
 *
 * @param array   $arrStack
 * @param integer $intIndex
 *
 * @return array
 *
 * @deprecated Deprecated since Contao 4.3, to be removed in Contao 5.0.
 */
function array_move_up($arrStack, $intIndex)
{
	trigger_deprecation('contao/core-bundle', '4.0', 'Using "array_move_up()" has been deprecated and will no longer work in Contao 5.0.');

	if ($intIndex > 0)
	{
		$arrBuffer = $arrStack[$intIndex];
		$arrStack[$intIndex] = $arrStack[($intIndex-1)];
		$arrStack[($intIndex-1)] = $arrBuffer;
	}
	else
	{
		$arrStack[] = $arrStack[$intIndex];
		array_shift($arrStack);
	}

	return $arrStack;
}

/**
 * Move an array element one position down
 *
 * @param array   $arrStack
 * @param integer $intIndex
 *
 * @return array
 *
 * @deprecated Deprecated since Contao 4.3, to be removed in Contao 5.0.
 */
function array_move_down($arrStack, $intIndex)
{
	trigger_deprecation('contao/core-bundle', '4.0', 'Using "array_move_down()" has been deprecated and will no longer work in Contao 5.0.');

	if (($intIndex+1) < count($arrStack))
	{
		$arrBuffer = $arrStack[$intIndex];
		$arrStack[$intIndex] = $arrStack[($intIndex+1)];
		$arrStack[($intIndex+1)] = $arrBuffer;
	}
	else
	{
		array_unshift($arrStack, $arrStack[$intIndex]);
		array_pop($arrStack);
	}

	return $arrStack;
}

/**
 * Delete a particular element of an array
 *
 * @param array   $arrStack
 * @param integer $intIndex
 *
 * @return array
 *
 * @deprecated Deprecated since Contao 4.3, to be removed in Contao 5.0.
 */
function array_delete($arrStack, $intIndex)
{
	trigger_deprecation('contao/core-bundle', '4.0', 'Using "array_delete()" has been deprecated and will no longer work in Contao 5.0.');

	unset($arrStack[$intIndex]);

	return array_values($arrStack);
}

/**
 * Return true if an array is associative
 *
 * @param array $arrArray
 *
 * @return boolean
 *
 * @deprecated Deprecated since Contao 4.10, to be removed in Contao 5.0.
 */
function array_is_assoc($arrArray)
{
	trigger_deprecation('contao/core-bundle', '4.0', 'Using "array_is_assoc()" has been deprecated and will no longer work in Contao 5.0. Use "Contao\ArrayUtil::isAssoc()" instead.');

	return ArrayUtil::isAssoc($arrArray);
}

/**
 * Return a specific character
 *
 * Unicode version of chr() that handles UTF-8 characters.
 *
 * @param integer $dec
 *
 * @return string
 *
 * @deprecated Deprecated since Contao 4.0, to be removed in Contao 5.0.
 *             Use mb_chr() instead.
 */
function utf8_chr($dec)
{
	trigger_deprecation('contao/core-bundle', '4.0', 'Using "utf8_chr()" has been deprecated and will no longer work in Contao 5.0. Use "mb_chr()" instead.');

	return mb_chr($dec);
}

/**
 * Return the ASCII value of a character
 *
 * Unicode version of ord() that handles UTF-8 characters.
 *
 * @param string $str
 *
 * @return integer
 *
 * @deprecated Deprecated since Contao 4.0, to be removed in Contao 5.0.
 *             Use mb_ord() instead.
 */
function utf8_ord($str)
{
	trigger_deprecation('contao/core-bundle', '4.0', 'Using "utf8_ord()" has been deprecated and will no longer work in Contao 5.0. Use "mb_ord()" instead.');

	return mb_ord($str);
}

/**
 * Convert character encoding
 *
 * @param string $str
 * @param string $to
 * @param string $from
 *
 * @return string
 *
 * @deprecated Deprecated since Contao 4.0, to be removed in Contao 5.0.
 *             Use StringUtil::convertEncoding() instead.
 */
function utf8_convert_encoding($str, $to, $from=null)
{
	trigger_deprecation('contao/core-bundle', '4.0', 'Using "utf8_convert_encoding()" has been deprecated and will no longer work in Contao 5.0. Use "Contao\StringUtil::convertEncoding()" instead.');

	if (!$str)
	{
		return '';
	}

	if (!$from)
	{
		$from = mb_detect_encoding($str);
	}

	if ($from == $to)
	{
		return $str;
	}

	if ($from == 'UTF-8' && $to == 'ISO-8859-1')
	{
		return utf8_decode($str);
	}

	if ($from == 'ISO-8859-1' && $to == 'UTF-8')
	{
		return utf8_encode($str);
	}

	return mb_convert_encoding($str, $to, $from);
}

/**
 * Convert all unicode entities to their applicable characters
 *
 * Calls mb_chr() to convert unicode entities. HTML entities like '&nbsp;'
 * or '&quot;' will not be decoded.
 *
 * @param string $str
 *
 * @return string
 *
 * @deprecated Deprecated since Contao 4.0, to be removed in Contao 5.0.
 *             Use html_entity_decode() instead.
 */
function utf8_decode_entities($str)
{
	trigger_deprecation('contao/core-bundle', '4.0', 'Using "utf8_decode_entities()" has been deprecated and will no longer work in Contao 5.0. Use "html_entity_decode()" instead.');

	$str = preg_replace_callback('~&#x([0-9a-f]+);~i', static function ($matches) { return mb_chr(hexdec($matches[1])); }, $str);
	$str = preg_replace_callback('~&#([0-9]+);~', static function ($matches) { return mb_chr($matches[1]); }, $str);

	return $str;
}

/**
 * Callback function for utf8_decode_entities
 *
 * @param array $matches
 *
 * @return string
 *
 * @deprecated Deprecated since Contao 4.0, to be removed in Contao 5.0.
 */
function utf8_chr_callback($matches)
{
	trigger_deprecation('contao/core-bundle', '4.0', 'Using "utf8_chr_callback()" has been deprecated and will no longer work in Contao 5.0.');

	return mb_chr($matches[1]);
}

/**
 * Callback function for utf8_decode_entities
 *
 * @param array $matches
 *
 * @return string
 *
 * @deprecated Deprecated since Contao 4.0, to be removed in Contao 5.0.
 */
function utf8_hexchr_callback($matches)
{
	trigger_deprecation('contao/core-bundle', '4.0', 'Using "utf8_hexchr_callback()" has been deprecated and will no longer work in Contao 5.0.');

	return mb_chr(hexdec($matches[1]));
}

/**
 * Detect the encoding of a string
 *
 * @param string $str
 *
 * @return string
 *
 * @deprecated Deprecated since Contao 4.0, to be removed in Contao 5.0.
 *             Use mb_detect_encoding() instead.
 */
function utf8_detect_encoding($str)
{
	trigger_deprecation('contao/core-bundle', '4.0', 'Using "utf8_detect_encoding()" has been deprecated and will no longer work in Contao 5.0. Use "mb_detect_encoding()" instead.');

	return mb_detect_encoding($str, array('ASCII', 'ISO-2022-JP', 'UTF-8', 'EUC-JP', 'ISO-8859-1'));
}

/**
 * Romanize a string
 *
 * @param string $str
 *
 * @return string
 *
 * @deprecated Deprecated since Contao 4.0, to be removed in Contao 5.0.
 *             Use the symfony/string component instead.
 */
function utf8_romanize($str)
{
	trigger_deprecation('contao/core-bundle', '4.0', 'Using "utf8_romanize()" has been deprecated and will no longer work in Contao 5.0. Use the "symfony/string" component instead.');

	return (new UnicodeString($str))->ascii()->toString();
}

/**
 * Determine the number of characters of a string
 *
 * @param string $str
 *
 * @return integer
 *
 * @deprecated Deprecated since Contao 4.0, to be removed in Contao 5.0.
 *             Use mb_strlen() instead.
 */
function utf8_strlen($str)
{
	trigger_deprecation('contao/core-bundle', '4.0', 'Using "utf8_strlen()" has been deprecated and will no longer work in Contao 5.0. Use "mb_strlen()" instead.');

	return mb_strlen($str);
}

/**
 * Find the position of the first occurrence of a string in another string
 *
 * @param string  $haystack
 * @param string  $needle
 * @param integer $offset
 *
 * @return integer
 *
 * @deprecated Deprecated since Contao 4.0, to be removed in Contao 5.0.
 *             Use mb_strpos instead.
 */
function utf8_strpos($haystack, $needle, $offset=0)
{
	trigger_deprecation('contao/core-bundle', '4.0', 'Using "utf8_strpos()" has been deprecated and will no longer work in Contao 5.0. Use "mb_strpos()" instead.');

	return mb_strpos($haystack, $needle, $offset);
}

/**
 * Find the last occurrence of a character in a string
 *
 * @param string $haystack
 * @param string $needle
 *
 * @return string
 *
 * @deprecated Deprecated since Contao 4.0, to be removed in Contao 5.0.
 *             Use mb_strrchr() instead.
 */
function utf8_strrchr($haystack, $needle)
{
	trigger_deprecation('contao/core-bundle', '4.0', 'Using "utf8_strrchr()" has been deprecated and will no longer work in Contao 5.0. Use "mb_strrchr()" instead.');

	return mb_strrchr($haystack, $needle);
}

/**
 * Find the position of the last occurrence of a string in another string
 *
 * @param string $haystack
 * @param string $needle
 *
 * @return mixed
 *
 * @deprecated Deprecated since Contao 4.0, to be removed in Contao 5.0.
 *             Use mb_strrpos() instead.
 */
function utf8_strrpos($haystack, $needle)
{
	trigger_deprecation('contao/core-bundle', '4.0', 'Using "utf8_strrpos()" has been deprecated and will no longer work in Contao 5.0. Use "mb_strrpos()" instead.');

	return mb_strrpos($haystack, $needle);
}

/**
 * Find the first occurrence of a string in another string
 *
 * @param string $haystack
 * @param string $needle
 *
 * @return string
 *
 * @deprecated Deprecated since Contao 4.0, to be removed in Contao 5.0.
 *             Use mb_strstr() instead.
 */
function utf8_strstr($haystack, $needle)
{
	trigger_deprecation('contao/core-bundle', '4.0', 'Using "utf8_strstr()" has been deprecated and will no longer work in Contao 5.0. Use "mb_strstr()" instead.');

	return mb_strstr($haystack, $needle);
}

/**
 * Make a string lowercase
 *
 * @param string $str
 *
 * @return string
 *
 * @deprecated Deprecated since Contao 4.0, to be removed in Contao 5.0.
 *             Use mb_strtolower() instead.
 */
function utf8_strtolower($str)
{
	trigger_deprecation('contao/core-bundle', '4.0', 'Using "utf8_strtolower()" has been deprecated and will no longer work in Contao 5.0. Use "mb_strtolower()" instead.');

	return mb_strtolower($str);
}

/**
 * Make a string uppercase
 *
 * @param string $str
 *
 * @return string
 *
 * @deprecated Deprecated since Contao 4.0, to be removed in Contao 5.0.
 *             Use mb_strtoupper() instead.
 */
function utf8_strtoupper($str)
{
	trigger_deprecation('contao/core-bundle', '4.0', 'Using "utf8_strtoupper()" has been deprecated and will no longer work in Contao 5.0. Use "mb_strtoupper()" instead.');

	return mb_strtoupper($str);
}

/**
 * Return substring of a string
 *
 * @param string  $str
 * @param integer $start
 * @param integer $length
 *
 * @return string
 *
 * @deprecated Deprecated since Contao 4.0, to be removed in Contao 5.0.
 *             Use mb_substr() instead.
 */
function utf8_substr($str, $start, $length=null)
{
	trigger_deprecation('contao/core-bundle', '4.0', 'Using "utf8_substr()" has been deprecated and will no longer work in Contao 5.0. Use "mb_substr()" instead.');

	return mb_substr($str, $start, $length);
}

/**
 * Make sure the first letter is uppercase
 *
 * @param string $str
 *
 * @return string
 *
 * @deprecated Deprecated since Contao 4.0, to be removed in Contao 5.0.
 *             Use the symfony/string component instead.
 */
function utf8_ucfirst($str)
{
	trigger_deprecation('contao/core-bundle', '4.0', 'Using "utf8_ucfirst()" has been deprecated and will no longer work in Contao 5.0. Use the "symfony/string" component instead.');

	return (new UnicodeString($str))->title()->toString();
}

/**
 * Convert a string to an array
 *
 * @param string $str
 *
 * @return array
 *
 * @deprecated Deprecated since Contao 4.0, to be removed in Contao 5.0.
 *             Use mb_str_split() instead.
 */
function utf8_str_split($str)
{
	trigger_deprecation('contao/core-bundle', '4.0', 'Using "utf8_str_split()" has been deprecated and will no longer work in Contao 5.0. Use "mb_str_split()" instead.');

	return mb_str_split($str);
}

/**
 * Replace line breaks with <br> tags (to be used with preg_replace_callback)
 *
 * @param array $matches
 *
 * @return string
 *
 * @deprecated Deprecated since Contao 4.0, to be removed in Contao 5.0.
 */
function nl2br_callback($matches)
{
	trigger_deprecation('contao/core-bundle', '4.0', 'Using "nl2br_callback()" has been deprecated and will no longer work in Contao 5.0.');

	return str_replace("\n", '<br>', $matches[0]);
}
