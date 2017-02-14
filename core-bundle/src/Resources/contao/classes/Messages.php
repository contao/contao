<?php

/**
 * Contao Open Source CMS
 *
 * Copyright (c) 2005-2017 Leo Feyer
 *
 * @license LGPL-3.0+
 */

namespace Contao;


/**
 * Add system messages to the welcome screen.
 *
 * @author Leo Feyer <https://github.com/leofeyer>
 */
class Messages extends \Backend
{

	/**
	 * Check for the latest Contao version
	 *
	 * @return string
	 */
	public function versionCheck()
	{
		$cache = \System::getContainer()->get('contao.cache');

		if (!$cache->contains('latest-version'))
		{
			return '';
		}

		$strVersion = $cache->fetch('latest-version');

		if ($strVersion && version_compare(VERSION . '.' . BUILD, $strVersion, '<'))
		{
			$this->import('BackendUser', 'User');

			if ($this->User->hasAccess('maintenance', 'modules'))
			{
				return '<p class="tl_new"><a href="contao/main.php?do=maintenance">' . sprintf($GLOBALS['TL_LANG']['MSC']['updateVersion'], $strVersion) . '</a></p>';
			}
			else
			{
				return '<p class="tl_new">' . sprintf($GLOBALS['TL_LANG']['MSC']['updateVersion'], $strVersion) . '</p>';
			}
		}

		return '';
	}


	/**
	 * Check for maintenance mode
	 *
	 * @return string
	 */
	public function maintenanceCheck()
	{
		$this->import('BackendUser', 'User');

		if (!$this->User->hasAccess('maintenance', 'modules'))
		{
			return '';
		}

		try
		{
			if (\System::getContainer()->get('lexik_maintenance.driver.factory')->getDriver()->isExists())
			{
				return '<p class="tl_error">' . $GLOBALS['TL_LANG']['MSC']['maintenanceEnabled'] . '</p>';
			}
		}
		catch (\Exception $e)
		{
			// ignore
		}

		return '';
	}


	/**
	 * Show a warning if there is no language fallback page
	 *
	 * @return string
	 */
	public function languageFallback()
	{
		$arrRoots = array();
		$time = \Date::floorToMinute();
		$objRoots = $this->Database->execute("SELECT fallback, dns FROM tl_page WHERE type='root' AND (start='' OR start<='$time') AND (stop='' OR stop>'" . ($time + 60) . "') AND published='1' ORDER BY dns");

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
			if ($v != '')
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
