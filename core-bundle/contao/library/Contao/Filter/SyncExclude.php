<?php

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao\Filter;

use Contao\Folder;
use Contao\StringUtil;

/**
 * Filters a directory listing
 *
 * The class filters dot files and folders, which are excluded from
 * being synchronized, from a directory listing.
 */
class SyncExclude extends \RecursiveFilterIterator
{
	/**
	 * Check whether the current element of the iterator is acceptable
	 *
	 * @return boolean True if the element is acceptable
	 */
	public function accept(): bool
	{
		// The resource is to be ignored
		if (str_starts_with($this->current()->getFilename(), '.'))
		{
			return false;
		}

		$strPath = $this->current()->getPathname();

		if (is_file($strPath))
		{
			$strPath = \dirname($strPath);
		}

		$objFolder = new Folder(StringUtil::stripRootDir($strPath));

		return !$objFolder->isUnsynchronized();
	}
}
