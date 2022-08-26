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
 * Sort iterator items ascending
 */
class SortedIterator extends \SplHeap
{
	/**
	 * Insert the elements
	 *
	 * @param \Iterator $iterator
	 */
	public function __construct(\Iterator $iterator)
	{
		foreach ($iterator as $item)
		{
			$this->insert($item);
		}
	}

	/**
	 * Sort items ascending
	 *
	 * @param mixed $value1 The first SplFileInfo object
	 * @param mixed $value2 The second SplFileInfo object
	 *
	 * @return integer Negative value if $b is less than $a, positive value if $b is greater than $a or 0 if they are equal
	 */
	#[\ReturnTypeWillChange]
	public function compare($value1, $value2)
	{
		return strcmp($value2->getRealpath(), $value1->getRealpath());
	}
}

class_alias(SortedIterator::class, 'SortedIterator');
