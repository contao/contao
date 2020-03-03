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
 *
 * @author Leo Feyer <https://github.com/leofeyer>
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
	 * Return true if an array is associative
	 *
	 * @param array $arrArray
	 *
	 * @return boolean
	 */
	public static function isAssoc($arrArray): bool
	{
		return \is_array($arrArray) && array_keys($arrArray) !== range(0, \count($arrArray) - 1);
	}

	/**
	 * @param array        $arrItems   Items array that should ge sorted
	 * @param string|array $strOrder   Serialized order field or array
	 * @param string       $strIdField Name of the id field to be used for
	 *                                 sorting if the items are objects
	 * @return array
	 */
	public static function sortByOrderField(array $arrItems, $strOrder, string $strIdField = 'uuid'): array
	{
		// Remove all values
		$arrOrder = array_map(static function () {}, array_flip(StringUtil::deserialize($strOrder, true)));

		// Move the matching elements to their position in $arrOrder
		foreach ($arrItems as $key=>$item)
		{
			if (\is_string($item)) {
				$strKey = $item;
			}
			else {
				$strKey = is_object($item) ? $item->$strIdField : $item[$strIdField];
			}

			if (\array_key_exists($strKey, $arrOrder))
			{
				$arrOrder[$strKey] = $item;
				unset($arrItems[$key]);
			}
		}

		// Remove empty (unreplaced) entries and append the left-over images at the end
		return array_merge(array_values(array_filter($arrOrder)), array_values($images));
	}
}
