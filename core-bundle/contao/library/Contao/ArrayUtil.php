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
 * Provides array manipulation methods
 */
class ArrayUtil
{
	/**
	 * Insert a parameter or array into an existing array at a particular index
	 *
	 * @param array   $arrCurrent
	 * @param integer $intIndex
	 * @param mixed   $arrNew
	 */
	public static function arrayInsert(&$arrCurrent, $intIndex, $arrNew): void
	{
		if (!\is_array($arrCurrent))
		{
			$arrCurrent = $arrNew;

			return;
		}

		if (\is_array($arrNew))
		{
			$arrBuffer = array_splice($arrCurrent, 0, $intIndex);
			$arrCurrent = array_merge_recursive($arrBuffer, $arrNew, $arrCurrent);

			return;
		}

		array_splice($arrCurrent, $intIndex, 0, $arrNew);
	}

	/**
	 * Recursively sort an array by key
	 */
	public static function recursiveKeySort(array &$array): void
	{
		foreach ($array as &$value)
		{
			if (\is_array($value))
			{
				self::recursiveKeySort($value);
			}
		}

		ksort($array);
	}

	/**
	 * Return true if an array is associative
	 *
	 * @param mixed $arrArray
	 *
	 * @return boolean
	 */
	public static function isAssoc($arrArray): bool
	{
		return \is_array($arrArray) && !array_is_list($arrArray);
	}

	/**
	 * @param  array        $arrItems   Items array that should ge sorted
	 * @param  string|array $strOrder   Serialized order field or array
	 * @param  string|null  $strIdField Name of the id field to be used for
	 *                                  sorting if the items are objects
	 * @param  boolean      $blnByKey   If true the keys of the $arrItems are used
	 * @return array
	 */
	public static function sortByOrderField(array $arrItems, $strOrder, string|null $strIdField = 'uuid', bool $blnByKey = false): array
	{
		// Remove all values
		$arrOrder = array_map(static function () {}, array_flip(StringUtil::deserialize($strOrder, true)));

		// Move the matching elements to their position in $arrOrder
		foreach ($arrItems as $key=>$item)
		{
			if ($blnByKey)
			{
				$strKey = $key;
			}
			elseif (\is_object($item))
			{
				$strKey = $item->$strIdField;
			}
			elseif (\is_array($item))
			{
				$strKey = $item[$strIdField];
			}
			else
			{
				$strKey = $item;
			}

			if (\array_key_exists($strKey, $arrOrder))
			{
				$arrOrder[$strKey] = $item;
				unset($arrItems[$key]);
			}
		}

		// Remove empty (not replaced) entries
		$arrOrder = array_filter($arrOrder, static function ($item) {
			return $item !== null;
		});

		if ($blnByKey)
		{
			// Append the left-over images at the end
			return $arrOrder + $arrItems;
		}

		// Append the left-over images at the end
		return array_merge(array_values($arrOrder), array_values($arrItems));
	}

	public static function flattenToString(array $arrArray): string
	{
		$iterator = new \RecursiveIteratorIterator(new \RecursiveArrayIterator($arrArray));
		$result = array();

		foreach ($iterator as $leafValue)
		{
			$keys = array();

			foreach (range(0, $iterator->getDepth()) as $depth)
			{
				$keys[] = $iterator->getSubIterator($depth)->key();
			}

			$result[] = implode('.', $keys) . ': ' . $leafValue;
		}

		return implode(', ', $result);
	}
}
