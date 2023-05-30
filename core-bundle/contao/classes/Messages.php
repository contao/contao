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
 * Add system messages to the welcome screen.
 */
class Messages extends Backend
{
	/**
	 * Show a warning if there is no language fallback page
	 *
	 * @return string
	 */
	public function languageFallback()
	{
		$arrRoots = array();
		$time = Date::floorToMinute();
		$objRoots = $this->Database->execute("SELECT fallback, dns FROM tl_page WHERE type='root' AND published=1 AND (start='' OR start<=$time) AND (stop='' OR stop>$time) ORDER BY dns");

		while ($objRoots->next())
		{
			$strDns = $objRoots->dns ?: '*';

			if (isset($arrRoots[$strDns]) && $arrRoots[$strDns] == 1)
			{
				continue;
			}

			$arrRoots[$strDns] = $objRoots->fallback;
		}

		$arrReturn = array();

		foreach ($arrRoots as $k=>$v)
		{
			if ($v)
			{
				continue;
			}

			if ($k == '*')
			{
				$arrReturn[] = '<p class="tl_error">' . $GLOBALS['TL_LANG']['ERR']['noFallbackEmpty'] . '</p>';
			}
			else
			{
				$arrReturn[] = '<p class="tl_error">' . sprintf($GLOBALS['TL_LANG']['ERR']['noFallbackDns'], $k) . '</p>';
			}
		}

		return implode("\n", $arrReturn);
	}
}
