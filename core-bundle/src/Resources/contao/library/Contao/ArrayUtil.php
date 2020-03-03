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
}

class_alias(ArrayUtil::class, 'ArrayUtil');
