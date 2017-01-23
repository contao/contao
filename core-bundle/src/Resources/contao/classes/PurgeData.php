<?php

/**
 * Contao Open Source CMS
 *
 * Copyright (c) 2005-2016 Leo Feyer
 *
 * @license LGPL-3.0+
 */

namespace Contao;

use Symfony\Component\Finder\Finder;


/**
 * Maintenance module "purge data".
 *
 * @author Leo Feyer <https://github.com/leofeyer>
 */
class PurgeData extends \Backend implements \executable
{

	/**
	 * Return true if the module is active
	 *
	 * @return boolean
	 */
	public function isActive()
	{
		return (\Input::post('FORM_SUBMIT') == 'tl_purge');
	}


	/**
	 * Generate the module
	 *
	 * @return string
	 */
	public function run()
	{
		$arrJobs = array();

		/** @var BackendTemplate|object $objTemplate */
		$objTemplate = new \BackendTemplate('be_purge_data');
		$objTemplate->isActive = $this->isActive();
		$objTemplate->message = \Message::generateUnwrapped();

		// Run the jobs
		if (\Input::post('FORM_SUBMIT') == 'tl_purge')
		{
			$purge = \Input::post('purge');

			if (!empty($purge) && is_array($purge))
			{
				foreach ($purge as $group=>$jobs)
				{
					foreach ($jobs as $job)
					{
						list($class, $method) = $GLOBALS['TL_PURGE'][$group][$job]['callback'];
						$this->import($class);
						$this->$class->$method();
					}
				}
			}

			\Message::addConfirmation($GLOBALS['TL_LANG']['tl_maintenance']['cacheCleared']);
			$this->reload();
		}

		// Tables
		foreach ($GLOBALS['TL_PURGE']['tables'] as $key=>$config)
		{
			$arrJobs[$key] = array
			(
				'id' => 'purge_' . $key,
				'title' => $GLOBALS['TL_LANG']['tl_maintenance_jobs'][$key][0],
				'description' => $GLOBALS['TL_LANG']['tl_maintenance_jobs'][$key][1],
				'group' => 'tables',
				'affected' => ''
			);

			// Get the current table size
			foreach ($config['affected'] as $table)
			{
				$objCount = $this->Database->execute("SELECT COUNT(*) AS count FROM " . $table);
				$arrJobs[$key]['affected'] .= '<br>' . $table . ': <span>' . sprintf($GLOBALS['TL_LANG']['MSC']['entries'], $objCount->count) . ', ' . $this->getReadableSize($this->Database->getSizeOf($table), 0) . '</span>';
			}
		}

		$strCachePath = str_replace(TL_ROOT . DIRECTORY_SEPARATOR, '', \System::getContainer()->getParameter('kernel.cache_dir'));

		// Folders
		foreach ($GLOBALS['TL_PURGE']['folders'] as $key=>$config)
		{
			$arrJobs[$key] = array
			(
				'id' => 'purge_' . $key,
				'title' => $GLOBALS['TL_LANG']['tl_maintenance_jobs'][$key][0],
				'description' => $GLOBALS['TL_LANG']['tl_maintenance_jobs'][$key][1],
				'group' => 'folders',
				'affected' => ''
			);

			// Get the current folder size
			foreach ($config['affected'] as $folder)
			{
				$total = 0;
				$folder = sprintf($folder, $strCachePath);

				// Only check existing folders
				if (is_dir(TL_ROOT . '/' . $folder))
				{
					$objFiles = Finder::create()->in(TL_ROOT . '/' . $folder)->files();
					$total = iterator_count($objFiles);
				}

				$arrJobs[$key]['affected'] .= '<br>' . $folder . ': <span>' . sprintf($GLOBALS['TL_LANG']['MSC']['files'], $total) . '</span>';
			}
		}

		// Custom
		foreach ($GLOBALS['TL_PURGE']['custom'] as $key=>$job)
		{
			$arrJobs[$key] = array
			(
				'id' => 'purge_' . $key,
				'title' => $GLOBALS['TL_LANG']['tl_maintenance_jobs'][$key][0],
				'description' => $GLOBALS['TL_LANG']['tl_maintenance_jobs'][$key][1],
				'group' => 'custom'
			);
		}

		$objTemplate->jobs = $arrJobs;
		$objTemplate->action = ampersand(\Environment::get('request'));
		$objTemplate->headline = $GLOBALS['TL_LANG']['tl_maintenance']['clearCache'];
		$objTemplate->job = $GLOBALS['TL_LANG']['tl_maintenance']['job'];
		$objTemplate->description = $GLOBALS['TL_LANG']['tl_maintenance']['description'];
		$objTemplate->submit = \StringUtil::specialchars($GLOBALS['TL_LANG']['tl_maintenance']['clearCache']);
		$objTemplate->help = (\Config::get('showHelp') && ($GLOBALS['TL_LANG']['tl_maintenance']['cacheTables'][1] != '')) ? $GLOBALS['TL_LANG']['tl_maintenance']['cacheTables'][1] : '';

		return $objTemplate->parse();
	}
}
